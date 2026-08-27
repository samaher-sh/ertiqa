<?php

/**
 * 
 */

namespace App\Controllers;

use App\Models\AuditNoteModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;
use App\Models\MissionModel;

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

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    /** التحقق من صلاحية الوصول للمهمة — نفس حدود missionsForCurrentSession() المستخدَمة بمنتقي المهمة */
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
            'activeNavKey' => 'observations',
            'currentUser'  => $this->sessionUserSummary(),
        ], $extra);
    }

    /** GET /dashboard/observations — صفحة القائمة الحقيقية (Server-Rendered) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $mission = null;
        $items = [];
        if ($missionId) {
            $mission = $this->assertMissionAccess($missionId);
            $items = (new AuditNoteModel())->forMission($missionId);
        }

        $roleCode = session()->get('role_code');

        /* embed=1 -- الصفحة مضمَّنة بـ iframe داخل مراحل اعتماد التقرير النهائي
           لغرض المعاينة فقط، فتُجبَر على عرض فقط ويُخفى تصدير PDF الخاص بها */
        $embed = $this->request->getGet('embed') === '1';

        return view('dashboard/observations/index', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'items'             => $items,
            'readOnly'          => $this->roleFlags()['obsReadOnly'] || $embed,
            'isAuditMember'     => $roleCode === 'audit_member',
            'isAuditHead'       => $roleCode === 'audit_head',
            'embed'             => $embed,
        ]));
    }

    /** GET /dashboard/observations/create */
    public function create()
    {
        if ($this->roleFlags()['obsReadOnly']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الإضافة.');
        }

        $missions = $this->missionsForCurrentSession();
        $missionId = (int) ($this->request->getGet('mission_id') ?: 0);
        $mission = null;
        if ($missionId) {
            $this->assertMissionAccess($missionId);
            foreach ($missions as $m) {
                if ((int) $m['id'] === $missionId) { $mission = $m; break; }
            }
        }

        return view('dashboard/observations/create', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'observation'       => null,
        ]));
    }

    /** GET /dashboard/observations/{id}/edit */
    public function edit(int $id)
    {
        if ($this->roleFlags()['obsReadOnly']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $obs = (new AuditNoteModel())->findWithDepartment($id);
        if (!$obs) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('الملاحظة غير موجودة.');
        }
        $this->assertMissionAccess((int) $obs['mission_id']);

        $missions = $this->missionsForCurrentSession();
        $mission = null;
        foreach ($missions as $m) {
            if ((int) $m['id'] === (int) $obs['mission_id']) { $mission = $m; break; }
        }

        return view('dashboard/observations/edit', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => (int) $obs['mission_id'],
            'mission'           => $mission,
            'observation'       => $obs,
        ]));
    }

    /** GET /dashboard/observations/{id} */
    public function show(int $id)
    {
        $obs = (new AuditNoteModel())->findWithDepartment($id);
        if (!$obs) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('الملاحظة غير موجودة.');
        }
        $mission = $this->assertMissionAccess((int) $obs['mission_id']);

        return view('dashboard/observations/show', $this->pageViewData([
            'observation' => $obs,
            'mission'     => $mission,
            'readOnly'    => $this->roleFlags()['obsReadOnly'],
        ]));
    }

    /** GET /dashboard/observations/api/list?mission_id=X */
    public function list()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'items' => []]);

        $model = new AuditNoteModel();
        return $this->response->setJSON(['success' => true, 'items' => $model->forMission($missionId)]);
    }

    /**
     * POST /dashboard/observations/api/save (id فاضي = جديد)
     * فرعان: JSON (Content-Type: application/json — السلوك الأصلي بدون أي تغيير)
     * أو نموذج HTML عادي (Post/Redirect/Get — للصفحة الحقيقية الجديدة بدون جافاسكربت)
     */
    public function save()
    {
        $isJson = $this->isJsonRequest();
        $flags = $this->roleFlags();
        if ($flags['obsReadOnly']) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل.']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        if (!$missionId) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
            }
            return redirect()->back()->withInput()->with('error', 'يرجى اختيار المهمة المرتبطة أولاً.');
        }
        if (empty($data['department_id']) || empty($data['observation_text'])) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى تعبئة الإدارة محل المراجعة ونص الملاحظة على الأقل.']);
            }
            return redirect()->back()->withInput()->with('error', 'يرجى تعبئة الإدارة محل المراجعة ونص الملاحظة على الأقل.');
        }
        if (!$isJson) {
            $this->assertMissionAccess($missionId);
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
            (new MissionModel())->syncCurrentStage($missionId);
        }

        if ($isJson) {
            return $this->response->setJSON(['success' => true, 'id' => $id]);
        }
        return redirect()->to(base_url('dashboard/observations/' . $id))->with('success', 'تم الحفظ بنجاح.');
    }

    /** POST /dashboard/observations/api/delete/{id} */
    public function delete(int $id)
    {
        $isJson = $this->isJsonRequest();
        $flags = $this->roleFlags();
        if ($flags['obsReadOnly']) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false]);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الحذف.');
        }

        $redirectMissionId = $isJson ? null : ((new AuditNoteModel())->find($id)['mission_id'] ?? null);
        (new AuditNoteModel())->delete($id);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        $redirectUrl = base_url('dashboard/observations') . ($redirectMissionId ? '?mission_id=' . $redirectMissionId : '');
        return redirect()->to($redirectUrl)->with('success', 'تم الحذف بنجاح.');
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
