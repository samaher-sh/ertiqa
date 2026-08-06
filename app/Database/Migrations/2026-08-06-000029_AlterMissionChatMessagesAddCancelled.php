<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterMissionChatMessagesAddCancelled extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE mission_chat_messages MODIFY COLUMN type ENUM('text','proposal','confirmed','cancelled') NOT NULL DEFAULT 'text'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE mission_chat_messages MODIFY COLUMN type ENUM('text','proposal','confirmed') NOT NULL DEFAULT 'text'");
    }
}
