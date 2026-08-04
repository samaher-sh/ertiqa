<?php

namespace App\Controllers;

use App\Models\MissionStageHistoryModel;
use App\Models\RiskMatrixItemModel;
use App\Models\MeetingModel;
use App\Models\AuditNoteModel;

class SentTasksController extends BaseController
{
    /** GET /dashboard/sent-tasks/api/timeline?mission_id=X — السجل الزمني الفعلي لمهمة */
    public function timeline()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'events' => [], 'next_stage' => null]);

        $history = (new MissionStageHistoryModel())
            ->select('mission_stage_history.*, users.full_name as user_name')
            ->join('users', 'users.id = mission_stage_history.responsible_user_id', 'left')
            ->where('mission_id', $missionId)
            ->orderBy('entered_at', 'ASC')
            ->findAll();

        $stageNames = [1 => 'طلب المراجعة الداخلية', 2 => 'اتفاقية مستوى الخدمة / المستندات', 3 => 'مصفوفة المخاطر', 4 => 'ملخص الاجتماع', 5 => 'الملاحظات', 6 => 'التوقيع', 7 => 'التقرير النهائي'];

        $events = array_map(function ($h) use ($stageNames) {
            return [
                'stage_name' => $stageNames[$h['stage_number']] ?? ('مرحلة ' . $h['stage_number']),
                'user_name'  => $h['user_name'] ?? '—',
                'entered_at' => $h['entered_at'],
            ];
        }, $history);

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
