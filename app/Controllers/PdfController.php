<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\DepartmentModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
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

        // هيدر مكوَّن من سطرين فوق بعض (مو أعمدة جنب بعض) -- أعمدة الجداول داخل
        // SetHTMLHeader() بمPDF ما تلتزم دائمًا بالعرض المحدَّد لها، فيصير تصادم
        // بصري بين الأعمدة مع أي نص طويل؛ سطر واحد تحت الثاني يتفادى هذا كليًا
        $logo = FCPATH . 'assets/images/kamc.png';
        $header = '
            <table dir="rtl" width="100%" style="border-bottom:2px solid #3185b3;padding-bottom:6px;">
                <tr>
                    <td width="34" style="vertical-align:middle;"><img src="' . $logo . '" width="30"></td>
                    <td style="vertical-align:middle;text-align:right;font-family:dejavusans;">
                        <span style="font-size:12px;font-weight:bold;color:#196b7f;">إدارة المراجعة الداخلية</span>
                        <span style="font-size:9px;color:#6b7280;"> — ' . esc($docTitle) . ($deptName !== '' ? ' — ' . esc($deptName) : '') . '</span>
                    </td>
                </tr>
            </table>
            <div style="text-align:left;font-size:9px;color:#4b5563;font-family:dejavusans;margin-top:2px;">
                التاريخ: ' . date('d/m/Y') . '&nbsp;&nbsp;|&nbsp;&nbsp;رقم المهمة: ' . esc($missionCode) . '
            </div>';
        $mpdf->SetHTMLHeader($header);
    }

    private function assertMissionAccess(array $mission): void
    {
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();
        $allowed = $missionModel->activeMissionsForUser($userId);
        $ids = array_map('intval', array_column($allowed, 'id'));

        if ((int) $mission['mission_head_id'] !== $userId && !in_array((int) $mission['id'], $ids, true)) {
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
}
