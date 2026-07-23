<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MissionStageHistoryModel;
use App\Models\DepartmentModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\DocumentRequestModel;

class MissionController extends BaseController
{
    /**
     * GET /dashboard/new-task — عرض نموذج "بدء مهمة"
     */
    public function newTask()
    {
        $deptModel = new DepartmentModel();

        $mainDepts = $deptModel->mainDepartments();

        // نجهّز خريطة {main_dept_id: [subs...]} عشان نبعثها كـ JSON واحد للـ JS
        // (Cascading Select بدون طلبات إضافية للسيرفر مع كل اختيار)
        $subsByParent = [];
        foreach ($mainDepts as $main) {
            $subsByParent[$main['id']] = $deptModel->subDepartments((int) $main['id']);
        }

        $today = date('Y');

        // بنود اتفاقية مستوى الخدمة - محتوى ثابت (نفس SLA_SECTIONS بالواجهة الأصلية بالضبط)
        $slaSections = [
            [
                'title' => 'الحصول على المعلومات والتقارير والاجتماعات',
                'rows'  => [
                    'الوصول غير المقيد لجميع المعلومات والبيانات والوثائق والمستندات (اليدوية والإلكترونية) الخاصة لدى الجهة الخاضعة للمراجعة.',
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
                'title' => 'إصدار التقارير البدئي والنهائي',
                'rows'  => [
                    'تحديد اجتماع للمناقشة النهائية للملاحظات المكتوبة.',
                    'الرد على التقرير النهائي الأولي خلال عشر أيام عمل.',
                    'عدم الاعتراض على نشر التقرير النهائي بعد انتهاء مدة الرد.',
                ],
            ],
        ];

        return view('dashboard/new-task', [
            'slaSections'     => $slaSections,
            'full_name'       => session()->get('full_name'),
            'role_code'       => session()->get('role_code'),
            'role_name'       => session()->get('role_name'),
            'department_name' => session()->get('department_name'),
            'national_id'     => session()->get('national_id'),
            'isAuditMember'   => session()->get('role_code') === 'audit_member',
            'navItems'        => $this->navItemsForCurrentSession(),
            'mainDepts'       => $mainDepts,
            'subsByParent'    => $subsByParent,
            'years'           => ['2024', '2025', '2026', '2027'],
            'currentYear'     => $today,
        ]);
    }

    /**
     * POST /dashboard/new-task — إنشاء المهمة كاملة (الخطوات الثلاث دفعة وحدة،
     * لأن الإرسال الفعلي يصير مرة وحدة بعد آخر خطوة فقط - نفس سلوك الواجهة الأصلية)
     */
    public function store()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'main_dept_id'   => 'required|integer',
            'target_dept_id' => 'required|integer',
            'year'           => 'required',
            'procedure'      => 'required|min_length[3]',
            'reviewer_name'  => 'required|min_length[3]',
            'reviewer_email' => 'required|valid_email',
            'reviewer_phone' => 'required|min_length[8]',
            'director_name'  => 'required|min_length[3]',
        ];

        if (!$this->validateData($data ?? [], $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        // قائمة المستندات - لازم مستند واحد على الأقل، وكل الأسماء غير فاضية (نفس page3Valid بالأصل)
        $docNames = array_values(array_filter(array_map('trim', $data['doc_names'] ?? [])));
        if (count($docNames) === 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'يرجى إضافة مستند واحد على الأقل بقائمة المستندات.',
            ]);
        }

        $deptModel = new DepartmentModel();
        $targetDept = $deptModel->find((int) $data['target_dept_id']);

