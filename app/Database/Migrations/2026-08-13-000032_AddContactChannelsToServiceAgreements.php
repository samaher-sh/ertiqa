<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactChannelsToServiceAgreements extends Migration
{
    public function up()
    {
        $this->forge->addColumn('service_agreements', [
            'channel_email'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'coordinator_phone'],
            'channel_email_value'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'channel_email'],
            'channel_memo'         => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'channel_email_value'],
            'channel_memo_value'   => ['type' => 'TEXT', 'null' => true, 'after' => 'channel_memo'],
            'channel_phone'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'channel_memo_value'],
            'channel_phone_value'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'channel_phone'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('service_agreements', [
            'channel_email', 'channel_email_value', 'channel_memo', 'channel_memo_value', 'channel_phone', 'channel_phone_value',
        ]);
    }
}
