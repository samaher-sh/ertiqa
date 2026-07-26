<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingApprovalModel extends Model
{
    protected $table         = 'meeting_approvals';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['meeting_id', 'statement', 'signer_name', 'position', 'signature_data', 'approval_date', 'sort_order'];

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
                'meeting_id'      => $meetingId,
                'statement'       => $r['statement'] ?? 'إعداد واعتماد',
                'signer_name'     => $r['name'] ?? '',
                'position'        => $r['position'] ?? '',
                'signature_data'  => $r['signature'] ?? null,
                'approval_date'   => !empty($r['date']) ? $r['date'] : null,
                'sort_order'      => $i + 1,
            ];
        }
        $this->insertBatch($insertRows);
    }
}
