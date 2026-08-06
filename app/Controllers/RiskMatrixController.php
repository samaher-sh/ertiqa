<?php

namespace App\Controllers;

use App\Models\RiskMatrixItemModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;

class RiskMatrixController extends BaseController
{
    /**
     * GET /dashboard/risk-matrix/api/items?mission_id=X — جلب صفوف مهمة معيّنة
     */
    public function items()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'مهمة غير محددة']);
        }

        $itemModel = new RiskMatrixItemModel();
        return $this->response->setJSON(['success' => true, 'items' => $itemModel->forMission($missionId)]);
    }

    /**
     * POST /dashboard/risk-matrix/api/save — حفظ كل صفوف مهمة معيّنة دفعة وحدة
     */
    public function save()
    {
        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        if ($isHrDept || $roleCode === 'audit_head') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
        }

        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $rows      = $data['rows'] ?? [];

        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
        }

        $itemModel = new RiskMatrixItemModel();
        $itemModel->replaceForMission($missionId, $rows);

        $userId = (int) session()->get('user_id');
        $stageHistoryModel = new MissionStageHistoryModel();
        $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 3)->countAllResults();
        if ($alreadyLogged === 0) {
            $stageHistoryModel->openStage($missionId, 3, $userId);
        }
        (new AuditLogModel())->log($missionId, $userId, 'risk_matrix_saved', 'risk_matrix', null, count($rows) . ' صف');

        return $this->response->setJSON(['success' => true]);
    }
}
