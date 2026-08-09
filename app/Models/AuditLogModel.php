<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'mission_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'created_at',
    ];

    /** يعرّبها senttasks.js إلى سجل زمني حقيقي لكل الأحداث المهمة بالمهمة */
    private const ACTION_LABELS = [
        'mission_created'        => 'تم إنشاء المهمة وإرسال طلب المراجعة الداخلية',
        'sla_submitted'          => 'تم تعبئة اتفاقية مستوى الخدمة',
        'documents_submitted'    => 'تم رفع المستندات المطلوبة',
        'risk_matrix_saved'      => 'تم حفظ مصفوفة المخاطر',
        'chat_message'           => 'رسالة جديدة بشات جدولة الاجتماع',
        'meeting_proposed'       => 'تم اقتراح موعد اجتماع',
        'meeting_confirmed'      => 'تم تأكيد موعد الاجتماع',
        'meeting_cancelled'      => 'تم إلغاء الموعد المقترح',
        'meeting_summary_saved'  => 'تم حفظ ملخص الاجتماع',
        'observation_added'      => 'تمت إضافة ملاحظة',
        'report_finalized'       => 'تم اعتماد التقرير النهائي',
    ];

    /** $detail نص مختصر يوصف الحدث فعليًا (اسم المستند، التاريخ/الوقت المقترح، ...) —
     *  يُخزَّن بعمود new_values الموجود أصلًا (بدون الحاجة لعمود جديد) */
    public function log(int $missionId, ?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $detail = null): int
    {
        return $this->insert([
            'mission_id'   => $missionId,
            'user_id'      => $userId,
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'new_values'   => $detail,
            'created_at'   => date('Y-m-d H:i:s'),
        ], true);
    }

    /** السجل الزمني الكامل لمهمة، بترتيب حدوثه فعليًا */
    public function forMission(int $missionId): array
    {
        $rows = $this->select('audit_logs.*, users.full_name as user_name')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->where('mission_id', $missionId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return array_map(fn($r) => [
            'action'     => $r['action'],
            'stage_name' => self::ACTION_LABELS[$r['action']] ?? $r['action'],
            'user_name'  => $r['user_name'] ?? '—',
            'entered_at' => $r['created_at'],
            'detail'     => $r['new_values'] ?? null,
        ], $rows);
    }
}
