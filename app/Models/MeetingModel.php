<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingModel extends Model
{
    protected $table         = 'meetings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'meeting_code', 'title', 'meeting_date', 'meeting_time',
        'location', 'meeting_type', 'minutes_text', 'status', 'created_by',
    ];

    /**
     * الاجتماعات المجدولة (status=scheduled) لمهام مستخدم معيّن، مرتبة بالأقرب أولًا
     */
    public function scheduledMeetingsForUser(int $userId): array
    {
        return $this->select('meetings.*')
            ->join('missions', 'missions.id = meetings.mission_id')
            ->groupStart()
                ->where('missions.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = missions.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->where('meetings.status', 'scheduled')
            ->orderBy('meetings.meeting_date', 'ASC')
            ->orderBy('meetings.meeting_time', 'ASC')
            ->findAll();
    }

    public function countScheduledForUser(int $userId): int
    {
        return count($this->scheduledMeetingsForUser($userId));
    }
}
