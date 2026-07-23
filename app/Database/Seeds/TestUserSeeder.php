<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // نجيب id دور "عضو إدارة المراجعة الداخلية" وid قسم "المراجعة الداخلية"
        $role = $this->db->table('roles')->where('code', 'audit_member')->get()->getRowArray();
        $dept = $this->db->table('departments')->where('name_ar', 'المراجعة الداخلية')->get()->getRowArray();

        if (!$role || !$dept) {
            echo "تنبيه: تأكدي إن RoleSeeder و DepartmentSeeder اشتغلوا قبل هذا الـ Seeder.\n";
            return;
        }

        $this->db->table('users')->insert([
            'national_id'    => '1111111111',
            'full_name'      => 'مستخدم تجريبي - عضو مراجعة',
            'email'          => null,
            'phone'          => null,
            'auth_source'    => 'local',
            'password_hash'  => password_hash('Test@1234', PASSWORD_DEFAULT),
            'role_id'        => $role['id'],
            'department_id'  => $dept['id'],
            'is_active'      => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        echo "تم إنشاء مستخدم تجريبي:\n";
        echo "اسم المستخدم (رقم الهوية): 1111111111\n";
        echo "كلمة المرور: Test@1234\n";
    }
}
