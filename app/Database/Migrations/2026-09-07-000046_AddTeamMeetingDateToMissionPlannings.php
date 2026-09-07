<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * تاريخ الاجتماع الداخلي لفريق العمل بقسم "الاعتماد" (خطوة 3 بمعالج "بدء
 * مهمة" وصفحة "تخطيط المهمة") -- كان نص ثابت "(..................)" يُملأ
 * يدويًا، صار الآن حقل تاريخ حقيقي.
 */
class AddTeamMeetingDateToMissionPlannings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('mission_plannings', [
            'team_meeting_date' => ['type' => 'DATE', 'null' => true, 'after' => 'approved_by_title'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('mission_plannings', 'team_meeting_date');
    }
}
