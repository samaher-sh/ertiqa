<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MeetingModel;
use App\Models\ReportModel;
use App\Models\NotificationModel;

class DashboardController extends BaseController
{
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

        // مستخدمو الإدارة الخاضعة للمراجعة يحتاجون بيانات شريط الإخطارات بالرئيسية
        if ($isHrDept) {
            $notifModel = new NotificationModel();
            $data['unread_notifications_count'] = $notifModel->unreadCountForUser($userId);
            $data['latest_notification']        = $notifModel->latestUnreadForUser($userId);
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
