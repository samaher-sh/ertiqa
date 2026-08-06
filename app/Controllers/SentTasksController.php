<?php

namespace App\Controllers;

use App\Models\MissionStageHistoryModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\AuditNoteModel;
use App\Models\AuditLogModel;

class SentTasksController extends BaseController
{
    /**
     * GET /dashboard/sent-tasks/api/timeline?mission_id=X — السجل الزمني الفعلي لمهمة
     * المصدر الحقيقي هو audit_logs (حدث منفصل لكل فعل مهم: إنشاء مهمة، رفع مستندات،
     * حفظ مصفوفة مخاطر، اقتراح/تأكيد/إلغاء موعد، حفظ ملخص اجتماع، إضافة ملاحظة، اعتماد
     * تقرير) — أدق من mission_stage_history اللي مخصصة لتتبع دخول رقم المرحلة فقط
     * (تبقى مستخدمة لحساب next_stage تحت، بما إنها مرتبطة بمنطق SLA المرحلي)
     */
    public function timeline()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'events' => [], 'next_stage' => null]);

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
        $riskCount = (new RiskMatrixItemModel())->where('mission_id', $missionId)->countAllResults();
        if ($riskCount === 0) return 3;

        $meeting = (new MeetingModel())->where('mission_id', $missionId)->first();
        if (!$meeting || !$meeting['meeting_date']) return 4;

        $obsCount = (new AuditNoteModel())->where('mission_id', $missionId)->countAllResults();
        if ($obsCount === 0) return 5;

        return 7;
    }
}
