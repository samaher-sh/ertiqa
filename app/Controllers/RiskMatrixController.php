<?php

namespace App\Controllers;

use App\Models\RiskMatrixItemModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;
use App\Models\MissionModel;

class RiskMatrixController extends BaseController
{
    private function isReadOnly(): bool
    {
        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        return $isHrDept || $roleCode === 'audit_head';
    }

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    private function assertMissionAccess(int $missionId): array
    {
        $mission = (new MissionModel())->find($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة.');
        }
        $allowedIds = array_map('intval', array_column($this->missionsForCurrentSession(), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
        return $mission;
    }

    private function pageViewData(array $extra = []): array
    {
        return array_merge([
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'riskMatrix',
            'currentUser'  => $this->sessionUserSummary(),
        ], $extra);
    }

    /** GET /dashboard/risk-matrix — صفحة القائمة الحقيقية (Server-Rendered، قراءة فقط) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $rows = [];
        if ($missionId) {
            $this->assertMissionAccess($missionId);
            $rows = (new RiskMatrixItemModel())->forMission($missionId);
        }

        /* embed=1 -- الصفحة مضمَّنة بـ iframe داخل مراحل اعتماد التقرير النهائي
           لغرض المعاينة فقط، فتُجبَر على عرض فقط ويُخفى تصدير PDF الخاص بها
           (التقرير النهائي فيه تصدير واحد شامل يغطّيها) */
        $embed = $this->request->getGet('embed') === '1';

        return view('dashboard/risk-matrix/index', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'rows'              => $rows,
            'readOnly'          => $this->isReadOnly() || $embed,
            'embed'             => $embed,
        ]));
    }

    /** GET /dashboard/risk-matrix/edit?mission_id=X — نموذج تعديل الجدول كاملًا */
    public function edit()
    {
        if ($this->isReadOnly()) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $missions = $this->missionsForCurrentSession();
        $missionId = (int) ($this->request->getGet('mission_id') ?: 0);

        $rows = session()->getFlashdata('draftRows');
        if ($rows === null && $missionId) {
            $this->assertMissionAccess($missionId);
            $rows = (new RiskMatrixItemModel())->forMission($missionId);
        }

        return view('dashboard/risk-matrix/edit', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'rows'              => $rows ?? [],
        ]));
    }

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
        $isJson = $this->isJsonRequest();

        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        if ($isHrDept || $roleCode === 'audit_head') {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $rows      = $data['rows'] ?? [];

        if (!$missionId) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
            }
            return redirect()->back()->with('error', 'يرجى اختيار المهمة المرتبطة أولاً.');
        }

        if (!$isJson) {
            $this->assertMissionAccess($missionId);

            /* نموذج التعديل بدون جافاسكربت: زرّي "إضافة صف"/"حذف صف" لا يحفظان بقاعدة
               البيانات فعليًا -- فقط يعدّلان المصفوفة المؤقتة (draftRows بالجلسة) ويعيدان
               عرض نفس النموذج بالتغيير، بنفس نمط round-trip الكلاسيكي لنماذج بدون JS */
            $formAction = $data['form_action'] ?? 'save';
            if ($formAction === 'add_row') {
                $rows[] = ['risk' => '', 'risk_rating' => '', 'controls' => '', 'activity_type' => ''];
                return redirect()->to(base_url('dashboard/risk-matrix/edit?mission_id=' . $missionId))->with('draftRows', $rows);
            }
            if ($formAction === 'remove_row') {
                unset($rows[(int) ($data['remove_index'] ?? -1)]);
                $rows = array_values($rows);
                return redirect()->to(base_url('dashboard/risk-matrix/edit?mission_id=' . $missionId))->with('draftRows', $rows);
            }
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
        (new MissionModel())->syncCurrentStage($missionId);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/risk-matrix?mission_id=' . $missionId))->with('success', 'تم حفظ مصفوفة المخاطر بنجاح.');
    }
}
