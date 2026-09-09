<?php

namespace App\Libraries;

use App\Models\ServiceAgreementModel;
use App\Models\ServiceAgreementResponseModel;
use App\Models\MissionPlanningModel;
use App\Models\DocumentRequestModel;
use App\Models\DocumentModel;

/**
 * محرّك اقتراح مصفوفة المخاطر -- تحليل قائم بالكامل على مطابقة كلمات مفتاحية
 * محلية (بدون أي استدعاء لخدمة ذكاء اصطناعي خارجية)، لكن يجمع نصه المصدَر من
 * كل بيانات المهمة الفعلية بدل الاعتماد على اسم الإدارة وحده:
 *   - بيانات المهمة (الإدارة الخاضعة، "المراد مناقشته")
 *   - اتفاقية مستوى الخدمة (قنوات الاتصال، ملاحظات الإدارة على كل بند)
 *   - مذكرة تخطيط المهمة (نبذة المهمة، الأهداف، النطاق، الإجراءات الفرعية...)
 *   - قائمة المستندات المطلوبة (أسماء المستندات)
 *   - المستندات المرفوعة فعليًا -- اسم كل ملف ونصّه الفعلي المستخرَج من
 *     PDF/Word (لو كان الملف نصيًا؛ الملفات الممسوحة ضوئيًا بلا نص تُتجاهَل
 *     بصمت، بدون OCR)
 *
 * كل هذا النص المجمَّع يُطابَق مع قاموس مخاطر نموذجية معدّ مسبقًا بالكود --
 * القاعدة الأولى (اسم الإدارة) تبقى الأساس، وأي قاعدة ثانية تنطبق على بقية
 * النصوص تُضاف كمخاطر إضافية (حتى حد أقصى معقول للصفوف)، عشان الاقتراح
 * يعكس فعليًا محتوى المهمة لا بس نوع الإدارة.
 */
class RiskMatrixAiEngine
{
    /** ملفات أكبر من هذا الحجم تُتجاهَل من استخراج النص (يبقى اسم الملف نفسه بالتحليل) */
    private const MAX_FILE_BYTES = 15 * 1024 * 1024; // 15 ميجا

    /** أقصى عدد أحرف من نص كل ملف يُضاف للتحليل -- يكفي لالتقاط فحوى الملف بدون استهلاك ذاكرة زائد */
    private const MAX_TEXT_PER_FILE = 20000;

    /** أقصى عدد ملفات تتم معالجتها فعليًا (استخراج نص) لكل مهمة -- حماية من مهمة عندها عشرات المرفقات */
    private const MAX_FILES_PROCESSED = 8;

    /** أقصى عدد صفوف مخاطر تُقترَح دفعة وحدة */
    private const MAX_ROWS = 6;

