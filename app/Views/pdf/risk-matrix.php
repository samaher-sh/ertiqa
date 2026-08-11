<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #152c33; direction: rtl; }
    .sub { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
    table.rm { width: 100%; border-collapse: collapse; }
    table.rm th { background: #f0f7fa; color: #196b7f; font-size: 11px; padding: 8px; border: 1px solid #b3d4e5; text-align: right; }
    table.rm td { padding: 8px; border: 1px solid #d8e6eb; font-size: 11px; text-align: right; vertical-align: top; }
    .rating-high { background: #f0fdf6; }
    .rating-medium { background: #fffceb; }
    .rating-low { background: #fff8f8; }
</style>
</head>
<body>
    <p class="sub">مصفوفة المخاطر — <?= esc($mission['mission_code']) ?> — <?= esc($targetDept['name_ar'] ?? '') ?> — <?= esc($mission['year']) ?></p>

    <table class="rm">
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
            <?php if (empty($items)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">لا توجد مخاطر مسجّلة</td></tr>
            <?php else: ?>
                <?php foreach ($items as $i => $it): ?>
                    <?php
                        $cls = $it['risk_rating'] === 'عالي' ? 'rating-high' : ($it['risk_rating'] === 'متوسط' ? 'rating-medium' : ($it['risk_rating'] === 'منخفض' ? 'rating-low' : ''));
                    ?>
                    <tr class="<?= $cls ?>">
                        <td><?= $i + 1 ?></td>
                        <td><?= nl2br(esc($it['risk'])) ?></td>
                        <td><?= esc($it['risk_rating'] ?: '—') ?></td>
                        <td><?= nl2br(esc($it['controls'])) ?></td>
                        <td><?= esc($it['activity_type']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
