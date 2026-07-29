<?php

namespace App\Controllers;

use App\Models\MissionStageHistoryModel;

class SentTasksController extends BaseController
{
    /** GET /dashboard/sent-tasks/api/timeline?mission_id=X — السجل الزمني الفعلي لمهمة */
    public function timeline()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) return $this->response->setJSON(['success' => true, 'events' => []]);

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

        return $this->response->setJSON(['success' => true, 'events' => $events]);
    }
}
