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

        $this->streamPdf($this->makeMpdf(), $html, 'خطاب-' . $mission['mission_code'] . '.pdf');
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

        $this->streamPdf($this->makeMpdf(), $html, 'مصفوفة-مخاطر-' . $mission['mission_code'] . '.pdf');
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

        $this->streamPdf($this->makeMpdf(), $html, 'ملخص-اجتماع-' . $mission['mission_code'] . '.pdf');
    }
}
