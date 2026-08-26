<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeadSignatureToReports extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reports', [
            'head_name'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'status'],
            'head_signature' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'head_name'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reports', ['head_name', 'head_signature']);
    }
}
