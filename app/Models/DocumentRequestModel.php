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

    /** قائمة المستندات المطلوبة لمهمة معيّنة مع حالة الرد عليها (إن وُجد) */
    public function forMissionWithResponses(int $missionId): array
    {
        return $this->select('document_requests.*, resp.exists_flag, resp.note as response_note, resp.responded_at')
            ->join('document_responses resp', 'resp.document_request_id = document_requests.id', 'left')
            ->where('document_requests.mission_id', $missionId)
            ->orderBy('document_requests.sort_order')
            ->findAll();
    }
}
