<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentRequestModel extends Model
{
    protected $table         = 'document_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['mission_id', 'doc_name', 'sort_order', 'created_at'];
}
