<?php

namespace App\Models;

use CodeIgniter\Model;

class RiskMatrixItemModel extends Model
{
    protected $table         = 'risk_matrix_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'risk', 'risk_rating', 'controls', 'activity_type', 'sort_order',
    ];

    public function forMission(int $missionId): array
    {
        return $this->where('mission_id', $missionId)->orderBy('sort_order')->findAll();
    }

    /**
     * يستبدل كل صفوف المهمة دفعة وحدة (نفس سلوك زر "حفظ" بالواجهة الأصلية —
     * يرسل كل حالة الجدول مرة وحدة، مو صف بصف)
     */
    public function replaceForMission(int $missionId, array $rows): void
    {
        $this->where('mission_id', $missionId)->delete();

        if (empty($rows)) {
            return;
        }

        $insertRows = [];
        foreach ($rows as $i => $r) {
            $insertRows[] = [
                'mission_id'    => $missionId,
                'risk'          => $r['risk'] ?? '',
                'risk_rating'   => in_array($r['risk_rating'] ?? '', ['عالي', 'متوسط', 'منخفض'], true) ? $r['risk_rating'] : null,
                'controls'      => $r['controls'] ?? '',
                'activity_type' => $r['activity_type'] ?? '',
                'sort_order'    => $i + 1,
            ];
        }
        $this->insertBatch($insertRows);
    }
}
