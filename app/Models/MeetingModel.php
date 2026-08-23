<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingModel extends Model
{
    protected $table         = 'meetings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_id', 'title', 'objective', 'meeting_code', 'meeting_date', 'meeting_time',
        'location', 'meeting_type', 'minutes_text', 'status', 'created_by',
    ];

    /** الاجتماع الأول (التمهيدي) المرتبط بمهمة - نفس مفهوم "ملخص الاجتماع" بصفحة واحدة لكل مهمة */
    public function firstForMission(int $missionId): ?array
    {
        return $this->where('mission_id', $missionId)->orderBy('id', 'ASC')->first() ?: null;
    }

    public function findOrCreateForMission(int $missionId, int $userId): array
    {
        $existing = $this->firstForMission($missionId);
        if ($existing) {
            return $existing;
        }

        $code = 'M-' . str_pad((string) ($this->countAllResults() + 1), 3, '0', STR_PAD_LEFT);
        $id = $this->insert([
            'mission_id'   => $missionId,
            'meeting_code' => $code,
            'meeting_date' => null,
            'meeting_time' => null,
            'location'     => null,
            'meeting_type' => 'in_person',
            'status'       => 'scheduled',
            'created_by'   => $userId,
        ], true);

        return $this->find($id);
    }

    public function scheduledMeetingsForUser(int $userId): array
    {
        return $this->select('meetings.*, missions.mission_code, missions.title as mission_title')
            ->join('missions', 'missions.id = meetings.mission_id')
            ->groupStart()
                ->where('missions.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = missions.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->where('meetings.status', 'scheduled')
            ->where('meetings.meeting_date IS NOT NULL')
            ->orderBy('meetings.meeting_date', 'ASC')
            ->findAll();
    }

    public function countScheduledForUser(int $userId): int
    {
        return count($this->scheduledMeetingsForUser($userId));
    }

    /**
     * الاجتماعات المجدولة (status='scheduled') لمجموعة مهام محددة — تُستخدم لمستخدمي
     * الإدارة الخاضعة للمراجعة، اللي يرتبطون بالمهمة عبر target_department_id
     * لا mission_head_id/audit_team_members (شرط scheduledMeetingsForUser أعلاه
     * ما يشملهم إطلاقًا)
     */
    public function scheduledMeetingsForMissions(array $missionIds): array
    {
        if (empty($missionIds)) {
            return [];
        }

        return $this->select('meetings.*, missions.mission_code, missions.title as mission_title')
            ->join('missions', 'missions.id = meetings.mission_id')
            ->whereIn('meetings.mission_id', $missionIds)
            ->where('meetings.status', 'scheduled')
            ->where('meetings.meeting_date IS NOT NULL')
            ->orderBy('meetings.meeting_date', 'ASC')
            ->findAll();
    }

    /**
     * كل الاجتماعات المؤكدة (لها تاريخ فعلي، اليوم أو مستقبلًا) من بين مجموعة مهام --
     * تُستخدم لقائمة إخطارات الصفحة الرئيسية (كل مهمة معها موعد مؤكد تظهر كإخطار
     * مستقل، مو أقرب واحد فقط). شرط meeting_date >= اليوم يستثني تلقائيًا اجتماعات
     * findOrCreateForMission() الفارغة (meeting_date = null) بدون شرط IS NOT NULL منفصل.
     */
    public function confirmedUpcomingListForMissions(array $missionIds): array
    {
        if (empty($missionIds)) {
            return [];
        }

        return $this->select('meetings.*, missions.mission_code')
            ->join('missions', 'missions.id = meetings.mission_id')
            ->whereIn('meetings.mission_id', $missionIds)
            ->where('meetings.status', 'scheduled')
            ->where('meetings.meeting_date >=', date('Y-m-d'))
            ->orderBy('meetings.meeting_date', 'ASC')
            ->findAll();
    }
}
