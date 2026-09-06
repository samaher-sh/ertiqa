<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionPlanningModel extends Model
{
    protected $table         = 'mission_plannings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'project_start_date', 'plan_execution_days', 'proposed_execution_days',
        'target_dept_manager_name', 'participating_reviewers', 'mission_brief', 'previous_audits',
        'previous_audit_report_date', 'regulatory_notes', 'audit_objectives', 'scope_included',
        'scope_excluded', 'sub_procedures', 'prepared_by_name', 'prepared_by_title',
        'approved_by_name', 'approved_by_title',
    ];

    public function forMission(int $missionId): ?array
    {
        return $this->where('mission_id', $missionId)->first();
    }
}
