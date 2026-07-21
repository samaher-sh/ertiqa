<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceAgreementResponsesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'service_agreement_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'section_title'         => ['type' => 'VARCHAR', 'constraint' => 300],
            'row_text'              => ['type' => 'TEXT'],
            'agree'                 => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'disagree'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'note'                  => ['type' => 'TEXT', 'null' => true],
            'sort_order'            => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('service_agreement_id', 'service_agreements', 'id', false, 'CASCADE');
        $this->forge->createTable('service_agreement_responses');
    }

    public function down()
    {
        $this->forge->dropTable('service_agreement_responses');
    }
}
