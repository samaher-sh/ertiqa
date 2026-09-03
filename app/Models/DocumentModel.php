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

    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    private const MAX_SIZE_KB = 10240; // 10 ميجا

    public function forRelated(string $relatedType, int $relatedId): array
    {
        return $this->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->orderBy('uploaded_at', 'DESC')
            ->findAll();
    }

    /**
     * يتحقق من ملف مرفوع (امتداد + حجم)، ينقله لمجلد uploads/<relatedType>s/<relatedId>،
     * ويسجّله بجدول documents -- منطق مشترك يستخدمه DocumentController::uploadObservationAttachment()
     * (رفع فوري بـ AJAX من صفحتَي عرض/تعديل ملاحظة) وObservationController::save()
     * (رفع مجمّع مع نموذج الإضافة نفسه، قبل ما تكون الملاحظة محفوظة أصلًا)
     */
    public function saveUploadedFile(\CodeIgniter\HTTP\Files\UploadedFile $file, string $relatedType, int $relatedId, int $missionId, int $userId): array
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return ['success' => false, 'message' => 'لم يتم اختيار ملف صحيح.'];
        }
        if ($file->getSizeByUnit('kb') > self::MAX_SIZE_KB) {
            return ['success' => false, 'message' => 'حجم الملف أكبر من الحد المسموح (10 ميجا).'];
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return ['success' => false, 'message' => 'نوع الملف غير مسموح به.'];
        }

        $uploadDir = WRITEPATH . 'uploads/' . $relatedType . 's/' . $relatedId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        $docId = $this->insert([
            'mission_id'   => $missionId,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
            'file_name'    => $file->getClientName(),
            'file_path'    => $relatedType . 's/' . $relatedId . '/' . $newName,
            'file_size'    => $file->getSize(),
            'mime_type'    => $file->getClientMimeType(),
            'uploaded_by'  => $userId,
            'uploaded_at'  => date('Y-m-d H:i:s'),
        ], true);

        return ['success' => true, 'document' => $this->find($docId)];
    }
}
