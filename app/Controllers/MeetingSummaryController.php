<?php

namespace App\Controllers;

use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;
use App\Models\MissionModel;
use App\Models\DocumentModel;

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

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    private function assertMissionAccess(int $missionId): array
    {
        $mission = (new MissionModel())->findWithDetails($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة.');
        }
        $allowedIds = array_map('intval', array_column($this->missionsForCurrentSession(), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
        return $mission;
    }

    /** GET /dashboard/meetings — صفحة ملخص الاجتماع الحقيقية (Server-Rendered) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $flags = $this->roleFlags();
        $meeting = null;
        $attendees = [['id' => null, 'external_name' => '', 'attendee_dept' => '', 'attendee_position' => '']];
        $points = [['id' => null, 'point_text' => '', 'statement' => '', 'hr_opinion' => '', 'hr_reason' => '']];
        $approvals = [['id' => null, 'statement' => 'إعداد واعتماد', 'signer_name' => '', 'position' => 'رئيس المهمة', 'signature_data' => null, 'approval_date' => null]];
        $attachments = [];
        $mission = null;

        $draft = session()->getFlashdata('draftMeeting');

        if ($missionId) {
            $mission = $this->assertMissionAccess($missionId);
            $userId = (int) session()->get('user_id');
            $meetingModel  = new MeetingModel();
            $attendeeModel = new MeetingAttendeeModel();
            $pointModel    = new MeetingSummaryPointModel();
            $approvalModel = new MeetingApprovalModel();

            $meeting = $meetingModel->findOrCreateForMission($missionId, $userId);

            if ($draft !== null) {
                $meeting      = array_merge($meeting, $draft['meeting'] ?? []);
                $attendees    = $draft['attendees'] ?: $attendees;
                $points       = $draft['points'] ?: $points;
                $approvals    = $draft['approvals'] ?: $approvals;
            } else {
                $dbAttendees = $attendeeModel->forMeeting($meeting['id']);
                $dbPoints    = $pointModel->forMeeting($meeting['id']);
                $dbApprovals = $approvalModel->forMeeting($meeting['id']);
                if ($dbAttendees) $attendees = $dbAttendees;
                if ($dbPoints) $points = $dbPoints;
                if ($dbApprovals) $approvals = $dbApprovals;
            }

            $attachments = (new DocumentModel())->forRelated('meeting', $meeting['id']);
        }

        /* embed=1 -- الصفحة مضمَّنة بـ iframe داخل مراحل اعتماد التقرير النهائي
           لغرض المعاينة فقط، فتُجبَر على عرض فقط ويُخفى تصدير PDF الخاص بها */
        $embed = $this->request->getGet('embed') === '1';

        return view('dashboard/meetings/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'meetingSummary',
            'currentUser'  => $this->sessionUserSummary(),
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'meeting'           => $meeting,
            'attendees'         => $attendees,
            'points'            => $points,
            'approvals'         => $approvals,
            'attachments'       => $attachments,
            'isHrUser'          => $flags['isHrUser'],
            'allReadOnly'       => $flags['allReadOnly'] || $embed,
            'embed'             => $embed,
        ]);
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
            $points = [['id' => null, 'point_text' => '', 'statement' => '', 'hr_opinion' => '', 'hr_reason' => '']];
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
        $isJson = $this->isJsonRequest();
        $flags = $this->roleFlags();
        if ($flags['allReadOnly']) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        if (!$missionId) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
            }
            return redirect()->back()->with('error', 'يرجى اختيار المهمة المرتبطة أولاً.');
        }

        if (!$isJson) {
            $this->assertMissionAccess($missionId);

            /* نموذج بدون جافاسكربت: أزرار "إضافة/حذف" لجدولي الحضور والنقاط لا تحفظان
               فعليًا -- تعدّلان مصفوفة مؤقتة بالجلسة (draftMeeting) وتعيدان عرض نفس
               النموذج، بنفس نمط RiskMatrixController::save() بالضبط */
            $formAction = $data['form_action'] ?? 'save';
            if (in_array($formAction, ['add_attendee', 'remove_attendee', 'add_point', 'remove_point'], true)) {
                $attendees = $data['attendees'] ?? [];
                $points    = $data['points'] ?? [];

                if ($formAction === 'add_attendee') {
                    $attendees[] = ['name' => '', 'dept' => '', 'position' => ''];
                } elseif ($formAction === 'remove_attendee') {
                    unset($attendees[(int) ($data['remove_index'] ?? -1)]);
                    $attendees = array_values($attendees);
                } elseif ($formAction === 'add_point') {
                    $points[] = ['text' => '', 'statement' => '', 'hr_opinion' => '', 'hr_reason' => ''];
                } elseif ($formAction === 'remove_point') {
                    unset($points[(int) ($data['remove_index'] ?? -1)]);
                    $points = array_values($points);
                }

                return redirect()->to(base_url('dashboard/meetings?mission_id=' . $missionId))->with('draftMeeting', [
                    'meeting'   => [
                        'title' => $data['title'] ?? '', 'objective' => $data['objective'] ?? '',
                        'meeting_date' => $data['date'] ?? '', 'meeting_time' => $data['time'] ?? '', 'location' => $data['location'] ?? '',
                    ],
                    'attendees' => $attendees,
                    'points'    => $points,
                    'approvals' => $data['approvals'] ?? [],
                ]);
            }
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
        (new MissionModel())->syncCurrentStage($missionId);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meetings?mission_id=' . $missionId))->with('success', 'تم حفظ ملخص الاجتماع بنجاح.');
    }
}
