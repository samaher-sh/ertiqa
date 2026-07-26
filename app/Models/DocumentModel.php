<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table         = 'documents';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'mission_id', 'related_type', 'related_id',
        'file_name', 'file_path', 'file_size', 'mime_type',
        'uploaded_by', 'uploaded_at',
    ];

    public function forRelated(string $relatedType, int $relatedId): array
    {
        return $this->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->orderBy('uploaded_at', 'DESC')
            ->findAll();
    }
}
