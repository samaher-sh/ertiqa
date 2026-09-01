<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table         = 'reports';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['mission_id', 'status', 'head_name', 'head_signature', 'head_approved_at', 'head_rejection_note', 'generated_at', 'pdf_document_id', 'created_by'];

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

    /** تقارير المهام الموجّهة فعليًا لإدارة مستخدم "الإدارة محل المراجعة" (منسق/مدير إدارة) */
    public function forTargetDepartment(int $departmentId): array
    {
        return $this->select('reports.*, m.mission_code, m.year, m.audit_department_id, m.target_department_id, ad.name_ar as audit_dept_name, td.name_ar as target_dept_name')
            ->join('missions m', 'm.id = reports.mission_id')
            ->join('departments ad', 'ad.id = m.audit_department_id', 'left')
            ->join('departments td', 'td.id = m.target_department_id', 'left')
            ->where('m.target_department_id', $departmentId)
            ->orderBy('reports.created_at', 'DESC')
            ->findAll();
    }

    /**
     * عدد التقارير حسب الحالة على مستوى إدارة المراجعة الداخلية كاملة
     * (يُستخدم لإحصائيات رئيس إدارة المراجعة الداخلية، اللي يشرف على كل التقارير مو بس تقاريره)
     */
    public function countForDepartmentByStatus(int $auditDepartmentId, string $status): int
    {
        return $this->join('missions m', 'm.id = reports.mission_id')
            ->where('m.audit_department_id', $auditDepartmentId)
            ->where('reports.status', $status)
            ->countAllResults();
    }

    /**
     * كل تقارير إدارة المراجعة الداخلية (بغض النظر عن فريق مهمّة بعينها) —
     * يُستخدم لرئيس إدارة المراجعة الداخلية اللي يشرف على كل التقارير مو بس
     * تقارير مهامه هو (نفس معيار countForDepartmentByStatus بالضبط)
     */
    public function forDepartment(int $auditDepartmentId): array
    {
        return $this->select('reports.*, m.mission_code, m.year, m.audit_department_id, m.target_department_id, ad.name_ar as audit_dept_name, td.name_ar as target_dept_name')
            ->join('missions m', 'm.id = reports.mission_id')
            ->join('departments ad', 'ad.id = m.audit_department_id', 'left')
            ->join('departments td', 'td.id = m.target_department_id', 'left')
            ->where('m.audit_department_id', $auditDepartmentId)
            ->orderBy('reports.created_at', 'DESC')
            ->findAll();
    }

    public function findOrCreateForMission(int $missionId, int $userId): array
    {
        $existing = $this->where('mission_id', $missionId)->first();
        if ($existing) return $existing;

        $id = $this->insert(['mission_id' => $missionId, 'status' => 'draft', 'created_by' => $userId], true);
        return $this->find($id);
    }
}
