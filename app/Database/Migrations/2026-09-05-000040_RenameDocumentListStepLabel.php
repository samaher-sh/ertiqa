<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * اسم خطوة "قائمة المستندات" (رقم 3 بمراحل اعتماد التقرير) تغيّر إلى "قائمة
 * الطلبات" (ReportController::STEPS، وكل الأماكن الأخرى المرتبطة بها). النص
 * القديم مخزَّن أصلًا بجدول report_checklist_items وقت إنشاء كل تقرير (لقطة
 * ثابتة لا تُعاد حسابها من الثابت لاحقًا)، فالتعديل بالكود وحده لا يطال
 * التقارير الموجودة أصلًا -- هذا تحديث بيانات (لا تعديل مخطط) لمزامنتها.
 */
class RenameDocumentListStepLabel extends Migration
{
    public function up()
    {
        $this->db->table('report_checklist_items')
            ->where('section_number', 3)
            ->where('section_title', 'قائمة المستندات')
            ->update(['section_title' => 'قائمة الطلبات']);

        $this->db->table('report_checklist_items')
            ->where('section_number', 3)
            ->where('item_text', 'قائمة المستندات')
            ->update(['item_text' => 'قائمة الطلبات']);
    }

    public function down()
    {
        $this->db->table('report_checklist_items')
            ->where('section_number', 3)
            ->where('section_title', 'قائمة الطلبات')
            ->update(['section_title' => 'قائمة المستندات']);

        $this->db->table('report_checklist_items')
            ->where('section_number', 3)
            ->where('item_text', 'قائمة الطلبات')
            ->update(['item_text' => 'قائمة المستندات']);
    }
}
