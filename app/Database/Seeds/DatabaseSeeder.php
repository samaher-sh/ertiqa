<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // الترتيب مهم: roles و departments أول (ما فيهم اعتمادية على بعض)
        // بعدين permissions، وأخيرًا role_permissions اللي يعتمد على الاثنين
        $this->call('RoleSeeder');
        $this->call('DepartmentSeeder');
        $this->call('PermissionSeeder');
        $this->call('RolePermissionSeeder');
    }
}
