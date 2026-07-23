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
}
