<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditTeamMembersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'added_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['mission_id', 'user_id']);
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', false, 'CASCADE');
        $this->forge->createTable('audit_team_members');
    }

    public function down()
    {
        $this->forge->dropTable('audit_team_members');
    }
}
