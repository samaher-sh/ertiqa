<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\MissionModel;

class SentTasksController extends BaseController
{
    /* نفس ST_STAGE_TO_PAGE بـ senttasks.js بالضبط -- key = رابط الصفحة الحقيقية
       المطابقة (كلها صفحات MVC حقيقية الآن ما عدا missionReview)، forRole يحدد
       مين عليه الدور الحالي: "target" = الإدارة الخاضعة، "audit" = عضو المراجعة */
    private const STAGE_TO_PAGE = [
        2 => ['label' => 'استكمال الاتفاقية والمستندات', 'forRole' => 'target', 'url' => null],
        3 => ['label' => 'مصفوفة المخاطر',              'forRole' => 'audit',  'url' => 'dashboard/risk-matrix'],
        4 => ['label' => 'ملخص الاجتماع',                'forRole' => 'audit',  'url' => 'dashboard/meetings'],
        5 => ['label' => 'الملاحظات',                    'forRole' => 'audit',  'url' => 'dashboard/observations'],
        7 => ['label' => 'التقرير النهائي',               'forRole' => 'audit',  'url' => null],
    ];

    private function isHrUser(): bool
    {
        return in_array(session()->get('role_code'), ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
    }

    private function stageBadge(int $nextStage): array
    {
        $info = self::STAGE_TO_PAGE[$nextStage] ?? null;
        if (!$info) {
            return ['text' => $nextStage === 7 ? 'التقرير النهائي' : 'المرحلة ' . $nextStage, 'myTurn' => false, 'info' => null];
        }
        $isHr = $this->isHrUser();
        $myTurn = ($info['forRole'] === 'target' && $isHr) || ($info['forRole'] === 'audit' && !$isHr);
        $text = ($myTurn ? 'بانتظارك — ' : 'بانتظار الطرف الآخر — ') . $info['label'];
        return ['text' => $text, 'myTurn' => $myTurn, 'info' => $info];
    }

    /** المهمة لو المستخدم الحالي فعليًا طرف فيها (مراجع أو الإدارة المستهدفة)، وإلا null */
    private function missionForParty(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) {
            return null;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');

        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        return ($isAuditSide || $isTargetSide) ? $mission : null;
    }

    /** GET /dashboard/sent-tasks — قائمة المهام الحقيقية (Server-Rendered) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        foreach ($missions as &$m) {
            $m['stage_badge_text'] = $this->stageBadge((int) $m['next_stage'])['text'];
        }
        unset($m);

        return view('dashboard/sent-tasks/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'sentTasks',
            'currentUser'  => $this->sessionUserSummary(),
            'missions'     => $missions,
        ]);
    }

    /** GET /dashboard/sent-tasks/{id} — تفاصيل المهمة والسجل الزمني الحقيقي */
    public function show(int $id)
    {
        $mission = $this->missionForParty($id);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }

        $missionModel = new MissionModel();
        $nextStage = $missionModel->computeRealNextStage($id);
        $events = (new AuditLogModel())->forMission($id);

        return view('dashboard/sent-tasks/show', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'sentTasks',
            'currentUser'  => $this->sessionUserSummary(),
            'mission'      => $mission,
            'events'       => $events,
            'nextStage'    => $nextStage,
        ]);
    }

    /**
     * GET /dashboard/sent-tasks/api/timeline?mission_id=X — السجل الزمني الفعلي لمهمة،
     * متاح فقط لطرفي المهمة (المراجع والإدارة الخاضعة للمراجعة)
     * المصدر الحقيقي هو audit_logs (حدث منفصل لكل فعل مهم: إنشاء مهمة، رفع مستندات،
     * تعبئة اتفاقية مستوى الخدمة، حفظ مصفوفة مخاطر، رسائل/اقتراح/تأكيد/إلغاء موعد
     * الاجتماع، حفظ ملخص اجتماع، إضافة ملاحظة، اعتماد تقرير) — أدق من
     * mission_stage_history اللي مخصصة لتتبع دخول رقم المرحلة فقط (تبقى مستخدمة
     * لحساب next_stage تحت، بما إنها مرتبطة بمنطق SLA المرحلي)
     */
    public function timeline()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'events' => [], 'next_stage' => null]);

        if (!$this->missionForParty($missionId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لسجل هذه المهمة.']);
        }

        $events = (new AuditLogModel())->forMission($missionId);

        return $this->response->setJSON([
            'success'    => true,
            'events'     => $events,
            'next_stage' => (new MissionModel())->computeRealNextStage($missionId),
        ]);
    }
}
