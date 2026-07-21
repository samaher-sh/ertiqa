<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft', 'pending_signatures', 'sent'], 'default' => 'draft'],
            'generated_at'      => ['type' => 'DATETIME', 'null' => true],
            'pdf_document_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('mission_id');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('pdf_document_id', 'documents', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('reports');
    }

    public function down()
    {
        $this->forge->dropTable('reports');
    }
}
