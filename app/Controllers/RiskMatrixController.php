<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\RiskMatrixItemModel;

class RiskMatrixController extends BaseController
{
    /**
     * GET /dashboard/risk-matrix — عرض الصفحة
     */
    public function index()
    {
        $roleCode = session()->get('role_code');
        $userId   = (int) session()->get('user_id');

        // نفس منطق readOnly بالواجهة الأصلية: isHrCoordinator || isAuditHead
        $readOnly = in_array($roleCode, ['hr_coordinator', 'audit_head'], true);

        $missionModel = new MissionModel();
        $missions = $missionModel->activeMissionsForUser($userId);

        return view('dashboard/risk-matrix', [
            'full_name'       => session()->get('full_name'),
            'role_name'       => session()->get('role_name'),
            'national_id'     => session()->get('national_id'),
            'navItems'        => $this->buildNavItems(),
            'missions'        => $missions,
            'readOnly'        => $readOnly,
        ]);
    }

    /**
     * GET /dashboard/risk-matrix/api/items?mission_id=X — جلب صفوف مهمة معيّنة
     */
    public function items()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'مهمة غير محددة']);
        }

        $itemModel = new RiskMatrixItemModel();
        return $this->response->setJSON(['success' => true, 'items' => $itemModel->forMission($missionId)]);
    }

    /**
     * POST /dashboard/risk-matrix/api/save — حفظ كل صفوف مهمة معيّنة دفعة وحدة
     */
    public function save()
    {
        $roleCode = session()->get('role_code');
        if (in_array($roleCode, ['hr_coordinator', 'audit_head'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
        }

        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $rows      = $data['rows'] ?? [];

        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
        }

        $itemModel = new RiskMatrixItemModel();
        $itemModel->replaceForMission($missionId, $rows);

        return $this->response->setJSON(['success' => true]);
    }

    private function buildNavItems(): array
    {
        $roleCode  = session()->get('role_code');
        $isPresident = $roleCode === 'top_management';
        $isHrDept    = in_array($roleCode, ['hr_coordinator', 'dept_manager', 'specialized_manager'], true);
        $isAuditHead = $roleCode === 'audit_head';

        $icon = fn(string $path) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';

        $all = [
            'home'           => ['label' => 'الرئيسية',          'desc' => 'Dashboard',        'url' => base_url('dashboard'),               'icon' => $icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>')],
            'newTask'        => ['label' => 'بدء مهمة',           'desc' => 'New Audit Task',   'url' => base_url('dashboard/new-task'),      'icon' => $icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>')],
            'riskMatrix'     => ['label' => 'مصفوفة المخاطر',     'desc' => 'Risk Matrix',      'url' => base_url('dashboard/risk-matrix'),   'icon' => $icon('<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>')],
            'meetingSummary' => ['label' => 'ملخص اجتماع',        'desc' => 'Meeting Summary',  'url' => base_url('dashboard/meetings'),      'icon' => $icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')],
            'observations'   => ['label' => 'الملاحظات',          'desc' => 'Observations',     'url' => base_url('dashboard/observations'),  'icon' => $icon('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>')],
            'finalReports'   => ['label' => 'تقرير نهائي',        'desc' => 'Final Reports',    'url' => base_url('dashboard/reports'),       'icon' => $icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>')],
            'sentTasks'      => ['label' => 'المراسلات المشتركة', 'desc' => 'Sent Tasks',       'url' => base_url('dashboard/sent-tasks'),    'icon' => $icon('<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>')],
        ];

        $keys = array_keys($all);
        if ($isPresident) {
            $keys = ['home', 'finalReports'];
        } elseif ($isHrDept) {
            $keys = ['home', 'meetingSummary', 'observations', 'finalReports', 'sentTasks'];
        } elseif ($isAuditHead) {
            $keys = array_diff($keys, ['newTask']);
        }

        $result = [];
        foreach ($keys as $k) {
            if (isset($all[$k])) {
                $result[] = array_merge(['key' => $k], $all[$k]);
            }
        }
        return $result;
    }
}
