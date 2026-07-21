<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCorrectiveActionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'audit_note_id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'action_description'      => ['type' => 'TEXT'],
            'responsible_user_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'due_date'                => ['type' => 'DATE', 'null' => true],
            'status'                  => ['type' => 'ENUM', 'constraint' => ['pending', 'in_progress', 'completed', 'overdue'], 'default' => 'pending'],
            'completion_evidence_note'=> ['type' => 'TEXT', 'null' => true],
            'completed_at'            => ['type' => 'DATETIME', 'null' => true],
            'verified_by'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'verified_at'             => ['type' => 'DATETIME', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('audit_note_id', 'audit_notes', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('responsible_user_id', 'users', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('verified_by', 'users', 'id', false, 'SET NULL');
        $this->forge->createTable('corrective_actions');
    }

    public function down()
    {
        $this->forge->dropTable('corrective_actions');
    }
}
