<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\ReportModel;
use App\Models\ReportChecklistItemModel;
use App\Models\ServiceAgreementModel;
use App\Models\DocumentRequestModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\AuditNoteModel;

class ReportController extends BaseController
{
    private const STEPS = [
        1 => 'طلب المراجعة الداخلية',
        2 => 'اتفاقية مستوى الخدمة',
        3 => 'قائمة المستندات',
        4 => 'مصفوفة المخاطر',
        5 => 'ملخص الاجتماع',
        6 => 'الملاحظات',
    ];

    private function roleFlags(): array
    {
        $roleCode = session()->get('role_code');
        $isHrUser = in_array($roleCode, ['hr_coordinator', 'dept_manager', 'specialized_manager'], true);
        return [
            'isAuditHead' => $roleCode === 'audit_head',
            'isHrUser'    => $isHrUser,
            'isPresident' => $roleCode === 'top_management',
        ];
    }

    /** GET /dashboard/reports */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        $reportModel = new ReportModel();
        $missionModel = new MissionModel();
        $flags = $this->roleFlags();

        return view('dashboard/final-reports', array_merge([
            'full_name' => session()->get('full_name'),
            'role_name' => session()->get('role_name'),
            'navItems'  => $this->buildNavItems(),
            'reports'   => $reportModel->forUser($userId),
            'missions'  => $missionModel->activeMissionsForUser($userId),
        ], $flags));
    }

    /**
     * GET /dashboard/reports/api/checklist?mission_id=X
     * يتحقق فعليًا من اكتمال كل مرحلة من قاعدة البيانات، وينشئ صفوف Checklist أول مرة
     */
    public function checklist()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setStatusCode(422)->setJSON(['success' => false]);

        $userId = (int) session()->get('user_id');
        $reportModel = new ReportModel();
        $itemModel   = new ReportChecklistItemModel();

        $report = $reportModel->findOrCreateForMission($missionId, $userId);
        $items  = $itemModel->forReport($report['id']);

        if (empty($items)) {
            $now = date('Y-m-d H:i:s');
            $rows = [];
            $i = 0;
            foreach (self::STEPS as $num => $title) {
                $i++;
                $rows[] = ['report_id' => $report['id'], 'section_number' => $num, 'section_title' => $title, 'item_text' => $title, 'is_checked' => 0, 'sort_order' => $i, 'created_at' => $now];
            }
            $itemModel->insertBatch($rows);
            $items = $itemModel->forReport($report['id']);
        }

        // التحقق الفعلي من الاكتمال (Read-only إعلامي، الاعتماد نفسه يدوي بس يعتمد على هذا)
        $completion = $this->realCompletionStatus($missionId);

        return $this->response->setJSON(['success' => true, 'report' => $report, 'items' => $items, 'completion' => $completion]);
    }

    /** POST /dashboard/reports/api/toggle-check — اعتماد/إلغاء اعتماد مرحلة */
    public function toggleCheck()
    {
        $data = $this->request->getJSON(true);
        $reportId = (int) ($data['report_id'] ?? 0);
        $section  = (int) ($data['section_number'] ?? 0);
        $checked  = (bool) ($data['checked'] ?? false);

        if (!$reportId || !$section) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false]);
        }

        (new ReportChecklistItemModel())->setChecked($reportId, $section, $checked);
        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/reports/api/finalize — يعتمد التقرير نهائيًا (كل المراحل لازم تكون معتمدة) */
    public function finalize()
    {
        $data = $this->request->getJSON(true);
        $reportId = (int) ($data['report_id'] ?? 0);

        $itemModel = new ReportChecklistItemModel();
        $items = $itemModel->forReport($reportId);
        $allChecked = count($items) > 0 && !in_array(0, array_column($items, 'is_checked'), true);

        if (!$allChecked) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يجب اعتماد كل المراحل أولاً.']);
        }

        (new ReportModel())->update($reportId, ['status' => 'pending_signatures', 'generated_at' => date('Y-m-d H:i:s')]);
        return $this->response->setJSON(['success' => true]);
    }

    /** يتحقق فعليًا هل كل مرحلة فيها بيانات حقيقية بقاعدة البيانات */
    private function realCompletionStatus(int $missionId): array
    {
        $sla  = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        $docs = (new DocumentRequestModel())->where('mission_id', $missionId)->countAllResults();
        $risk = (new RiskMatrixItemModel())->where('mission_id', $missionId)->countAllResults();
        $meet = (new MeetingModel())->where('mission_id', $missionId)->first();
        $obs  = (new AuditNoteModel())->where('mission_id', $missionId)->countAllResults();

        return [
            1 => true,          // طلب المراجعة - موجود أكيد لأن المهمة موجودة
            2 => (bool) $sla,
            3 => $docs > 0,
            4 => $risk > 0,
            5 => (bool) $meet,
            6 => $obs > 0,
        ];
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
            $keys = ['home', 'finalReports'];
        }

        $result = [];
        foreach ($keys as $k) {
            if (isset($all[$k])) $result[] = array_merge(['key' => $k], $all[$k]);
        }
        return $result;
    }
}
