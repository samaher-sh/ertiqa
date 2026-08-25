<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.info td { padding: 6px 10px; border: 1px solid #d8e6eb; font-size: 11px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 150px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th { background: #f0f7fa; color: #196b7f; font-size: 10px; padding: 6px; border: 1px solid #b3d4e5; text-align: right; }
    table.grid td { padding: 6px; border: 1px solid #d8e6eb; font-size: 10px; text-align: right; vertical-align: top; }
    .section-row td { font-weight: bold; color: #196b7f; background: #f0f7fa; }
    h2 { font-size: 13px; color: #196b7f; background: #f0f7fa; padding: 6px 10px; margin: 16px 0 6px; }
    .sig-img { max-height: 40px; }
    .disclosure { background: #eaf4fa; border: 1px solid #b3d4e5; border-radius: 6px; padding: 10px 12px; margin-top: 14px; font-size: 11px; line-height: 1.8; }
    .disclosure b { color: #196b7f; }
</style>
</head>
<body>
    <p class="sub">اتفاقية مستوى الخدمة<?= $subjectDept !== '' ? ' — ' . esc($subjectDept) : '' ?></p>

    <table class="info">
        <tr>
            <td class="label">الإدارة الخاضعة للمراجعة</td><td><?= esc($subjectDept ?: '—') ?></td>
            <td class="label">تاريخ الاتفاقية</td><td><?= esc($date ?: '—') ?></td>
        </tr>
        <tr><td class="label">وصف الخدمة</td><td colspan="3"><?= nl2br(esc($desc ?: '—')) ?></td></tr>
        <tr>
            <td class="label">قنوات الاتصال المعتمدة</td>
            <td colspan="3">
                <?php if (empty($activeChannels)): ?>
                    —
                <?php else: foreach ($activeChannels as $ch): ?>
                    <div><?= esc($ch['label']) ?><?= $ch['value'] !== '' ? ': ' . esc($ch['value']) : '' ?></div>
                <?php endforeach; endif; ?>
            </td>
        </tr>
    </table>

    <table class="grid">
        <thead><tr><th>الموضوع</th><th style="width:60px;">موافق</th><th style="width:60px;">غير موافق</th><th style="width:150px;">ملاحظات</th></tr></thead>
        <tbody>
            <?php if (empty($sections)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;">لا توجد بنود مسجّلة</td></tr>
            <?php else: foreach ($sections as $si => $sec): ?>
                <tr class="section-row"><td colspan="4"><?= ($si + 1) . '. ' . esc($sec['title'] ?? '') ?></td></tr>
                <?php foreach (($sec['rows'] ?? []) as $row): ?>
                    <tr>
                        <td><?= esc($row) ?></td>
                        <td style="text-align:center;">&#9633;</td>
                        <td style="text-align:center;">&#9633;</td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h2>التوقيعات</h2>
    <table class="info">
        <tr>
            <td class="label">المراجع الرئيسي</td><td><?= esc($sigName ?: '—') ?></td>
            <td class="label">التاريخ</td><td><?= esc($sigDate ?: '—') ?></td>
        </tr>
        <tr>
            <td class="label">التوقيع</td>
            <td colspan="3"><?php if (!empty($sigSignature)): ?><img class="sig-img" src="<?= esc($sigSignature) ?>"><?php else: ?>—<?php endif; ?></td>
        </tr>
        <tr>
            <td class="label">ممثل الإدارة</td>
            <td colspan="3" style="color:#9ca3af;">تُملأ من قِبل الإدارة المستهدفة</td>
        </tr>
    </table>

    <div class="disclosure">
        <b>المسؤولية والإفصاح:</b> تؤكد إدارة المراجعة الداخلية، بأن جميع المعلومات المستلمة سوف تتعامل معها الإدارة بسرية عالية، وفقاً للمادة التاسعة عشرة من قرار مجلس الوزراء 129 بتاريخ 06/04/1428هـ اللائحة الموحدة لوحدات المراجعة الداخلية.
    </div>
</body>
</html>
