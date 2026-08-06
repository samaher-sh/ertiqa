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

    /** يحدّث الرد الموجود لهذا الطلب لو موجود، وإلا يضيف رد جديد — يمنع تكرار الصفوف عند إعادة الإرسال */
    public function upsertForRequest(int $requestId, array $data): int
    {
        $existing = $this->where('document_request_id', $requestId)->first();
        if ($existing) {
            $this->update($existing['id'], $data);
            return (int) $existing['id'];
        }

        $data['document_request_id'] = $requestId;
        return (int) $this->insert($data, true);
    }
}
