<?php

namespace App\Controllers;

use App\Models\RiskMatrixItemModel;
use App\Models\MissionStageHistoryModel;
use App\Models\AuditLogModel;
use App\Models\MissionModel;

class RiskMatrixController extends BaseController
{
    private function isReadOnly(): bool
    {
        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        return $isHrDept || $roleCode === 'audit_head';
    }

    private function isJsonRequest(): bool
    {
        return str_contains((string) $this->request->getHeaderLine('Content-Type'), 'application/json');
    }

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

    private function pageViewData(array $extra = []): array
    {
        return array_merge([
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'riskMatrix',
            'currentUser'  => $this->sessionUserSummary(),
        ], $extra);
    }

    /** GET /dashboard/risk-matrix — صفحة القائمة الحقيقية (Server-Rendered، قراءة فقط) */
    public function index()
    {
        $missions = $this->missionsForCurrentSession();
        $requestedId = (int) ($this->request->getGet('mission_id') ?: 0);
        $missionId = $requestedId ?: (int) ($missions[0]['id'] ?? 0);

        $rows = [];
        if ($missionId) {
            $this->assertMissionAccess($missionId);
            $rows = (new RiskMatrixItemModel())->forMission($missionId);
        }

        /* embed=1 -- الصفحة مضمَّنة بـ iframe داخل مراحل اعتماد التقرير النهائي
           لغرض المعاينة فقط، فتُجبَر على عرض فقط ويُخفى تصدير PDF الخاص بها
           (التقرير النهائي فيه تصدير واحد شامل يغطّيها) */
        $embed = $this->request->getGet('embed') === '1';

        return view('dashboard/risk-matrix/index', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'rows'              => $rows,
            'readOnly'          => $this->isReadOnly() || $embed,
            'embed'             => $embed,
        ]));
    }

    /** GET /dashboard/risk-matrix/edit?mission_id=X — نموذج تعديل الجدول كاملًا */
    public function edit()
    {
        if ($this->isReadOnly()) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $missions = $this->missionsForCurrentSession();
        $missionId = (int) ($this->request->getGet('mission_id') ?: 0);

        $aiSuggested = false;
        $rows = session()->getFlashdata('draftRows');
        if ($rows === null && $missionId) {
            $mission = $this->assertMissionAccess($missionId);
            $rows = (new RiskMatrixItemModel())->forMission($missionId);

            if (empty($rows)) {
                // أول مرة تُفتح فيها الصفحة لهذي المهمة ومافي أي خطر مسجّل بعد --
                // نقترح صفوفًا مبدئية بمجرد اختيار المهمة (تحليل بسيط قائم على
                // كلمات مفتاحية بالإدارة الخاضعة للمراجعة، مو استدعاء ذكاء
                // اصطناعي حقيقي)، بدل جدول فارغ يحتاج تعبئة يدوية كاملة
                $rows = $this->suggestRiskRows($mission['target_department_name'] ?? '', $mission['procedure_note'] ?? '');
                $aiSuggested = true;
            } elseif ($this->request->getGet('add_new') === '1') {
                // زر "إضافة مخاطر" بصفحة القائمة يوجّه هنا مباشرة كأنها بدأت
                // إضافة خطر فعليًا -- بدل ما تحتاج تضغط "إضافة خطر" يدويًا
                // بعد فتح الصفحة (لمهمة عندها مخاطر مسجّلة أصلًا)
                $rows[] = ['risk' => '', 'risk_rating' => '', 'controls' => '', 'activity_type' => ''];
            }
        }

        return view('dashboard/risk-matrix/edit', $this->pageViewData([
            'missions'          => $missions,
            'selectedMissionId' => $missionId,
            'rows'              => $rows ?? [],
            'aiSuggested'       => $aiSuggested,
        ]));
    }

    /**
     * GET /dashboard/risk-matrix/api/items?mission_id=X — جلب صفوف مهمة معيّنة
     */
    public function items()
    {
        $missionId = (int) $this->request->getGet('mission_id');
        if (!$missionId) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'مهمة غير محددة']);
        }

        $itemModel = new RiskMatrixItemModel();
        return $this->response->setJSON(['success' => true, 'items' => $itemModel->forMission($missionId)]);
    }

    /**
     * POST /dashboard/risk-matrix/api/save — حفظ كل صفوف مهمة معيّنة دفعة وحدة
     */
    public function save()
    {
        $isJson = $this->isJsonRequest();

        $roleCode = session()->get('role_code');
        $isHrDept = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        if ($isHrDept || $roleCode === 'audit_head') {
            if ($isJson) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'ليس لديك صلاحية التعديل (عرض فقط).']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية التعديل.');
        }

        $data = $isJson ? $this->request->getJSON(true) : $this->request->getPost();
        $missionId = (int) ($data['mission_id'] ?? 0);
        $rows      = $data['rows'] ?? [];

        if (!$missionId) {
            if ($isJson) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'يرجى اختيار المهمة المرتبطة أولاً.']);
            }
            return redirect()->back()->with('error', 'يرجى اختيار المهمة المرتبطة أولاً.');
        }

        if (!$isJson) {
            $this->assertMissionAccess($missionId);

            /* نموذج التعديل بدون جافاسكربت: زرّي "إضافة صف"/"حذف صف" لا يحفظان بقاعدة
               البيانات فعليًا -- فقط يعدّلان المصفوفة المؤقتة (draftRows بالجلسة) ويعيدان
               عرض نفس النموذج بالتغيير، بنفس نمط round-trip الكلاسيكي لنماذج بدون JS */
            $formAction = $data['form_action'] ?? 'save';
            if ($formAction === 'add_row') {
                $rows[] = ['risk' => '', 'risk_rating' => '', 'controls' => '', 'activity_type' => ''];
                return redirect()->to(base_url('dashboard/risk-matrix/edit?mission_id=' . $missionId))->with('draftRows', $rows);
            }
            if ($formAction === 'remove_row') {
                unset($rows[(int) ($data['remove_index'] ?? -1)]);
                $rows = array_values($rows);
                return redirect()->to(base_url('dashboard/risk-matrix/edit?mission_id=' . $missionId))->with('draftRows', $rows);
            }
        }

        $itemModel = new RiskMatrixItemModel();
        $itemModel->replaceForMission($missionId, $rows);

        $userId = (int) session()->get('user_id');
        $stageHistoryModel = new MissionStageHistoryModel();
        $alreadyLogged = $stageHistoryModel->where('mission_id', $missionId)->where('stage_number', 3)->countAllResults();
        if ($alreadyLogged === 0) {
            $stageHistoryModel->openStage($missionId, 3, $userId);
        }
        (new AuditLogModel())->log($missionId, $userId, 'risk_matrix_saved', 'risk_matrix', null, count($rows) . ' صف');
        (new MissionModel())->syncCurrentStage($missionId);

        if ($isJson) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to(base_url('dashboard/risk-matrix?mission_id=' . $missionId))->with('success', 'تم حفظ مصفوفة المخاطر بنجاح.');
    }

    /**
     * يقترح صفوف مخاطر مبدئية بناءً على الإدارة الخاضعة للمراجعة ونص "المراد
     * مناقشته" -- تحليل بسيط قائم على مطابقة كلمات مفتاحية (مو نموذج ذكاء
     * اصطناعي فعلي)، يعطي فريق المراجعة نقطة انطلاق قابلة للتعديل الكامل بدل
     * جدول فارغ. القيم المرجَعة تطابق تمامًا القوائم المعتمدة بواجهة التعديل:
     * مستوى الخطر (مرتفع/متوسط/منخفض/فرصة تحسين) وتصنيف الملاحظة (تشغيلي/
     * مالي/حوكمة/التزام وامتثال/تقني/جودة وسلامة)
     */
    private function suggestRiskRows(string $deptName, string $procedureNote = ''): array
    {
        $haystack = $deptName . ' ' . $procedureNote;

        $rules = [
            'بشري|توظيف|أداء الموظف|علاقات الموظف' => [
                ['risk' => 'تأخر إجراءات التوظيف عن المدة المعتمدة بالسياسة الداخلية', 'risk_rating' => 'مرتفع', 'activity_type' => 'تشغيلي', 'controls' => 'متابعة دورية لمؤشرات زمن التوظيف من قِبل الإدارة المعنية'],
                ['risk' => 'عدم اكتمال المستندات المطلوبة بملفات بعض الموظفين', 'risk_rating' => 'متوسط', 'activity_type' => 'التزام وامتثال', 'controls' => 'مراجعة دورية لملفات الموظفين والتأكد من اكتمال المستندات'],
                ['risk' => 'غياب آلية موحّدة لتقييم أداء الموظفين', 'risk_rating' => 'متوسط', 'activity_type' => 'حوكمة', 'controls' => 'اعتماد نموذج تقييم أداء موحّد وربطه بدورة الأداء السنوية'],
            ],
            'مالي|محاسب|إيراد|استثمار|مخزون' => [
                ['risk' => 'تأخر تسوية الفروقات المالية بين السجلات والحسابات الفعلية', 'risk_rating' => 'مرتفع', 'activity_type' => 'مالي', 'controls' => 'مطابقة دورية للحسابات مع توثيق أي فروقات وأسبابها'],
                ['risk' => 'ضعف الفصل بين المهام بدورة الصرف المالي', 'risk_rating' => 'مرتفع', 'activity_type' => 'حوكمة', 'controls' => 'مراجعة صلاحيات الاعتماد والصرف والتأكد من فصل المهام'],
                ['risk' => 'عدم مطابقة الإيرادات المسجَّلة مع الإيرادات الفعلية بشكل دوري', 'risk_rating' => 'متوسط', 'activity_type' => 'مالي', 'controls' => 'تسوية شهرية للإيرادات مع الجهات المختصة'],
            ],
            'تقنية المعلومات|أمن المعلومات|أنظمة المعلومات|تطبيقات الأعمال|البنية التحتية' => [
                ['risk' => 'ضعف إجراءات النسخ الاحتياطي واسترجاع البيانات', 'risk_rating' => 'مرتفع', 'activity_type' => 'تقني', 'controls' => 'اختبار دوري لاستعادة النسخ الاحتياطية والتحقق من اكتمالها'],
                ['risk' => 'عدم تحديث صلاحيات الوصول للأنظمة بعد تغيّر المسمى الوظيفي', 'risk_rating' => 'مرتفع', 'activity_type' => 'التزام وامتثال', 'controls' => 'مراجعة دورية لصلاحيات المستخدمين ومطابقتها مع الوضع الوظيفي الحالي'],
                ['risk' => 'غياب اختبارات دورية لخطة استمرارية الأعمال التقنية', 'risk_rating' => 'متوسط', 'activity_type' => 'تقني', 'controls' => 'وضع جدول سنوي لاختبار خطة استمرارية الأعمال وتوثيق النتائج'],
            ],
            'مشتريات|مستودعات|ممتلكات|الإمداد' => [
                ['risk' => 'عدم الالتزام بسياسة المنافسة في بعض طلبات الشراء', 'risk_rating' => 'مرتفع', 'activity_type' => 'التزام وامتثال', 'controls' => 'مراجعة إجراءات الترسية والتأكد من تطبيق سياسة المنافسة'],
                ['risk' => 'فروقات بين الجرد الفعلي وسجلات المستودع', 'risk_rating' => 'متوسط', 'activity_type' => 'تشغيلي', 'controls' => 'جرد دوري ومطابقة السجلات مع الأرصدة الفعلية'],
                ['risk' => 'تأخر تجديد العقود قبل تاريخ الانتهاء', 'risk_rating' => 'متوسط', 'activity_type' => 'تشغيلي', 'controls' => 'إعداد جدول متابعة لتواريخ انتهاء العقود بفترة كافية مسبقًا'],
            ],
            'صيدل' => [
                ['risk' => 'ضعف الرقابة على صرف الأدوية عالية الخطورة', 'risk_rating' => 'مرتفع', 'activity_type' => 'جودة وسلامة', 'controls' => 'تطبيق إجراء تحقق مزدوج قبل صرف الأدوية عالية الخطورة'],
                ['risk' => 'عدم الالتزام بشروط التخزين الخاصة ببعض الأدوية', 'risk_rating' => 'متوسط', 'activity_type' => 'التزام وامتثال', 'controls' => 'متابعة دورية لظروف التخزين (الحرارة/الرطوبة) وتوثيقها'],
            ],
            'تمريض|طبية|عيادات|رعاية|طوارئ|عناية|مرضى' => [
                ['risk' => 'عدم الالتزام الكامل ببروتوكولات مكافحة العدوى', 'risk_rating' => 'مرتفع', 'activity_type' => 'جودة وسلامة', 'controls' => 'جولات رقابية دورية على الالتزام ببروتوكولات مكافحة العدوى'],
                ['risk' => 'نقص التوثيق الكامل للرعاية بالملف الطبي', 'risk_rating' => 'متوسط', 'activity_type' => 'التزام وامتثال', 'controls' => 'تدقيق دوري لعيّنة من الملفات الطبية والتأكد من اكتمال التوثيق'],
                ['risk' => 'فرصة تحسين بآلية التبليغ عن الأحداث غير المرغوبة', 'risk_rating' => 'فرصة تحسين', 'activity_type' => 'جودة وسلامة', 'controls' => 'تسهيل وتبسيط نموذج التبليغ لرفع نسبة الإبلاغ الفعلي'],
            ],
            'مرافق|سلامة|أمن|صيانة|هندس|القياس والمعايرة' => [
                ['risk' => 'تأخر إغلاق بلاغات الصيانة الوقائية عن الجدول الزمني المعتمد', 'risk_rating' => 'متوسط', 'activity_type' => 'تشغيلي', 'controls' => 'متابعة دورية لمؤشرات إغلاق بلاغات الصيانة الوقائية'],
                ['risk' => 'عدم اكتمال سجلات معايرة الأجهزة الطبية', 'risk_rating' => 'مرتفع', 'activity_type' => 'جودة وسلامة', 'controls' => 'التأكد من اكتمال سجلات المعايرة الدورية لكل جهاز حسب جدولته'],
            ],
            'جودة' => [
                ['risk' => 'تأخر إغلاق خطط التحسين الناتجة عن مؤشرات الجودة', 'risk_rating' => 'متوسط', 'activity_type' => 'جودة وسلامة', 'controls' => 'متابعة دورية لحالة خطط التحسين ومواعيد إغلاقها'],
            ],
            'قانون' => [
                ['risk' => 'تأخر مراجعة العقود من الناحية القانونية قبل التوقيع', 'risk_rating' => 'متوسط', 'activity_type' => 'حوكمة', 'controls' => 'اعتماد مدة زمنية قصوى لمراجعة العقود قبل مرحلة التوقيع'],
            ],
            'أبحاث' => [
                ['risk' => 'عدم اكتمال موافقات اللجنة الأخلاقية قبل بدء بعض الأبحاث', 'risk_rating' => 'مرتفع', 'activity_type' => 'التزام وامتثال', 'controls' => 'التحقق من اكتمال موافقة اللجنة الأخلاقية قبل بدء أي بحث'],
            ],
            'أكاديمي|تدريب|تعليم' => [
                ['risk' => 'عدم اكتمال الساعات التدريبية المطلوبة لبعض الموظفين', 'risk_rating' => 'منخفض', 'activity_type' => 'تشغيلي', 'controls' => 'متابعة دورية لمؤشر إنجاز الساعات التدريبية المطلوبة'],
            ],
        ];

        foreach ($rules as $pattern => $suggested) {
            if (preg_match('/' . $pattern . '/u', $haystack)) {
                return $suggested;
            }
        }

        // إدارة ما تطابقت مع أي قاعدة محدَّدة -- مخاطر عامة تصلح كنقطة انطلاق لأي إدارة
        return [
            ['risk' => 'عدم توثيق الإجراءات التشغيلية الرئيسية للإدارة بشكل كافٍ', 'risk_rating' => 'متوسط', 'activity_type' => 'حوكمة', 'controls' => 'توثيق الإجراءات الرئيسية واعتمادها ونشرها للمعنيين'],
            ['risk' => 'غياب مؤشرات أداء واضحة لقياس فعالية العمليات', 'risk_rating' => 'منخفض', 'activity_type' => 'تشغيلي', 'controls' => 'تحديد مؤشرات أداء رئيسية ومتابعتها بشكل دوري'],
        ];
    }
}
