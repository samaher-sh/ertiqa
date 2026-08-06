<?php

namespace App\Controllers;

use App\Models\MissionChatMessageModel;
use App\Models\MeetingModel;

class MissionChatController extends BaseController
{
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
        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $message   = trim($data['message'] ?? '');

        if (!$missionId || $message === '') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الرسالة فارغة.']);
        }

        $chatModel = new MissionChatMessageModel();
        $chatModel->insert([
            'mission_id' => $missionId,
            'sender_id'  => (int) session()->get('user_id'),
            'message'    => $message,
            'type'       => 'text',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/meeting-schedule/api/propose — اقتراح موعد اجتماع */
    public function propose()
    {
        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $date = $data['date'] ?? null;
        $time = $data['time'] ?? null;
        $location = trim($data['location'] ?? '');

        if (!$missionId || !$date || !$time) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى تحديد التاريخ والوقت.']);
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

        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/meeting-schedule/api/confirm — تأكيد موعد مُقترح مسبقًا (بالطرف التاني) */
    public function confirm()
    {
        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $messageId = (int) ($data['message_id'] ?? 0);
        $userId    = (int) session()->get('user_id');

        $chatModel = new MissionChatMessageModel();
        $proposal  = $chatModel->find($messageId);

        if (!$proposal || (int) $proposal['mission_id'] !== $missionId || $proposal['type'] !== 'proposal') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الاقتراح غير موجود.']);
        }

        // لا يجوز إن نفس الشخص اللي اقترح يأكد اقتراحه هو (لازم الطرف الثاني)
        if ((int) $proposal['sender_id'] === $userId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'لا يمكنك تأكيد اقتراحك الخاص — بانتظار تأكيد الطرف الآخر.']);
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

        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/meeting-schedule/api/cancel — إلغاء اقتراح موعد لم يُؤكَّد بعد (بالطرف الثاني) */
    public function cancel()
    {
        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $messageId = (int) ($data['message_id'] ?? 0);
        $userId    = (int) session()->get('user_id');

        $chatModel = new MissionChatMessageModel();
        $proposal  = $chatModel->find($messageId);

        if (!$proposal || (int) $proposal['mission_id'] !== $missionId || $proposal['type'] !== 'proposal') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'الاقتراح غير موجود أو تم الرد عليه مسبقًا.']);
        }

        if ((int) $proposal['sender_id'] === $userId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'لا يمكنك إلغاء اقتراحك الخاص.']);
        }

        $chatModel->update($messageId, ['type' => 'cancelled']);

        $chatModel->insert([
            'mission_id' => $missionId,
            'sender_id'  => $userId,
            'message'    => 'تم إلغاء هذا الموعد المقترح',
            'type'       => 'cancelled',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true]);
    }
}
