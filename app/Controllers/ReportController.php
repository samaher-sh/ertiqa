<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\ReportChecklistItemModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\DocumentRequestModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
use App\Models\AuditNoteModel;
use App\Models\MissionModel;

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

    /** true لدور الإدارة الخاضعة للمراجعة (منسق/مدير إدارة) — قراءة فقط بكل نقاط هذا الـ Controller */
    private function isHrDept(): bool
    {
        return in_array(session()->get('role_code'), ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
    }

    /** المهمة لو المستخدم الحالي طرف فيها فعليًا (مراجع أو الإدارة المستهدفة)، وإلا null */
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

    /**
     * GET /dashboard/reports/api/list — قائمة تقارير المستخدم. الإدارة الخاضعة للمراجعة
     * تشوف تقارير مهامها هي تحديدًا (طرف ثاني بالمهمة)، مو تقارير أي مهمة بالنظام
     */
    public function list()
    {
        $reportModel = new ReportModel();

        if ($this->isHrDept()) {
            $departmentId = (int) session()->get('department_id');
            $reports = $departmentId ? $reportModel->forTargetDepartment($departmentId) : [];
        } else {
            $reports = $reportModel->forUser((int) session()->get('user_id'));
        }

        return $this->response->setJSON(['success' => true, 'reports' => $reports]);
    }

    /**
     * GET /dashboard/reports/api/checklist?mission_id=X
     * يتحقق فعليًا من اكتمال كل مرحلة من قاعدة البيانات، وينشئ صفوف Checklist أول مرة
     */
    public function checklist()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setStatusCode(422)->setJSON(['success' => false]);

        if (!$this->missionForParty($missionId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

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

    /** اعتماد/إنشاء التقرير مقصور على عضو المراجعة صاحب المهمة — لا رئيس المراجعة، لا الرئيس التنفيذي، ولا الإدارة الخاضعة للمراجعة (عرض فقط للثلاثة) */
    private function canEditReport(int $reportId): bool
    {
        $roleCode = session()->get('role_code');
        if ($this->isHrDept() || in_array($roleCode, ['audit_head', 'top_management'], true)) {
            return false;
        }

        $report = (new ReportModel())->find($reportId);
        return $report && $this->missionForParty((int) $report['mission_id']) !== null;
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
        if (!$this->canEditReport($reportId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
        }

        (new ReportChecklistItemModel())->setChecked($reportId, $section, $checked);
        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/reports/api/finalize — يعتمد التقرير نهائيًا (كل المراحل لازم تكون معتمدة) */
    public function finalize()
    {
        $data = $this->request->getJSON(true);
        $reportId = (int) ($data['report_id'] ?? 0);

        if (!$this->canEditReport($reportId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
        }

        $itemModel = new ReportChecklistItemModel();
        $items = $itemModel->forReport($reportId);
        $allChecked = count($items) > 0 && !in_array(0, array_column($items, 'is_checked'), true);

        if (!$allChecked) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يجب اعتماد كل المراحل أولاً.']);
        }

        $reportModel = new ReportModel();
        $reportModel->update($reportId, ['status' => 'pending_signatures', 'generated_at' => date('Y-m-d H:i:s')]);

        $report = $reportModel->find($reportId);
        if ($report && !empty($report['mission_id'])) {
            (new \App\Models\AuditLogModel())->log((int) $report['mission_id'], (int) session()->get('user_id'), 'report_finalized', 'report', $reportId, 'رقم التقرير: ' . $reportId);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * GET /dashboard/reports/api/preview?mission_id=X&section=N (1-6)
     * يجيب بيانات المعاينة الفعلية لمرحلة معيّنة من مراحل الاعتماد، لعرضها بزر
     * "عرض" بجدول مراحل الاعتماد — قراءة فقط، بدون إنشاء أي صفوف جديدة
     * (بخلاف findOrCreateForMission المستخدمة بصفحات التعبئة الفعلية)
     */
    public function preview()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        $section   = (int) $this->request->getGet('section');

        if (!$missionId || !isset(self::STEPS[$section])) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'بيانات غير صحيحة.']);
        }
        if (!$this->missionForParty($missionId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        switch ($section) {
            case 1:
                $mission = (new MissionModel())->findWithDetails($missionId);
                if (!$mission) {
                    return $this->response->setStatusCode(404)->setJSON(['success' => false]);
                }
                return $this->response->setJSON(['success' => true, 'section' => 1, 'mission' => $mission]);

            case 2:
                $agreement = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
                $responses = (new ServiceAgreementResponseModel())->forMission($missionId);
                return $this->response->setJSON(['success' => true, 'section' => 2, 'agreement' => $agreement, 'responses' => $responses]);

            case 3:
                $documents = (new DocumentRequestModel())->forMissionWithResponses($missionId);
                return $this->response->setJSON(['success' => true, 'section' => 3, 'documents' => $documents]);

            case 4:
                $items = (new RiskMatrixItemModel())->forMission($missionId);
                return $this->response->setJSON(['success' => true, 'section' => 4, 'items' => $items]);

            case 5:
                $meeting = (new MeetingModel())->firstForMission($missionId);
                $attendees = $meeting ? (new MeetingAttendeeModel())->forMeeting($meeting['id']) : [];
                $points    = $meeting ? (new MeetingSummaryPointModel())->forMeeting($meeting['id']) : [];
                $approvals = $meeting ? (new MeetingApprovalModel())->forMeeting($meeting['id']) : [];
                return $this->response->setJSON([
                    'success' => true, 'section' => 5,
                    'meeting' => $meeting, 'attendees' => $attendees, 'points' => $points, 'approvals' => $approvals,
                ]);

            case 6:
                $observations = (new AuditNoteModel())->forMission($missionId);
                return $this->response->setJSON(['success' => true, 'section' => 6, 'observations' => $observations]);
        }
    }

    /** يتحقق فعليًا هل كل مرحلة فيها بيانات حقيقية بقاعدة البيانات */
    private function realCompletionStatus(int $missionId): array
    {
        // مجرد وجود صف service_agreement/document_requests لا يعني اكتمال الرد عليه —
        // الصفوف تُنشأ فارغة أول ما تُنشأ المهمة (Snapshot)، لازم نتحقق من الرد الفعلي
        $sla = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        $slaSubmitted = $sla && $sla['status'] === 'submitted';

        $docRequests = (new DocumentRequestModel())->forMissionWithResponses($missionId);
        $docsComplete = count($docRequests) > 0
            && count(array_filter($docRequests, fn($d) => $d['exists_flag'] === null)) === 0;

        $risk = (new RiskMatrixItemModel())->where('mission_id', $missionId)->countAllResults();
        $meet = (new MeetingModel())->where('mission_id', $missionId)->first();
        $obs  = (new AuditNoteModel())->where('mission_id', $missionId)->countAllResults();

        return [
            1 => true,          // طلب المراجعة - موجود أكيد لأن المهمة موجودة
            2 => $slaSubmitted,
            3 => $docsComplete,
            4 => $risk > 0,
            5 => (bool) $meet && !empty($meet['meeting_date']),
            6 => $obs > 0,
        ];
    }
}
