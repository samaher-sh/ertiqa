<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $roles = [
            ['code' => 'audit_head',          'name_ar' => 'رئيس إدارة المراجعة الداخلية',      'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'audit_member',        'name_ar' => 'عضو إدارة المراجعة الداخلية',        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hr_coordinator',      'name_ar' => 'المنسق المعتمد / ممثل الإدارة',      'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'dept_manager',        'name_ar' => 'مدير الإدارة محل المراجعة',          'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'specialized_manager', 'name_ar' => 'مدير الإدارة المختصة',               'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'top_management',      'name_ar' => 'الرئيس التنفيذي / الإدارة العليا',    'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->db->table('roles')->insertBatch($roles);
    }
}
