<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionChatMessageModel extends Model
{
    protected $table         = 'mission_chat_messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'mission_id', 'sender_id', 'message', 'type',
        'proposed_date', 'proposed_time', 'proposed_location', 'created_at',
    ];

    public function forMission(int $missionId): array
    {
        return $this->select('mission_chat_messages.*, users.full_name as sender_name, users.role_id as sender_role_id')
            ->join('users', 'users.id = mission_chat_messages.sender_id', 'left')
            ->where('mission_id', $missionId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
