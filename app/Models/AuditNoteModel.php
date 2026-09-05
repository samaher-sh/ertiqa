<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditNoteModel extends Model
{
    protected $table         = 'audit_notes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'ref_code', 'department_id', 'title', 'observation_date',
        'risk_severity', 'status', 'observation_text', 'standard_text',
        'reason_text', 'impact_text', 'recommendations_text', 'add_to_report',
        'reviewer_signature_user_id', 'team_head_signature_user_id',
        'reviewer_signed_at', 'team_head_signed_at', 'created_by',
    ];

    public function forMission(int $missionId): array
    {
        return $this->select('audit_notes.*, departments.name_ar as department_name')
            ->join('departments', 'departments.id = audit_notes.department_id', 'left')
            ->where('mission_id', $missionId)
            ->orderBy('audit_notes.created_at', 'DESC')
            ->findAll();
    }

    public function findWithDepartment(int $id): ?array
    {
        return $this->select('audit_notes.*, departments.name_ar as department_name')
            ->join('departments', 'departments.id = audit_notes.department_id', 'left')
            ->where('audit_notes.id', $id)
            ->first();
    }

    /**
     * تحديث دفعة وحدة لخيار "تضاف للتقرير" (0/1) لكل ملاحظة تابعة فعليًا لمهمة
     * معيّنة -- $flags = [id => 0|1]. يتجاهل أي id مو تابع لهذي المهمة (حماية
     * من التلاعب بمعرّفات ملاحظات مهام أخرى عبر التلاعب بالنموذج المُرسَل)
     */
    public function updateReportInclusion(int $missionId, array $flags): void
    {
        $ownIds = array_map('intval', array_column($this->where('mission_id', $missionId)->select('id')->findAll(), 'id'));
        foreach ($flags as $id => $flag) {
            $id = (int) $id;
            if (!in_array($id, $ownIds, true)) continue;
            $this->update($id, ['add_to_report' => (int) $flag]);
        }
    }

    public function generateRefCode(): string
    {
        $count = $this->countAllResults();
        $ref = 'OBS-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
        while ($this->where('ref_code', $ref)->first()) {
            $count++;
            $ref = 'OBS-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
        }
        return $ref;
    }
}
