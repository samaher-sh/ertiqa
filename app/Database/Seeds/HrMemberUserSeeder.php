<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HrMemberUserSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $role = $this->db->table('roles')->where('code', 'dept_coordinator')->get()->getRowArray();
        $dept = $this->db->table('departments')->where('name_ar', 'الموارد البشرية')->get()->getRowArray();

        if (!$role || !$dept) {
            echo "تنبيه: تأكدي إن جدول roles فيه دور بكود dept_coordinator (شغّلي RENAME_ROLE.sql\n";
            echo "أول لو ما سويتيها بعد)، وإن جدول departments فيه إدارة رئيسية اسمها 'الموارد البشرية'.\n";
            return;
        }

        $existing = $this->db->table('users')->where('national_id', '2222222222')->get()->getRowArray();
        if ($existing) {
            echo "المستخدم موجود أصلًا برقم الهوية 2222222222 — ما تم إنشاء صف جديد.\n";
            return;
        }

        $this->db->table('users')->insert([
            'national_id'    => '2222222222',
            'full_name'      => 'مستخدم تجريبي - عضو موارد بشرية',
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
        echo "اسم المستخدم (رقم الهوية): 2222222222\n";
        echo "كلمة المرور: Test@1234\n";
        echo "الدور: dept_coordinator (منسق الإدارة محل المراجعة)\n";
    }
}
