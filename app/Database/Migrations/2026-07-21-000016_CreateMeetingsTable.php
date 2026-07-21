<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMeetingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'meeting_code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 300],
            'meeting_date' => ['type' => 'DATE'],
            'meeting_time' => ['type' => 'TIME'],
            'location'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'meeting_type' => ['type' => 'ENUM', 'constraint' => ['in_person', 'online'], 'default' => 'in_person'],
            'minutes_text' => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['scheduled', 'held', 'cancelled'], 'default' => 'scheduled'],
            'created_by'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('meeting_code');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('meetings');
    }

    public function down()
    {
        $this->forge->dropTable('meetings');
    }
}
