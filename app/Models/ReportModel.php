<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table         = 'reports';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['mission_id', 'status', 'generated_at', 'pdf_document_id', 'created_by'];

    public function forUser(int $userId, array $filters = []): array
    {
        $builder = $this->select('reports.*, m.mission_code, m.year, m.audit_department_id, m.target_department_id, ad.name_ar as audit_dept_name, td.name_ar as target_dept_name')
            ->join('missions m', 'm.id = reports.mission_id')
            ->join('departments ad', 'ad.id = m.audit_department_id', 'left')
            ->join('departments td', 'td.id = m.target_department_id', 'left')
            ->groupStart()
                ->where('m.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = m.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd();

        if (!empty($filters['year']))        $builder->where('m.year', $filters['year']);
        if (!empty($filters['status']))      $builder->where('reports.status', $filters['status']);
        if (!empty($filters['dept_id']))     $builder->where('m.audit_department_id', $filters['dept_id']);
        if (!empty($filters['target_id']))   $builder->where('m.target_department_id', $filters['target_id']);

        return $builder->orderBy('reports.created_at', 'DESC')->findAll();
    }

    public function findOrCreateForMission(int $missionId, int $userId): array
    {
        $existing = $this->where('mission_id', $missionId)->first();
        if ($existing) return $existing;

        $id = $this->insert(['mission_id' => $missionId, 'status' => 'draft', 'created_by' => $userId], true);
        return $this->find($id);
    }
}
