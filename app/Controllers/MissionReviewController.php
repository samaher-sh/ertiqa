<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;
use App\Models\DepartmentModel;

/**
 * صفحة مراجعة المهمة من طرف الإدارة الخاضعة للمراجعة (dept_coordinator وما شابه):
 * نفس واجهة "بدء مهمة" اللي يشوفها عضو المراجعة، لكن الخطاب هنا للعرض فقط، واتفاقية
 * مستوى الخدمة تصير قابلة للتعبئة من طرف الإدارة. قائمة المستندات نفسها تُدار عبر
 * DocumentRequestController الموجود أصلًا (لا تكرار).
 */
class MissionReviewController extends BaseController
{
    /**
     * يقارن إدارة المستخدم بالإدارة المستهدفة بالمهمة مع مراعاة الهرمية
     * (إدارة رئيسية + أقسام فرعية تحتها) -- لا يكفي تطابق الرقم بالضبط، لأنه
     * منسّق الإدارة الرئيسية (زي الموارد البشرية) لازم يقدر يعبّي اتفاقية
     * مهمة تستهدف قسمًا فرعيًا تحتها (زي التوظيف) والعكس صحيح
     */
    private function departmentInScope(int $targetDeptId, int $userDeptId): bool
    {
        if (!$userDeptId || !$targetDeptId) {
            return false;
        }
        if ($targetDeptId === $userDeptId) {
            return true;
        }

        $deptModel = new DepartmentModel();

        $target = $deptModel->find($targetDeptId);
        if ($target && (int) ($target['parent_id'] ?? 0) === $userDeptId) {
            return true;
        }

        $userDept = $deptModel->find($userDeptId);
        if ($userDept && (int) ($userDept['parent_id'] ?? 0) === $targetDeptId) {
            return true;
        }

        return false;
    }

    /** المهمة لو المستخدم الحالي فعليًا من الإدارة المستهدفة لها (أو إدارة رئيسية/فرعية منها)، وإلا null */
    private function missionForTargetUser(int $missionId): ?array
    {
        $mission = (new MissionModel())->findWithDetails($missionId);
        if (!$mission) {
            return null;
        }

        $departmentId = (int) session()->get('department_id');
        if (!$this->departmentInScope((int) $mission['target_department_id'], $departmentId)) {
            return null;
        }

        return $mission;
    }

