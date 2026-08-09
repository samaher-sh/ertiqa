<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;

/**
 * صفحة مراجعة المهمة من طرف الإدارة الخاضعة للمراجعة (dept_coordinator وما شابه):
 * نفس واجهة "بدء مهمة" اللي يشوفها عضو المراجعة، لكن الخطاب هنا للعرض فقط، واتفاقية
 * مستوى الخدمة تصير قابلة للتعبئة من طرف الإدارة. قائمة المستندات نفسها تُدار عبر
 * DocumentRequestController الموجود أصلًا (لا تكرار).
 */
class MissionReviewController extends BaseController
{
    /** المهمة لو المستخدم الحالي فعليًا من الإدارة المستهدفة لها، وإلا null */
    private function missionForTargetUser(int $missionId): ?array
    {
        $mission = (new MissionModel())->findWithDetails($missionId);
        if (!$mission) {
            return null;
        }

        $departmentId = (int) session()->get('department_id');
        if (!$departmentId || (int) $mission['target_department_id'] !== $departmentId) {
            return null;
        }

        return $mission;
    }

    /** المهمة لو المستخدم الحالي طرف فيها فعليًا (مراجع أو الإدارة المستهدفة)، وإلا null —
     *  يسمح للمراجع بمعاينة (قراءة فقط) اللي عبّته الإدارة المستهدفة بعد إرساله */
    private function missionForParty(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->findWithDetails($missionId);
        if (!$mission) {
            return null;
        }

        $userId = (int) session()->get('user_id');
        $departmentId = (int) session()->get('department_id');

        $allowedIds = array_column($missionModel->activeMissionsForUser($userId), 'id');
        $isAuditSide  = in_array($missionId, $allowedIds, true);
        $isTargetSide = $departmentId && (int) $mission['target_department_id'] === $departmentId;

        return ($isAuditSide || $isTargetSide) ? $mission : null;
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

    /** POST /dashboard/target-mission/api/save-agreement — يحفظ ردود بنود الاتفاقية + بيانات المنسّق دفعة وحدة */
    public function saveAgreement()
    {
        $data = $this->request->getJSON(true);
        $missionId = (int) ($data['mission_id'] ?? 0);
        $mission = $missionId ? $this->missionForTargetUser($missionId) : null;
        if (!$mission) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لهذه المهمة.']);
        }

        $agreementModel = new ServiceAgreementModel();
        $agreement = $agreementModel->where('mission_id', $missionId)->first();
        if (!$agreement) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'لا توجد اتفاقية مستوى خدمة لهذه المهمة.']);
        }

        // نبني ردود الطلب بمصفوفة id => row أول شي، ونتحقق قبل أي حفظ إن كل بند
        // فعليًا معه رد (موافق أو غير موافق -- مو الاثنين صفر) -- بدون هذا التحقق،
        // اتفاقية ما ردّ عليها المستخدم فعليًا كانت تُعتمَد "submitted" بس لأن الزر
        // انضغط، فتتقدّم المرحلة بدون أي بيانات حقيقية محفوظة
        $responseModel = new ServiceAgreementResponseModel();
        $ownRows = $responseModel->select('id')->where('service_agreement_id', $agreement['id'])->findAll();
        $ownRowIds = array_column($ownRows, 'id');

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
            // تشخيص مؤقت -- يُحذف فور ما نلقى سبب الرفض الحقيقي (بلاغ حي: بيانات
            // تبدو مكتملة بالواجهة لكن هذا الفحص يرفضها رغم ذلك)
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'يرجى الرد (موافق أو غير موافق) على كل بند بالاتفاقية قبل الإرسال.',
                'debug'   => [
                    'own_row_ids'      => $ownRowIds,
                    'own_row_ids_type' => array_map('gettype', $ownRowIds),
                    'incoming_row_ids' => array_keys($incomingById),
                    'unanswered_ids'   => array_values($unanswered),
                    'raw_rows_from_request' => $data['rows'] ?? null,
                ],
            ]);
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
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'حدث خطأ أثناء حفظ الاتفاقية. حاول مرة أخرى.']);
        }

        (new MissionModel())->syncCurrentStage($missionId);

        return $this->response->setJSON(['success' => true]);
    }
}
