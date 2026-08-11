<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MeetingModel;
use App\Models\ReportModel;

class DashboardController extends BaseController
{
    /**
     * دور "target"/"audit" الحقيقي بكل مرحلة + رسالة بانر "لديك إخطارات جديدة"
     * المناسبة لها — نفس ST_STAGE_TO_PAGE بـ senttasks.js بالضبط. المرحلة 2 هي
     * الوحيدة اللي دورها "target" (الإدارة الخاضعة للمراجعة)؛ بقية المراحل دورها
     * "audit" (فريق المراجعة)
     */
    private const STAGE_NOTIFICATIONS = [
        2 => ['for' => 'target', 'title' => 'بانتظار الرد على مهمة مراجعة',   'suffix' => 'بانتظار استكمال اتفاقية مستوى الخدمة أو الرد على المستندات المطلوبة.'],
        3 => ['for' => 'audit',  'title' => 'بانتظار تعبئة مصفوفة المخاطر',   'suffix' => 'بانتظار تعبئة مصفوفة المخاطر.'],
        4 => ['for' => 'audit',  'title' => 'بانتظار ملخص الاجتماع',         'suffix' => 'بانتظار تعبئة ملخص الاجتماع.'],
        5 => ['for' => 'audit',  'title' => 'بانتظار إضافة الملاحظات',       'suffix' => 'بانتظار إضافة الملاحظات.'],
        7 => ['for' => 'audit',  'title' => 'بانتظار إعداد التقرير النهائي', 'suffix' => 'بانتظار إعداد واعتماد التقرير النهائي.'],
    ];

    /**
     * GET /dashboard — يعرض هيكل لوحة التحكم (SPA shell)
     * بيانات البروفايل والقائمة الجانبية تُجلب من /api/session و /api/nav-items
     */
    public function index()
    {
        return view('dashboard/shell');
    }

    /**
     * GET /dashboard/api/home-stats — إحصائيات الصفحة الرئيسية (JSON)
     */
    public function homeStats()
    {
        $userId   = (int) session()->get('user_id');
        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);

        $missionModel = new MissionModel();
        $meetingModel = new MeetingModel();

        // مصدر واحد لمهام "طرف" المستخدم الحالي بالمهمة، يُستخدم لعدّ الاجتماعات المجدولة
        // ولتنبيه الاجتماع المؤكد أدناه معًا — منسّق الإدارة يرتبط بمهامه عبر
        // target_department_id لا mission_head_id/فريق المراجعة
        $departmentId = (int) session()->get('department_id');
        $ownMissions = $isHrDept
            ? ($departmentId ? $missionModel->missionsForTargetDepartment($departmentId) : [])
            : $missionModel->activeMissionsForUser($userId);
        $missionIds = array_column($ownMissions, 'id');

        $data = [
            'active_count'   => $missionModel->countActiveForUser($userId),
            'review_count'   => $missionModel->countInStageForUser($userId, 2),
            'meetings_count' => $isHrDept
                ? count($meetingModel->scheduledMeetingsForMissions($missionIds))
                : $meetingModel->countScheduledForUser($userId),
        ];

        // رئيس إدارة المراجعة الداخلية يشرف على كل تقارير الإدارة، مو فقط مهامه هو
        if ($roleCode === 'audit_head') {
            $reportModel   = new ReportModel();
            $data['reports_pending_count']  = $reportModel->countForDepartmentByStatus($departmentId, 'pending_signatures');
            $data['reports_approved_count'] = $reportModel->countForDepartmentByStatus($departmentId, 'sent');
        }

