<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MissionStageHistoryModel;
use App\Models\DepartmentModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\AuditLogModel;

class MissionController extends BaseController
{
    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

    /** GET /dashboard/new-task — نموذج بدء مهمة جديدة (Server-Rendered) */
    public function create()
    {
        $deptModel = new DepartmentModel();
        $mainDepts = $deptModel->mainDepartments();

        $selectedDeptId = (int) ($this->request->getGet('main_dept_id') ?: 0);
        $subDepts = $selectedDeptId ? $deptModel->subDepartments($selectedDeptId) : [];

        return view('dashboard/new-task/create', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'newTask',
            'currentUser'  => $this->sessionUserSummary(),
            'mainDepts'      => $mainDepts,
            'subDepts'       => $subDepts,
            'selectedDeptId' => $selectedDeptId,
            'slaSections'    => $this->slaSectionsSnapshot(),
            'years'          => ['2024', '2025', '2026', '2027'],
        ]);
    }

    /**
     * POST /dashboard/new-task — إنشاء المهمة كاملة (الخطوات الثلاث دفعة وحدة،
     * لأن الإرسال الفعلي يصير مرة وحدة بعد آخر خطوة فقط - نفس سلوك الواجهة الأصلية)
     */
    public function store()
    {
        $isJson = $this->isJsonRequest();
        $data = $isJson ? $this->request->getJSON(true) : $this->formPostToStoreData();

        $rules = [
            'main_dept_id'   => 'required|integer',
            'target_dept_id' => 'required|integer',
            'year'           => 'required',
            'procedure'      => 'required|min_length[3]',
            'reviewer_name'  => 'required|min_length[3]',
            'reviewer_email' => 'required|valid_email',
            'reviewer_phone' => 'required|min_length[8]',
            'director_name'  => 'permit_empty|min_length[3]',
        ];

        if (!$this->validateData($data ?? [], $rules)) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'errors'  => $this->validator->getErrors(),
                ]);
            }
            return redirect()->back()->withInput()->with('error', implode(' - ', $this->validator->getErrors()));
        }

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find((int) $data['target_dept_id']);

        if (!$targetDept) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'الإدارة المستهدفة غير صحيحة.',
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'الإدارة المستهدفة غير صحيحة.');
        }

        $mainDept = $deptModel->find((int) $data['main_dept_id']);
        if (!$mainDept) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'الإدارة غير صحيحة.',
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'الإدارة غير صحيحة.');
        }

        $auditDept = $deptModel->findByNameAr('المراجعة الداخلية');
        if (!$auditDept) {
            if ($isJson) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'تعذّر تحديد إدارة المراجعة الداخلية بالنظام. تواصل مع الدعم الفني.',
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'تعذّر تحديد إدارة المراجعة الداخلية بالنظام. تواصل مع الدعم الفني.');
        }

        $userId = (int) session()->get('user_id');

        $missionModel      = new MissionModel();
        $stageHistoryModel = new MissionStageHistoryModel();
        $slaModel          = new ServiceAgreementModel();
        $slaResponseModel  = new ServiceAgreementResponseModel();

        $missionCode = $missionModel->generateMissionCode($mainDept['name_ar']);

        $db = \Config\Database::connect();
        $db->transStart();

        $missionId = $missionModel->insert([
            'mission_code'         => $missionCode,
            'title'                => 'مراجعة داخلية — ' . $targetDept['name_ar'],
            'year'                 => $data['year'],
            'audit_department_id'  => $auditDept['id'],
            'target_department_id' => $targetDept['id'],
            'mission_head_id'      => $userId,
            'reviewer_name'        => $data['reviewer_name'],
            'reviewer_email'       => $data['reviewer_email'],
            'reviewer_phone'       => $data['reviewer_phone'],
            'director_name'        => $data['director_name'] ?? null,
            'current_stage'        => 1,
            'status'               => 'active',
            'procedure_note'       => $data['procedure'],
            'created_by'           => $userId,
        ], true);

        $stageHistoryModel->openStage($missionId, 1, $userId);
        (new AuditLogModel())->log($missionId, $userId, 'mission_created', 'mission', $missionId, $missionCode . ' — ' . $targetDept['name_ar']);

        // اتفاقية مستوى الخدمة - رأس الاتفاقية + كل بنودها (Snapshot) بحالة فارغة
        // (تُملأ فعليًا لاحقًا من قِبل ممثل الإدارة المستهدفة) -- قنوات الاتصال
        // المعتمدة (خطوة 2 بالويزارد) تُحفظ هنا لأنها بيانات يعبّئها فريق
        // المراجعة وقت إنشاء المهمة، لا الإدارة المستهدفة لاحقًا
        $channels = $data['channels'] ?? [];
        $slaId = $slaModel->insert([
            'mission_id' => $missionId,
            'status'     => 'pending',
            'channel_email'       => !empty($channels['email']['active']) ? 1 : 0,
            'channel_email_value' => $channels['email']['value'] ?? null,
            'channel_memo'        => !empty($channels['memo']['active']) ? 1 : 0,
            'channel_memo_value'  => $channels['memo']['value'] ?? null,
            'channel_phone'       => !empty($channels['phone']['active']) ? 1 : 0,
            'channel_phone_value' => $channels['phone']['value'] ?? null,
        ], true);

        $slaSections = $this->slaSectionsSnapshot();
        $sortOrder = 0;
        $responseRows = [];
        foreach ($slaSections as $sec) {
            foreach ($sec['rows'] as $row) {
                $sortOrder++;
                $responseRows[] = [
                    'service_agreement_id' => $slaId,
                    'section_title'        => $sec['title'],
                    'row_text'              => $row,
                    'agree'                 => 0,
                    'disagree'              => 0,
                    'note'                  => null,
                    'sort_order'            => $sortOrder,
                ];
            }
        }
        $slaResponseModel->insertBatch($responseRows);

        $db->transComplete();

        if ($db->transStatus() === false) {
            if ($isJson) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ المهمة. حاول مرة أخرى.',
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء حفظ المهمة. حاول مرة أخرى.');
        }

        $missionModel->syncCurrentStage($missionId);

        if ($isJson) {
            return $this->response->setJSON([
                'success'      => true,
                'mission_code' => $missionCode,
                'redirect'     => base_url('dashboard'),
            ]);
        }
        return redirect()->to(base_url('dashboard/sent-tasks/' . $missionId))->with('success', 'تم إنشاء المهمة بنجاح — رقمها: ' . $missionCode);
    }

    /** يحوّل حقول نموذج HTML عادي (channel_email_active, channel_email_value, ...)
     *  لنفس شكل $data المتوقَّع من الفرع JSON الأصلي (channels: {email: {active, value}, ...}) */
    private function formPostToStoreData(): array
    {
        $post = $this->request->getPost();
        $channels = [];
        foreach (['email', 'memo', 'phone'] as $ch) {
            $channels[$ch] = [
                'active' => !empty($post['channel_' . $ch . '_active']),
                'value'  => $post['channel_' . $ch . '_value'] ?? '',
            ];
        }
        $post['channels'] = $channels;
        return $post;
    }

    /**
     * بنود اتفاقية مستوى الخدمة الثابتة - نفس SLA_SECTIONS بالواجهة الأصلية بالضبط
     */
    private function slaSectionsSnapshot(): array
    {
        return [
            [
                'title' => 'الحصول على المعلومات والتقارير والاجتماعات',
                'rows'  => [
                    'الوصول غير المقيد لجميع المعلومات والبيانات والوثائق والمستندات (اليدوية والإلكترونية) الخاصة لدى الجهة الخاضعة للمراجعة أو المحفوظة في أي جهة في الوزارة.',
                    'تعيين منسق من الإدارة ليكون حلقة الوصل مع فريق المراجعة الداخلية.',
                    'الحصول على المتطلبات الرئيسة الأولية بحد أقصى 5 أيام عمل.',
                ],
            ],
            [
                'title' => 'العمل الميداني',
                'rows'  => [
                    'الحصول على متطلبات المراجعة الداخلية خلال العمل الميداني كحد أقصى يومين.',
                    'تعيين مكان للمراجع الداخلي خلال العمل الميداني داخل الإدارة.',
                ],
            ],
            [
                'title' => 'إصدار التقارير المبدئي والنهائي',
                'rows'  => [
                    'تحديد اجتماع للمناقشة النهائية للملاحظات المكتوبة.',
                    'الرد على التقرير النهائي الأولي خلال عشر أيام عمل.',
                    'إصدار التقرير النهائي وإرساله للإدارات، وقائمة التوزيع.',
                ],
            ],
        ];
    }
}
