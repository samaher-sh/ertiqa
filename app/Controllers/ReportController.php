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
    /* روابط الصفحات الحقيقية المطابقة لكل مرحلة -- تُضمَّن مباشرة بـ iframe
       داخل صفحة مراحل الاعتماد نفسها (بدل رابط "افتح الصفحة" فقط)، عشان
       يظهر نفس النموذج/الصفحة الحقيقية اللي يشوفها أي طرف آخر بالضبط،
       بدل تكرار عرضه بقالب مختلف. القسم 1 (الخطاب الرسمي) رابط PDF مباشر
       يُبنى بالكنترولر (يحتاج missionId) فمو موجود هنا */
    private const STEP_VIEW_URL = [
        2 => 'dashboard/target-mission',
        3 => 'dashboard/document-requests',
        4 => 'dashboard/risk-matrix',
        5 => 'dashboard/meetings',
        6 => 'dashboard/observations',
    ];

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

    /** المهمة لو المستخدم الحالي طرف فيها فعليًا (مراجع أو الإدارة المستهدفة)، وإلا null.
     *  رئيس إدارة المراجعة الداخلية طرف ضمنيًا بكل مهام إدارته (audit_department_id)
     *  حتى لو مو عضو فريق فيها -- يحتاج يستعرض كل مراحلها (1-6) قبل اعتماد تقريرها */
    private function missionForParty(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) {
            return null;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');

        if (session()->get('role_code') === 'audit_head') {
            return ((int) $mission['audit_department_id'] === $departmentId) ? $mission : null;
        }

        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        return ($isAuditSide || $isTargetSide) ? $mission : null;
    }

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    /** GET /dashboard/reports — قائمة التقارير الحقيقية (Server-Rendered) */
    public function index()
    {
        $missionIdParam = (int) ($this->request->getGet('mission_id') ?: 0);
        if ($missionIdParam) {
            return redirect()->to(base_url('dashboard/reports/' . $missionIdParam));
        }

        $reportModel = new ReportModel();
        $departmentId = (int) session()->get('department_id');
        $roleCode = session()->get('role_code');
        $isPresident = $roleCode === 'top_management';
        $isAuditHead = $roleCode === 'audit_head';
        $isHr = $this->isHrDept();

        if ($isHr || $isPresident) {
            $reports = $isHr
                ? ($departmentId ? $reportModel->forTargetDepartment($departmentId) : [])
                : ($departmentId ? $reportModel->forDepartment($departmentId) : []); // president: نفس نطاق إدارة المراجعة الكامل، سيُفلتَر لـ pending أدناه
            if ($isPresident) $reports = array_values(array_filter($reports, fn($r) => $r['status'] === 'pending_signatures'));
        } elseif ($isAuditHead) {
            $reports = $departmentId ? $reportModel->forDepartment($departmentId) : [];
            $statusFilter = (string) ($this->request->getGet('status') ?: '');
            if ($statusFilter) $reports = array_values(array_filter($reports, fn($r) => $r['status'] === $statusFilter));
        } else {
            $reports = $reportModel->forUser((int) session()->get('user_id'));
        }

        // قائمة الإدارات المستهدفة لخيارات فلتر "الإدارة" -- تُبنى من التقارير
        // كاملة قبل أي فلترة (نفس منطق فلتر السنة: يعرض كل الإدارات المتاحة
        // بغض النظر عن الفلاتر النشطة حاليًا، مو بس اللي طلعت بالنتيجة المفلترة)
        $deptOptions = [];
        foreach ($reports as $r) {
            if (!empty($r['target_department_id'])) {
                $deptOptions[(int) $r['target_department_id']] = $r['target_dept_name'];
            }
        }
        asort($deptOptions);

        $yearFilter = (string) ($this->request->getGet('year') ?: '');
        if ($yearFilter) $reports = array_values(array_filter($reports, fn($r) => (string) $r['year'] === $yearFilter));

        $deptFilter = (string) ($this->request->getGet('dept') ?: '');
        if ($deptFilter) $reports = array_values(array_filter($reports, fn($r) => (string) $r['target_department_id'] === $deptFilter));

        $canCreate = !$isHr && !$isPresident && !$isAuditHead;
        $missions = $canCreate ? $this->missionsForCurrentSession() : [];

        return view('dashboard/reports/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'finalReports',
            'currentUser'  => $this->sessionUserSummary(),
            'reports'      => $reports,
            'isPresident'  => $isPresident,
            'isAuditHead'  => $isAuditHead,
            'isReadOnlyViewer' => $isHr || $isPresident,
            'canCreate'    => $canCreate,
            'missions'     => $missions,
            'yearFilter'   => $yearFilter,
            'deptOptions'  => $deptOptions,
            'deptFilter'   => $deptFilter,
            'statusFilter' => (string) ($this->request->getGet('status') ?: ''),
        ]);
    }

    /** GET /dashboard/reports/{missionId} — مراحل اعتماد تقرير مهمة معيّنة */
    public function show(int $missionId)
    {
        $mission = $this->missionForParty($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }

        $userId = (int) session()->get('user_id');
        $reportModel = new ReportModel();
        $itemModel = new ReportChecklistItemModel();

        $report = $reportModel->findOrCreateForMission($missionId, $userId);
        $items = $itemModel->forReport($report['id']);
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
        foreach ($items as &$it) {
            $it['section_number'] = (int) $it['section_number'];
        }
        unset($it);

        $completion = $this->realCompletionStatus($missionId);

        $roleCode = session()->get('role_code');
        $readOnlyViewer = $this->isHrDept() || $roleCode === 'audit_head' || $roleCode === 'top_management';

        $requestedStep = (int) ($this->request->getGet('step') ?: 0);
        $firstUnchecked = null;
        foreach ($items as $it) {
            if ((int) $it['is_checked'] !== 1) { $firstUnchecked = (int) $it['section_number']; break; }
        }
        $expandedStep = ($requestedStep && isset(self::STEPS[$requestedStep])) ? $requestedStep : ($firstUnchecked ?? (int) end($items)['section_number']);

        /* محتوى المرحلة المعروضة حاليًا يُضمَّن مباشرة بـ iframe لصفحته
           الحقيقية (نفس النموذج بالضبط اللي يشوفه أي طرف آخر) بدل رابط
           "افتح الصفحة" فقط أو إعادة بنائه بقالب مختلف هنا */
        $stepUrl = self::STEP_VIEW_URL[$expandedStep] ?? null;
        $stepEmbedUrl = $expandedStep === 1
            ? base_url('dashboard/pdf/mission-letter/' . $missionId) . '?inline=1'
            : ($stepUrl ? base_url($stepUrl) . '?mission_id=' . $missionId . '&embed=1' : null);

        return view('dashboard/reports/show', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'finalReports',
            'currentUser'  => $this->sessionUserSummary(),
            'mission'      => $mission,
            'report'       => $report,
            'items'        => $items,
            'completion'   => $completion,
            'readOnlyViewer' => $readOnlyViewer,
            'isAuditHead'  => $roleCode === 'audit_head',
            'expandedStep' => $expandedStep,
            'stepEmbedUrl' => $stepEmbedUrl,
        ]);
    }

    /**
     * GET /dashboard/reports/api/list — قائمة تقارير المستخدم. الإدارة الخاضعة للمراجعة
     * تشوف تقارير مهامها هي تحديدًا (طرف ثاني بالمهمة)، مو تقارير أي مهمة بالنظام
     */
    public function list()
    {
        $reportModel = new ReportModel();
        $departmentId = (int) session()->get('department_id');

        if ($this->isHrDept()) {
            $reports = $departmentId ? $reportModel->forTargetDepartment($departmentId) : [];
        } elseif (session()->get('role_code') === 'audit_head') {
            // رئيس إدارة المراجعة الداخلية يشرف على كل تقارير الإدارة، مو فقط تقارير
            // مهام هو نفسه فريق فيها (audit_team_members) -- forUser() ما تشمله أصلًا
            $reports = $departmentId ? $reportModel->forDepartment($departmentId) : [];
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
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $reportId = (int) ($data['report_id'] ?? 0);
        $section  = (int) ($data['section_number'] ?? 0);
        $checked  = $isJson ? (bool) ($data['checked'] ?? false) : true;

        if (!$reportId || !$section) {
            return $isJson ? $this->response->setStatusCode(422)->setJSON(['success' => false]) : redirect()->back();
        }
        if (!$this->canEditReport($reportId)) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        (new ReportChecklistItemModel())->setChecked($reportId, $section, $checked);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        $missionId = (int) ($data['mission_id'] ?? 0);
        $stepKeys = array_keys(self::STEPS);
        $pos = array_search($section, $stepKeys, true);
        $nextStep = ($pos !== false && isset($stepKeys[$pos + 1])) ? $stepKeys[$pos + 1] : $section;
        return redirect()->to(base_url('dashboard/reports/' . $missionId . '?step=' . $nextStep));
    }

    /** POST /dashboard/reports/api/finalize — يعتمد التقرير نهائيًا (كل المراحل لازم تكون معتمدة) */
    public function finalize()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $reportId = (int) ($data['report_id'] ?? 0);

        if (!$this->canEditReport($reportId)) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $itemModel = new ReportChecklistItemModel();
        $items = $itemModel->forReport($reportId);
        $allChecked = count($items) > 0 && !in_array(0, array_column($items, 'is_checked'), true);

        if (!$allChecked) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يجب اعتماد كل المراحل أولاً.']);
            }
            return redirect()->back()->with('error', 'يجب اعتماد كل المراحل أولاً.');
        }

        $reportModel = new ReportModel();
        $reportModel->update($reportId, ['status' => 'pending_signatures', 'generated_at' => date('Y-m-d H:i:s')]);

        $report = $reportModel->find($reportId);
        if ($report && !empty($report['mission_id'])) {
            (new \App\Models\AuditLogModel())->log((int) $report['mission_id'], (int) session()->get('user_id'), 'report_finalized', 'report', $reportId, 'رقم التقرير: ' . $reportId);
        }

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/reports/' . (int) ($data['mission_id'] ?? $report['mission_id'] ?? 0)))->with('success', 'تم إرسال التقرير للمراجعة بنجاح.');
    }

    /** الاعتماد النهائي (pending_signatures → sent) مقصور على رئيس إدارة المراجعة
     *  الداخلية، وفقط لتقارير إدارته هو (audit_department_id) */
    private function canApproveReport(int $reportId): bool
    {
        if (session()->get('role_code') !== 'audit_head') {
            return false;
        }

        $report = (new ReportModel())->find($reportId);
        if (!$report) {
            return false;
        }

        $mission = (new MissionModel())->find((int) $report['mission_id']);
        return $mission && (int) $mission['audit_department_id'] === (int) session()->get('department_id');
    }

    /** POST /dashboard/reports/api/approve — اعتماد رئيس إدارة المراجعة الداخلية النهائي،
     *  مع اسمه وتوقيعه (يُحفظان بالتقرير ويظهران أيضًا بمستند PDF الخاص به) */
    public function approve()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $reportId = (int) ($data['report_id'] ?? 0);
        $headName = trim((string) ($data['head_name'] ?? ''));
        $headSignature = (string) ($data['head_signature'] ?? '');
        $headApprovedAt = trim((string) ($data['head_approved_at'] ?? '')) ?: date('Y-m-d');

        if (!$reportId || !$this->canApproveReport($reportId)) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية اعتماد هذا التقرير.']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية اعتماد هذا التقرير.');
        }

        $reportModel = new ReportModel();
        $report = $reportModel->find($reportId);
        if (!$report || $report['status'] !== 'pending_signatures') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'التقرير غير جاهز للاعتماد حاليًا.']);
            }
            return redirect()->back()->with('error', 'التقرير غير جاهز للاعتماد حاليًا.');
        }
        if (!$headName || !$headSignature) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يجب إدخال اسم الرئيس والتوقيع قبل الاعتماد.']);
            }
            return redirect()->back()->with('error', 'يجب إدخال اسم الرئيس والتوقيع قبل الاعتماد.');
        }

        $reportModel->update($reportId, ['status' => 'sent', 'head_name' => $headName, 'head_signature' => $headSignature, 'head_approved_at' => $headApprovedAt]);
        (new \App\Models\AuditLogModel())->log((int) $report['mission_id'], (int) session()->get('user_id'), 'report_approved', 'report', $reportId, 'رقم التقرير: ' . $reportId);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/reports/' . (int) $report['mission_id']))->with('success', 'تم اعتماد التقرير بنجاح.');
    }

    /** POST /dashboard/reports/api/reject — رفض التقرير من رئيس إدارة المراجعة الداخلية،
     *  يرجعه للمراجع ليعدّله (pending_signatures → draft) مع سبب الرفض */
    public function reject()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $reportId = (int) ($data['report_id'] ?? 0);
        $note = trim((string) ($data['note'] ?? ''));

        if (!$reportId || !$this->canApproveReport($reportId)) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية رفض هذا التقرير.']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية رفض هذا التقرير.');
        }

        $reportModel = new ReportModel();
        $report = $reportModel->find($reportId);
        if (!$report || $report['status'] !== 'pending_signatures') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'التقرير غير جاهز للرفض حاليًا (لازم يكون بانتظار الاعتماد).']);
            }
            return redirect()->back()->with('error', 'التقرير غير جاهز للرفض حاليًا (لازم يكون بانتظار الاعتماد).');
        }
        if (!$note) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يجب كتابة سبب الرفض.']);
            }
            return redirect()->back()->with('error', 'يجب كتابة سبب الرفض.');
        }

        $reportModel->update($reportId, ['status' => 'draft', 'head_rejection_note' => $note]);
        (new \App\Models\AuditLogModel())->log((int) $report['mission_id'], (int) session()->get('user_id'), 'report_rejected', 'report', $reportId, $note);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/reports'))->with('success', 'تم رفض التقرير وإرجاعه للمراجع.');
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
