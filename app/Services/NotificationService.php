<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Models\MissionModel;
use Config\Database;

/**
 * نقطة مركزية وحيدة لإرسال أي إخطار بالنظام: تسجّله بجدول notifications
 * (كان موجود بالمخطط من الأصل بس غير مستخدَم إطلاقًا)، وترسله بريديًا فورًا
 * لو المستخدم عنده إيميل مسجّل (يُملأ تلقائيًا من الدليل الموحّد LDAP وقت
 * إنشاء الحساب -- راجع UsersService::createFromLdap()) وخادم SMTP مُعدّ
 * فعليًا بـ .env. نفس نمط التحقق المتدرّج المستخدَم مع LDAPModel::connect():
 * أي غياب إعداد أو فشل إرسال يُسجَّل بالسجل (log) ويُتجاهَل بصمت، ما يوقف
 * أبدًا العملية الأساسية (اعتماد/رفض تقرير، تقدّم مرحلة، اقتراح موعد...).
 *
 * محتوى الإخطار (عنوان+وصف) يطابق حرفيًا نفس النصوص المحسوبة حيًا بودجت
 * "إخطارات" بالصفحة الرئيسية (DashboardController::homeStatsData) -- أي
 * تعديل مستقبلي على تلك النصوص يلزم تطبيقه هنا بالتوازي.
 */
class NotificationService
{
    private NotificationModel $notifModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
        $this->userModel  = new UserModel();
    }

    /** يسجّل الإخطار لمستخدم واحد ويحاول إرساله بريديًا */
    public function notifyUser(int $userId, string $type, string $title, string $body, ?int $missionId = null, ?string $link = null): void
    {
        if (!$userId) {
            return;
        }

        $notificationId = $this->notifModel->insert([
            'user_id'    => $userId,
            'mission_id' => $missionId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'channel'    => 'both',
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        $this->sendEmail((int) $notificationId, $userId, $title, $body, $link);
    }

    /** نفس notifyUser() لعدة مستخدمين دفعة وحدة (تُزال التكرارات والقيم الفارغة) */
    public function notifyUsers(array $userIds, string $type, string $title, string $body, ?int $missionId = null, ?string $link = null): void
    {
        foreach (array_unique(array_filter(array_map('intval', $userIds))) as $uid) {
            $this->notifyUser($uid, $type, $title, $body, $missionId, $link);
        }
    }

    private function sendEmail(int $notificationId, int $userId, string $title, string $body, ?string $link): void
    {
        $config = config('Email');
        if ($config->protocol === 'smtp' && empty($config->SMTPHost)) {
            // خادم SMTP لسا ما تعدّل بـ .env -- تجاهل صامت، الإخطار داخل
            // المنصة سُجِّل أصلًا بالخطوة اللي قبل هذي
            return;
        }

        $user = $this->userModel->find($userId);
        if (!$user || empty($user['email'])) {
            return;
        }

        try {
            $email = \Config\Services::email(false);
            $email->setTo($user['email']);
            $email->setFrom($config->fromEmail ?: 'no-reply@ertiqa.local', $config->fromName ?: 'ارتقاء');
            $email->setSubject($title);
            $email->setMessage(view('emails/notification', ['title' => $title, 'body' => $body, 'link' => $link]));

            if (!$email->send()) {
                log_message('error', 'فشل إرسال إخطار بريدي للمستخدم #' . $userId . ': ' . $email->printDebugger(['headers']));
                return;
            }

            if ($notificationId) {
                $this->notifModel->update($notificationId, ['sent_at' => date('Y-m-d H:i:s')]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'استثناء أثناء إرسال إخطار بريدي للمستخدم #' . $userId . ': ' . $e->getMessage());
        }
    }

    /** أعضاء فريق المراجعة لمهمة (رئيس المهمة + كل الأعضاء المسندين) */
    public function auditSideUserIds(int $missionId): array
    {
        $mission = (new MissionModel())->find($missionId);
        $ids = [];
        if ($mission && !empty($mission['mission_head_id'])) {
            $ids[] = (int) $mission['mission_head_id'];
        }

        $rows = Database::connect()->table('audit_team_members')
            ->select('user_id')
            ->where('mission_id', $missionId)
            ->get()->getResultArray();
        foreach ($rows as $r) {
            $ids[] = (int) $r['user_id'];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** كل مستخدمي الإدارة المستهدفة بمهمة (منسّق/مدير الإدارة الخاضعة للمراجعة) */
    public function targetSideUserIds(int $missionId): array
    {
        $mission = (new MissionModel())->find($missionId);
        if (!$mission || empty($mission['target_department_id'])) {
            return [];
        }
        return $this->departmentUserIds((int) $mission['target_department_id']);
    }

    /** كل رؤساء إدارة المراجعة الداخلية بإدارة معيّنة */
    public function auditHeadUserIds(int $departmentId): array
    {
        if (!$departmentId) {
            return [];
        }
        $rows = $this->userModel
            ->select('users.id')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.code', 'audit_head')
            ->where('users.department_id', $departmentId)
            ->where('users.is_active', 1)
            ->findAll();

        return array_map(fn ($r) => (int) $r['id'], $rows);
    }

    private function departmentUserIds(int $departmentId): array
    {
        $rows = $this->userModel->where('department_id', $departmentId)->where('is_active', 1)->findAll();
        return array_map(fn ($r) => (int) $r['id'], $rows);
    }
}
