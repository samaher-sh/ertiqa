<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMissionChatMessagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'sender_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'message'     => ['type' => 'TEXT'],
            // نوع الرسالة: نص عادي، أو اقتراح موعد، أو تأكيد نهائي للموعد
            'type'        => ['type' => 'ENUM', 'constraint' => ['text', 'proposal', 'confirmed'], 'default' => 'text'],
            'proposed_date' => ['type' => 'DATE', 'null' => true],
            'proposed_time' => ['type' => 'TIME', 'null' => true],
            'proposed_location' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('mission_id');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('sender_id', 'users', 'id', false, 'CASCADE');
        $this->forge->createTable('mission_chat_messages');
    }

    public function down()
    {
        $this->forge->dropTable('mission_chat_messages');
    }
}
