<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ردّ الإدارة محل المراجعة (الرأي: موافق/متحفظ + السبب) كان لكل نقطة على
 * حِدة بجدول meeting_summary_points (PR #166) -- يُنقَل ليصير ردًّا واحدًا
 * شاملًا لكل نقاط "ملخص ما تم مناقشته" مجتمعة، فيصير عمودًا بجدول meetings
 * نفسه (اجتماع واحد لكل مهمة -- MeetingModel::firstForMission()) بدل جدول
 * النقاط.
 */
class MoveHrResponseToMeetingLevel extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('meeting_summary_points', ['hr_opinion', 'hr_reason']);

        $this->forge->addColumn('meetings', [
            'hr_opinion' => ['type' => 'ENUM', 'constraint' => ['agree', 'reserved'], 'null' => true, 'after' => 'minutes_text'],
            'hr_reason'  => ['type' => 'TEXT', 'null' => true, 'after' => 'hr_opinion'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('meetings', ['hr_opinion', 'hr_reason']);

        $this->forge->addColumn('meeting_summary_points', [
            'hr_opinion' => ['type' => 'ENUM', 'constraint' => ['agree', 'reserved'], 'null' => true, 'after' => 'statement'],
            'hr_reason'  => ['type' => 'TEXT', 'null' => true, 'after' => 'hr_opinion'],
        ]);
    }
}
