<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ردّ الإدارة محل المراجعة (منسق/مدير الإدارة) على كل نقطة بملخص الاجتماع --
 * "الرأي" (موافق/متحفظ) + "السبب"، منفصل تمامًا عن حقل "الإفادة" اللي يكتبه
 * فريق المراجعة (statement).
 */
class AddHrResponseToMeetingSummaryPoints extends Migration
{
    public function up()
    {
        $this->forge->addColumn('meeting_summary_points', [
            'hr_opinion' => ['type' => 'ENUM', 'constraint' => ['agree', 'reserved'], 'null' => true, 'after' => 'statement'],
            'hr_reason'  => ['type' => 'TEXT', 'null' => true, 'after' => 'hr_opinion'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('meeting_summary_points', ['hr_opinion', 'hr_reason']);
    }
}
