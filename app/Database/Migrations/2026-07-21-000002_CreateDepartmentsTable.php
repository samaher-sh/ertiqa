<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'name_ar'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'departments', 'id', false, 'SET NULL');
        $this->forge->createTable('departments');
    }

    public function down()
    {
        $this->forge->dropTable('departments');
    }
}