    private const RULES = [
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
        'مشتريات|مستودعات|ممتلكات|الإمداد|عقد|عقود' => [
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
        /* قواعد إضافية تُستهدَف غالبًا من محتوى اتفاقية مستوى الخدمة/مذكرة
           التخطيط/المستندات الفعلية، مو بس اسم الإدارة */
        'غير موافق|تحفظ|رفض|عدم موافقة' => [
            ['risk' => 'وجود بنود باتفاقية مستوى الخدمة لم توافق عليها الإدارة الخاضعة للمراجعة', 'risk_rating' => 'متوسط', 'activity_type' => 'حوكمة', 'controls' => 'متابعة أسباب عدم الموافقة مع الإدارة المعنية قبل بدء العمل الميداني'],
        ],
        'ملاحظات سابقة|تكرار الملاحظة|ملاحظة متكررة' => [
            ['risk' => 'تكرار بعض الملاحظات من عمليات المراجعة السابقة دون إغلاق كامل', 'risk_rating' => 'مرتفع', 'activity_type' => 'حوكمة', 'controls' => 'متابعة حالة إغلاق خطط تصحيح الملاحظات السابقة قبل اعتماد التقرير الجديد'],
        ],
    ];

    private const GENERIC_ROWS = [
        ['risk' => 'عدم توثيق الإجراءات التشغيلية الرئيسية للإدارة بشكل كافٍ', 'risk_rating' => 'متوسط', 'activity_type' => 'حوكمة', 'controls' => 'توثيق الإجراءات الرئيسية واعتمادها ونشرها للمعنيين'],
        ['risk' => 'غياب مؤشرات أداء واضحة لقياس فعالية العمليات', 'risk_rating' => 'منخفض', 'activity_type' => 'تشغيلي', 'controls' => 'تحديد مؤشرات أداء رئيسية ومتابعتها بشكل دوري'],
    ];

    /** يبني نص التحليل من كل مصادر بيانات المهمة، ويطابقه مع قاموس المخاطر */
    public function suggest(array $mission): array
    {
        $haystack = $this->buildHaystack($mission);
        return $this->matchRules($haystack);
    }

    private function buildHaystack(array $mission): string
    {
        $missionId = (int) ($mission['id'] ?? 0);
        $parts = [
            $mission['target_department_name'] ?? '',
            $mission['procedure_note'] ?? '',
        ];

        // اتفاقية مستوى الخدمة -- قنوات الاتصال وملاحظات الإدارة على كل بند
        $agreement = (new ServiceAgreementModel())->where('mission_id', $missionId)->first();
        if ($agreement) {
            $parts[] = $agreement['channel_email_value'] ?? '';
            $parts[] = $agreement['channel_memo_value'] ?? '';
            foreach ((new ServiceAgreementResponseModel())->forMission($missionId) as $resp) {
                $parts[] = $resp['note'] ?? '';
                if (!empty($resp['disagree'])) {
                    $parts[] = 'غير موافق';
                }
            }
        }

        // مذكرة تخطيط المهمة
        $planning = (new MissionPlanningModel())->forMission($missionId);
        if ($planning) {
            foreach (['mission_brief', 'previous_audits', 'regulatory_notes', 'audit_objectives', 'scope_included', 'scope_excluded', 'sub_procedures'] as $field) {
                $parts[] = $planning[$field] ?? '';
            }
        }

        // قائمة المستندات المطلوبة + الملفات المرفوعة فعليًا (اسم كل ملف ونصّه)
        $requestModel = new DocumentRequestModel();
        $docModel = new DocumentModel();
        $filesProcessed = 0;
        foreach ($requestModel->where('mission_id', $missionId)->findAll() as $req) {
            $parts[] = $req['doc_name'] ?? '';
            foreach ($docModel->forRelated('document_request', (int) $req['id']) as $file) {
                $parts[] = $file['file_name'] ?? '';
                if ($filesProcessed >= self::MAX_FILES_PROCESSED) {
                    continue;
                }
                $text = $this->extractFileText($file);
                if ($text !== '') {
                    $parts[] = $text;
                    $filesProcessed++;
                }
            }
        }

        return implode(' ', array_filter($parts, static fn ($p) => trim((string) $p) !== ''));
    }

    /** يستخرج النص الفعلي من ملف PDF أو Word مرفوع -- يتجاهل بصمت أي ملف فشل استخراجه
     *  (تالف، مشفَّر، أو صورة ممسوحة ضوئيًا بلا طبقة نص) بدل ما يكسر الصفحة كاملة */
    private function extractFileText(array $file): string
    {
        $fullPath = WRITEPATH . 'uploads/' . ($file['file_path'] ?? '');
        if ($file['file_path'] === null || !is_file($fullPath) || filesize($fullPath) > self::MAX_FILE_BYTES) {
            return '';
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        try {
            if ($ext === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $text = $parser->parseFile($fullPath)->getText();
                /* استخراج النص العربي من PDF غالبًا يطلع بترتيب أحرف كل كلمة
                   معكوس (خلل معروف بمستخرِجات PDF مع نصوص RTL -- طبقة المحتوى
                   الداخلية تخزّن الرموز بترتيب الرسم البصري لا القرائي)، فنضيف
                   نسخة بأحرف كل كلمة معكوسة عشان تبقى مطابقة الكلمات المفتاحية
                   شغّالة بغض النظر عن اتجاه استخراج الملف المحدَّد */
                $text .= ' ' . $this->reverseEachWord($text);
            } elseif ($ext === 'docx') {
                // Word يخزّن النص بترتيبه المنطقي الصحيح دائمًا (XML عادي، مو رسم خطوط)، فما يحتاج هذا الإصلاح
                $text = $this->extractDocxText($fullPath);
            } else {
                return '';
            }
        } catch (\Throwable $e) {
            log_message('error', 'RiskMatrixAiEngine: تعذّر استخراج نص الملف ' . $fullPath . ' — ' . $e->getMessage());
            return '';
        }

        return mb_substr(trim($text), 0, self::MAX_TEXT_PER_FILE);
    }

    /** يعكس ترتيب أحرف كل "كلمة" (مفصولة بمسافات) بنص معيّن -- يُستخدَم فقط
     *  كنسخة احتياطية إضافية لنص PDF المستخرَج (انظر extractFileText) */
    private function reverseEachWord(string $text): string
    {
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return '';
        }
        foreach ($tokens as &$token) {
            if (trim($token) === '') {
                continue;
            }
            $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY);
            if ($chars === false) {
                continue;
            }
            $token = implode('', array_reverse($chars));
        }
        return implode('', $tokens);
    }

    /** استخراج النص من DOCX عبر قراءة word/document.xml مباشرة (بدون مكتبة خارجية
     *  إضافية -- نفس أسلوب استخراج قوالب Word المستخدَم بمكان ثانٍ بالمشروع) */
    private function extractDocxText(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        return $dom->textContent ?? '';
    }

    /** يطابق النص المجمَّع مع كل قواعد القاموس (مو أول قاعدة تنطبق فقط)، ويجمع
     *  صفوفها بدون تكرار حتى الحد الأقصى للصفوف -- عشان الاقتراح يعكس تنوّع
     *  محتوى المهمة الفعلي لا نوع إدارة واحد بس */
    private function matchRules(string $haystack): array
    {
        $matchedRows = [];
        $seenRisks = [];

        foreach (self::RULES as $pattern => $rows) {
            if (!preg_match('/' . $pattern . '/u', $haystack)) {
                continue;
            }
            foreach ($rows as $row) {
                if (isset($seenRisks[$row['risk']])) {
                    continue;
                }
                $seenRisks[$row['risk']] = true;
                $matchedRows[] = $row;
                if (count($matchedRows) >= self::MAX_ROWS) {
                    break 2;
                }
            }
        }

        return $matchedRows ?: self::GENERIC_ROWS;
    }
}
