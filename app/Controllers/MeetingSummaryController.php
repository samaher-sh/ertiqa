<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MeetingModel;
use App\Models\MeetingAttendeeModel;
use App\Models\MeetingSummaryPointModel;
use App\Models\MeetingApprovalModel;

class MeetingSummaryController extends BaseController
{
    private function roleFlags(): array
    {
        $roleCode = session()->get('role_code');
        return [
            'isHrUser'    => in_array($roleCode, ['hr_coordinator', 'dept_manager', 'specialized_manager'], true),
            'allReadOnly' => $roleCode === 'audit_head',
        ];
    }

    /**
     * GET /dashboard/meetings — عرض الصفحة
     */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();
        $flags = $this->roleFlags();

        return view('dashboard/meeting-summary', array_merge([
            'full_name'   => session()->get('full_name'),
            'role_name'   => session()->get('role_name'),
            'navItems'    => $this->buildNavItems(),
            'missions'    => $missionModel->activeMissionsForUser($userId),
        ], $flags));
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
            'meeting_date' => $data['date'] ?: null,
            'meeting_time' => $data['time'] ?: null,
            'location'     => $data['location'] ?? null,
        ]);

        $attendeeModel->replaceForMeeting($meeting['id'], $data['attendees'] ?? []);
        $pointModel->replaceForMeeting($meeting['id'], $data['points'] ?? []);

        // الاعتماد يظهر فقط لغير HR - نتحقق بالباك-إند برضو مو بس بالواجهة
        if (!$flags['isHrUser']) {
            $approvalModel->replaceForMeeting($meeting['id'], $data['approvals'] ?? []);
        }

        return $this->response->setJSON(['success' => true]);
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
            if (isset($all[$k])) {
                $result[] = array_merge(['key' => $k], $all[$k]);
            }
        }
        return $result;
    }
}
