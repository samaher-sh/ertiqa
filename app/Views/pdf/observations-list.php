<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    h2 { font-size: 13px; color: #196b7f; background: #f0f7fa; padding: 6px 10px; margin: 16px 0 6px; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 8px 10px; border: 1px solid #d8e6eb; font-size: 11px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 160px; }
    .empty-row { text-align: center; color: #9ca3af; }
</style>
</head>
<body>
    <p class="sub">الملاحظات الرقابية<?= $missionCode !== '' ? ' — ' . esc($missionCode) : '' ?></p>

    <?php if (empty($observations)): ?>
        <p class="empty-row">لا توجد ملاحظات مسجّلة</p>
    <?php else: foreach ($observations as $i => $o): ?>
        <h2><?= ($i + 1) . '. ' . esc($o['title'] ?? '') ?></h2>
        <table class="info">
            <tr>
                <td class="label">الإدارة محل المراجعة</td><td><?= esc($o['dept'] ?: '—') ?></td>
                <td class="label">التاريخ</td><td><?= esc($o['date'] ?: '—') ?></td>
            </tr>
            <tr><td class="label">الحالة (الخطر)</td><td colspan="3"><?= esc($o['risk'] ?: '—') ?></td></tr>
            <tr><td class="label">الملاحظة</td><td colspan="3"><?= nl2br(esc($o['observation'] ?: '—')) ?></td></tr>
            <tr><td class="label">المعيار أو النظام</td><td colspan="3"><?= nl2br(esc($o['standard'] ?: '—')) ?></td></tr>
            <tr><td class="label">السبب</td><td colspan="3"><?= nl2br(esc($o['reason'] ?: '—')) ?></td></tr>
            <tr><td class="label">الأثر</td><td colspan="3"><?= nl2br(esc($o['impact'] ?: '—')) ?></td></tr>
            <tr><td class="label">التوصيات</td><td colspan="3"><?= nl2br(esc($o['recommendations'] ?: '—')) ?></td></tr>
        </table>
    <?php endforeach; endif; ?>
</body>
</html>