        // بانر "لديك إخطارات جديدة" بالصفحة الرئيسية — يُحسب حيًا من الحالة الفعلية
        // لكل مهمة (computeRealNextStage) بدل الاعتماد على صف إخطار ثابت يُدرَج مرة
        // وحدة فقط عند إنشاء المهمة (كان السبب في ظهور البانر بشكل غير محدَّث: يبقى
        // كما هو حتى بعد ما تردّ الإدارة وتنتقل المهمة لدور المراجع، أو لا يظهر
        // إطلاقًا لو رجع الدور لهم لاحقًا -- زي إضافة طلب مستند جديد بعد ما خلصوا
        // الردود الأولى). يظهر لطرفي المهمة: الإدارة الخاضعة للمراجعة (دورها
        // "target"، مرحلة 2 فقط) وفريق المراجعة (دورهم "audit"، بقية المراحل) --
        // عشان لما تردّ الإدارة وينتقل الدور فعليًا لفريق المراجعة (مصفوفة
        // المخاطر...) يوصلهم إخطار حقيقي بدل ما يبقوا بدون أي تنبيه إطلاقًا
        $isAuditMember = $roleCode === 'audit_member';
        if ($isHrDept || $isAuditMember) {
            $forRole = $isHrDept ? 'target' : 'audit';
            $reportModel = null;

            $pendingMissions = [];
            foreach ($ownMissions as $m) {
                if ($m['status'] !== 'active') continue;

                $stage = $missionModel->computeRealNextStage((int) $m['id']);
                $info = self::STAGE_NOTIFICATIONS[$stage] ?? null;
                if (!$info || $info['for'] !== $forRole) continue;

                // مرحلة "التقرير النهائي" (7) تبقى محسوبة "بانتظار فريق المراجعة"
                // حتى لو التقرير نفسه اتُّخذ فيه إجراء فعلي (اعتماد مبدئي) -- بمجرد
                // ما يصير التقرير pending_signatures/sent، الدور فعليًا انتقل لرئيس
                // إدارة المراجعة/الرئاسة، فما عاد إخطار فريق المراجعة دقيقًا
                if ($stage === 7) {
                    $reportModel ??= new ReportModel();
                    $report = $reportModel->where('mission_id', $m['id'])->first();
                    if ($report && $report['status'] !== 'draft') continue;
                }

                $m['_notif'] = $info;
                $pendingMissions[] = $m;
            }
            usort($pendingMissions, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

            $data['unread_notifications_count'] = count($pendingMissions);
            $data['latest_notification'] = null;
            if (!empty($pendingMissions)) {
                $m = $pendingMissions[0];
                $data['latest_notification'] = [
                    'mission_id' => (int) $m['id'],
                    'title'      => $m['_notif']['title'],
                    'body'       => 'المهمة (' . $m['mission_code'] . ') ' . $m['_notif']['suffix'],
                ];
            }
        }

        // تنبيه اجتماع مؤكد بالصفحة الرئيسية — يظهر لطرفي المهمة (عضو المراجعة ومنسّق
        // الإدارة الخاضعة للمراجعة) فور تأكيد أحدهما لموعد عبر شات "جدولة اجتماع"
        // ($ownMissions/$missionIds محسوبة أعلاه، نفس مصدر meetings_count)
        $data['confirmed_meeting_alert'] = null;
        if (!empty($missionIds)) {
            $meeting = $meetingModel->confirmedUpcomingForMissions($missionIds);
            if ($meeting) {
                $mission = array_values(array_filter(
                    $ownMissions,
                    fn($m) => (int) $m['id'] === (int) $meeting['mission_id']
                ))[0] ?? null;

                $data['confirmed_meeting_alert'] = [
                    'mission_code' => $mission['mission_code'] ?? '',
                    'meeting_date' => $meeting['meeting_date'],
                    'meeting_time' => $meeting['meeting_time'],
                    'location'     => $meeting['location'],
                ];
            }
        }

        return $this->response->setJSON($data);
    }

    /**
     * GET /dashboard/api/target-missions — المهام الموجّهة فعليًا لإدارة المستخدم الحالي
     * (يُستخدم من قِبل مستخدمي "الإدارة محل المراجعة" — dept_coordinator وما شابه)
     */
    public function targetMissions()
    {
        $departmentId = session()->get('department_id');

        if (!$departmentId) {
            return $this->response->setJSON(['success' => true, 'missions' => []]);
        }

        $missionModel = new MissionModel();
        return $this->response->setJSON([
            'success'  => true,
            'missions' => $this->withRealStage($missionModel, $missionModel->missionsForTargetDepartment((int) $departmentId)),
        ]);
    }

    /**
     * GET /dashboard/api/active-missions — قائمة المهام النشطة (JSON)
     */
    public function activeMissions()
    {
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();

        return $this->response->setJSON([
            'missions' => $this->withRealStage($missionModel, $missionModel->activeMissionsForUser($userId)),
        ]);
    }

    /**
     * يلحق next_stage الحقيقي (من MissionModel::computeRealNextStage) بكل مهمة —
     * missions.current_stage الخام يبقى 1 دائمًا (لا يتحدّث بأي مكان بالنظام)، وهذا
     * كان يجعل شارة "المرحلة" بقائمة "المراسلات المشتركة" تعرض دايمًا "مرحلة 1"
     * حتى بعد ما ترد الإدارة الخاضعة للمراجعة فعليًا وتُرسل ردها
     */
    private function withRealStage(MissionModel $missionModel, array $missions): array
    {
        foreach ($missions as &$m) {
            $m['next_stage'] = $missionModel->computeRealNextStage((int) $m['id']);
        }
        return $missions;
    }

    /**
     * GET /dashboard/api/scheduled-meetings — قائمة الاجتماعات المجدولة (JSON)
     */
    public function scheduledMeetings()
    {
        $userId   = (int) session()->get('user_id');
        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);

        $meetingModel = new MeetingModel();

        // منسّق/مدير الإدارة الخاضعة للمراجعة يرتبط بمهامه عبر target_department_id،
        // لا mission_head_id/audit_team_members اللي يعتمد عليها scheduledMeetingsForUser
        // (نفس الفرق المستخدم بـ homeStats للتنبيه وبـ loadHomeData بالواجهة)
        if ($isHrDept) {
            $departmentId = (int) session()->get('department_id');
            $missionModel = new MissionModel();
            $missionIds   = $departmentId ? array_column($missionModel->missionsForTargetDepartment($departmentId), 'id') : [];
            $meetings     = $meetingModel->scheduledMeetingsForMissions($missionIds);
        } else {
            $meetings = $meetingModel->scheduledMeetingsForUser($userId);
        }

        return $this->response->setJSON(['meetings' => $meetings]);
    }
}
