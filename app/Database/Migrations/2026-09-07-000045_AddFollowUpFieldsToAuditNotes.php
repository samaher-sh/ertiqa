<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * حقول صفحة "التوصيات" الجديدة (متابعة تنفيذ توصيات الملاحظات المعتمدة
 * بالتقرير النهائي) -- نفس حقول "نموذج مرحلة متابعة تنفيذ التوصيات" الرسمي:
 * رد الجهة (تعبّئه الإدارة الخاضعة للمراجعة)، الحالة والمطلوب لاستيفاء
 * الملاحظة (يعبّيهما عضو المراجعة). لا علاقة له بحقول "بعد اعتماد الرئيس"
 * المضافة سابقًا (الربط بالمستهدفات/خطة التنفيذ) -- قسم مختلف تمامًا.
 */
class AddFollowUpFieldsToAuditNotes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('audit_notes', [
            'dept_reply'              => ['type' => 'TEXT', 'null' => true, 'after' => 'dept_response_plan'],
            'fulfillment_status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'dept_reply'],
            'fulfillment_requirement' => ['type' => 'TEXT', 'null' => true, 'after' => 'fulfillment_status'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('audit_notes', ['dept_reply', 'fulfillment_status', 'fulfillment_requirement']);
    }
}
