<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * حقلا "الرأي" و"السبب / التوضيح" بجدول نقاط ملخص الاجتماع يُدمَجان بحقل واحد
 * "الإفادة" -- إعادة تسمية opinion إلى statement (تُستخدَم كخانة الإفادة الجديدة)،
 * وحذف عمود reason (ما عاد له استخدام بالواجهة).
 */
class MergeMeetingSummaryPointFields extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('meeting_summary_points', [
            'opinion' => ['name' => 'statement', 'type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->dropColumn('meeting_summary_points', 'reason');
    }

    public function down()
    {
        $this->forge->addColumn('meeting_summary_points', [
            'reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'statement'],
        ]);
        $this->forge->modifyColumn('meeting_summary_points', [
            'statement' => ['name' => 'opinion', 'type' => 'TEXT', 'null' => true],
        ]);
    }
}
