<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\MissionModel;
use App\Models\MeetingModel;
use App\Models\DepartmentModel;
use App\Models\AuditNoteModel;

class DocumentController extends BaseController
{
    /** يسمح فقط بامتدادات آمنة ومعقولة لمرفقات الاجتماعات */
    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    private const MAX_SIZE_KB = 10240; // 10 ميجا

    /**
     * رئيس إدارة المراجعة الداخلية طرف ضمنيًا بكل مهام إدارته (audit_department_id)
     * حتى لو مو عضو فريق فيها -- نفس نمط PdfController::assertMissionAccess/
     * ReportController::missionForParty، مطلوب هنا لأن endpoint المرفقات هذا هو
     * اللي كان يفشل بصمت (403) ويكسر تحميل بيانات ملخص الاجتماع كاملة عبر
     * Promise.all بمسم meetingsummary.js لمّا يُستخدم رئيس إدارة المراجعة
     * الداخلية (مثلًا أثناء تصدير التقرير النهائي).
     *
     * كانت ناقصة طرف "الإدارة المستهدفة" (منسّق/مدير إدارة محل المراجعة)
     * كليًا -- فمستند رفعه المنسّق نفسه عبر صفحة قائمة المستندات كان يفشل
     * تنزيله بـ 404 "ليس لديك صلاحية الوصول"، لأن activeMissionsForUser()
     * ترجّع بس مهام فريق المراجعة (مو الإدارة الخاضعة له)
     */
    private function missionAccessAllowed(int $missionId): bool
    {
        $mission = (new MissionModel())->find($missionId);
        if (!$mission) {
            return false;
        }

        $departmentId = (int) session()->get('department_id');

        if (session()->get('role_code') === 'audit_head') {
            return (int) $mission['audit_department_id'] === $departmentId;
        }

        $userId = (int) session()->get('user_id');
        $allowedIds = array_map('intval', array_column((new MissionModel())->activeMissionsForUser($userId), 'id'));
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = (new DepartmentModel())->isInScope((int) $mission['target_department_id'], $departmentId);

        return $isAuditSide || $isTargetSide;
    }

    /**
     * POST /dashboard/meetings/api/upload — رفع مرفق لاجتماع معيّن
     */
    public function uploadMeetingAttachment()
    {
        $missionId = (int) $this->request->getPost('mission_id');
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'مهمة غير محددة.']);
        }

        // تحقق من صلاحية الوصول للمهمة
        $userId = (int) session()->get('user_id');
        $missionModel = new MissionModel();
        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        $meetingModel = new MeetingModel();
        $meeting = $meetingModel->findOrCreateForMission($missionId, $userId);

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لم يتم اختيار ملف صحيح.']);
        }

        if ($file->getSizeByUnit('kb') > self::MAX_SIZE_KB) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'حجم الملف أكبر من الحد المسموح (10 ميجا).']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'نوع الملف غير مسموح به.']);
        }

        $uploadDir = WRITEPATH . 'uploads/meetings/' . $meeting['id'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        $docModel = new DocumentModel();
        $docId = $docModel->insert([
            'mission_id'   => $missionId,
            'related_type' => 'meeting',
            'related_id'   => $meeting['id'],
            'file_name'    => $file->getClientName(),
            'file_path'    => 'meetings/' . $meeting['id'] . '/' . $newName,
            'file_size'    => $file->getSize(),
            'mime_type'    => $file->getClientMimeType(),
            'uploaded_by'  => $userId,
            'uploaded_at'  => date('Y-m-d H:i:s'),
        ], true);

        return $this->response->setJSON(['success' => true, 'document' => $docModel->find($docId)]);
    }

    /**
     * POST /dashboard/observations/api/upload — رفع مرفق لملاحظة معيّنة (لازم
     * تكون محفوظة أصلًا -- عندها id حقيقي، فما تظهر خانة الرفع بنموذج إضافة
     * ملاحظة جديدة قبل الحفظ، فقط بصفحتَي التعديل/العرض)
     */
    public function uploadObservationAttachment()
    {
        $observationId = (int) $this->request->getPost('observation_id');
        if (!$observationId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'ملاحظة غير محددة.']);
        }

        $obs = (new AuditNoteModel())->find($observationId);
        if (!$obs || !$this->missionAccessAllowed((int) $obs['mission_id'])) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه الملاحظة.']);
        }

        $file = $this->request->getFile('file');
        if (!$file) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لم يتم اختيار ملف صحيح.']);
        }

        $result = (new DocumentModel())->saveUploadedFile($file, 'observation', $observationId, (int) $obs['mission_id'], (int) session()->get('user_id'));
        if (!$result['success']) {
            return $this->response->setStatusCode(422)->setJSON($result);
        }
        return $this->response->setJSON($result);
    }

    /**
     * GET /dashboard/meetings/api/attachments?mission_id=X — قائمة مرفقات اجتماع مهمة معيّنة
     */
    public function meetingAttachments()
    {
        $missionId = (int) $this->request->getGet('mission_id');

        if (!$this->missionAccessAllowed($missionId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $meetingModel = new MeetingModel();
        $meeting = $meetingModel->firstForMission($missionId);
        if (!$meeting) {
            return $this->response->setJSON(['success' => true, 'documents' => []]);
        }

        $docModel = new DocumentModel();
        return $this->response->setJSON(['success' => true, 'documents' => $docModel->forRelated('meeting', $meeting['id'])]);
    }

    /**
     * GET /dashboard/documents/download/{id} — تحميل ملف مرفق (بعد التحقق من الصلاحية)
     */
    public function download(int $id)
    {
        $docModel = new DocumentModel();
        $doc = $docModel->find($id);
        if (!$doc) throw new \CodeIgniter\Exceptions\PageNotFoundException('الملف غير موجود.');

        if (!$this->missionAccessAllowed((int) $doc['mission_id'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذا الملف.');
        }

        $fullPath = WRITEPATH . 'uploads/' . $doc['file_path'];
        if (!is_file($fullPath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('الملف غير موجود على الخادم.');
        }

        return $this->response->download($fullPath, null)->setFileName($doc['file_name']);
    }

    /**
     * POST /dashboard/documents/delete/{id} — حذف مرفق
     */
    public function delete(int $id)
    {
        $docModel = new DocumentModel();
        $doc = $docModel->find($id);
        if (!$doc) return $this->response->setStatusCode(404)->setJSON(['success' => false]);

        $userId = (int) session()->get('user_id');
        if ((int) $doc['uploaded_by'] !== $userId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'لا يمكنك حذف مرفق رفعه شخص آخر.']);
        }

        $fullPath = WRITEPATH . 'uploads/' . $doc['file_path'];
        if (is_file($fullPath)) unlink($fullPath);

        $docModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }
}
