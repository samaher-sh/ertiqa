<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'related_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'related_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'file_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_path'    => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_size'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'mime_type'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'uploaded_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'uploaded_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['related_type', 'related_id']);
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('documents');
    }

    public function down()
    {
        $this->forge->dropTable('documents');
    }
}
