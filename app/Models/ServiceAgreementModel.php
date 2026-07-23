<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceAgreementModel extends Model
{
    protected $table         = 'service_agreements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['mission_id', 'status', 'submitted_by', 'submitted_at'];
}
