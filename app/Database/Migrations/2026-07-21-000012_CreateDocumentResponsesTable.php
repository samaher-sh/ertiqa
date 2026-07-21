<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentResponsesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'document_request_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'exists_flag'          => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'note'                 => ['type' => 'TEXT', 'null' => true],
            'responded_by'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'responded_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('document_request_id', 'document_requests', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('responded_by', 'users', 'id', false, 'SET NULL');
        $this->forge->createTable('document_responses');
    }

    public function down()
    {
        $this->forge->dropTable('document_responses');
    }
}
