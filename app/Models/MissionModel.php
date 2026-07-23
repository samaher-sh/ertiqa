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
        'mission_head_id', 'dept_director_id', 'coordinator_id',
        'reviewer_name', 'reviewer_email', 'reviewer_phone', 'director_name',
        'current_stage', 'status', 'procedure_note', 'created_by',
    ];

    /**
     * المهام "النشطة" (status=active) المرئية لمستخدم معيّن
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

    /**
     * يولّد كود مهمة فريد بصيغة AUD-{السنة}-{رقم تسلسلي 3 خانات}
     * مثال: AUD-2026-001, AUD-2026-002 ...
     */
    public function generateMissionCode(string $year): string
    {
        $count = $this->where('year', $year)->countAllResults();
        $seq   = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
        $code  = "AUD-{$year}-{$seq}";

        // احتياط بسيط لو صار تعارض نادر (طلبين بنفس الثانية) — نزيد الرقم لين يصير فريد
        while ($this->where('mission_code', $code)->first()) {
            $seq  = str_pad((string) ((int) $seq + 1), 3, '0', STR_PAD_LEFT);
            $code = "AUD-{$year}-{$seq}";
        }

        return $code;
    }
}
