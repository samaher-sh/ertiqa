<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_code'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 300],
            'year'                 => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'audit_department_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'target_department_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'mission_head_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'dept_director_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'coordinator_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'current_stage'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 1],
            'status'               => ['type' => 'ENUM', 'constraint' => ['active', 'on_hold', 'closed', 'cancelled'], 'default' => 'active'],
            'procedure_note'       => ['type' => 'TEXT', 'null' => true],
            'created_by'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('mission_code');
        $this->forge->addForeignKey('audit_department_id', 'departments', 'id', false, 'RESTRICT');
        $this->forge->addForeignKey('target_department_id', 'departments', 'id', false, 'RESTRICT');
        $this->forge->addForeignKey('mission_head_id', 'users', 'id', false, 'RESTRICT');
        $this->forge->addForeignKey('dept_director_id', 'users', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('coordinator_id', 'users', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('missions');
    }

    public function down()
    {
        $this->forge->dropTable('missions');
    }
}
