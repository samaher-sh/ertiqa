<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MissionPlanningModel;
use App\Models\MissionPlanningMilestoneModel;
use App\Models\AuditLogModel;

class MissionPlanningController extends BaseController
{
    private function isHrUser(): bool
    {
        return in_array(session()->get('role_code'), ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
    }

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    private function assertMissionAccess(int $missionId): array
    {
        $mission = (new MissionModel())->findWithDetails($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة.');
        }
        $allowedIds = array_map('intval', array_column($this->missionsForCurrentSession(), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
        return $mission;
    }

    /** المهمة عندها صف تخطيط دائمًا (تُنشَأ فارغة أول ما تُنشأ المهمة نفسها -- انظر
     *  MissionController::store())، لكن هذا احتياط لمهام أُنشئت قبل هذي الميزة */
    private function planningForMission(int $missionId): array
    {
        $planningModel = new MissionPlanningModel();
        $planning = $planningModel->forMission($missionId);
        if ($planning) {
            return $planning;
        }

        $planningId = $planningModel->insert(['mission_id' => $missionId], true);
        (new MissionPlanningMilestoneModel())->seedDefaults((int) $planningId);
        return $planningModel->find($planningId);
    }

    /** GET /dashboard/mission-planning — صفحة "تخطيط المهمة" المستقلة (Server-Rendered)
     *  تعرض/تعدّل خطوة "تخطيط المهمة" لمهمة موجودة فعليًا (بخلاف خطوة 3 بمعالج "بدء
     *  مهمة" اللي تُعبَّأ أول مرة وقت الإنشاء) -- تُستخدم لمراجعة/تحديث نفس البيانات
     *  لاحقًا من فريق المراجعة، أو لعرضها لممثل الإدارة المستهدفة (قراءة فقط) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $mission = null;
        $planning = null;
        $milestones = [];

        if ($missionId) {
            $mission = $this->assertMissionAccess($missionId);
            $planning = $this->planningForMission($missionId);
            $milestones = (new MissionPlanningMilestoneModel())->forPlanning((int) $planning['id']);
        }

        return view('dashboard/mission-planning/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'missionPlanning',
            'currentUser'  => $this->sessionUserSummary(),
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'planning'          => $planning,
            'milestones'        => $milestones,
            'canEdit'           => (bool) $missionId && !$this->isHrUser(),
        ]);
    }

    /** POST /dashboard/mission-planning/api/save — يعمل فقط لفريق المراجعة (قراءة
     *  فقط لممثل الإدارة المستهدفة، نتحقق بالباك-إند برضو مو بس بالواجهة) */
    public function save()
    {
        $isJson = $this->isJsonRequest();
        if ($this->isHrUser()) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        if (!$missionId) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
            }
            return redirect()->back()->with('error', 'يرجى اختيار المهمة المرتبطة أولاً.');
        }
        $this->assertMissionAccess($missionId);

        $planning = $this->planningForMission($missionId);
        $planningModel = new MissionPlanningModel();
        $planningModel->update($planning['id'], [
            'project_start_date'       => ($data['project_start_date'] ?? null) ?: null,
            'plan_execution_days'      => $data['plan_execution_days'] ?? null,
            'proposed_execution_days'  => $data['proposed_execution_days'] ?? null,
            'target_dept_manager_name' => $data['target_dept_manager_name'] ?? null,
            'participating_reviewers'  => $data['participating_reviewers'] ?? null,
            'mission_brief'            => $data['mission_brief'] ?? null,
            'previous_audits'          => $data['previous_audits'] ?? null,
            'previous_audit_report_date' => ($data['previous_audit_report_date'] ?? null) ?: null,
            'regulatory_notes'         => $data['regulatory_notes'] ?? null,
            'audit_objectives'         => $data['audit_objectives'] ?? null,
            'scope_included'           => $data['scope_included'] ?? null,
            'scope_excluded'           => $data['scope_excluded'] ?? null,
            'sub_procedures'           => $data['sub_procedures'] ?? null,
            'prepared_by_name'         => $data['prepared_by_name'] ?? null,
            'prepared_by_title'        => $data['prepared_by_title'] ?? null,
            'approved_by_name'         => $data['approved_by_name'] ?? null,
            'approved_by_title'        => $data['approved_by_title'] ?? null,
            'team_meeting_date'        => ($data['team_meeting_date'] ?? null) ?: null,
        ]);
        (new MissionPlanningMilestoneModel())->replaceForPlanning((int) $planning['id'], $data['milestones'] ?? []);

        (new AuditLogModel())->log($missionId, (int) session()->get('user_id'), 'mission_planning_saved', 'mission_planning', (int) $planning['id'], null);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/mission-planning?mission_id=' . $missionId))->with('success', 'تم حفظ تخطيط المهمة بنجاح.');
    }
}
