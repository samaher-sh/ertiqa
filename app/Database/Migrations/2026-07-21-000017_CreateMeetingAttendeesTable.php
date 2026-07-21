<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMeetingAttendeesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'external_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'attended'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['meeting_id', 'user_id']);
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', false, 'CASCADE');
        $this->forge->createTable('meeting_attendees');
    }

    public function down()
    {
        $this->forge->dropTable('meeting_attendees');
    }
}
