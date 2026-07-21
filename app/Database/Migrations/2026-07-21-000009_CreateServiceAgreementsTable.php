<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceAgreementsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'submitted'], 'default' => 'pending'],
            'submitted_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'submitted_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('mission_id');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->addForeignKey('submitted_by', 'users', 'id', false, 'SET NULL');
        $this->forge->createTable('service_agreements');
    }

    public function down()
    {
        $this->forge->dropTable('service_agreements');
    }
}
