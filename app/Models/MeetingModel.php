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
        return $this->select('meetings.*')
            ->join('missions', 'missions.id = meetings.mission_id')
            ->groupStart()
                ->where('missions.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = missions.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->where('meetings.status', 'scheduled')
            ->orderBy('meetings.meeting_date', 'ASC')
            ->findAll();
    }

    public function countScheduledForUser(int $userId): int
    {
        return count($this->scheduledMeetingsForUser($userId));
    }

    /**
     * أقرب اجتماع مؤكد (له تاريخ فعلي، اليوم أو مستقبلًا) من بين مجموعة مهام —
     * يُستخدم لتنبيه الصفحة الرئيسية بعد تأكيد موعد اجتماع عبر شات "جدولة اجتماع".
     * شرط meeting_date >= اليوم يستثني تلقائيًا اجتماعات findOrCreateForMission()
     * الفارغة (meeting_date = null) بدون الحاجة لشرط IS NOT NULL منفصل.
     */
    public function confirmedUpcomingForMissions(array $missionIds): ?array
    {
        if (empty($missionIds)) {
            return null;
        }

        return $this->whereIn('mission_id', $missionIds)
            ->where('status', 'scheduled')
            ->where('meeting_date >=', date('Y-m-d'))
            ->orderBy('meeting_date', 'ASC')
            ->first() ?: null;
    }
}
