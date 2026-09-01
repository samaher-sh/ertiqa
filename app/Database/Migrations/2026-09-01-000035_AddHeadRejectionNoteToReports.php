<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeadRejectionNoteToReports extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reports', [
            'head_rejection_note' => ['type' => 'TEXT', 'null' => true, 'after' => 'head_approved_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reports', ['head_rejection_note']);
    }
}
