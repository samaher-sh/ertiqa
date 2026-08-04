<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentResponseModel extends Model
{
    protected $table         = 'document_responses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['document_request_id', 'exists_flag', 'note', 'responded_by', 'responded_at'];
}