    /** المهمة لو المستخدم الحالي طرف فيها فعليًا (مراجع أو الإدارة المستهدفة)، وإلا null —
     *  يسمح للمراجع بمعاينة (قراءة فقط) اللي عبّته الإدارة المستهدفة بعد إرساله.
     *  رئيس إدارة المراجعة الداخلية طرف ضمنيًا بكل مهام إدارته (audit_department_id)
     *  حتى لو مو عضو فريق فيها -- يحتاج يستعرض هذي المرحلة قبل اعتماد التقرير النهائي */
    private function missionForParty(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->findWithDetails($missionId);
        if (!$mission) {
            return null;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');

        if (session()->get('role_code') === 'audit_head') {
            return ((int) $mission['audit_department_id'] === $departmentId) ? $mission : null;
        }

        $allowedIds = array_map('intval', array_column($missionModel->activeMissionsForUser($userId), 'id'));
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = $this->departmentInScope((int) $mission['target_department_id'], $departmentId);

        return ($isAuditSide || $isTargetSide) ? $mission : null;
    }

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    /** GET /dashboard/target-mission — صفحة "استكمال الاتفاقية والمستندات" الحقيقية
     *  (Server-Rendered). خطوة "قائمة المستندات" أصبحت رابطًا مباشرًا لصفحة قائمة
     *  المستندات المستقلة (DocumentRequestController) بدل تكرارها هنا -- تلك الصفحة
     *  صارت تدعم رد الإدارة المستهدفة فعليًا (راجع canSubmit هناك) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $mission = null;
        $agreement = null;
        $rowsBySection = [];
        $canEdit = false;

        if ($missionId) {
            $mission = $this->missionForParty($missionId);
            if (!$mission) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
            }
            $agreement = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
            $rows = $agreement ? (new ServiceAgreementResponseModel())->forMission($missionId) : [];
            foreach ($rows as $r) {
                $rowsBySection[$r['section_title']][] = $r;
            }
            $departmentId = (int) session()->get('department_id');
            $canEdit = $this->departmentInScope((int) $mission['target_department_id'], $departmentId);
        }

        /* embed=1 -- الصفحة مضمَّنة بـ iframe داخل مراحل اعتماد التقرير النهائي
           لغرض المعاينة فقط، فتُجبَر على عرض فقط بغض النظر عن صلاحية المستخدم
           الفعلية على هذي المهمة */
        $embed = $this->request->getGet('embed') === '1';

        return view('dashboard/mission-review/index', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'sentTasks',
            'currentUser'  => $this->sessionUserSummary(),
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'agreement'         => $agreement,
            'rowsBySection'     => $rowsBySection,
            'canEdit'           => $canEdit && !$embed,
            'embed'             => $embed,
        ]);
    }

    /** GET /dashboard/target-mission/api/data?mission_id=X — قراءة متاحة لطرفي المهمة، الكتابة (saveAgreement) للإدارة المستهدفة فقط */
    public function data()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        $mission = $missionId ? $this->missionForParty($missionId) : null;
        if (!$mission) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        $agreement = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        $rows = $agreement ? (new ServiceAgreementResponseModel())->forMission($missionId) : [];

        return $this->response->setJSON([
            'success'   => true,
            'mission'   => $mission,
            'agreement' => $agreement,
            'rows'      => $rows,
        ]);
    }

    /** يحوّل حقول نموذج HTML عادي (rows[id][answer], rows[id][note], ...) لنفس
     *  بنية $data المتوقَّعة من الفرع JSON الأصلي (rows: [{id, agree, disagree, note}]) */
    private function formPostToSaveData(): array
    {
        $post = $this->request->getPost();
        $rows = [];
        foreach (($post['rows'] ?? []) as $id => $r) {
            $answer = $r['answer'] ?? '';
            $rows[] = [
                'id'       => $id,
                'agree'    => $answer === 'agree' ? 1 : 0,
                'disagree' => $answer === 'disagree' ? 1 : 0,
                'note'     => $r['note'] ?? '',
            ];
        }
        return [
            'mission_id'        => $post['mission_id'] ?? null,
            'coordinator_name'  => $post['coordinator_name'] ?? '',
            'coordinator_email' => $post['coordinator_email'] ?? '',
            'coordinator_phone' => $post['coordinator_phone'] ?? '',
            'rows'              => $rows,
        ];
    }

    /** POST /dashboard/target-mission/api/save-agreement — يحفظ ردود بنود الاتفاقية + بيانات المنسّق دفعة وحدة */
    public function saveAgreement()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->formPostToSaveData();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $mission = $missionId ? $this->missionForTargetUser($missionId) : null;
        if (!$mission) {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }

        $agreementModel = new ServiceAgreementModel();
        $agreement = $agreementModel->where('mission_id', $missionId)->first();
        if (!$agreement) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لا توجد اتفاقية مستوى خدمة لهذه المهمة.']);
            }
            return redirect()->to(base_url('dashboard/target-mission?mission_id=' . $missionId))->with('error', 'لا توجد اتفاقية مستوى خدمة لهذه المهمة.');
        }

        // نبني ردود الطلب بمصفوفة id => row أول شي، ونتحقق قبل أي حفظ إن كل بند
        // فعليًا معه رد (موافق أو غير موافق -- مو الاثنين صفر) -- بدون هذا التحقق،
        // اتفاقية ما ردّ عليها المستخدم فعليًا كانت تُعتمَد "submitted" بس لأن الزر
        // انضغط، فتتقدّم المرحلة بدون أي بيانات حقيقية محفوظة
        $responseModel = new ServiceAgreementResponseModel();
        $ownRows = $responseModel->select('id')->where('service_agreement_id', $agreement['id'])->findAll();
        // (int) إلزامي هنا: سائق MySQLi بهذا السيرفر يرجّع عمود id كسلسلة نصية
        // ("65" لا 65)، فلو تركناها كما هي، in_array(..., true) الصارمة تحتها
        // ترفض كل صف بصمت لأن النوع يختلف عن $rowId المُحوَّل (int) -- بالضبط
        // سبب رفض بيانات مكتملة فعليًا كأنها فارغة بالكامل
        $ownRowIds = array_map('intval', array_column($ownRows, 'id'));

        $incomingById = [];
        foreach (($data['rows'] ?? []) as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId && in_array($rowId, $ownRowIds, true)) {
                $incomingById[$rowId] = $row;
            }
        }

        $unanswered = array_filter($ownRowIds, function ($id) use ($incomingById) {
            $row = $incomingById[$id] ?? null;
            return !$row || (empty($row['agree']) && empty($row['disagree']));
        });
        if (!empty($unanswered)) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'يرجى الرد (موافق أو غير موافق) على كل بند بالاتفاقية قبل الإرسال.',
                ]);
            }
            return redirect()->to(base_url('dashboard/target-mission?mission_id=' . $missionId))->withInput()->with('error', 'يرجى الرد (موافق أو غير موافق) على كل بند بالاتفاقية قبل الإرسال.');
        }

        $userId = (int) session()->get('user_id');
        $db = \Config\Database::connect();
        $db->transStart();

        $agreementModel->update($agreement['id'], [
            'coordinator_name'  => trim($data['coordinator_name'] ?? '') ?: null,
            'coordinator_email' => trim($data['coordinator_email'] ?? '') ?: null,
            'coordinator_phone' => trim($data['coordinator_phone'] ?? '') ?: null,
            'status'            => 'submitted',
            'submitted_by'      => $userId,
            'submitted_at'      => date('Y-m-d H:i:s'),
        ]);

        $agreeCount = 0;
        $disagreeCount = 0;
        foreach ($incomingById as $rowId => $row) {
            $agree = !empty($row['agree']) ? 1 : 0;
            $disagree = !empty($row['disagree']) ? 1 : 0;
            if ($agree) $agreeCount++;
            if ($disagree) $disagreeCount++;
            $responseModel->update($rowId, [
                'agree'    => $agree,
                'disagree' => $disagree,
                'note'     => $row['note'] ?? null,
            ]);
        }

        $stageHistoryModel = new MissionStageHistoryModel();
        $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 2)->countAllResults();
        if ($alreadyLogged === 0) {
            $stageHistoryModel->openStage($missionId, 2, $userId);
        }

        $coordName = trim($data['coordinator_name'] ?? '');
        $detail = ($coordName ? 'المنسّق: ' . $coordName . ' — ' : '') . $agreeCount . ' بند موافق، ' . $disagreeCount . ' غير موافق';
        (new AuditLogModel())->log($missionId, $userId, 'sla_submitted', 'service_agreement', $agreement['id'], $detail);

        $db->transComplete();

        if ($db->transStatus() === false) {
            if ($isJson) {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'حدث خطأ أثناء حفظ الاتفاقية. حاول مرة أخرى.']);
            }
            return redirect()->to(base_url('dashboard/target-mission?mission_id=' . $missionId))->with('error', 'حدث خطأ أثناء حفظ الاتفاقية. حاول مرة أخرى.');
        }

        (new MissionModel())->syncCurrentStage($missionId);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/target-mission?mission_id=' . $missionId))->with('success', 'تم حفظ اتفاقية مستوى الخدمة بنجاح.');
    }
}
