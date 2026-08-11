<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\DocumentRequestModel;
use App\Models\DocumentResponseModel;
use App\Models\DocumentModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;

class DocumentRequestController extends BaseController
{
    /** يسمح فقط بامتدادات آمنة ومعقولة لمستندات الاتفاقية */
    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    private const MAX_SIZE_KB = 10240; // 10 ميجا

    /** المهمة (لو المستخدم الحالي له صلاحية وصول فعلية لها، من أي طرف)، وإلا null */
    private function missionForCurrentUser(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
        if (!$mission) {
            return null;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');

        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        return ($isAuditSide || $isTargetSide) ? $mission : null;
    }

    /**
     * GET /dashboard/document-requests/api/list?mission_id=X — قائمة المستندات المطلوبة
     * لمهمة، مع حالة الرد عليها إن وُجد. يظهر لطرفي المهمة (المراجع يشوفها للمتابعة،
     * الإدارة الخاضعة تشوفها لتعبئتها/رفعها)
     */
    public function list()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        $mission = $missionId ? $this->missionForCurrentUser($missionId) : null;
        if (!$mission) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        $departmentId = (int) session()->get('department_id');
        $canSubmit = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        $requestModel = new DocumentRequestModel();
        $requests = $requestModel->forMissionWithResponses($missionId);

        $docModel = new DocumentModel();
        $requests = array_map(function ($r) use ($docModel) {
            $r['file'] = $docModel->forRelated('document_request', (int) $r['id'])[0] ?? null;
            return $r;
        }, $requests);

        return $this->response->setJSON(['success' => true, 'requests' => $requests, 'can_submit' => $canSubmit]);
    }

    /**
     * POST /dashboard/document-requests/api/add — يضيف طلب مستند جديد لمهمة قائمة
     * (فريق المراجعة فقط -- عضو أو رئيس المهمة، مو الإدارة الخاضعة للمراجعة). صفحة
     * "قائمة المستندات" المستقلة بالسايدبار تستخدمها لطلب مستندات إضافية في أي وقت،
     * وليس فقط أثناء إنشاء المهمة (بدل ما كان مقصورًا على خطوة واحدة بمعالج "بدء مهمة")
     */
    public function add()
    {
        $missionId = (int) $this->request->getPost('mission_id');
        $docName   = trim((string) $this->request->getPost('doc_name'));

        $missionModel = new MissionModel();
        $mission = $missionId ? $missionModel->find($missionId) : null;
        if (!$mission) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'المهمة غير موجودة.']);
        }

        $userId = (int) session()->get('user_id');
        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'إضافة طلبات مستندات جديدة متاحة فقط لفريق المراجعة المسؤول عن المهمة.']);
        }

        if ($docName === '') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى إدخال اسم المستند.']);
        }

        $requestModel = new DocumentRequestModel();
        $maxRow = $requestModel->where('mission_id', $missionId)->selectMax('sort_order')->first();
        $nextSort = ((int) ($maxRow['sort_order'] ?? 0)) + 1;

        $id = $requestModel->insert([
            'mission_id' => $missionId,
            'doc_name'   => $docName,
            'sort_order' => $nextSort,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        (new AuditLogModel())->log($missionId, $userId, 'document_request_added', 'document_request', $id, $docName);

        return $this->response->setJSON(['success' => true, 'id' => $id]);
    }

    /**
     * POST /dashboard/document-requests/api/submit — إرسال كل ردود المستندات دفعة وحدة
     * (multipart/form-data: responses[i][document_request_id], responses[i][exists_flag],
     * responses[i][note], وملف اختياري باسم الحقل file_{document_request_id})
     * بس المستخدم اللي إدارته = target_department_id تبع المهمة يقدر يرفع
     */
    public function submit()
    {
        $missionId = (int) $this->request->getPost('mission_id');
        $mission = $missionId ? $this->missionForCurrentUser($missionId) : null;
        if (!$mission) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');
        if (!$departmentId || (int) $mission['target_department_id'] !== $departmentId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'رفع المستندات متاح فقط لمستخدمي الإدارة الخاضعة للمراجعة.']);
        }

        $responses = $this->request->getPost('responses') ?? [];
        if (!is_array($responses) || empty($responses)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لا يوجد أي رد لإرساله.']);
        }

        $requestModel = new DocumentRequestModel();
        $ownRequests  = $requestModel->where('mission_id', $missionId)->findAll();
        $ownRequestIds = array_map('intval', array_column($ownRequests, 'id'));
        $docNameById   = array_column($ownRequests, 'doc_name', 'id');

        $db = \Config\Database::connect();
        $db->transStart();

        $responseModel = new DocumentResponseModel();
        $docModel      = new DocumentModel();
        $now = date('Y-m-d H:i:s');
        $submittedDocNames = [];

        foreach ($responses as $row) {
            $requestId = (int) ($row['document_request_id'] ?? 0);
            if (!$requestId || !in_array($requestId, $ownRequestIds, true)) {
                continue; // تجاهل أي معرّف مو تابع فعليًا لهذي المهمة
            }
            $submittedDocNames[] = $docNameById[$requestId] ?? '';

            $responseModel->upsertForRequest($requestId, [
                'exists_flag'  => isset($row['exists_flag']) && $row['exists_flag'] !== '' ? (int) $row['exists_flag'] : null,
                'note'         => $row['note'] ?? null,
                'responded_by' => $userId,
                'responded_at' => $now,
            ]);

            $file = $this->request->getFile('file_' . $requestId);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if ($file->getSizeByUnit('kb') > self::MAX_SIZE_KB) {
                    continue;
                }
                $ext = strtolower($file->getClientExtension());
                if (!in_array($ext, self::ALLOWED_EXT, true)) {
                    continue;
                }

                $uploadDir = WRITEPATH . 'uploads/document-requests/' . $requestId;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newName = $file->getRandomName();
                $file->move($uploadDir, $newName);

                // ملف سابق لنفس الطلب (لو موجود) يُستبدل بالجديد بدل ما يتكدس
                foreach ($docModel->forRelated('document_request', $requestId) as $old) {
                    $oldPath = WRITEPATH . 'uploads/' . $old['file_path'];
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                    $docModel->delete($old['id']);
                }

                $docModel->insert([
                    'mission_id'   => $missionId,
                    'related_type' => 'document_request',
                    'related_id'   => $requestId,
                    'file_name'    => $file->getClientName(),
                    'file_path'    => 'document-requests/' . $requestId . '/' . $newName,
                    'file_size'    => $file->getSize(),
                    'mime_type'    => $file->getClientMimeType(),
                    'uploaded_by'  => $userId,
                    'uploaded_at'  => $now,
                ]);
            }
        }

        // تسجيل فعلي بالسجل الزمني — مرة وحدة فقط لكل مهمة (أول إرسال يفتح المرحلة،
        // إعادة الإرسال لتحديث رد تحدّث نفس الصفوف بدون تكرار دخول للمرحلة)
        $stageHistoryModel = new MissionStageHistoryModel();
        $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 2)->countAllResults();
        if ($alreadyLogged === 0) {
            $stageHistoryModel->openStage($missionId, 2, $userId);
        }

        $detail = implode('، ', array_filter($submittedDocNames));
        (new AuditLogModel())->log($missionId, $userId, 'documents_submitted', 'document_request', null, $detail ?: null);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'حدث خطأ أثناء حفظ المستندات. حاول مرة أخرى.']);
        }

        (new MissionModel())->syncCurrentStage($missionId);

        return $this->response->setJSON(['success' => true]);
    }
}
