<?php

/**
 * 
 */

namespace App\Controllers;

use App\Models\MissionChatMessageModel;
use App\Models\MeetingModel;
use App\Models\AuditLogModel;

class MissionChatController extends BaseController
{
    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    private function assertMissionAccess(int $missionId): array
    {
        $mission = (new \App\Models\MissionModel())->find($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة.');
        }
        $allowedIds = array_map('intval', array_column($this->missionsForCurrentSession(), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
        return $mission;
    }

    /** GET /dashboard/meeting-schedule — صفحة جدولة الاجتماع الحقيقية (Server-Rendered) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $messages = [];
        $meeting = null;
        if ($missionId) {
            $this->assertMissionAccess($missionId);
            $chatModel = new MissionChatMessageModel();
            $meetingModel = new MeetingModel();
            $messages = $chatModel->forMission($missionId);
            $meeting = $meetingModel->firstForMission($missionId);
        }

        return view('dashboard/meeting-schedule/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'meetingSchedule',
            'currentUser'  => $this->sessionUserSummary(),
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'messages'          => $messages,
            'meeting'           => $meeting,
            'myUserId'          => (int) session()->get('user_id'),
        ]);
    }

    /** GET /dashboard/meeting-schedule/api/messages?mission_id=X */
    public function messages()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) {
            return $this->response->setJSON(['success' => true, 'messages' => [], 'meeting' => null]);
        }

        $chatModel = new MissionChatMessageModel();
        $meetingModel = new MeetingModel();

        return $this->response->setJSON([
            'success'  => true,
            'messages' => $chatModel->forMission($missionId),
            'meeting'  => $meetingModel->firstForMission($missionId),
            'my_user_id' => (int) session()->get('user_id'),
        ]);
    }

    /** POST /dashboard/meeting-schedule/api/send — رسالة نصية عادية */
    public function send()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $message   = trim($data['message'] ?? '');

