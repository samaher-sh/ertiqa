<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 8px 10px; border: 1px solid #d8e6eb; font-size: 11px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 160px; }
</style>
</head>
<body>
    <p class="sub">ملاحظة رقابية<?= $ref !== '' ? ' — ' . esc($ref) : '' ?><?= $missionCode !== '' ? ' — ' . esc($missionCode) : '' ?></p>

    <table class="info">
        <tr><td class="label">عنوان الملاحظة</td><td colspan="3"><?= esc($title ?: '—') ?></td></tr>
        <tr>
            <td class="label">الإدارة محل المراجعة</td><td><?= esc($dept ?: '—') ?></td>
            <td class="label">التاريخ</td><td><?= esc($date ?: '—') ?></td>
        </tr>
        <tr><td class="label">الحالة (الخطر)</td><td colspan="3"><?= esc($risk ?: '—') ?></td></tr>
        <tr><td class="label">الملاحظة</td><td colspan="3"><?= nl2br(esc($observation ?: '—')) ?></td></tr>
        <tr><td class="label">المعيار أو النظام</td><td colspan="3"><?= nl2br(esc($standard ?: '—')) ?></td></tr>
        <tr><td class="label">السبب</td><td colspan="3"><?= nl2br(esc($reason ?: '—')) ?></td></tr>
        <tr><td class="label">الأثر</td><td colspan="3"><?= nl2br(esc($impact ?: '—')) ?></td></tr>
        <tr><td class="label">التوصيات</td><td colspan="3"><?= nl2br(esc($recommendations ?: '—')) ?></td></tr>
    </table>
</body>
</html>
