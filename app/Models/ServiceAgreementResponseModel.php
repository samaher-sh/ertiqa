<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceAgreementResponseModel extends Model
{
    protected $table         = 'service_agreement_responses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'service_agreement_id', 'section_title', 'row_text',
        'agree', 'disagree', 'note', 'sort_order',
    ];

    /** بنود اتفاقية مستوى الخدمة لمهمة معيّنة (عبر ربط service_agreements.mission_id) */
    public function forMission(int $missionId): array
    {
        return $this->select('service_agreement_responses.*')
            ->join('service_agreements sa', 'sa.id = service_agreement_responses.service_agreement_id')
            ->where('sa.mission_id', $missionId)
            ->orderBy('service_agreement_responses.sort_order')
            ->findAll();
    }
}
