<?php

namespace App\Controllers;

use App\Models\AuditNoteModel;
use App\Models\MissionModel;
use App\Models\ReportModel;

/**
 * صفحة "التوصيات" — متابعة تنفيذ توصيات الملاحظات المعتمدة بالتقرير النهائي
 * (نفس حقول "نموذج مرحلة متابعة تنفيذ التوصيات" الرسمي بالضبط). تظهر فقط
 * للملاحظات اللي رئيس إدارة المراجعة الداخلية اختار لها "تضاف" صراحة
 * بالتقرير المعتمد (نفس فلتر قسم "الملاحظات المعتمدة" بصفحة الملاحظات).
 */
class RecommendationController extends BaseController
{
    private function roleFlags(): array
    {
        $roleCode = session()->get('role_code');
        return [
            'isDeptSide'   => in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true),
            'isAuditMember'=> $roleCode === 'audit_member',
            'isAuditHead'  => $roleCode === 'audit_head',
        ];
    }

    /** التحقق من صلاحية الوصول للمهمة — نفس حدود missionsForCurrentSession() المستخدَمة بمنتقي المهمة */
    private function assertMissionAccess(int $missionId): array
    {
        $mission = (new MissionModel())->find($missionId);
        if (!$mission) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('المهمة غير موجودة.');
        }
        $allowedIds = array_map('intval', array_column($this->missionsForCurrentSession(), 'id'));
        if (!in_array($missionId, $allowedIds, true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذه المهمة.');
        }
        return $mission;
    }

    /** GET /dashboard/recommendations — صفحة القائمة الحقيقية (Server-Rendered) */
    public function index()
    {
        $flags = $this->roleFlags();
        if (!$flags['isDeptSide'] && !$flags['isAuditMember'] && !$flags['isAuditHead']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذي الصفحة.');
        }

        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $mission = null;
        $report = null;
        $items = [];
        if ($missionId) {
            $this->assertMissionAccess($missionId);
            foreach ($missions as $m) {
                if ((int) $m['id'] === $missionId) { $mission = $m; break; }
            }
            $report = (new ReportModel())->where('mission_id', $missionId)->first();

            // نفس فلتر قسم "الملاحظات المعتمدة" بصفحة الملاحظات بالضبط: التقرير
            // معتمد فعليًا من الرئيس، والملاحظة اختار لها "تضاف" صراحة
            $reportApproved = (bool) ($report['head_approved_at'] ?? null);
            if ($reportApproved) {
                $items = array_values(array_filter(
                    (new AuditNoteModel())->forMission($missionId),
                    fn ($i) => (int) ($i['add_to_report'] ?? 0) === 1
                ));
            }
        }

        return view('dashboard/recommendations/index', [
            'navItems'          => $this->navItemsForCurrentSession(),
            'migratedKeys'      => $this->migratedPageKeys(),
            'activeNavKey'      => 'recommendations',
            'currentUser'       => $this->sessionUserSummary(),
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'mission'           => $mission,
            'report'            => $report,
            'items'             => $items,
            'canEditDeptReply'  => $flags['isDeptSide'],
            'canEditFollowUp'   => $flags['isAuditMember'],
        ]);
    }

    /** POST /dashboard/recommendations/api/save — حفظ دفعة وحدة، حسب الدور */
    public function save()
    {
        $flags = $this->roleFlags();
        $missionId = (int) $this->request->getPost('mission_id');
        $this->assertMissionAccess($missionId);

        $noteModel = new AuditNoteModel();

        if ($flags['isDeptSide']) {
            $replies = $this->request->getPost('dept_reply') ?? [];
            $noteModel->updateDeptReplies($missionId, is_array($replies) ? $replies : []);
        } elseif ($flags['isAuditMember']) {
            $rows = $this->request->getPost('follow_up') ?? [];
            $noteModel->updateFollowUpFields($missionId, is_array($rows) ? $rows : []);
        } else {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        return redirect()->to(base_url('dashboard/recommendations') . '?mission_id=' . $missionId)->with('success', 'تم حفظ البيانات بنجاح.');
    }
}
