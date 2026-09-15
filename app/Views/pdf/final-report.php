<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    /* خط "Amiri" (نسخ كلاسيكي أنيق مناسب لمستند رسمي -- مسجَّل بـ
       PdfController::makeFinalReportMpdf) بدل DejaVu Sans. أحجام الخط كبيرة
       لتحسين وضوح القراءة، وألوان الجداول أخذناها من نموذج تقرير المراجعة
       الرسمي بصيغة Word، مع تحسين جودة عرضها (نص أبيض بارز فوق الدرجات
       الغامقة/المشبَّعة بدل نص غامق ضعيف التباين) */
    body { font-family: 'amiri', 'DejaVu Sans', sans-serif; font-size: 16px; color: #152c33; direction: rtl; }
    .section-break { page-break-before: always; }
    h1.cover-title { font-size: 22px; color: #196b7f; text-align: center; margin: 0 0 4px; }
    h2.cover-sub { font-size: 17px; color: #3185b3; text-align: center; margin: 0 0 18px; font-weight: normal; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.info td { padding: 9px 10px; border: 1px solid #d8e6eb; font-size: 15px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 160px; }
    /* كل قسم من الأقسام الستة يبدأ بصفحة جديدة مستقلة */
    h1.section-title { font-size: 19px; color: #196b7f; border-bottom: 2px solid #3185b3; padding-bottom: 6px; margin: 18px 0 12px; page-break-before: always; }
    /* القسم الأول يبقى بنفس صفحة الغلاف (بدل صفحة شبه فاضية لوحدها) -- بقية الأقسام (2-6) لسا كل وحد بصفحته المستقلة */
    h1.section-title.no-break { page-break-before: auto; }
    h2.sub-title { font-size: 16.5px; color: #196b7f; font-weight: bold; margin: 12px 0 6px; }
    p.body-p { font-size: 15px; line-height: 1.9; text-align: justify; margin: 0 0 10px; }
    ul.body-list { font-size: 15px; margin: 0 0 10px; padding-right: 20px; line-height: 1.8; }
    ul.body-list li { margin-bottom: 4px; }
    table.grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    /* هيدر موحّد لكل جداول المستند (نفس خلفية جدول بيانات الغلاف وجدول تفاصيل
       الملاحظات) بدل تعدد الألوان بين الأزرق المشبَّع والرمادي حسب الجدول */
    table.grid th { background: #f8fafc; color: #196b7f; font-weight: bold; font-size: 14.5px; padding: 9px; border: 1px solid #d8e6eb; text-align: center; }
    table.grid td { padding: 9px; border: 1px solid #d8e6eb; font-size: 14.5px; text-align: right; vertical-align: top; }
    table.grid td.center { text-align: center; }
    /* ألوان مستويات المخاطر -- مطابقة لألوان نفس الخانات بجدول "مستوي المخاطر" (القسم الأول) بملف الوورد، بنص
       أبيض فوق الدرجات الغامقة (أحمر/أخضر/أزرق) عشان يبين واضح، وأسود فوق الأصفر الفاتح */
    th.risk-high { background: #E8281B; color: #ffffff; }
    th.risk-med { background: #FFD966; color: #152c33; }
    th.risk-low { background: #4CA23A; color: #ffffff; }
    th.risk-opp { background: #4C8FCC; color: #ffffff; }
    /* ألوان "تعريف مستوى المخاطر" (القسم الرابع) -- مطابقة لنفس الجدول بملف الوورد */
    td.legend-high { background: #E8281B; color: #ffffff; font-weight: bold; }
    td.legend-med { background: #FFC000; color: #152c33; font-weight: bold; }
    td.legend-low { background: #24A855; color: #ffffff; font-weight: bold; }
    td.legend-opp { background: #1E9E9C; color: #ffffff; font-weight: bold; }
    /* ألوان جدول "معايير تقييم الملاحظة" (خطر/فرصة) -- مطابقة لنفس الجدول بملف الوورد */
    td.crit-risk { background: #FFE599; color: #152c33; }
    td.crit-opp { background: #F4B083; color: #152c33; }
    .empty-row { text-align: center; color: #9ca3af; }
    /* جدول تفاصيل الملاحظة (القسم الثالث) -- <table> حقيقي بدل خدعة CSS
       "display:table" على divs، اللي mPDF يطلعها بارتفاع صف متضخّم وغير
       منتظم (فراغات كبيرة تحت كل قيمة قصيرة) بدل الالتزام بارتفاع المحتوى الفعلي */
    table.obs-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 14px; border: 1px solid #d8e6eb; page-break-inside: avoid; }
    /* تفتيح خلفية عمود التصنيف (بدل الأزرق المشبَّع اللي يغطي نص الجدول) -- نفس درجة
       خلفية جدول بيانات الغلاف (table.info) عشان يطلع مريح للعين ومتّسق مع باقي المستند.
       العرض مضبوط مباشرة على الخليتين (مو عبر <col>) -- mPDF يطبّق عرض <col> على
       العمود المعاكس بالجداول RTL، فيطلع عمود القيمة (الكتابة الفعلية) ضيّق جدًا
       وعمود التصنيف (كلمة وحدة قصيرة) عريض جدًا، عكس المطلوب تمامًا */
    table.obs-table th.obs-label { width: 22%; background: #f8fafc; color: #196b7f; font-weight: bold; font-size: 14px; padding: 8px 10px; text-align: right; vertical-align: middle; border: 1px solid #d8e6eb; }
    table.obs-table td.obs-value { width: 78%; background: #ffffff; padding: 8px 10px; font-size: 14px; vertical-align: middle; border: 1px solid #d8e6eb; text-align: right; }
    /* قيمة "مستوى الأهمية" بكل ملاحظة تُلوَّن حسب مستواها، بنفس أسلوب مصفوفة المخاطر بملف الوورد */
    table.obs-table td.obs-value.risk-high { background: #E8281B; color: #ffffff; font-weight: bold; }
    table.obs-table td.obs-value.risk-med { background: #FFD966; color: #152c33; font-weight: bold; }
    table.obs-table td.obs-value.risk-low { background: #4CA23A; color: #ffffff; font-weight: bold; }
    table.obs-table td.obs-value.risk-opp { background: #4C8FCC; color: #ffffff; font-weight: bold; }
</style>
</head>
<body>
    <!-- ========== غلاف التقرير ========== -->
    <h1 class="cover-title">إدارة المراجعة الداخلية بمدينة الملك عبد الله الطبية بالعاصمة المقدسة</h1>
    <h2 class="cover-sub">تقرير المراجعة – النهائي</h2>

    <table class="info">
        <tr><td class="label">أسم مهمة المراجعة</td><td><?= esc($mission['title'] ?: ('مراجعة داخلية — ' . ($targetDept['name_ar'] ?? ''))) ?></td></tr>
        <tr>
            <td class="label">تاريخ التقرير</td>
            <td><?= esc($report['head_approved_at'] ? date('d / m / Y', strtotime($report['head_approved_at'])) . 'م' : '—') ?></td>
        </tr>
        <tr><td class="label">رقم التقرير</td><td><?= esc($mission['mission_code']) ?></td></tr>
    </table>

    <!-- ========== القسم الأول (1) التعريفات والأهداف ========== -->
    <h1 class="section-title no-break">القسم الأول ( 1 ) التعريفات والاهداف</h1>

    <h2 class="sub-title">مقدمة:</h2>
    <p class="body-p">يعتبر تقرير المراجعة أحد وسائل تبليغ التوصيات وفقاً للمادة الحادية عشرة الفقرة (1) "تعد الوحدة تقرير بنتائج أعمال المراجعة في نهاية كل عملية مراجعة التي تقوم بها على الإدارات الأخرى داخل الجهة، ومن تم تبليغها بتلك النتائج والتوصيات المتعلقة بها، ومتابعة التوصيات الواردة في تقاريرها للتأكد من تنفيذها "قرار مجلس الوزراء رقم ( 129 ) بتاريخ 06/ 04/1428هـ اللائحة الموحدة لوحدات المراجعة الداخلية في الأجهزة الحكومية والمؤسسات العامة .</p>

    <h2 class="sub-title">أهداف تقارير المراجعة:</h2>
    <p class="body-p">تهدف تقارير المراجعة الى تعزيز فرص التحسين والتحقق من اتساق الإجراءات التنفيذية مع مستهدفات المدينة الطبية وتقديم تأكيد معقول ، على النحو التالي:</p>
    <ul class="body-list">
        <li>التحقق من التقيد بالأنظمة والتعليمات والسياسات والخطط الملزمة للمدينة الطبية لتحقيق أهدافها بكفاية وبطريقة منتظمة.</li>
        <li>التحقق من سلامة أنظمة الرقابة الداخلية وفعاليتها.</li>
        <li>التحقق من ملائمة العمليات الإدارية والمالية بما يؤدي الى الاستغلال الأمثل للموارد المتاحة المالية والبشرية.</li>
        <li>التحقق من صحة البيانات المالية والسجلات المحاسبية واكتمالها.</li>
        <li>مراجعة أعمال المستودعات وفحص دفاترها وسجلاتها ومستنداتها.</li>
        <li>مراجعة العقود المبرمة مع المدينة الطبية للتأكد من مدي التقيد بها.</li>
    </ul>

    <table class="grid">
        <tr>
            <th rowspan="2">م</th>
            <th rowspan="2">الملاحظات</th>
            <th colspan="4">مستوي المخاطر</th>
        </tr>
        <tr>
            <th class="risk-high">مرتفع</th><th class="risk-med">متوسط</th><th class="risk-low">منخفض</th><th class="risk-opp">فرص تحسين</th>
        </tr>
        <?php if (empty($observations)): ?>
        <tr><td colspan="6" class="empty-row">لا توجد ملاحظات مضافة للتقرير</td></tr>
        <?php else: ?>
        <?php foreach ($observations as $i => $o): ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td><?= esc($o['title'] ?: $o['ref_code']) ?></td>
            <td class="center"><?= $o['risk_severity'] === 'عالي' ? '✓' : '' ?></td>
            <td class="center"><?= $o['risk_severity'] === 'متوسط' ? '✓' : '' ?></td>
            <td class="center"><?= $o['risk_severity'] === 'منخفض' ? '✓' : '' ?></td>
            <td class="center"></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- ========== القسم الثاني (2) ملخص الملاحظات ========== -->
    <h1 class="section-title">القسم الثاني ( 2 ) ملخص الملاحظات</h1>

    <!-- ========== القسم الثالث (3) تفاصيل الملاحظات والتوصيات ========== -->
    <h1 class="section-title">القسم الثالث ( 3 )<br>تفاصيل الملاحظات والتوصيات</h1>

    <?php if (empty($observations)): ?>
        <p class="body-p empty-row">لا توجد ملاحظات مضافة للتقرير</p>
    <?php else: ?>
        <?php foreach ($observations as $o): ?>
        <?php
        $severityClass = [
            'عالي'   => 'risk-high',
            'متوسط'  => 'risk-med',
            'منخفض'  => 'risk-low',
            'فرصة تحسين' => 'risk-opp',
        ][$o['risk_severity'] ?? ''] ?? '';
        ?>
        <table class="obs-table">
            <tr><th class="obs-label">الملاحظة</th><td class="obs-value"><?= nl2br(esc($o['observation_text'] ?: ($o['title'] ?: $o['ref_code']))) ?></td></tr>
            <tr><th class="obs-label">مستوي الأهمية</th><td class="obs-value <?= $severityClass ?>"><?= esc($o['risk_severity'] ?: '—') ?></td></tr>
            <tr><th class="obs-label">المعيار أو النظام</th><td class="obs-value"><?= nl2br(esc($o['standard_text'] ?: '—')) ?></td></tr>
            <tr><th class="obs-label">الأثر</th><td class="obs-value"><?= nl2br(esc($o['impact_text'] ?: '—')) ?></td></tr>
            <tr><th class="obs-label">التوصيات</th><td class="obs-value"><?= nl2br(esc($o['recommendations_text'] ?: '—')) ?></td></tr>
            <tr><th class="obs-label">الربط بمستهدفات المدينة الطبية</th><td class="obs-value"><?= nl2br(esc($o['kamc_targets_link'] ?: '—')) ?></td></tr>
            <tr><th class="obs-label">الربط بمستهدفات التحول الصحي الوطني</th><td class="obs-value"><?= nl2br(esc($o['health_transformation_targets_link'] ?: '—')) ?></td></tr>
            <tr><th class="obs-label">رد الإدارة (خطة التنفيذ التوصيات)</th><td class="obs-value"><?= nl2br(esc($o['dept_response_plan'] ?: '—')) ?></td></tr>
        </table>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ========== القسم الرابع (4) معايير الملاحظات وتعريفات المخاطر ========== -->
    <h1 class="section-title">القسم الرابع ( 4 ) معايير الملاحظات وتعريفات المخاطر</h1>

    <table class="grid">
        <tr><th colspan="2">معاير تقييم الملاحظة</th></tr>
        <tr>
            <td class="crit-risk" style="width:100px;font-weight:bold;">مخاطر</td>
            <td>الخطر المحتمل هو مصدر أذى محتمل أو حالة محتملة للتسبب في إحداث خسارة<br>الخسارة قد تكون لها عواقب تؤثر في العمليات التشغيلية والمالية والقانونية والطبية</td>
        </tr>
        <tr>
            <td class="crit-opp" style="font-weight:bold;">فرصة</td>
            <td>
                الفرص تتضمن الملاحظات التي تزيد من الخطر:
                <ul class="body-list">
                    <li>الأداء دون الأمثل</li>
                    <li>إضاعة الفرصة للتحسين</li>
                    <li>عدم تحقيق نتيجة إيجابية محتملة</li>
                    <li>إضاعة الفرصة لتحقيق منفعة قصوى</li>
                    <li>زيادة التكاليف والاعمال الإجرائية والمالية</li>
                </ul>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr><th colspan="2">تعريف مستوي المخاطر</th></tr>
        <tr><th style="width:100px;">التقييم</th><th>الوصف</th></tr>
        <tr>
            <td class="center legend-high">مرتفع</td>
            <td>أهمية مرتفعة: تمثل الملاحظة نقطة ضعف في الضوابط الرقابية ذات أولوية عالية يجب على الإدارة تكريس اهتمامها على المدى القريب لمعالجتها</td>
        </tr>
        <tr>
            <td class="center legend-med">متوسط</td>
            <td>مهم إلى حد ما: تمثل الملاحظة نقطة ضعف معتدلة في الأولوية في الضوابط الرقابية ويجب على الإدارة تكريس الاهتمام لمعالجتها</td>
        </tr>
        <tr>
            <td class="center legend-low">منخفض</td>
            <td>الأقل أولوية: تمثل الملاحظة نقطة ضعف في الضوابط الرقابية ذات أولوية منخفضة للإدارة للنظر في معالجتها وفقًا لتقديرها</td>
        </tr>
        <tr>
            <td class="center legend-opp">فرصة تحسين</td>
            <td>التنفيذ الاختياري: تمثل الملاحظة فرصة لتحسين تدفق العمليات وكفاءتها، ومع ذلك، فإن عدم تنفيذ التوصية لا يشكل أوجه قصور في التحكم</td>
        </tr>
    </table>

    <!-- ========== القسم الخامس (5) مسئولية الإدارة ========== -->
    <h1 class="section-title">القسم الخامس ( 5 ) مسئولية الإدارة</h1>
    <h2 class="sub-title">مسؤولية الإدارة :</h2>
    <p class="body-p">إن الملاحظات المشار إليها في هذا التقرير هي تلك الملاحظات التي تم ملاحظتها فقط خلال عملية المراجعة ولا تشكل بالضرورة بياناً شاملاً لكافة نقاط الضعف الموجودة أو كافة التحسينات التي قد تتطلب لها الإدارة، ويجب أن تقييم الإدارة التوصيات من حيث الأثر الكلي لها قبل تطبيقها، ولا ينبغي اعتبار أداء عمل المراجعة الداخلية كبديل عن مسؤوليات الإدارة فيما يخص تطبيق الممارسات السليمة ( الأنظمة والتعليمات ) .</p>
    <p class="body-p">ونؤكد على أن مسؤولية وجود نظام جيد للرقابة الداخلية تقع على عاتق الإدارة ولا ينبغي الاعتماد على العمل المؤدي من قبل المراجعة الداخلية في تحديد كافة نقاط القوة والضعف والتي قد تكون موجودة، كما لا ينبغي الاعتماد عليه في تحديد كافة ظروف عمليات الاحتيال أو المخالفات إن وجد.</p>
    <p class="body-p">يعود ذلك الى أن أعمال المراجعة الداخلية محدده بزمن معين يتم فيه تحديد مخاطر أولوية للفحص، وأن عملية المراجعة تقدم تأكيد معقول وليس مطلق، وهذا لا يقلل من عمليات الفحص التي تمت حيث أن هناك حدود لعمليات المرجعة وهي أن الفحص يتم على عينة من الإجراءات وكذلك أن هناك تقدير مهني شخصي يبذله المراجع مبني على معايير المراجعة والعناية المهنية والتأهيل العلمي للمراجع</p>

    <!-- ========== القسم السادس (6) سرية المعلومات ========== -->
    <h1 class="section-title">القسم (6) سرية المعلومات</h1>
    <p class="body-p">تلتزم إدارة المراجعة الداخلية بسرية المعلومات والبيانات والمستندات التي حصلت عليها أثناء أدائها لعملها وذلك فقاً للمادة التاسعة عشرة من قرار مجلس الوزراء رقم ( 129 ) بتاريخ 06-04-1428هـ .</p>
</body>
</html>
