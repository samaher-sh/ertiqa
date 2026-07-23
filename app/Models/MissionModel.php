<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionModel extends Model
{
    protected $table         = 'missions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_code', 'title', 'year', 'audit_department_id', 'target_department_id',
        'mission_head_id', 'dept_director_id', 'coordinator_id', 'current_stage',
        'status', 'procedure_note', 'created_by',
    ];

    /**
     * المهام "النشطة" (status=active) المرئية لمستخدم معيّن —
     * حاليًا: كل المهام النشطة اللي هو رئيسها أو أحد أعضاء فريقها.
     * (لاحقًا لو احتجنا صلاحيات أدق، نضيف شرط هنا فقط بدون ما نلمس أي مكان ثاني)
     */
    public function activeMissionsForUser(int $userId): array
    {
        return $this->select('missions.*, td.name_ar as target_department_name')
            ->join('departments td', 'td.id = missions.target_department_id')
            ->groupStart()
                ->where('missions.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = missions.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->where('missions.status', 'active')
            ->orderBy('missions.created_at', 'DESC')
            ->findAll();
    }

    public function countActiveForUser(int $userId): int
    {
        return count($this->activeMissionsForUser($userId));
    }

    public function countInStageForUser(int $userId, int $stage): int
    {
        return count(array_filter(
            $this->activeMissionsForUser($userId),
            fn($m) => (int) $m['current_stage'] === $stage
        ));
    }
}
