<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * تنظيف حقلَين بنموذج الملاحظات:
 * - add_to_report (تضاف للتقرير؟ نعم/لا) يُحذف كليًا -- ما له أي استخدام
 *   فعلي بمنطق بناء التقرير النهائي أصلًا، مجرّد حقل عرض بلا أثر.
 * - risk_severity يرجع لقائمة اختيار مقفلة بثلاث قيم فقط (عالي/متوسط/منخفض)
 *   بدل حقل نصي حر (كان اتوسّع بميغريشن WidenAuditNotesRiskSeverity) --
 *   الواجهة أصلًا (صفحة العرض/تصدير PDF) تلوّن القيمة بالاعتماد حصرًا على
 *   تطابقها مع إحدى هذي الثلاث، فحقل حر كان يكسر هذا التلوين لأي قيمة غيرها.
 */
class TidyObservationFormFields extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('audit_notes', 'add_to_report');

        $this->forge->modifyColumn('audit_notes', [
            'risk_severity' => ['name' => 'risk_severity', 'type' => 'ENUM', 'constraint' => ['عالي', 'متوسط', 'منخفض'], 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('audit_notes', [
            'risk_severity' => ['name' => 'risk_severity', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);

        $this->forge->addColumn('audit_notes', [
            'add_to_report' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'recommendations_text'],
        ]);
    }
}
