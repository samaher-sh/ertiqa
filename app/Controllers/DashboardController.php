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

        $missionModel = new MissionModel();
        $meetingModel = new MeetingModel();

        $data = [
            'active_count'   => $missionModel->countActiveForUser($userId),
            'review_count'   => $missionModel->countInStageForUser($userId, 2),
            'meetings_count' => $meetingModel->countScheduledForUser($userId),
        ];

        // رئيس إدارة المراجعة الداخلية يشرف على كل تقارير الإدارة، مو فقط مهامه هو
        if ($roleCode === 'audit_head') {
            $reportModel   = new ReportModel();
            $departmentId  = (int) session()->get('department_id');
            $data['reports_pending_count']  = $reportModel->countForDepartmentByStatus($departmentId, 'pending_signatures');
            $data['reports_approved_count'] = $reportModel->countForDepartmentByStatus($departmentId, 'sent');
        }

        // مستخدمو الإدارة الخاضعة للمراجعة يحتاجون بيانات شريط الإخطارات بالرئيسية
        if (in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true)) {
            $notifModel = new NotificationModel();
            $data['unread_notifications_count'] = $notifModel->unreadCountForUser($userId);
            $data['latest_notification']        = $notifModel->latestUnreadForUser($userId);
        }

        return $this->response->setJSON($data);
    }

    /**
     * GET /dashboard/api/active-missions — قائمة المهام النشطة (JSON)
     */
    public function activeMissions()
    {
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();

        return $this->response->setJSON([
            'missions' => $missionModel->activeMissionsForUser($userId),
        ]);
    }

    /**
     * GET /dashboard/api/scheduled-meetings — قائمة الاجتماعات المجدولة (JSON)
     */
    public function scheduledMeetings()
    {
        $userId = (int) session()->get('user_id');
        $meetingModel = new MeetingModel();

        return $this->response->setJSON([
            'meetings' => $meetingModel->scheduledMeetingsForUser($userId),
        ]);
    }
}
