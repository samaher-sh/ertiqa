<?php

namespace App\Controllers;

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

    /**
     * GET /dashboard/reports/api/list — قائمة تقارير المستخدم (لم تكن مذكورة صراحة بالمواصفة،
     * لكن لازمة لعرض قائمة "التقارير النهائية" — نفس البيانات اللي كانت تُحقن بالـ View القديم)
     */
    public function list()
    {
        $userId = (int) session()->get('user_id');
        $reportModel = new ReportModel();

        return $this->response->setJSON([
            'success' => true,
            'reports' => $reportModel->forUser($userId),
        ]);
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
}
