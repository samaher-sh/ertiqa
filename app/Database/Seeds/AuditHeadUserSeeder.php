<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuditHeadUserSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $role = $this->db->table('roles')->where('code', 'audit_head')->get()->getRowArray();
        $dept = $this->db->table('departments')->where('name_ar', 'المراجعة الداخلية')->get()->getRowArray();

        if (!$role || !$dept) {
            echo "تنبيه: تأكدي إن RoleSeeder و DepartmentSeeder اشتغلوا قبل هذا الـ Seeder.\n";
            return;
        }

        // تحقق إنه مو موجود أصلًا (يمنع تكرار لو شغّلتِ الأمر أكثر من مرة)
        $existing = $this->db->table('users')->where('national_id', '0111111111')->get()->getRowArray();
        if ($existing) {
            echo "المستخدم موجود أصلًا برقم الهوية 0111111111 — ما تم إنشاء صف جديد.\n";
            return;
        }

        $this->db->table('users')->insert([
            'national_id'    => '0111111111',
            'full_name'      => 'مستخدم تجريبي - رئيس مراجعة',
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

        echo "تم إنشاء المستخدم:\n";
        echo "اسم المستخدم (رقم الهوية): 0111111111\n";
        echo "كلمة المرور: Test@1234\n";
        echo "الدور: رئيس إدارة المراجعة الداخلية\n";
    }
}
