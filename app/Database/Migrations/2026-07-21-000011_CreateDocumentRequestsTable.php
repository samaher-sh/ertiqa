<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'doc_name'   => ['type' => 'VARCHAR', 'constraint' => 300],
            'sort_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->createTable('document_requests');
    }

    public function down()
    {
        $this->forge->dropTable('document_requests');
    }
}
