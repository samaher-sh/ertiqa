<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\DepartmentModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController extends BaseController
{
    /**
     * يبني كائن Dompdf جاهز بإعدادات تدعم العربي (خط Amiri/DejaVu Sans مدمج بالمكتبة)
     */
    private function makeDompdf(): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // يدعم العربي أصلًا بدون تثبيت خط إضافي

        $dompdf = new Dompdf($options);
        return $dompdf;
    }

    private function streamPdf(Dompdf $dompdf, string $html, string $filename)
    {
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit; // Dompdf يرسل الهيدرز مباشرة، لازم نوقف تنفيذ CI4 بعدها
    }

    /**
     * يتحقق إن المستخدم الحالي له صلاحية الوصول لهذي المهمة (رئيسها أو أحد أعضاء فريقها)
     */
    private function assertMissionAccess(array $mission): void
    {
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();
        $allowed = $missionModel->activeMissionsForUser($userId);
        $ids = array_column($allowed, 'id');

        if ((int) $mission['mission_head_id'] !== $userId && !in_array((int) $mission['id'], $ids, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
    }

    /**
     * GET /dashboard/pdf/mission-letter/{missionId}
     */
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

        $this->streamPdf($this->makeDompdf(), $html, 'خطاب-' . $mission['mission_code'] . '.pdf');
    }

    /**
     * GET /dashboard/pdf/risk-matrix/{missionId}
     */
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

        $this->streamPdf($this->makeDompdf(), $html, 'مصفوفة-مخاطر-' . $mission['mission_code'] . '.pdf');
    }

    /**
     * GET /dashboard/pdf/meeting-summary/{missionId}
     */
    public function meetingSummary(int $missionId)
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة');
        $this->assertMissionAccess($mission);

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find($mission['target_department_id']);

        $meetingModel  = new MeetingModel();
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

        $this->streamPdf($this->makeDompdf(), $html, 'ملخص-اجتماع-' . $mission['mission_code'] . '.pdf');
    }
}
