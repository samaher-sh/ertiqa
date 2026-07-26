<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingAttendeeModel extends Model
{
    protected $table         = 'meeting_attendees';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['meeting_id', 'user_id', 'external_name', 'attendee_dept', 'attendee_position', 'attended'];

    public function forMeeting(int $meetingId): array
    {
        return $this->where('meeting_id', $meetingId)->orderBy('id')->findAll();
    }

    public function replaceForMeeting(int $meetingId, array $rows): void
    {
        $this->where('meeting_id', $meetingId)->delete();
        if (empty($rows)) return;

        $insertRows = [];
        foreach ($rows as $r) {
            $insertRows[] = [
                'meeting_id'        => $meetingId,
                'external_name'     => $r['name'] ?? '',
                'attendee_dept'     => $r['dept'] ?? '',
                'attendee_position' => $r['position'] ?? '',
                'attended'          => 1,
            ];
        }
        $this->insertBatch($insertRows);
    }
}
