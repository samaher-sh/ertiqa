<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'national_id'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'full_name'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'auth_source'    => ['type' => 'ENUM', 'constraint' => ['local', 'ldap'], 'default' => 'local'],
            'password_hash'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'role_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'department_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_login_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('national_id');
        $this->forge->addForeignKey('role_id', 'roles', 'id', false, 'RESTRICT');
        $this->forge->addForeignKey('department_id', 'departments', 'id', false, 'SET NULL');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
