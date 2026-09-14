<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    /* خط "Saudi" الرسمي (مسجَّل بـ PdfController::makeMpdf مع useSaudiFont=true)
       بدل DejaVu Sans. أحجام الخط كبيرة لتحسين وضوح القراءة، وألوان الجداول
       أخذناها من نموذج تقرير المراجعة الرسمي بصيغة Word، مع تحسين جودة عرضها
       (نص أبيض بارز فوق الدرجات الغامقة/المشبَّعة بدل نص غامق ضعيف التباين)،
       ورأس موحَّد (#9CC2E5) لكل جداول المستند بدون استثناء */
    body { font-family: 'saudi', 'DejaVu Sans', sans-serif; font-size: 14.5px; color: #152c33; direction: rtl; }
    .section-break { page-break-before: always; }
    h1.cover-title { font-size: 20px; color: #196b7f; text-align: center; margin: 0 0 4px; }
    h2.cover-sub { font-size: 15.5px; color: #3185b3; text-align: center; margin: 0 0 18px; font-weight: normal; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.info td { padding: 8px 10px; border: 1px solid #d8e6eb; font-size: 13.5px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 160px; }
    /* كل قسم من الأقسام الستة يبدأ بصفحة جديدة مستقلة */
    h1.section-title { font-size: 17.5px; color: #196b7f; border-bottom: 2px solid #3185b3; padding-bottom: 6px; margin: 18px 0 12px; page-break-before: always; }
    h2.sub-title { font-size: 15px; color: #196b7f; font-weight: bold; margin: 12px 0 6px; }
    /* text-align:right لا justify -- التبرير بخط Saudi المخصَّص يخلي mPDF يباعد
       بين الحروف نفسها (بدل الكشيدة العادية) لملء عرض السطر، فتبين الكلمة
       متفرقة الحروف بدل متصلة */
    p.body-p { font-size: 13.5px; line-height: 1.9; text-align: right; margin: 0 0 10px; }
    ul.body-list { font-size: 13.5px; margin: 0 0 10px; padding-right: 20px; line-height: 1.8; }
    ul.body-list li { margin-bottom: 4px; }
    table.grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.grid th { background: #9CC2E5; color: #152c33; font-size: 13px; padding: 8px; border: 1px solid #b3d4e5; text-align: center; }
    table.grid td { padding: 8px; border: 1px solid #d8e6eb; font-size: 13px; text-align: right; vertical-align: top; }
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
    .obs-block { border: 1px solid #b3d4e5; border-radius: 6px; margin-bottom: 14px; page-break-inside: avoid; }
    .obs-row { display: table; width: 100%; border-bottom: 1px solid #e2ecf0; }
    .obs-row:last-child { border-bottom: none; }
    .obs-cell-label { display: table-cell; width: 200px; background: #9CC2E5; color: #152c33; font-weight: bold; font-size: 13px; padding: 10px; vertical-align: top; }
    .obs-cell-value { display: table-cell; padding: 10px; font-size: 13.5px; vertical-align: top; }
    /* قيمة "مستوى الأهمية" بكل ملاحظة تُلوَّن حسب مستواها، بنفس أسلوب مصفوفة المخاطر بملف الوورد */
    .obs-cell-value.risk-high { background: #E8281B; color: #ffffff; font-weight: bold; }
    .obs-cell-value.risk-med { background: #FFD966; color: #152c33; font-weight: bold; }
    .obs-cell-value.risk-low { background: #4CA23A; color: #ffffff; font-weight: bold; }
    .obs-cell-value.risk-opp { background: #4C8FCC; color: #ffffff; font-weight: bold; }
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
    <h1 class="section-title">القسم الأول ( 1 ) التعريفات والاهداف</h1>

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
        <div class="obs-block">
            <div class="obs-row">
                <div class="obs-cell-label">الملاحظة</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['observation_text'] ?: ($o['title'] ?: $o['ref_code']))) ?></div>
            </div>
            <?php
            $severityClass = [
                'عالي'   => 'risk-high',
                'متوسط'  => 'risk-med',
                'منخفض'  => 'risk-low',
                'فرصة تحسين' => 'risk-opp',
            ][$o['risk_severity'] ?? ''] ?? '';
            ?>
            <div class="obs-row">
                <div class="obs-cell-label">مستوي الأهمية</div>
                <div class="obs-cell-value <?= $severityClass ?>"><?= esc($o['risk_severity'] ?: '—') ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">المعيار أو النظام</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['standard_text'] ?: '—')) ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">الأثر</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['impact_text'] ?: '—')) ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">التوصيات</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['recommendations_text'] ?: '—')) ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">الربط بمستهدفات المدينة الطبية</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['kamc_targets_link'] ?: '—')) ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">الربط بمستهدفات التحول الصحي الوطني</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['health_transformation_targets_link'] ?: '—')) ?></div>
            </div>
            <div class="obs-row">
                <div class="obs-cell-label">رد الإدارة (خطة التنفيذ التوصيات)</div>
                <div class="obs-cell-value"><?= nl2br(esc($o['dept_response_plan'] ?: '—')) ?></div>
            </div>
        </div>
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
