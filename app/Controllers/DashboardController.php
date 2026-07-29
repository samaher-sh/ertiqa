<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MeetingModel;

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
        $userId = (int) session()->get('user_id');

        $missionModel = new MissionModel();
        $meetingModel = new MeetingModel();

        return $this->response->setJSON([
            'active_count'   => $missionModel->countActiveForUser($userId),
            'review_count'   => $missionModel->countInStageForUser($userId, 2),
            'meetings_count' => $meetingModel->countScheduledForUser($userId),
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
