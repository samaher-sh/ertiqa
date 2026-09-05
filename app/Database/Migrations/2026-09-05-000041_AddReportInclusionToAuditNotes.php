<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * add_to_report كان اتحذف بالكامل (TidyObservationFormFields) لأنه ما كان
 * له أي أثر فعلي. يرجع الآن بأثر حقيقي: رئيس إدارة المراجعة الداخلية يقدر
 * يختار -- أثناء مراجعة التقرير النهائي -- إذا كل ملاحظة تُضمَّن بمستند
 * PDF المصدَّر أو لا (PdfController::finalReport). القيمة الافتراضية NULL
 * تُعامَل كـ"مضمَّنة" (نفس معاملة 1)؛ فقط 0 الصريحة تستثني الملاحظة.
 */
class AddReportInclusionToAuditNotes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('audit_notes', [
            'add_to_report' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'recommendations_text'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('audit_notes', 'add_to_report');
    }
}
