<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSignaturesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'signable_type'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'signable_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'sequence_order'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'signer_user_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'signed', 'rejected'], 'default' => 'pending'],
            'signed_at'        => ['type' => 'DATETIME', 'null' => true],
            'rejection_reason' => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['signable_type', 'signable_id', 'sequence_order']);
        $this->forge->addForeignKey('signer_user_id', 'users', 'id', false, 'RESTRICT');
        $this->forge->createTable('signatures');
    }

    public function down()
    {
        $this->forge->dropTable('signatures');
    }
}
