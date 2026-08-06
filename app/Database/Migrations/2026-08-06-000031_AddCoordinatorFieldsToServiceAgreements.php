<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCoordinatorFieldsToServiceAgreements extends Migration
{
    public function up()
    {
        $this->forge->addColumn('service_agreements', [
            'coordinator_name'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'mission_id'],
            'coordinator_email' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'coordinator_name'],
            'coordinator_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'coordinator_email'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('service_agreements', ['coordinator_name', 'coordinator_email', 'coordinator_phone']);
    }
}
