<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendMeetingsForSummaryPage extends Migration
{
    public function up()
    {
        // 'title' already exists on 'meetings' since CreateMeetingsTable — only 'objective' is new here.
        $this->forge->addColumn('meetings', [
            'objective' => ['type' => 'TEXT', 'null' => true, 'after' => 'title'],
        ]);

        $this->forge->addColumn('meeting_attendees', [
            'attendee_dept'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'external_name'],
            'attendee_position' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'attendee_dept'],
        ]);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'point_text'   => ['type' => 'TEXT'],
            'opinion'      => ['type' => 'TEXT', 'null' => true],
            'reason'       => ['type' => 'TEXT', 'null' => true],
            'sort_order'   => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', false, 'CASCADE');
        $this->forge->createTable('meeting_summary_points');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'statement'       => ['type' => 'VARCHAR', 'constraint' => 300],
            'signer_name'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'position'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'signature_data'  => ['type' => 'LONGTEXT', 'null' => true],
            'approval_date'   => ['type' => 'DATE', 'null' => true],
            'sort_order'      => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', false, 'CASCADE');
        $this->forge->createTable('meeting_approvals');
    }

    public function down()
    {
        $this->forge->dropTable('meeting_approvals');
        $this->forge->dropTable('meeting_summary_points');
        $this->forge->dropColumn('meeting_attendees', ['attendee_dept', 'attendee_position']);
        $this->forge->dropColumn('meetings', ['objective']);
    }
}
