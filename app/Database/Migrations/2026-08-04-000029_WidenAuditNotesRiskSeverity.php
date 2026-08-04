<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * risk_severity كانت ENUM('عالي','متوسط','منخفض') — الواجهة تحوّلت من أزرار
 * اختيار ثابتة لحقل نصي حر بعنوان "الحالة (الخطر)"، فلازم العمود يقبل أي نص
 * بدل القيم الثلاث الجامدة فقط.
 */
class WidenAuditNotesRiskSeverity extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('audit_notes', [
            'risk_severity' => ['name' => 'risk_severity', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('audit_notes', [
            'risk_severity' => ['name' => 'risk_severity', 'type' => 'ENUM', 'constraint' => ['عالي', 'متوسط', 'منخفض']],
        ]);
    }
}
