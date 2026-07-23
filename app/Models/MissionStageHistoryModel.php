<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionStageHistoryModel extends Model
{
    protected $table         = 'mission_stage_history';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'mission_id', 'stage_number', 'entered_at', 'exited_at',
        'responsible_user_id', 'sla_days_allowed', 'sla_due_date',
        'delay_status', 'notes', 'created_at',
    ];

    public function openStage(int $missionId, int $stageNumber, ?int $responsibleUserId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $this->insert([
            'mission_id'           => $missionId,
            'stage_number'         => $stageNumber,
            'entered_at'           => $now,
            'responsible_user_id'  => $responsibleUserId,
            'delay_status'         => 'on_time',
            'created_at'           => $now,
        ]);
        return $this->insertID();
    }
}
