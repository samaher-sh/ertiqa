<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiskMatrixItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'risk'         => ['type' => 'TEXT'],
            'risk_rating'  => ['type' => 'ENUM', 'constraint' => ['عالي', 'متوسط', 'منخفض'], 'null' => true],
            'controls'     => ['type' => 'TEXT', 'null' => true],
            'activity_type'=> ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'sort_order'   => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->createTable('risk_matrix_items');
    }

    public function down()
    {
        $this->forge->dropTable('risk_matrix_items');
    }
}
