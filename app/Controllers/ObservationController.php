<?php

namespace App\Controllers;

use App\Models\AuditNoteModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;

class ObservationController extends BaseController
{
    private function roleFlags(): array
    {
        $roleCode = session()->get('role_code');
        $isHrUser = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        return [
            'isHrUser'   => $isHrUser,
            'obsReadOnly'=> $isHrUser || $roleCode === 'audit_head',
        ];
    }

    /** GET /dashboard/observations/api/list?mission_id=X */
    public function list()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'items' => []]);

        $model = new AuditNoteModel();
        return $this->response->setJSON(['success' => true, 'items' => $model->forMission($missionId)]);
    }

    /** POST /dashboard/observations/api/save (id فاضي = جديد) */
    public function save()
    {
        $flags = $this->roleFlags();
        if ($flags['obsReadOnly']) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل.']);
        }

        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
        }
        if (empty($data['department_id']) || empty($data['observation_text'])) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى تعبئة الإدارة محل المراجعة ونص الملاحظة على الأقل.']);
        }

        $model = new AuditNoteModel();
        $userId = (int) session()->get('user_id');

        $payload = [
            'mission_id'            => $missionId,
            'department_id'         => $data['department_id'],
            'title'                 => $data['title'] ?? '',
            'observation_date'      => $data['observation_date'] ?: date('Y-m-d'),
            'risk_severity'         => mb_substr((string) ($data['risk_severity'] ?? ''), 0, 50),
            'observation_text'      => $data['observation_text'],
            'standard_text'         => $data['standard_text'] ?? '',
            'reason_text'           => $data['reason_text'] ?? '',
            'impact_text'           => $data['impact_text'] ?? '',
            'recommendations_text'  => $data['recommendations_text'] ?? '',
            'add_to_report'         => isset($data['add_to_report']) ? ($data['add_to_report'] ? 1 : 0) : null,
        ];

        if (!empty($data['id'])) {
            $model->update((int) $data['id'], $payload);
            $id = (int) $data['id'];
        } else {
            $payload['ref_code']   = $model->generateRefCode();
            $payload['status']     = 'بانتظار الرد';
            $payload['created_by'] = $userId;
            $id = $model->insert($payload, true);

            $stageHistoryModel = new MissionStageHistoryModel();
            $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 5)->countAllResults();
            if ($alreadyLogged === 0) {
                $stageHistoryModel->openStage($missionId, 5, $userId);
            }
            (new AuditLogModel())->log($missionId, $userId, 'observation_added', 'audit_note', $id, trim((string) ($payload['title'] ?? '')) ?: null);
        }

        return $this->response->setJSON(['success' => true, 'id' => $id]);
    }

    /** POST /dashboard/observations/api/delete/{id} */
    public function delete(int $id)
    {
        $flags = $this->roleFlags();
        if ($flags['obsReadOnly']) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }
        (new AuditNoteModel())->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    /** POST /dashboard/observations/api/status/{id} — تغيير الحالة فقط */
    public function updateStatus(int $id)
    {
        $data = $this->request->getJSON(true);
        $status = $data['status'] ?? '';
        if (!in_array($status, ['بانتظار الرد', 'قيد المعالجة', 'مغلقة'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false]);
        }
        (new AuditNoteModel())->update($id, ['status' => $status]);
        return $this->response->setJSON(['success' => true]);
    }
}