        if (!$targetDept) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'الإدارة المستهدفة غير صحيحة.',
            ]);
        }

        $auditDept = $deptModel->findByNameAr('المراجعة الداخلية');
        if (!$auditDept) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'تعذّر تحديد إدارة المراجعة الداخلية بالنظام. تواصل مع الدعم الفني.',
            ]);
        }

        $userId = (int) session()->get('user_id');

        $missionModel      = new MissionModel();
        $stageHistoryModel = new MissionStageHistoryModel();
        $slaModel          = new ServiceAgreementModel();
        $slaResponseModel  = new ServiceAgreementResponseModel();
        $docRequestModel   = new DocumentRequestModel();

        $missionCode = $missionModel->generateMissionCode($data['year']);

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
            'director_name'        => $data['director_name'],
            'current_stage'        => 1,
            'status'               => 'active',
            'procedure_note'       => $data['procedure'],
            'created_by'           => $userId,
        ], true);

        $stageHistoryModel->openStage($missionId, 1, $userId);

        // اتفاقية مستوى الخدمة - رأس الاتفاقية + كل بنودها (Snapshot) بحالة فارغة
        // (تُملأ فعليًا لاحقًا من قِبل ممثل الإدارة المستهدفة)
        $slaId = $slaModel->insert(['mission_id' => $missionId, 'status' => 'pending'], true);

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

        // قائمة المستندات المطلوبة
        $now = date('Y-m-d H:i:s');
        $docRows = [];
        foreach ($docNames as $i => $name) {
            $docRows[] = [
                'mission_id' => $missionId,
                'doc_name'   => $name,
                'sort_order' => $i + 1,
                'created_at' => $now,
            ];
        }
        $docRequestModel->insertBatch($docRows);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المهمة. حاول مرة أخرى.',
            ]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'mission_code' => $missionCode,
            'redirect'     => base_url('dashboard'),
        ]);
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
                    'الوصول غير المقيد لجميع المعلومات والبيانات والوثائق والمستندات (اليدوية والإلكترونية) الخاصة لدى الجهة الخاضعة للمراجعة.',
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
                'title' => 'إصدار التقارير البدئي والنهائي',
                'rows'  => [
                    'تحديد اجتماع للمناقشة النهائية للملاحظات المكتوبة.',
                    'الرد على التقرير النهائي الأولي خلال عشر أيام عمل.',
                    'عدم الاعتراض على نشر التقرير النهائي بعد انتهاء مدة الرد.',
                ],
            ],
        ];
    }

    /**
     * يبني عناصر القائمة الجانبية حسب دور الجلسة الحالية (نفس منطق DashboardController)
     */
    private function navItemsForCurrentSession(): array
    {
        return $this->buildNavItems();
    }

    private function buildNavItems(): array
    {
        $roleCode  = session()->get('role_code');
        $isPresident = $roleCode === 'top_management';
        $isHrDept    = in_array($roleCode, ['hr_coordinator', 'dept_manager', 'specialized_manager'], true);
        $isAuditHead = $roleCode === 'audit_head';

        $icon = fn(string $path) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';

        $all = [
            'home'           => ['label' => 'الرئيسية',          'desc' => 'Dashboard',        'url' => base_url('dashboard'),               'icon' => $icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>')],
            'newTask'        => ['label' => 'بدء مهمة',           'desc' => 'New Audit Task',   'url' => base_url('dashboard/new-task'),      'icon' => $icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>')],
            'riskMatrix'     => ['label' => 'مصفوفة المخاطر',     'desc' => 'Risk Matrix',      'url' => base_url('dashboard/risk-matrix'),   'icon' => $icon('<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>')],
            'meetingSummary' => ['label' => 'ملخص اجتماع',        'desc' => 'Meeting Summary',  'url' => base_url('dashboard/meetings'),      'icon' => $icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')],
            'observations'   => ['label' => 'الملاحظات',          'desc' => 'Observations',     'url' => base_url('dashboard/observations'),  'icon' => $icon('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>')],
            'finalReports'   => ['label' => 'تقرير نهائي',        'desc' => 'Final Reports',    'url' => base_url('dashboard/reports'),       'icon' => $icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>')],
            'sentTasks'      => ['label' => 'المراسلات المشتركة', 'desc' => 'Sent Tasks',       'url' => base_url('dashboard/sent-tasks'),    'icon' => $icon('<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>')],
        ];

        $keys = array_keys($all);
        if ($isPresident) {
            $keys = ['home', 'finalReports'];
        } elseif ($isHrDept) {
            $keys = ['home', 'meetingSummary', 'observations', 'finalReports', 'sentTasks'];
        } elseif ($isAuditHead) {
            $keys = array_diff($keys, ['newTask']);
        }

        $result = [];
        foreach ($keys as $k) {
            if (isset($all[$k])) {
                $result[] = array_merge(['key' => $k], $all[$k]);
            }
        }
        return $result;
    }
}
