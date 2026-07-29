<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    /** GET /dashboard/notifications/api/list */
    public function list()
    {
        $userId = (int) session()->get('user_id');
        return $this->response->setJSON([
            'success'       => true,
            'notifications' => (new NotificationModel())->forUser($userId),
        ]);
    }

    /** POST /dashboard/notifications/api/mark-read/{id} */
    public function markRead(int $id)
    {
        $userId = (int) session()->get('user_id');
        (new NotificationModel())->markRead($id, $userId);
        return $this->response->setJSON(['success' => true]);
    }
}
