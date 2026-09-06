<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionPlanningMilestoneModel extends Model
{
    protected $table         = 'mission_planning_milestones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['mission_planning_id', 'row_label', 'milestone_date', 'days_required', 'note', 'sort_order'];

    /** أول 4 صفوف بعنوان ثابت (نفس نص نموذج "مذكرة تخطيط المهمة" الرسمي حرفيًا)، الصف
     *  الخامس فاضٍ بالكامل (عنوان حر يُكتب لاحقًا لو احتاج فريق المراجعة نقطة إضافية) */
    public const DEFAULT_ROW_LABELS = [
        'إرسال مذكرة المهمة ( التبليغ )',
        'اكمال مرحلة التخطيط',
        'العمل الميداني',
        'كتابة التقرير',
        '',
    ];

    public function forPlanning(int $missionPlanningId): array
    {
        return $this->where('mission_planning_id', $missionPlanningId)->orderBy('sort_order')->findAll();
    }

    public function seedDefaults(int $missionPlanningId): void
    {
        $rows = [];
        foreach (self::DEFAULT_ROW_LABELS as $i => $label) {
            $rows[] = [
                'mission_planning_id' => $missionPlanningId,
                'row_label'           => $label,
                'sort_order'          => $i + 1,
            ];
        }
        $this->insertBatch($rows);
    }

    public function replaceForPlanning(int $missionPlanningId, array $rows): void
    {
        $this->where('mission_planning_id', $missionPlanningId)->delete();
        if (empty($rows)) {
            $this->seedDefaults($missionPlanningId);
            return;
        }

        $insertRows = [];
        foreach ($rows as $i => $r) {
            $insertRows[] = [
                'mission_planning_id' => $missionPlanningId,
                'row_label'           => $r['label'] ?? (self::DEFAULT_ROW_LABELS[$i] ?? ''),
                'milestone_date'      => ($r['date'] ?? null) ?: null,
                'days_required'       => $r['days'] ?? null,
                'note'                => $r['note'] ?? null,
                'sort_order'          => $i + 1,
            ];
        }
        $this->insertBatch($insertRows);
    }
}
