<?php
/**
 * كومبوننت مرفقات ملاحظة — مشترك بين صفحتَي العرض والتعديل. يحتاج ملاحظة
 * محفوظة أصلًا (لها id حقيقي)، فما يظهر بنموذج إضافة ملاحظة جديدة قبل الحفظ.
 * المتغيرات المتوقعة: $observationId, $attachments, $canUpload
 */
?>
<div class="obs-attach-section">
  <div class="obs-attach-section-head">
    <span class="wiz-label" style="margin:0;">المرفقات</span>
    <?php if ($canUpload): ?><span id="obsAttachMount" data-observation-id="<?= (int) $observationId ?>"></span><?php endif; ?>
  </div>
  <div id="obsAttachListWrap">
    <?php if (!empty($attachments)): ?>
      <div class="obs-attach-item-list">
        <?php foreach ($attachments as $a): ?>
          <div class="obs-attach-item" data-attach-name="<?= esc($a['file_name'], 'attr') ?>" data-attach-url="<?= base_url('dashboard/documents/download/' . $a['id']) ?>">
            <span class="obs-attach-item-name"><i data-lucide="paperclip"></i> <?= esc($a['file_name']) ?></span>
            <div class="obs-attach-item-actions">
              <button type="button" class="obs-attach-view-btn" title="عرض"><i data-lucide="eye"></i></button>
              <?php if ($canUpload && (int) ($a['uploaded_by'] ?? 0) === (int) session()->get('user_id')): ?>
                <button type="button" class="obs-attach-del-btn" data-attach-id="<?= (int) $a['id'] ?>" title="حذف"><i data-lucide="trash-2"></i></button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <span class="obs-attach-empty-msg">لا توجد مرفقات</span>
    <?php endif; ?>
  </div>
</div>
