<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactFieldsToMissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('missions', [
            'reviewer_name'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'coordinator_id'],
            'reviewer_email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'reviewer_name'],
            'reviewer_phone' => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'reviewer_email'],
            'director_name'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'reviewer_phone'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('missions', ['reviewer_name', 'reviewer_email', 'reviewer_phone', 'director_name']);
    }
}
