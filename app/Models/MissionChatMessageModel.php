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

    /**
     * كل اقتراحات مواعيد الاجتماع اللي لسا بانتظار رد الطرف الثاني (type='proposal'،
     * ما تحوّلت لـ confirmed/cancelled بعد) ضمن مهام معيّنة — تُستخدم لإخطار الطرف
     * الآخر بالصفحة الرئيسية إن فيه موعد مقترح ينتظر ردّه. ترجع أيضًا
     * target_department_id للمهمة و department_id لمرسل الاقتراح، عشان المستدعي
     * يحدد الطرف الآخر (الإدارة الخاضعة مقابل فريق المراجعة) بدون استعلام إضافي
     */
    public function pendingProposalsForMissions(array $missionIds): array
    {
        if (empty($missionIds)) {
            return [];
        }
        return $this->select('mission_chat_messages.*, missions.mission_code, missions.target_department_id, users.department_id as sender_department_id')
            ->join('missions', 'missions.id = mission_chat_messages.mission_id')
            ->join('users', 'users.id = mission_chat_messages.sender_id', 'left')
            ->where('mission_chat_messages.type', 'proposal')
            ->whereIn('mission_chat_messages.mission_id', $missionIds)
            ->orderBy('mission_chat_messages.created_at', 'DESC')
            ->findAll();
    }
}
