<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "عمليات المراجعة السابقة" بخطوة تخطيط المهمة كانت خانة نصية وحدة تجمع (رقم
 * التقرير + تاريخ إصداره + نبذة عن الملاحظات) بنفس الحقل -- صار تاريخ الإصدار
 * خانة منفصلة (previous_audit_report_date)، وبقي previous_audits نصًا حرًّا
 * لرقم التقرير ونبذة الملاحظات معًا.
 */
class AddPreviousAuditReportDateToMissionPlannings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('mission_plannings', [
            'previous_audit_report_date' => ['type' => 'DATE', 'null' => true, 'after' => 'previous_audits'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('mission_plannings', ['previous_audit_report_date']);
    }
}
