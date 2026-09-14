<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * اسم خطوة "ملخص الاجتماع" (رقم 5 بمراحل اعتماد التقرير) تغيّر إلى "محضر
 * الاجتماع" (ReportController::STEPS). النص القديم مخزَّن أصلًا بجدول
 * report_checklist_items وقت إنشاء كل تقرير (لقطة ثابتة لا تُعاد حسابها من
 * الثابت لاحقًا)، فالتعديل بالكود وحده لا يطال التقارير الموجودة أصلًا --
 * هذا تحديث بيانات (لا تعديل مخطط) لمزامنتها، بنفس نمط
 * RenameDocumentListStepLabel.
 */
class RenameMeetingSummaryStepLabel extends Migration
{
    public function up()
    {
        $this->db->table('report_checklist_items')
            ->where('section_number', 5)
            ->where('section_title', 'ملخص الاجتماع')
            ->update(['section_title' => 'محضر الاجتماع']);

        $this->db->table('report_checklist_items')
            ->where('section_number', 5)
            ->where('item_text', 'ملخص الاجتماع')
            ->update(['item_text' => 'محضر الاجتماع']);
    }

    public function down()
    {
        $this->db->table('report_checklist_items')
            ->where('section_number', 5)
            ->where('section_title', 'محضر الاجتماع')
            ->update(['section_title' => 'ملخص الاجتماع']);

        $this->db->table('report_checklist_items')
            ->where('section_number', 5)
            ->where('item_text', 'محضر الاجتماع')
            ->update(['item_text' => 'ملخص الاجتماع']);
    }
}
