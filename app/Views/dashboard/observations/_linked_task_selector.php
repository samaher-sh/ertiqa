<?php
/**
 * منتقي "المهمة / الإدارة المرتبطة" — نفس شكل renderLinkedTaskSelector() بـ
 * observations.js بالضبط، لكن كنموذج <form method="get"> حقيقي: تبديل المهمة
 * = تنقل GET حقيقي (mission_id=X بالرابط)، يشتغل بدون أي جافاسكربت.
 *
 * المتغيرات المتوقعة: $missions (array), $selectedMissionId (int), $formAction (string)
 */
$selected = null;
foreach ($missions as $m) {
    if ((int) $m['id'] === (int) $selectedMissionId) { $selected = $m; break; }
}
?>
<div class="obs-linked-card" style="border-color:<?= $selectedMissionId ? 'var(--pb)' : '#fbbf24' ?>;">
  <div class="obs-linked-band" style="background:<?= $selectedMissionId ? 'var(--p)' : '#fffbeb' ?>;border-bottom-color:<?= $selectedMissionId ? 'var(--pb)' : '#fde68a' ?>;">
    <i data-lucide="clipboard-list" style="color:<?= $selectedMissionId ? '#fff' : '#b45309' ?>;"></i>
    <p class="obs-linked-title" style="color:<?= $selectedMissionId ? '#fff' : '#92400e' ?>;">المهمة / الإدارة المرتبطة</p>
    <?php if (!$selectedMissionId): ?><span class="obs-linked-badge-req">مطلوب</span><?php endif; ?>
    <?php if ($selectedMissionId && $selected): ?><span class="obs-linked-badge-sel" dir="ltr"><?= esc($selected['mission_code']) ?></span><?php endif; ?>
  </div>
  <div class="obs-linked-body">
    <form method="get" action="<?= esc($formAction) ?>" id="obsTaskSelectForm">
      <label class="wiz-label" for="obsTaskSelect">اختر المهمة / الإدارة المرتبطة <span class="wiz-req">*</span></label>
      <select name="mission_id" id="obsTaskSelect" class="wiz-select<?= $selectedMissionId ? ' filled' : '' ?>" style="<?= !$selectedMissionId ? 'border-color:#fcd34d;background:#fffbeb;' : '' ?>" onchange="this.form.submit()">
        <option value="">— اختر المهمة المرتبطة —</option>
        <?php foreach ($missions as $m): ?>
          <option value="<?= (int) $m['id'] ?>" <?= (int) $selectedMissionId === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['mission_code']) ?> — <?= esc($m['target_department_name'] ?? '') ?> (<?= esc($m['year']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit" class="obs-btn-pdf" style="margin-top:8px;">عرض</button></noscript>
    </form>
    <?php if (!$selectedMissionId): ?><p class="wiz-error-text" style="color:#b45309;">يرجى تحديد المهمة المرتبطة قبل تعبئة النموذج</p><?php endif; ?>
  </div>
</div>
