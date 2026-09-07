<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * حقول تُعبَّأ بعد اعتماد رئيس إدارة المراجعة الداخلية للتقرير النهائي --
 * تظهر بقسم جديد أسفل جدول الملاحظات بصفحة "الملاحظات" (نفس المهمة المعروضة)،
 * فقط للملاحظات المضمَّنة بالتقرير (add_to_report) بعد اعتماد رئيس المراجعة
 * (reports.head_approved_at). كلها نصية اختيارية.
 */
class AddFinalReportFieldsToAuditNotes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('audit_notes', [
            'kamc_targets_link'        => ['type' => 'TEXT', 'null' => true, 'after' => 'add_to_report'],
            'health_transformation_targets_link' => ['type' => 'TEXT', 'null' => true, 'after' => 'kamc_targets_link'],
            'dept_response_plan'       => ['type' => 'TEXT', 'null' => true, 'after' => 'health_transformation_targets_link'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('audit_notes', ['kamc_targets_link', 'health_transformation_targets_link', 'dept_response_plan']);
    }
}
