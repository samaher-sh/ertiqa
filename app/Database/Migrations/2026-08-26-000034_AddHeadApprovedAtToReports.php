<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeadApprovedAtToReports extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reports', [
            'head_approved_at' => ['type' => 'DATE', 'null' => true, 'after' => 'head_signature'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reports', ['head_approved_at']);
    }
}
