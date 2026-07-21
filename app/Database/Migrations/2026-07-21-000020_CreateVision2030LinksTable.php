<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVision2030LinksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'audit_note_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'vision_pillar'  => ['type' => 'VARCHAR', 'constraint' => 300],
            'objective_text' => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('audit_note_id', 'audit_notes', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('vision2030_links');
    }

    public function down()
    {
        $this->forge->dropTable('vision2030_links');
    }
}
