<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * خطوة "تخطيط المهمة" (خطوة 3 بمعالج "بدء مهمة"، بعد "طلب المراجعة الداخلية"
 * و"اتفاقية مستوى الخدمة") -- نفس حقول نموذج "مذكرة تخطيط المهمة" الرسمي بالضبط.
 * لا علاقة له بخطوات التقرير النهائي (ReportController::STEPS) إطلاقًا.
 */
class CreateMissionPlanningTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'project_start_date'       => ['type' => 'DATE', 'null' => true],
            'plan_execution_days'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'proposed_execution_days'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'target_dept_manager_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'participating_reviewers' => ['type' => 'TEXT', 'null' => true],
            'mission_brief'           => ['type' => 'TEXT', 'null' => true],
            'previous_audits'         => ['type' => 'TEXT', 'null' => true],
            'regulatory_notes'        => ['type' => 'TEXT', 'null' => true],
            'audit_objectives'        => ['type' => 'TEXT', 'null' => true],
            'scope_included'          => ['type' => 'TEXT', 'null' => true],
            'scope_excluded'          => ['type' => 'TEXT', 'null' => true],
            'sub_procedures'          => ['type' => 'TEXT', 'null' => true],
            'prepared_by_name'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'prepared_by_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'approved_by_name'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'approved_by_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('mission_id');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', false, 'CASCADE');
        $this->forge->createTable('mission_plannings');

        // جدول "النقاط الهامة في المراجعة" -- 5 صفوف بالضبط لكل مهمة، أول 4 صفوف
        // عناوينها ثابتة (نفس نص النموذج الأصلي حرفيًا)، الصف الخامس فاضٍ بالكامل
        // (عنوان حر يُكتب لاحقًا لو احتاج فريق المراجعة نقطة إضافية)
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'mission_planning_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'row_label'             => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'milestone_date'        => ['type' => 'DATE', 'null' => true],
            'days_required'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'note'                  => ['type' => 'TEXT', 'null' => true],
            'sort_order'            => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('mission_planning_id', 'mission_plannings', 'id', false, 'CASCADE');
        $this->forge->createTable('mission_planning_milestones');
    }

    public function down()
    {
        $this->forge->dropTable('mission_planning_milestones');
        $this->forge->dropTable('mission_plannings');
    }
}
