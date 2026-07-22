<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $map = [
            'audit_head' => [
                'missions.view', 'missions.create', 'missions.edit',
                'sla.view', 'sla.create', 'sla.respond',
                'documents.view', 'documents.request', 'documents.respond',
                'risk_matrix.view', 'risk_matrix.edit',
                'meetings.view', 'meetings.create', 'meetings.edit',
                'audit_notes.view', 'audit_notes.create', 'audit_notes.edit', 'audit_notes.sign', 'audit_notes.approve',
                'reports.view', 'reports.edit_checklist', 'reports.send',
                'signatures.sign',
                'corrective_actions.view', 'corrective_actions.edit', 'corrective_actions.verify',
                'admin.manage_users', 'admin.manage_permissions',
            ],
            'audit_member' => [
                'missions.view',
                'sla.view', 'sla.create',
                'documents.view', 'documents.request',
                'risk_matrix.view', 'risk_matrix.edit',
                'meetings.view', 'meetings.create', 'meetings.edit',
                'audit_notes.view', 'audit_notes.create', 'audit_notes.edit', 'audit_notes.sign',
                'reports.view', 'reports.edit_checklist',
                'signatures.sign',
                'corrective_actions.view',
            ],
            'hr_coordinator' => [
                'missions.view',
                'sla.view', 'sla.respond',
                'documents.view', 'documents.respond',
                'risk_matrix.view',
                'meetings.view',
                'audit_notes.view',
                'reports.view',
                'corrective_actions.view',
            ],
            'dept_manager' => [
                'missions.view',
                'sla.view',
                'documents.view',
                'risk_matrix.view',
                'meetings.view',
                'audit_notes.view', 'audit_notes.sign',
                'reports.view',
                'signatures.sign',
                'corrective_actions.view', 'corrective_actions.edit',
            ],
            'specialized_manager' => [
                'missions.view',
                'audit_notes.view',
                'corrective_actions.view', 'corrective_actions.edit',
            ],
            'top_management' => [
                'missions.view',
                'audit_notes.view',
                'reports.view',
                'corrective_actions.view',
            ],
        ];

        $roles        = $this->db->table('roles')->select('id, code')->get()->getResultArray();
        $roleIdByCode = array_column($roles, 'id', 'code');

        $perms        = $this->db->table('permissions')->select('id, code')->get()->getResultArray();
        $permIdByCode = array_column($perms, 'id', 'code');

        $rows = [];
        foreach ($map as $roleCode => $permCodes) {
            if (!isset($roleIdByCode[$roleCode])) {
                continue;
            }
            foreach ($permCodes as $permCode) {
                if (!isset($permIdByCode[$permCode])) {
                    continue;
                }
                $rows[] = [
                    'role_id'       => $roleIdByCode[$roleCode],
                    'permission_id' => $permIdByCode[$permCode],
                ];
            }
        }

        if (!empty($rows)) {
            $this->db->table('role_permissions')->insertBatch($rows);
        }
    }
}
