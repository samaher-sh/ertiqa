<?php

namespace App\Controllers;

use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;

class MeetingSummaryController extends BaseController
{
    private function roleFlags(): array
    {
        $roleCode = session()->get('role_code');
        return [
            'isHrUser'    => in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true),
            'allReadOnly' => $roleCode === 'audit_head',
        ];
    }

    /**
     * GET /dashboard/meetings/api/data?mission_id=X — يجيب (أو ينشئ) الاجتماع وكل بياناته
     */
    public function data()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'مهمة غير محددة']);
        }

        $userId = (int) session()->get('user_id');

        $meetingModel  = new MeetingModel();
        $attendeeModel = new MeetingAttendeeModel();
        $pointModel    = new MeetingSummaryPointModel();
        $approvalModel = new MeetingApprovalModel();

        $meeting = $meetingModel->findOrCreateForMission($missionId, $userId);

        $attendees = $attendeeModel->forMeeting($meeting['id']);
        $points    = $pointModel->forMeeting($meeting['id']);
        $approvals = $approvalModel->forMeeting($meeting['id']);

        // قيم افتراضية أول مرة (نفس سلوك useState الافتراضي بالواجهة الأصلية)
        if (empty($attendees)) {
            $attendees = [['id' => null, 'external_name' => '', 'attendee_dept' => '', 'attendee_position' => '']];
        }
        if (empty($points)) {
            $points = [['id' => null, 'point_text' => '', 'opinion' => '', 'reason' => '']];
        }
        if (empty($approvals)) {
            $approvals = [['id' => null, 'statement' => 'إعداد واعتماد', 'signer_name' => '', 'position' => 'رئيس المهمة', 'signature_data' => null, 'approval_date' => null]];
        }

        return $this->response->setJSON([
            'success'   => true,
            'meeting'   => $meeting,
            'attendees' => $attendees,
            'points'    => $points,
            'approvals' => $approvals,
        ]);
    }

    /**
     * POST /dashboard/meetings/api/save — حفظ كل شي دفعة وحدة
     */
    public function save()
    {
        $flags = $this->roleFlags();
        if ($flags['allReadOnly']) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
        }

        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
        }

        $userId = (int) session()->get('user_id');

        $meetingModel  = new MeetingModel();
        $attendeeModel = new MeetingAttendeeModel();
        $pointModel    = new MeetingSummaryPointModel();
        $approvalModel = new MeetingApprovalModel();

        $meeting = $meetingModel->findOrCreateForMission($missionId, $userId);

        $meetingModel->update($meeting['id'], [
            'title'        => $data['title'] ?? null,
            'objective'    => $data['objective'] ?? null,
            'meeting_date' => ($data['date'] ?? null) ?: null,
            'meeting_time' => ($data['time'] ?? null) ?: null,
            'location'     => $data['location'] ?? null,
        ]);

        $attendeeModel->replaceForMeeting($meeting['id'], $data['attendees'] ?? []);
        $pointModel->replaceForMeeting($meeting['id'], $data['points'] ?? []);

        // الاعتماد يظهر فقط لغير HR - نتحقق بالباك-إند برضو مو بس بالواجهة
        if (!$flags['isHrUser']) {
            $approvalModel->replaceForMeeting($meeting['id'], $data['approvals'] ?? []);
        }

        $stageHistoryModel = new MissionStageHistoryModel();
        $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 4)->countAllResults();
        if ($alreadyLogged === 0) {
            $stageHistoryModel->openStage($missionId, 4, $userId);
        }
        (new AuditLogModel())->log($missionId, $userId, 'meeting_summary_saved', 'meeting', $meeting['id'], trim((string) ($data['title'] ?? '')) ?: null);

        return $this->response->setJSON(['success' => true]);
    }
}
