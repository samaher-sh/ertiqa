<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * meeting_date/meeting_time كانت NOT NULL بدون قيمة افتراضية منذ إنشاء الجدول،
 * لكن MeetingModel::findOrCreateForMission() صراحة يُنشئ صف اجتماع بحالة فارغة
 * (meeting_date => null) لحين ما يعبّيه المستخدم لاحقًا من صفحة ملخص الاجتماع —
 * وهذا كان يسبب خطأ "Column 'meeting_date' cannot be null" عند الحفظ.
 */
class MakeMeetingDateTimeNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('meetings', [
            'meeting_date' => ['name' => 'meeting_date', 'type' => 'DATE', 'null' => true],
            'meeting_time' => ['name' => 'meeting_time', 'type' => 'TIME', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('meetings', [
            'meeting_date' => ['name' => 'meeting_date', 'type' => 'DATE', 'null' => false],
            'meeting_time' => ['name' => 'meeting_time', 'type' => 'TIME', 'null' => false],
        ]);
    }
}
