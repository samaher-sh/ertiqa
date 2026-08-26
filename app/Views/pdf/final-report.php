<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    .section-break { page-break-before: always; }
    h1.section-title { font-size: 15px; color: #196b7f; border-bottom: 2px solid #3185b3; padding-bottom: 6px; margin: 0 0 12px; }
    h2 { font-size: 13px; color: #196b7f; background: #f0f7fa; padding: 6px 10px; margin: 16px 0 6px; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.info td { padding: 6px 10px; border: 1px solid #d8e6eb; font-size: 11px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 130px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th { background: #f0f7fa; color: #196b7f; font-size: 10px; padding: 6px; border: 1px solid #b3d4e5; text-align: right; }
    table.grid td { padding: 6px; border: 1px solid #d8e6eb; font-size: 10px; text-align: right; vertical-align: top; }
    .badge-yes { color: #15803d; font-weight: bold; }
    .badge-no { color: #b91c1c; font-weight: bold; }
    .rating-high { background: #fef2f2; }
    .rating-high .badge { color: #b91c1c; font-weight: bold; }
    .rating-medium { background: #fffceb; }
    .rating-medium .badge { color: #a16207; font-weight: bold; }
    .rating-low { background: #f0fdf4; }
    .rating-low .badge { color: #15803d; font-weight: bold; }
    .sig-img { max-height: 30px; }
    .empty-row { text-align: center; color: #9ca3af; }
    /* نفس تنسيق نموذج الخطاب الرسمي (pdf/mission-letter.php) بالضبط، عدا
       هيدره الخاص (شعار/تاريخ/رقم) -- محذوف هنا لأن الهيدر المتكرر المشترك
       بكل صفحات هذا المستند (applyRunningHeader) يغطّي نفس الغرض أصلًا، وتكراره
       يطلع شعارين بنفس الصفحة */
    .addr { font-weight: bold; font-size: 14px; margin-bottom: 8px; }
    .greet { font-weight: bold; margin-bottom: 12px; }
    p.body-p { line-height: 1.9; text-align: right; margin: 0 0 10px; }
    mark { background: #f0f7fa; color: #196b7f; padding: 1px 5px; font-weight: bold; }
    .procedure-box { border: 1px solid #b3d4e5; border-radius: 6px; margin-bottom: 10px; }
    .procedure-head { background: #f0f7fa; color: #196b7f; padding: 6px 10px; font-size: 11px; font-weight: bold; }
    .procedure-body { padding: 8px 10px; font-size: 13px; }
    .contacts { margin: 8px 0; }
    .contacts div { margin-bottom: 4px; }
    .bar { height: 5px; background: #3185b3; margin-top: 20px; border-radius: 3px; }
</style>
</head>
<body>
    <p class="sub">التقرير النهائي — <?= esc($mission['mission_code']) ?> — <?= esc($targetDept['name_ar'] ?? '') ?> — <?= esc($mission['year']) ?></p>

    <!-- ========== 1. نموذج الخطاب الرسمي ========== -->
    <h1 class="section-title">1. طلب المراجعة الداخلية</h1>

    <p class="addr">سعادة المدير التنفيذي لـ<mark><?= esc($mainDept['name_ar'] ?? '') ?></mark> المحترم</p>
    <p class="greet">السلام عليكم ورحمة الله وبركاته،،،</p>

    <p class="body-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة <mark><?= esc($targetDept['name_ar'] ?? '') ?></mark>، للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام <mark><?= esc($mission['year']) ?></mark>م المعتمدة من قبل المدير العام التنفيذي.</p>

    <p class="body-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>

    <?php if (!empty($mission['procedure_note'])): ?>
    <div class="procedure-box">
        <div class="procedure-head">المراد مناقشته في الاجتماع</div>
        <div class="procedure-body"><?= nl2br(esc($mission['procedure_note'])) ?></div>
    </div>
    <?php endif; ?>

    <p class="body-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
    <p class="body-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
    <p class="body-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark><?= esc($mission['reviewer_name']) ?></mark></p>
    <p class="body-p">والذي يمكن التواصل معه عبر القنوات التالية:</p>

    <div class="contacts">
        <div>البريد الإلكتروني: <strong><?= esc($mission['reviewer_email']) ?></strong></div>
        <div>رقم الجوال: <strong><?= esc($mission['reviewer_phone']) ?></strong></div>
    </div>

    <p class="body-p" style="margin-top:16px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
    <p class="body-p">مدير إدارة المراجعة الداخلية</p>
    <?php if (!empty($mission['director_name'])): ?>
        <p class="body-p" style="font-weight:bold;color:#196b7f;"><?= esc($mission['director_name']) ?></p>
    <?php endif; ?>

    <div class="bar"></div>

    <!-- ========== 2. اتفاقية مستوى الخدمة ========== -->
    <div class="section-break">
        <h1 class="section-title">2. اتفاقية مستوى الخدمة</h1>
        <table class="info">
            <tr><td class="label">اسم المنسّق</td><td><?= esc($agreement['coordinator_name'] ?? '—') ?></td>
                <td class="label">البريد الإلكتروني</td><td><?= esc($agreement['coordinator_email'] ?? '—') ?></td></tr>
            <tr><td class="label">رقم الجوال</td><td colspan="3"><?= esc($agreement['coordinator_phone'] ?? '—') ?></td></tr>
        </table>
        <table class="grid">
            <thead><tr><th>الموضوع</th><th style="width:80px;">الحالة</th><th>ملاحظات</th></tr></thead>
            <tbody>
                <?php if (empty($slaResponses)): ?>
                    <tr><td colspan="3" class="empty-row">لا توجد بنود مسجّلة</td></tr>
                <?php else: foreach ($slaResponses as $r): ?>
                    <tr>
                        <td><?= nl2br(esc($r['row_text'])) ?></td>
                        <td><?= (int) $r['agree'] === 1 ? '<span class="badge-yes">موافق</span>' : ((int) $r['disagree'] === 1 ? '<span class="badge-no">غير موافق</span>' : '—') ?></td>
                        <td><?= nl2br(esc($r['note'] ?: '—')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== 3. قائمة المستندات ========== -->
    <div class="section-break">
        <h1 class="section-title">3. قائمة المستندات</h1>
        <table class="grid">
            <thead><tr><th style="width:30px;">م</th><th>المستند</th><th style="width:80px;">يوجد</th><th>ملاحظات</th></tr></thead>
            <tbody>
                <?php if (empty($docRequests)): ?>
                    <tr><td colspan="4" class="empty-row">لا توجد مستندات مطلوبة</td></tr>
                <?php else: foreach ($docRequests as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($d['doc_name']) ?></td>
                        <td><?= $d['exists_flag'] === null ? '—' : ((int) $d['exists_flag'] === 1 ? '<span class="badge-yes">يوجد</span>' : '<span class="badge-no">لا يوجد</span>') ?></td>
                        <td><?= nl2br(esc($d['response_note'] ?: '—')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== 4. مصفوفة المخاطر ========== -->
    <div class="section-break">
        <h1 class="section-title">4. مصفوفة المخاطر</h1>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:30px;">الرقم</th>
                    <th>المخاطر</th>
                    <th style="width:70px;">التقييم</th>
                    <th>وصف الضوابط</th>
                    <th style="width:100px;">نوع النشاط</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riskItems)): ?>
                    <tr><td colspan="5" class="empty-row">لا توجد مخاطر مسجّلة</td></tr>
                <?php else: foreach ($riskItems as $i => $it): ?>
                    <?php $cls = $it['risk_rating'] === 'عالي' ? 'rating-high' : ($it['risk_rating'] === 'متوسط' ? 'rating-medium' : ($it['risk_rating'] === 'منخفض' ? 'rating-low' : '')); ?>
                    <tr class="<?= $cls ?>">
                        <td><?= $i + 1 ?></td>
                        <td><?= nl2br(esc($it['risk'])) ?></td>
                        <td><span class="badge"><?= esc($it['risk_rating'] ?: '—') ?></span></td>
                        <td><?= nl2br(esc($it['controls'])) ?></td>
                        <td><?= esc($it['activity_type']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== 5. ملخص الاجتماع ========== -->
    <div class="section-break">
        <h1 class="section-title">5. ملخص الاجتماع</h1>
        <h2>بيانات الاجتماع</h2>
        <table class="info">
            <tr><td class="label">التاريخ</td><td><?= esc($meeting['meeting_date'] ?? '—') ?></td>
                <td class="label">الوقت</td><td><?= esc($meeting['meeting_time'] ?? '—') ?></td></tr>
            <tr><td class="label">المكان</td><td colspan="3"><?= esc($meeting['location'] ?? '—') ?></td></tr>
        </table>

        <h2>قائمة الحضور</h2>
        <table class="grid">
            <thead><tr><th style="width:30px;">م</th><th>الاسم</th><th>الإدارة</th><th>الوظيفة</th></tr></thead>
            <tbody>
                <?php if (empty($attendees)): ?>
                    <tr><td colspan="4" class="empty-row">لا يوجد حضور مسجّل</td></tr>
                <?php else: foreach ($attendees as $i => $a): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= esc($a['external_name']) ?></td><td><?= esc($a['attendee_dept']) ?></td><td><?= esc($a['attendee_position']) ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>ملخص ما تم مناقشته</h2>
        <table class="grid">
            <thead><tr><th>النقطة</th><th>الرأي</th><th>السبب / التوضيح</th></tr></thead>
            <tbody>
                <?php if (empty($points)): ?>
                    <tr><td colspan="3" class="empty-row">لا توجد نقاط مسجّلة</td></tr>
                <?php else: foreach ($points as $p): ?>
                    <tr><td><?= nl2br(esc($p['point_text'])) ?></td><td><?= nl2br(esc($p['opinion'] ?: '—')) ?></td><td><?= nl2br(esc($p['reason'] ?: '—')) ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>الاعتماد</h2>
        <table class="grid">
            <thead><tr><th>البيان</th><th>الاسم</th><th>الوظيفة</th><th>التوقيع</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php if (empty($approvals)): ?>
                    <tr><td colspan="5" class="empty-row">لا يوجد اعتماد مسجّل</td></tr>
                <?php else: foreach ($approvals as $ap): ?>
                    <tr>
                        <td><?= esc($ap['statement']) ?></td>
                        <td><?= esc($ap['signer_name']) ?></td>
                        <td><?= esc($ap['position']) ?></td>
                        <td><?php if (!empty($ap['signature_data'])): ?><img class="sig-img" src="<?= esc($ap['signature_data']) ?>"><?php else: ?>—<?php endif; ?></td>
                        <td><?= esc($ap['approval_date'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== 6. الملاحظات ========== -->
    <div class="section-break">
        <h1 class="section-title">6. الملاحظات</h1>
        <?php if (empty($observations)): ?>
            <p class="empty-row">لا توجد ملاحظات مسجّلة</p>
        <?php else: foreach ($observations as $i => $o): ?>
            <h2><?= ($i + 1) . '. ' . esc($o['title']) ?></h2>
            <table class="info">
                <tr><td class="label">الإدارة المعنية</td><td><?= esc($o['department_name'] ?? '—') ?></td>
                    <td class="label">التاريخ</td><td><?= esc($o['observation_date']) ?></td></tr>
                <tr><td class="label">الحالة (الخطر)</td><td colspan="3"><?= esc($o['risk_severity'] ?: '—') ?></td></tr>
                <tr><td class="label">الملاحظة</td><td colspan="3"><?= nl2br(esc($o['observation_text'] ?: '—')) ?></td></tr>
                <tr><td class="label">المعيار أو النظام</td><td colspan="3"><?= nl2br(esc($o['standard_text'] ?: '—')) ?></td></tr>
                <tr><td class="label">السبب</td><td colspan="3"><?= nl2br(esc($o['reason_text'] ?: '—')) ?></td></tr>
                <tr><td class="label">الأثر</td><td colspan="3"><?= nl2br(esc($o['impact_text'] ?: '—')) ?></td></tr>
                <tr><td class="label">التوصيات</td><td colspan="3"><?= nl2br(esc($o['recommendations_text'] ?: '—')) ?></td></tr>
            </table>
        <?php endforeach; endif; ?>
    </div>

    <!-- ========== اعتماد رئيس إدارة المراجعة الداخلية ========== -->
    <h2>اعتماد رئيس إدارة المراجعة الداخلية</h2>
    <table class="info">
        <tr>
            <td class="label">الاسم</td><td><?= esc($report['head_name'] ?? '') ?: '—' ?></td>
            <td class="label">التاريخ</td><td><?= esc($report['head_approved_at'] ?? '') ?: '—' ?></td>
        </tr>
        <tr>
            <td class="label">التوقيع</td>
            <td colspan="3"><?php if (!empty($report['head_signature'])): ?><img class="sig-img" src="<?= esc($report['head_signature']) ?>"><?php else: ?>—<?php endif; ?></td>
        </tr>
    </table>
</body>
</html>
