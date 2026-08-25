<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\DepartmentModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\DocumentRequestModel;
use App\Models\AuditNoteModel;
use App\Models\ReportModel;
use Mpdf\Mpdf;

class PdfController extends BaseController
{
    /**
     * يبني كائن mPDF بإعدادات صحيحة للعربي (اتجاه RTL + تشكيل الحروف المتصلة تلقائيًا)
     * بديل Dompdf اللي كان يطلع النص العربي معكوس/غير متصل الحروف
     */
    private function makeMpdf(): Mpdf
    {
        return new Mpdf([
            'mode'            => 'utf-8',
            'format'          => 'A4',
            'default_font'    => 'dejavusans', // يدعم العربي بدون أي تثبيت خط إضافي
            'directionality'  => 'rtl',
            'margin_left'     => 15,
            'margin_right'    => 15,
            'margin_top'      => 15,
            'margin_bottom'   => 15,
        ]);
    }

    private function streamPdf(Mpdf $mpdf, string $html, string $filename)
    {
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, 'D'); // D = تحميل مباشر (Download)
        exit;
    }

    /**
     * فوتر متكرر بكل صفحات المستند (ترقيم صفحات تلقائي + إشعار سرية) -- يُستخدم
     * بكل مستندات PDF المصدَّرة من السيرفر عشان تكون كلها "مرتبة" بشكل موحّد
     */
    private function applyRunningFooter(Mpdf $mpdf, string $missionCode): void
    {
        // يخلي mPDF يوسّع الهامش السفلي تلقائيًا حسب الارتفاع الفعلي لمحتوى الفوتر
        // (بدل تخمين قيمة ثابتة يدويًا) عشان ما يتصادم بصريًا مع متن المستند
        $mpdf->setAutoBottomMargin = 'stretch';
        $footer = '
            <table dir="rtl" width="100%" style="border-top:1px solid #d8e6eb;padding-top:4px;font-size:8px;color:#9ca3af;font-family:dejavusans;table-layout:fixed;">
                <tr>
                    <td width="75%" style="text-align:right;">مستند صادر من نظام ارتقاء — إدارة المراجعة الداخلية، سرّي وخاص بالمهمة ' . esc($missionCode) . '</td>
                    <td width="25%" style="text-align:left;">صفحة {PAGENO} من {nbpg}</td>
                </tr>
            </table>';
        $mpdf->SetHTMLFooter($footer);
    }

    /**
     * هيدر متكرر (شعار + اسم الإدارة + عنوان المستند + التاريخ/رقم المهمة) --
     * تستخدمه مستندات مصفوفة المخاطر وملخص الاجتماع (ما كان فيها هيدر مرتب أصلاً)؛
     * الخطاب الرسمي (missionLetter) عنده هيدر خاص مدموج بنص الخطاب نفسه فلا يُستخدم هنا معه
     * لتفادي تكرار الشعار مرتين
     */
    private function applyRunningHeader(Mpdf $mpdf, string $docTitle, string $missionCode, string $deptName): void
    {
        // نقصّ اسم الإدارة (بعضها طويل جدًا) عشان ما يتصادم بصريًا مع بقية سطر العنوان --
        // الاسم الكامل يبقى ظاهر بمتن المستند نفسه على أي حال
        if (mb_strlen($deptName) > 26) {
            $deptName = mb_substr($deptName, 0, 25) . '…';
        }

        // نفس فكرة setAutoBottomMargin أعلاه بس للهامش العلوي، عشان ارتفاع الهيدر
        // الفعلي (سطرين) ما يفيض على متن المستند
        $mpdf->setAutoTopMargin = 'stretch';

        // هيدر رسمي بخلفية بيضاء (خطاب-ستايل) بدل الشريط الملوّن السابق -- شعار
        // المستشفى مباشرة بدون دائرة ملوّنة، وخط سفلي رفيع بلون هوية المنصة يفصله
        // عن متن المستند، بنفس طابع mission-letter.php وباقي مستندات PDF بالنظام
        // kamc-pdf-logo.png مقصوص لحجمه الفعلي بالضبط (32×30) بدون سمة width -- mPDF
        // يفشل بصمت بتصغير شعار kamc.png الأصلي (1005×944) داخل جدول مهما كانت نسبة
        // التصغير، حتى 1.5x؛ العرض الطبيعي 1:1 فقط يشتغل بثبات
        $logo = FCPATH . 'assets/images/kamc-pdf-logo.png';
        $header = '
            <table dir="rtl" width="100%" style="border-bottom:1.5px solid #3185b3;padding-bottom:6px;">
                <tr>
                    <td width="40" style="vertical-align:middle;"><img src="' . $logo . '"></td>
                    <td style="vertical-align:middle;text-align:right;font-family:dejavusans;">
                        <span style="font-size:12px;font-weight:bold;color:#196b7f;">إدارة المراجعة الداخلية</span>
                        <span style="font-size:9px;color:#6b8c95;"> — ' . esc($docTitle) . ($deptName !== '' ? ' — ' . esc($deptName) : '') . '</span>
                    </td>
                    <td style="vertical-align:middle;text-align:left;font-size:9px;color:#6b8c95;font-family:dejavusans;white-space:nowrap;">
                        التاريخ: ' . date('d/m/Y') . '&nbsp;&nbsp;|&nbsp;&nbsp;رقم المهمة: ' . esc($missionCode) . '
                    </td>
                </tr>
            </table>';
        $mpdf->SetHTMLHeader($header);
    }

    private function assertMissionAccess(array $mission): void
    {
        // رئيس إدارة المراجعة الداخلية طرف ضمنيًا بكل مهام إدارته (audit_department_id)
        // حتى لو مو عضو فريق فيها -- يحتاج يصدّر تقارير مهام لسا ما شارك بها مباشرة
        if (session()->get('role_code') === 'audit_head') {
            if ((int) $mission['audit_department_id'] !== (int) session()->get('department_id')) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
            }
            return;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');
        $missionModel = new MissionModel();
        $allowed = $missionModel->activeMissionsForUser($userId);
        $ids = array_map('intval', array_column($allowed, 'id'));

        // منسّق/مدير الإدارة الخاضعة للمراجعة طرف بالمهمة أيضًا (target_department_id)
        // حتى لو مو رئيسها ولا عضو فريقها -- نفس نمط ReportController::missionForParty،
        // مطلوب هنا عشان يقدر يصدّر تقريرًا نهائيًا معتمدًا يشاهده أصلًا
        $isTargetSide = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        if ((int) $mission['mission_head_id'] !== $userId && !in_array((int) $mission['id'], $ids, true) && !$isTargetSide) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
    }

    public function missionLetter(int $missionId)
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة');
        $this->assertMissionAccess($mission);

        $deptModel = new DepartmentModel();
        $mainDept   = $deptModel->find($mission['audit_department_id']);
        $targetDept = $deptModel->find($mission['target_department_id']);

        $html = view('pdf/mission-letter', [
            'mission'    => $mission,
            'mainDept'   => $mainDept,
            'targetDept' => $targetDept,
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningFooter($mpdf, $mission['mission_code']);
        $this->streamPdf($mpdf, $html, 'خطاب-' . $mission['mission_code'] . '.pdf');
    }

    /**
     * POST /dashboard/pdf/wizard-letter-preview — معاينة "نموذج الخطاب الرسمي" أثناء
     * تعبئة معالج بدء مهمة، قبل حفظ المهمة فعليًا (لا يوجد mission_id بعد) --
     * يبني المستند بنفس قالب mission-letter الحقيقي المستخدم لخطاب مهمة محفوظة
     * فعلًا، لكن من بيانات النموذج المُرسلة مباشرة (POST JSON) بدل قراءتها من
     * قاعدة البيانات، عشان يطلع بنفس شكل خطاب المهمة الرسمي بالضبط (بدون تكرار
     * قالب منفصل)
     */
    public function wizardLetterPreview()
    {
        $data = $this->request->getJSON(true) ?? [];

        $mission = [
            'year'            => $data['year'] ?? '',
            'mission_code'    => $data['mission_code'] ?? '',
            'procedure_note'  => $data['procedure_note'] ?? '',
            'reviewer_name'   => $data['reviewer_name'] ?? '',
            'reviewer_email'  => $data['reviewer_email'] ?? '',
            'reviewer_phone'  => $data['reviewer_phone'] ?? '',
            'director_name'   => $data['director_name'] ?? '',
        ];
        $mainDept   = ['name_ar' => $data['main_dept_name'] ?? ''];
        $targetDept = ['name_ar' => $data['target_dept_name'] ?? ''];

        $html = view('pdf/mission-letter', compact('mission', 'mainDept', 'targetDept'));

        $mpdf = $this->makeMpdf();
        $this->applyRunningFooter($mpdf, (string) $mission['mission_code']);
        $this->streamPdf($mpdf, $html, 'خطاب-معاينة-' . ($mission['mission_code'] ?: 'مسودة') . '.pdf');
    }

    /**
     * POST /dashboard/pdf/service-agreement-preview — تصدير اتفاقية مستوى الخدمة
     * (صفحة 2 بمعالج بدء مهمة)، قبل حفظها فعليًا -- بنود الاتفاقية (SLA_SECTIONS)
     * تُرسل من الواجهة مباشرة ضمن الطلب بدل تكرارها بالباك-إند (المصدر الوحيد
     * لها هو dashboard-data.js أصلًا)
     */
    public function serviceAgreementPreview()
    {
        $data = $this->request->getJSON(true) ?? [];

        $channelLabels = ['email' => 'البريد الإلكتروني', 'memo' => 'المذكرات الداخلية', 'phone' => 'الهاتف الداخلي'];
        $channelValues = (array) ($data['channel_values'] ?? []);
        $activeChannels = [];
        foreach ((array) ($data['channels'] ?? []) as $key => $on) {
            if ($on && isset($channelLabels[$key])) {
                $activeChannels[] = ['label' => $channelLabels[$key], 'value' => $channelValues[$key] ?? ''];
            }
        }

        $html = view('pdf/service-agreement', [
            'subjectDept'    => $data['subject_dept'] ?? '',
            'date'           => $data['date'] ?? '',
            'desc'           => $data['desc'] ?? '',
            'activeChannels' => $activeChannels,
            'sections'       => (array) ($data['sections'] ?? []),
            'sigName'        => $data['sig_name'] ?? '',
            'sigDate'        => $data['sig_date'] ?? '',
            'sigSignature'   => $data['sig_signature'] ?? '',
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'اتفاقية مستوى الخدمة', '', $data['subject_dept'] ?? '');
        $this->applyRunningFooter($mpdf, '');
        $this->streamPdf($mpdf, $html, 'اتفاقية-مستوى-الخدمة.pdf');
    }

    /**
     * POST /dashboard/pdf/observation-preview — تصدير ملاحظة رقابية واحدة (من
     * صفحة الملاحظات، سواء محفوظة فعلًا أو لسا مسودة تحت التعبئة) -- POST
     * دائمًا (بدل GET بمعرّف) عشان يشتغل حتى قبل حفظ الملاحظة
     */
    public function observationPreview()
    {
        $data = $this->request->getJSON(true) ?? [];

        $html = view('pdf/observation', [
            'ref'             => $data['ref'] ?? '',
            'missionCode'     => $data['mission_code'] ?? '',
            'title'           => $data['title'] ?? '',
            'dept'            => $data['dept'] ?? '',
            'date'            => $data['date'] ?? '',
            'risk'            => $data['risk'] ?? '',
            'observation'     => $data['observation'] ?? '',
            'standard'        => $data['standard'] ?? '',
            'reason'          => $data['reason'] ?? '',
            'impact'          => $data['impact'] ?? '',
            'recommendations' => $data['recommendations'] ?? '',
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'ملاحظة رقابية', (string) ($data['mission_code'] ?? ''), $data['dept'] ?? '');
        $this->applyRunningFooter($mpdf, (string) ($data['mission_code'] ?? ''));
        $this->streamPdf($mpdf, $html, 'ملاحظة-' . ($data['ref'] ?: 'رقابية') . '.pdf');
    }

    /**
     * POST /dashboard/pdf/observations-list-preview — تصدير كل ملاحظات مهمة
     * معيّنة دفعة وحدة من صفحة الملاحظات (زر "تصدير PDF" العلوي بالقائمة) --
     * كل ملاحظة بتفاصيلها الكاملة (نفس حقول observation-preview)، بنفس نمط
     * قسم "6. الملاحظات" بالتقرير النهائي بالضبط، بدل جدول ملخّص بأعمدة قليلة
     * فقط زي الجدول المعروض على الشاشة
     */
    public function observationsListPreview()
    {
        $data = $this->request->getJSON(true) ?? [];
        $missionCode = (string) ($data['mission_code'] ?? '');

        $html = view('pdf/observations-list', [
            'missionCode'  => $missionCode,
            'observations' => (array) ($data['observations'] ?? []),
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'الملاحظات', $missionCode, '');
        $this->applyRunningFooter($mpdf, $missionCode);
        $this->streamPdf($mpdf, $html, 'ملاحظات-' . ($missionCode ?: 'رقابية') . '.pdf');
    }

    public function riskMatrix(int $missionId)
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة');
        $this->assertMissionAccess($mission);

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find($mission['target_department_id']);

        $itemModel = new RiskMatrixItemModel();
        $items = $itemModel->forMission($missionId);

        $html = view('pdf/risk-matrix', [
            'mission'    => $mission,
            'targetDept' => $targetDept,
            'items'      => $items,
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'مصفوفة المخاطر', $mission['mission_code'], $targetDept['name_ar'] ?? '');
        $this->applyRunningFooter($mpdf, $mission['mission_code']);
        $this->streamPdf($mpdf, $html, 'مصفوفة-مخاطر-' . $mission['mission_code'] . '.pdf');
    }

    public function meetingSummary(int $missionId)
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة');
        $this->assertMissionAccess($mission);

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find($mission['target_department_id']);

        $meetingModel = new MeetingModel();
        $meeting = $meetingModel->firstForMission($missionId);

        $attendees = [];
        $points    = [];
        $approvals = [];

        if ($meeting) {
            $attendees = (new MeetingAttendeeModel())->forMeeting($meeting['id']);
            $points    = (new MeetingSummaryPointModel())->forMeeting($meeting['id']);
            $approvals = (new MeetingApprovalModel())->forMeeting($meeting['id']);
        }

        $html = view('pdf/meeting-summary', [
            'mission'    => $mission,
            'targetDept' => $targetDept,
            'meeting'    => $meeting,
            'attendees'  => $attendees,
            'points'     => $points,
            'approvals'  => $approvals,
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'ملخص الاجتماع', $mission['mission_code'], $targetDept['name_ar'] ?? '');
        $this->applyRunningFooter($mpdf, $mission['mission_code']);
        $this->streamPdf($mpdf, $html, 'ملخص-اجتماع-' . $mission['mission_code'] . '.pdf');
    }

    /**
     * تصدير التقرير النهائي الكامل (كل مراحله الست بمستند واحد) — مقصور على
     * تقارير معتمدة فعليًا (status = sent)، مو تحت الإعداد أو بانتظار الاعتماد
     */
    public function finalReport(int $missionId)
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة');
        $this->assertMissionAccess($mission);

        $report = (new ReportModel())->where('mission_id', $missionId)->first();
        if (!$report || $report['status'] !== 'sent') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('التقرير غير معتمد بعد.');
        }

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find($mission['target_department_id']);

        $agreement    = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        $slaResponses = (new ServiceAgreementResponseModel())->forMission($missionId);
        $docRequests  = (new DocumentRequestModel())->forMissionWithResponses($missionId);
        $riskItems    = (new RiskMatrixItemModel())->forMission($missionId);

        $meeting   = (new MeetingModel())->firstForMission($missionId);
        $attendees = $meeting ? (new MeetingAttendeeModel())->forMeeting($meeting['id']) : [];
        $points    = $meeting ? (new MeetingSummaryPointModel())->forMeeting($meeting['id']) : [];
        $approvals = $meeting ? (new MeetingApprovalModel())->forMeeting($meeting['id']) : [];

        $observations = (new AuditNoteModel())->forMission($missionId);

        $html = view('pdf/final-report', [
            'mission'      => $mission,
            'targetDept'   => $targetDept,
            'report'       => $report,
            'agreement'    => $agreement,
            'slaResponses' => $slaResponses,
            'docRequests'  => $docRequests,
            'riskItems'    => $riskItems,
            'meeting'      => $meeting,
            'attendees'    => $attendees,
            'points'       => $points,
            'approvals'    => $approvals,
            'observations' => $observations,
        ]);

        $mpdf = $this->makeMpdf();
        $this->applyRunningHeader($mpdf, 'التقرير النهائي', $mission['mission_code'], $targetDept['name_ar'] ?? '');
        $this->applyRunningFooter($mpdf, $mission['mission_code']);
        $this->streamPdf($mpdf, $html, 'تقرير-نهائي-' . $mission['mission_code'] . '.pdf');
    }
}
