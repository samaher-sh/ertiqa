<?php

namespace App\Models;

use CodeIgniter\Model;

class MissionModel extends Model
{
    protected $table         = 'missions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'mission_code', 'title', 'year', 'audit_department_id', 'target_department_id',
        'mission_head_id', 'dept_director_id', 'coordinator_id',
        'reviewer_name', 'reviewer_email', 'reviewer_phone', 'director_name',
        'current_stage', 'status', 'procedure_note', 'created_by',
    ];

    /**
     * المهام "النشطة" (status=active) المرئية لمستخدم معيّن
     */
    public function activeMissionsForUser(int $userId): array
    {
        return $this->select('missions.*, td.name_ar as target_department_name')
            ->join('departments td', 'td.id = missions.target_department_id')
            ->groupStart()
                ->where('missions.mission_head_id', $userId)
                ->orGroupStart()
                    ->join('audit_team_members atm', 'atm.mission_id = missions.id', 'left')
                    ->where('atm.user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->where('missions.status', 'active')
            ->orderBy('missions.created_at', 'DESC')
            ->findAll();
    }

    public function countActiveForUser(int $userId): int
    {
        return count($this->activeMissionsForUser($userId));
    }

    /**
     * missions.current_stage لا يتحدّث تلقائيًا بأي مكان بالنظام (يبقى 1 دائمًا منذ
     * الإنشاء) — هذي الدالة تحدد فعليًا أول مرحلة غير مكتملة بالمهمة من واقع قاعدة
     * البيانات نفسها. مصدر واحد مشترك يستخدمه SentTasksController (زر إكمال
     * الحقول/عرض) و DashboardController (شارة المرحلة بقائمة المراسلات المشتركة)
     * و ReportController (نفس منطق realCompletionStatus) لتبقى النتائج متوافقة دومًا.
     */
    public function computeRealNextStage(int $missionId): int
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

    /**
     * يحسب المرحلة الحقيقية ويكتبها فعليًا بعمود missions.current_stage (بالإضافة
     * لكونها محسوبة حيًا بكل مكان يستخدم computeRealNextStage) — عشان أي استعلام
     * SQL مباشر على العمود يعكس الواقع أيضًا، ويشتغل عليها countInStageForUser()
     * (إحصائية "قيد المراجعة" بالصفحة الرئيسية) اللي تعتمد على العمود الخام تحديدًا
     */
    public function syncCurrentStage(int $missionId): int
    {
        $stage = $this->computeRealNextStage($missionId);
        $this->update($missionId, ['current_stage' => $stage]);
        return $stage;
    }

    public function countInStageForUser(int $userId, int $stage): int
    {
        return count(array_filter(
            $this->activeMissionsForUser($userId),
            fn($m) => (int) $m['current_stage'] === $stage
        ));
    }

    /** اختصارات الإدارات الرئيسية لبناء رقم المهمة — نفس القائمة المستخدمة بالواجهة بالضبط */
    private const DEPT_ABBR = [
        'الإدارة التنفيذية'                 => 'EXE',
        'الأبحاث والابتكار'                  => 'RI',
        'الشؤون المالية والإدارية'           => 'FA',
        'الموارد البشرية'                    => 'HR',
        'تقنية المعلومات والاتصالات'         => 'ICT',
        'الشؤون الأكاديمية والتدريب'         => 'AT',
        'شؤون التمريض'                       => 'NUR',
        'العمليات'                           => 'OPS',
        'شؤون المرضى'                        => 'PA',
        'الخدمات الطبية والإكلينيكية'        => 'MED',
    ];

    /**
     * المهام الموجّهة فعليًا لإدارة مستخدم "الإدارة محل المراجعة" (منسق/مدير إدارة)
     * — تُجلب حسب target_department_id الحقيقي بالمهمة، مقارنةً بـ department_id
     * الحقيقي للمستخدم بجدول users. هذا يستبدل أي مطابقة نصية باسم الإدارة كانت
     * تُستخدم بالواجهة سابقًا (Mock).
     */
    public function missionsForTargetDepartment(int $departmentId): array
    {
        return $this->select('missions.*, ad.name_ar as audit_department_name, td.name_ar as target_department_name, u.full_name as mission_head_name')
            ->join('departments ad', 'ad.id = missions.audit_department_id', 'left')
            ->join('departments td', 'td.id = missions.target_department_id', 'left')
            ->join('users u', 'u.id = missions.mission_head_id', 'left')
            ->where('missions.target_department_id', $departmentId)
            ->orderBy('missions.created_at', 'DESC')
            ->findAll();
    }

    /**
     * تفاصيل مهمة واحدة كاملة (لمعاينة "طلب المراجعة الداخلية" بصفحة التقرير النهائي)
     */
    public function findWithDetails(int $missionId): ?array
    {
        return $this->select('missions.*, ad.name_ar as audit_department_name, td.name_ar as target_department_name')
            ->join('departments ad', 'ad.id = missions.audit_department_id', 'left')
            ->join('departments td', 'td.id = missions.target_department_id', 'left')
            ->where('missions.id', $missionId)
            ->first();
    }

    /**
     * يولّد كود مهمة فريد بصيغة [اختصار الإدارة][4 أرقام تسلسلية]
     * مثال: HR0001, MED0042 — الترقيم تسلسلي داخل كل إدارة لحالها (مو عام لكل النظام)
     *
     * @param string $mainDeptNameAr اسم الإدارة الرئيسية بالعربي بالضبط كما بجدول departments
     */
    public function generateMissionCode(string $mainDeptNameAr): string
    {
        $abbr = self::DEPT_ABBR[$mainDeptNameAr] ?? 'AUD';

        // نعد كم مهمة موجودة أصلاً بنفس الاختصار (يعني بنفس الإدارة) لنولّد الرقم التالي
        $count = $this->like('mission_code', $abbr, 'after')->countAllResults();
        $seq   = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        $code  = $abbr . $seq;

        // احتياط لو صار تعارض نادر
        while ($this->where('mission_code', $code)->first()) {
            $seq  = str_pad((string) ((int) $seq + 1), 4, '0', STR_PAD_LEFT);
            $code = $abbr . $seq;
        }

        return $code;
    }
}
