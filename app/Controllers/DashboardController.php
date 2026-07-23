<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MeetingModel;

class DashboardController extends BaseController
{
    /**
     * تعريف كل عناصر القائمة الجانبية الممكنة + أيقوناتها (SVG مباشرة، بدون مكتبة خارجية)
     */
    private function allNavItems(): array
    {
        $icon = fn(string $path) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';

        return [
            'home'           => ['label' => 'الرئيسية',          'desc' => 'Dashboard',        'url' => base_url('dashboard'),               'icon' => $icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>')],
            'newTask'        => ['label' => 'بدء مهمة',           'desc' => 'New Audit Task',   'url' => base_url('dashboard/new-task'),      'icon' => $icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>')],
            'riskMatrix'     => ['label' => 'مصفوفة المخاطر',     'desc' => 'Risk Matrix',      'url' => base_url('dashboard/risk-matrix'),   'icon' => $icon('<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>')],
            'meetingSummary' => ['label' => 'ملخص اجتماع',        'desc' => 'Meeting Summary',  'url' => base_url('dashboard/meetings'),      'icon' => $icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')],
            'observations'   => ['label' => 'الملاحظات',          'desc' => 'Observations',     'url' => base_url('dashboard/observations'),  'icon' => $icon('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>')],
            'finalReports'   => ['label' => 'تقرير نهائي',        'desc' => 'Final Reports',    'url' => base_url('dashboard/reports'),       'icon' => $icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>')],
            'sentTasks'      => ['label' => 'المراسلات المشتركة', 'desc' => 'Sent Tasks',       'url' => base_url('dashboard/sent-tasks'),    'icon' => $icon('<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>')],
        ];
    }

    /**
     * يفلتر عناصر القائمة حسب الدور — نفس منطق MAIN_NAV.filter() بالواجهة الأصلية بالضبط
     */
    private function navItemsForRole(bool $isPresident, bool $isHrDept, bool $isAuditHead): array
    {
        $all = $this->allNavItems();
        $keys = array_keys($all);

        if ($isPresident) {
            $keys = ['home', 'finalReports'];
        } elseif ($isHrDept) {
            $keys = ['home', 'meetingSummary', 'observations', 'finalReports', 'sentTasks'];
        } elseif ($isAuditHead) {
            $keys = array_diff($keys, ['newTask']); // رئيس المراجعة ما يشوف "بدء مهمة"
        }

        $result = [];
        foreach ($keys as $k) {
            if (isset($all[$k])) {
                $result[] = array_merge(['key' => $k], $all[$k]);
            }
        }
        return $result;
    }

    /**
     * GET /dashboard — الصفحة الرئيسية (تختلف حسب دور المستخدم)
     */
    public function index()
    {
        $session = session();

        $isAuditHead     = $session->get('role_code') === 'audit_head';
        $isAuditMember   = $session->get('role_code') === 'audit_member';
        $isHrCoordinator = $session->get('role_code') === 'hr_coordinator';
        $isPresident     = $session->get('role_code') === 'top_management';
        $isHrDept        = in_array($session->get('role_code'), ['hr_coordinator', 'dept_manager', 'specialized_manager'], true);

        $data = [
            'full_name'       => $session->get('full_name'),
            'role_code'       => $session->get('role_code'),
            'role_name'       => $session->get('role_name'),
            'department_name' => $session->get('department_name'),
            'national_id'     => $session->get('national_id'),

            'isAuditHead'     => $isAuditHead,
            'isAuditMember'   => $isAuditMember,
            'isHrCoordinator' => $isHrCoordinator,
            'isPresident'     => $isPresident,
            'isHrDept'        => $isHrDept,

            'navItems'        => $this->navItemsForRole($isPresident, $isHrDept, $isAuditHead),
        ];

        $data['isAuditRole'] = $isAuditHead || $isAuditMember;

        return view('dashboard/home', $data);
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
