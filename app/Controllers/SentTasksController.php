<?php

namespace App\Controllers;

use App\Models\MissionStageHistoryModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\AuditNoteModel;
use App\Models\AuditLogModel;
use App\Models\MissionModel;
use App\Models\ServiceAgreementModel;

class SentTasksController extends BaseController
{
    /** المهمة لو المستخدم الحالي فعليًا طرف فيها (مراجع أو الإدارة المستهدفة)، وإلا null */
    private function missionForParty(int $missionId): ?array
    {
        $missionModel = new MissionModel();
        $mission = $missionModel->find($missionId);
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

    /**
     * GET /dashboard/sent-tasks/api/timeline?mission_id=X — السجل الزمني الفعلي لمهمة،
     * متاح فقط لطرفي المهمة (المراجع والإدارة الخاضعة للمراجعة)
     * المصدر الحقيقي هو audit_logs (حدث منفصل لكل فعل مهم: إنشاء مهمة، رفع مستندات،
     * تعبئة اتفاقية مستوى الخدمة، حفظ مصفوفة مخاطر، رسائل/اقتراح/تأكيد/إلغاء موعد
     * الاجتماع، حفظ ملخص اجتماع، إضافة ملاحظة، اعتماد تقرير) — أدق من
     * mission_stage_history اللي مخصصة لتتبع دخول رقم المرحلة فقط (تبقى مستخدمة
     * لحساب next_stage تحت، بما إنها مرتبطة بمنطق SLA المرحلي)
     */
    public function timeline()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'events' => [], 'next_stage' => null]);

        if (!$this->missionForParty($missionId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية الوصول لسجل هذه المهمة.']);
        }

        $events = (new AuditLogModel())->forMission($missionId);

        return $this->response->setJSON([
            'success'    => true,
            'events'     => $events,
            'next_stage' => $this->computeRealNextStage($missionId),
        ]);
    }

    /**
     * missions.current_stage لا يتحدّث تلقائيًا حاليًا بأي مكان بالنظام (يبقى 1 دائمًا)،
     * فزر "إكمال الحقول" ما كان له معنى حقيقي يعتمد عليه. هذي الدالة تحدد فعليًا أول
     * مرحلة غير مكتملة بالمهمة من واقع قاعدة البيانات — نفس منطق
     * ReportController::realCompletionStatus() بالضبط لتبقى النتيجتان متوافقتين.
     */
    private function computeRealNextStage(int $missionId): int
    {
        $agreement = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        if (!$agreement || $agreement['status'] !== 'submitted') return 2;

        $riskCount = (new RiskMatrixItemModel())->where('mission_id', $missionId)->countAllResults();
        if ($riskCount === 0) return 3;

        $meeting = (new MeetingModel())->where('mission_id', $missionId)->first();
        if (!$meeting || !$meeting['meeting_date']) return 4;

        $obsCount = (new AuditNoteModel())->where('mission_id', $missionId)->countAllResults();
        if ($obsCount === 0) return 5;

        return 7;
    }
}