        if (!$missionId || $message === '') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الرسالة فارغة.']);
            }
            return redirect()->back()->with('error', 'الرسالة فارغة.');
        }
        if (!$isJson) {
            $this->assertMissionAccess($missionId);
        }

        $userId = (int) session()->get('user_id');
        $chatModel = new MissionChatMessageModel();
        $chatModel->insert([
            'mission_id' => $missionId,
            'sender_id'  => $userId,
            'message'    => $message,
            'type'       => 'text',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        (new AuditLogModel())->log($missionId, $userId, 'chat_message', 'mission_chat_message', null, mb_substr($message, 0, 100));

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meeting-schedule?mission_id=' . $missionId));
    }

    /** POST /dashboard/meeting-schedule/api/propose — اقتراح موعد اجتماع */
    public function propose()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $date = $data['date'] ?? null;
        $time = $data['time'] ?? null;
        $location = trim($data['location'] ?? '');

        if (!$missionId || !$date || !$time) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى تحديد التاريخ والوقت.']);
            }
            return redirect()->back()->with('error', 'يرجى تحديد التاريخ والوقت.');
        }
        if (!$isJson) {
            $this->assertMissionAccess($missionId);
        }

        $chatModel = new MissionChatMessageModel();
        $chatModel->insert([
            'mission_id'         => $missionId,
            'sender_id'          => (int) session()->get('user_id'),
            'message'            => 'اقترح موعدًا للاجتماع',
            'type'               => 'proposal',
            'proposed_date'      => $date,
            'proposed_time'      => $time,
            'proposed_location'  => $location ?: null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $detail = 'التاريخ: ' . $date . ' — الوقت: ' . $time . ($location ? ' — ' . $location : '');
        (new AuditLogModel())->log($missionId, (int) session()->get('user_id'), 'meeting_proposed', 'meeting', null, $detail);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meeting-schedule?mission_id=' . $missionId));
    }

    /** POST /dashboard/meeting-schedule/api/confirm — تأكيد موعد مُقترح مسبقًا (بالطرف التاني) */
    public function confirm()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $messageId = (int) ($data['message_id'] ?? 0);
        $userId    = (int) session()->get('user_id');
        if (!$isJson && $missionId) {
            $this->assertMissionAccess($missionId);
        }

        $chatModel = new MissionChatMessageModel();
        $proposal  = $chatModel->find($messageId);

        if (!$proposal || (int) $proposal['mission_id'] !== $missionId || $proposal['type'] !== 'proposal') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الاقتراح غير موجود.']);
            }
            return redirect()->back()->with('error', 'الاقتراح غير موجود.');
        }

        // لا يجوز إن نفس الشخص اللي اقترح يأكد اقتراحه هو (لازم الطرف الثاني)
        if ((int) $proposal['sender_id'] === $userId) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'لا يمكنك تأكيد اقتراحك الخاص — بانتظار تأكيد الطرف الآخر.']);
            }
            return redirect()->back()->with('error', 'لا يمكنك تأكيد اقتراحك الخاص — بانتظار تأكيد الطرف الآخر.');
        }

        $meetingModel = new MeetingModel();
        $meeting = $meetingModel->findOrCreateForMission($missionId, $userId);
        $meetingModel->update($meeting['id'], [
            'meeting_date' => $proposal['proposed_date'],
            'meeting_time' => $proposal['proposed_time'],
            'location'     => $proposal['proposed_location'],
            'status'       => 'scheduled',
        ]);

        // نحوّل رسالة الاقتراح الأصلية لنوع "مؤكد" حتى ما يبقى زر التأكيد ظاهرًا
        // عليها لو تحدّث الشات لاحقًا، ثم نضيف رسالة نظامية منفصلة بوقت التأكيد الفعلي
        $chatModel->update($messageId, ['type' => 'confirmed']);

        $chatModel->insert([
            'mission_id'         => $missionId,
            'sender_id'          => $userId,
            'message'            => 'تم تأكيد الموعد ✓',
            'type'               => 'confirmed',
            'proposed_date'      => $proposal['proposed_date'],
            'proposed_time'      => $proposal['proposed_time'],
            'proposed_location'  => $proposal['proposed_location'],
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $detail = 'التاريخ: ' . $proposal['proposed_date'] . ' — الوقت: ' . $proposal['proposed_time'];
        (new AuditLogModel())->log($missionId, $userId, 'meeting_confirmed', 'meeting', $meeting['id'], $detail);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meeting-schedule?mission_id=' . $missionId));
    }

    /** POST /dashboard/meeting-schedule/api/cancel — إلغاء اقتراح موعد لم يُؤكَّد بعد (بالطرف الثاني) */
    public function cancel()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $messageId = (int) ($data['message_id'] ?? 0);
        $userId    = (int) session()->get('user_id');
        if (!$isJson && $missionId) {
            $this->assertMissionAccess($missionId);
        }

        $chatModel = new MissionChatMessageModel();
        $proposal  = $chatModel->find($messageId);

        if (!$proposal || (int) $proposal['mission_id'] !== $missionId || $proposal['type'] !== 'proposal') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الاقتراح غير موجود أو تم الرد عليه مسبقًا.']);
            }
            return redirect()->back()->with('error', 'الاقتراح غير موجود أو تم الرد عليه مسبقًا.');
        }

        if ((int) $proposal['sender_id'] === $userId) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'لا يمكنك إلغاء اقتراحك الخاص.']);
            }
            return redirect()->back()->with('error', 'لا يمكنك إلغاء اقتراحك الخاص.');
        }

        $chatModel->update($messageId, ['type' => 'cancelled']);

        $chatModel->insert([
            'mission_id' => $missionId,
            'sender_id'  => $userId,
            'message'    => 'تم إلغاء هذا الموعد المقترح',
            'type'       => 'cancelled',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $detail = 'التاريخ: ' . $proposal['proposed_date'] . ' — الوقت: ' . $proposal['proposed_time'];
        (new AuditLogModel())->log($missionId, $userId, 'meeting_cancelled', 'meeting', null, $detail);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meeting-schedule?mission_id=' . $missionId));
    }

    /** POST /dashboard/meeting-schedule/api/cancel-confirmed — إلغاء موعد مؤكَّد فعليًا (بعد الاتفاق عليه من الطرفين) --
     *  يختلف عن cancel() اللي يلغي اقتراحًا لسا ما تأكّد؛ هذا يلغي اجتماعًا صار مُجدولًا فعليًا،
     *  وأي طرف بالمهمة يقدر يبدأه (ما يحتاج يكون الطرف الثاني تحديدًا زي إلغاء الاقتراح) */
    public function cancelConfirmed()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $userId    = (int) session()->get('user_id');
        if (!$isJson && $missionId) {
            $this->assertMissionAccess($missionId);
        }

        $meetingModel = new MeetingModel();
        $meeting = $meetingModel->firstForMission($missionId);

        if (!$meeting || $meeting['status'] !== 'scheduled') {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لا يوجد موعد مؤكَّد لإلغائه.']);
            }
            return redirect()->back()->with('error', 'لا يوجد موعد مؤكَّد لإلغائه.');
        }

        $meetingModel->update($meeting['id'], ['status' => 'cancelled']);

        $chatModel = new MissionChatMessageModel();
        $chatModel->insert([
            'mission_id' => $missionId,
            'sender_id'  => $userId,
            'message'    => 'تم إلغاء الموعد المؤكَّد',
            'type'       => 'cancelled',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $detail = 'التاريخ: ' . $meeting['meeting_date'] . ' — الوقت: ' . $meeting['meeting_time'];
        (new AuditLogModel())->log($missionId, $userId, 'meeting_cancelled', 'meeting', $meeting['id'], $detail);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/meeting-schedule?mission_id=' . $missionId));
    }
}
