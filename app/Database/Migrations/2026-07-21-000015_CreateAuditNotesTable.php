<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditNotesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'ref_code'                   => ['type' => 'VARCHAR', 'constraint' => 30],
            'department_id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'title'                      => ['type' => 'VARCHAR', 'constraint' => 300],
            'observation_date'           => ['type' => 'DATE'],
            'risk_severity'              => ['type' => 'ENUM', 'constraint' => ['عالي', 'متوسط', 'منخفض']],
            'status'                     => ['type' => 'ENUM', 'constraint' => ['بانتظار الرد', 'قيد المعالجة', 'مغلقة'], 'default' => 'بانتظار الرد'],
            'observation_text'           => ['type' => 'TEXT', 'null' => true],
            'standard_text'              => ['type' => 'TEXT', 'null' => true],
            'reason_text'                => ['type' => 'TEXT', 'null' => true],
            'impact_text'                => ['type' => 'TEXT', 'null' => true],
            'recommendations_text'       => ['type' => 'TEXT', 'null' => true],
            'add_to_report'              => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'reviewer_signature_user_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'team_head_signature_user_id'=> ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'reviewer_signed_at'         => ['type' => 'DATETIME', 'null' => true],
            'team_head_signed_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_by'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'                 => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                 => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('ref_code');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('reviewer_signature_user_id', 'users', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('team_head_signature_user_id', 'users', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('audit_notes');
    }

    public function down()
    {
        $this->forge->dropTable('audit_notes');
    }
}
