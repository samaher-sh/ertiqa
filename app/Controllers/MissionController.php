<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\MissionStageHistoryModel;
use App\Models\DepartmentModel;
use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\DocumentRequestModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use App\Models\NotificationModel;

class MissionController extends BaseController
{
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
            'director_name'  => 'permit_empty|min_length[3]',
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

        $mainDept = $deptModel->find((int) $data['main_dept_id']);
        if (!$mainDept) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'الإدارة غير صحيحة.',
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
        $now    = date('Y-m-d H:i:s');

        $missionModel      = new MissionModel();
        $stageHistoryModel = new MissionStageHistoryModel();
        $slaModel          = new ServiceAgreementModel();
        $slaResponseModel  = new ServiceAgreementResponseModel();
        $docRequestModel   = new DocumentRequestModel();
        $userModel         = new UserModel();
        $notificationModel = new NotificationModel();

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
        (new AuditLogModel())->log($missionId, $userId, 'mission_created', 'mission', $missionId);

        // إشعار حقيقي لمنسّق الإدارة المستهدفة بمهمة مراجعة جديدة — يوصل لصفحة الإخطارات
        // وبانر التنبيه بالصفحة الرئيسية (homeStats.latest_notification)
        $coordinator = $userModel
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.department_id', $targetDept['id'])
            ->where('roles.code', 'dept_coordinator')
            ->first();

        if ($coordinator) {
            $notificationModel->insert([
                'user_id'    => $coordinator['id'],
                'mission_id' => $missionId,
                'type'       => 'mission_created',
                'title'      => 'مهمة مراجعة جديدة',
                'body'       => 'تم إنشاء مهمة مراجعة داخلية جديدة (' . $missionCode . ') لإدارتكم.',
                'channel'    => 'system',
                'is_read'    => 0,
                'sent_at'    => $now,
                'created_at' => $now,
            ]);
        }

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
}
