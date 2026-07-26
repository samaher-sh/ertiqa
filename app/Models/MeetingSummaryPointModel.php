<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingSummaryPointModel extends Model
{
    protected $table         = 'meeting_summary_points';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['meeting_id', 'point_text', 'opinion', 'reason', 'sort_order'];

    public function forMeeting(int $meetingId): array
    {
        return $this->where('meeting_id', $meetingId)->orderBy('sort_order')->findAll();
    }

    public function replaceForMeeting(int $meetingId, array $rows): void
    {
        $this->where('meeting_id', $meetingId)->delete();
        if (empty($rows)) return;

        $insertRows = [];
        foreach ($rows as $i => $r) {
            $insertRows[] = [
                'meeting_id' => $meetingId,
                'point_text' => $r['text'] ?? '',
                'opinion'    => $r['opinion'] ?? null,
                'reason'     => $r['reason'] ?? null,
                'sort_order' => $i + 1,
            ];
        }
        $this->insertBatch($insertRows);
    }
}
