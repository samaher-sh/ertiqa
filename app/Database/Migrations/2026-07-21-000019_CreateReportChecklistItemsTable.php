<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportChecklistItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'report_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'section_number' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'section_title'  => ['type' => 'VARCHAR', 'constraint' => 300],
            'item_text'      => ['type' => 'TEXT'],
            'is_checked'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'     => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('report_id', 'reports', 'id', false, 'CASCADE');
        $this->forge->createTable('report_checklist_items');
    }

    public function down()
    {
        $this->forge->dropTable('report_checklist_items');
    }
}
