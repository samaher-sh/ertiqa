<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceAgreementModel extends Model
{
    protected $table         = 'service_agreements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'status', 'submitted_by', 'submitted_at',
        'coordinator_name', 'coordinator_email', 'coordinator_phone',
        'channel_email', 'channel_email_value', 'channel_memo', 'channel_memo_value',
        'channel_phone', 'channel_phone_value',
    ];
}
