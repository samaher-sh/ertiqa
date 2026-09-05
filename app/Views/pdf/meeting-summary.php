<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    h2 { font-size: 13px; color: #196b7f; background: #f0f7fa; padding: 6px 10px; margin: 16px 0 6px; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.info td { padding: 6px 10px; border: 1px solid #d8e6eb; font-size: 11px; }
    table.info td.label { background: #f8fafc; font-weight: bold; width: 130px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th { background: #f0f7fa; color: #196b7f; font-size: 10px; padding: 6px; border: 1px solid #b3d4e5; text-align: right; }
    table.grid td { padding: 6px; border: 1px solid #d8e6eb; font-size: 10px; text-align: right; vertical-align: top; }
    .sig-img { max-height: 30px; }
</style>
</head>
<body>
    <p class="sub">ملخص الاجتماع — <?= esc($mission['mission_code']) ?> — <?= esc($targetDept['name_ar'] ?? '') ?></p>

    <h2>بيانات الاجتماع</h2>
    <table class="info">
        <tr><td class="label">التاريخ</td><td><?= esc($meeting['meeting_date'] ?? '—') ?></td>
            <td class="label">الوقت</td><td><?= esc($meeting['meeting_time'] ?? '—') ?></td></tr>
        <tr><td class="label">المكان</td><td colspan="3"><?= esc($meeting['location'] ?? '—') ?></td></tr>
        <tr><td class="label">عنوان المهمة</td><td colspan="3"><?= esc($meeting['title'] ?? '—') ?></td></tr>
        <tr><td class="label">الهدف</td><td colspan="3"><?= nl2br(esc($meeting['objective'] ?? '—')) ?></td></tr>
    </table>

    <h2>قائمة الحضور</h2>
    <table class="grid">
        <thead><tr><th style="width:30px;">م</th><th>الاسم</th><th>الإدارة</th><th>الوظيفة</th></tr></thead>
        <tbody>
            <?php if (empty($attendees)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;">لا يوجد حضور مسجّل</td></tr>
            <?php else: foreach ($attendees as $i => $a): ?>
                <tr><td><?= $i + 1 ?></td><td><?= esc($a['external_name']) ?></td><td><?= esc($a['attendee_dept']) ?></td><td><?= esc($a['attendee_position']) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h2>ملخص ما تم مناقشته</h2>
    <table class="grid">
        <thead><tr><th>النقطة</th><th>الإفادة</th></tr></thead>
        <tbody>
            <?php if (empty($points)): ?>
                <tr><td colspan="2" style="text-align:center;color:#9ca3af;">لا توجد نقاط مسجّلة</td></tr>
            <?php else: foreach ($points as $p): ?>
                <tr><td><?= nl2br(esc($p['point_text'])) ?></td><td><?= nl2br(esc($p['statement'] ?? '' ?: '—')) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h2>الاعتماد</h2>
    <table class="grid">
        <thead><tr><th>البيان</th><th>الاسم</th><th>الوظيفة</th><th>الاعتماد</th><th>التاريخ</th></tr></thead>
        <tbody>
            <?php if (empty($approvals)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">لا يوجد اعتماد مسجّل</td></tr>
            <?php else: foreach ($approvals as $ap): ?>
                <tr>
                    <td><?= esc($ap['statement']) ?></td>
                    <td><?= esc($ap['signer_name']) ?></td>
                    <td><?= esc($ap['position']) ?></td>
                    <td><?= !empty($ap['signature_data']) ? '✓ معتمد' : '—' ?></td>
                    <td><?= esc($ap['approval_date'] ?: '—') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
