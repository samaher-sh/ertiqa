<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMissionStageHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'stage_number'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'entered_at'          => ['type' => 'DATETIME'],
            'exited_at'           => ['type' => 'DATETIME', 'null' => true],
            'responsible_user_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'sla_days_allowed'    => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'sla_due_date'        => ['type' => 'DATE', 'null' => true],
            'delay_status'        => ['type' => 'ENUM', 'constraint' => ['on_time', 'approaching', 'overdue'], 'default' => 'on_time'],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('responsible_user_id', 'users', 'id', false, 'SET NULL');
        $this->forge->createTable('mission_stage_history');
    }

    public function down()
    {
        $this->forge->dropTable('mission_stage_history');
    }
}
