<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';

$checkedCount = count(array_filter($items, fn($it) => (int) $it['is_checked'] === 1));
$total = count($items);
$expandedItem = null;
foreach ($items as $it) { if ((int) $it['section_number'] === $expandedStep) { $expandedItem = $it; break; } }

$firstUncheckedNum = null;
foreach ($items as $it) { if ((int) $it['is_checked'] !== 1) { $firstUncheckedNum = (int) $it['section_number']; break; } }

$stepState = function (array $it) use ($readOnlyViewer, $firstUncheckedNum): string {
    if ((int) $it['is_checked'] === 1) return 'done';
    if ($readOnlyViewer) return 'active';
    return ((int) $it['section_number'] === $firstUncheckedNum) ? 'active' : 'locked';
};
$expandedState = $expandedItem ? $stepState($expandedItem) : '';
$expandedIsDone = $expandedItem ? (bool) ($completion[$expandedItem['section_number']] ?? false) : false;

$isLastStep = !empty($items) && $expandedStep === (int) $items[count($items) - 1]['section_number'];
$priorChecked = true;
foreach (array_slice($items, 0, -1) as $it) { if ((int) $it['is_checked'] !== 1) { $priorChecked = false; break; } }
?>
<div class="flex flex-col gap-5">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <div class="fr-topbar">
    <a class="fr-back-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports') ?>"><i data-lucide="chevron-right"></i> التقارير النهائية</a>
    <div class="fr-topbar-sep"></div>
    <div><h2>إنشاء تقرير / متابعة الاعتماد</h2><p><?= esc($mission['mission_code']) ?> — <?= esc($mission['title']) ?></p></div>
  </div>

  <div class="fr-stepper-card">
    <div class="fr-stepper-head">
      <div class="fr-stepper-head-top">
        <i data-lucide="clipboard-list"></i><span class="t">مراحل الاعتماد</span>
        <span class="fr-phases-count" dir="ltr"><?= $checkedCount ?> / <?= $total ?></span>
      </div>
    </div>

    <div class="fr-hstep-track">
      <?php foreach ($items as $idx => $it): ?>
        <?php $state = $stepState($it); $isExpanded = (int) $it['section_number'] === $expandedStep; $isLast = $idx === count($items) - 1; ?>
        <div class="fr-hstep-node <?= $state ?><?= $isExpanded ? ' expanded' : '' ?>">
          <a class="fr-hstep-circle-btn" style="text-decoration:none;<?= $state === 'locked' ? 'pointer-events:none;opacity:.5;' : '' ?>" href="<?= base_url('dashboard/reports/' . $mission['id']) ?>?step=<?= (int) $it['section_number'] ?>" title="<?= esc($it['section_title']) ?>">
            <span class="fr-hstep-circle"><?= $state === 'done' ? '<i data-lucide="check"></i>' : (int) $it['section_number'] ?></span>
          </a>
          <span class="fr-hstep-label"><?= esc($it['section_title']) ?></span>
        </div>
        <?php if (!$isLast): ?><div class="fr-hstep-line <?= $state === 'done' ? 'done' : '' ?>"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($expandedItem): ?>
    <div class="fr-step-detail">
      <div class="fr-step-detail-head">
        <span class="fr-step-detail-title"><?= esc($expandedItem['section_title']) ?></span>
        <span class="fr-step-status <?= $expandedState ?>"><?= $expandedState === 'done' ? 'معتمدة' : ($expandedState === 'active' ? 'الحالية' : 'قادمة') ?></span>
      </div>
      <div class="fr-step-detail-body">
        <?php if ($expandedStep === 1): ?>
          <div class="fr-preview-grid"><div class="fr-preview-field span2">
            <span class="lbl">الخطاب الرسمي</span>
            <span class="val"><a href="<?= base_url('dashboard/pdf/mission-letter/' . $mission['id']) ?>" target="_blank" style="color:var(--p);text-decoration:underline;">فتح الخطاب الرسمي (PDF)</a></span>
          </div></div>
        <?php elseif ($expandedStep === 2 && $section2Data): ?>
          <?php $ag = $section2Data['agreement']; ?>
          <div class="fr-preview-grid">
            <div class="fr-preview-field"><span class="lbl">حالة الاتفاقية</span><span class="val"><?= $ag && $ag['status'] === 'submitted' ? 'مُرسَلة' : 'لم تُرسَل بعد' ?></span></div>
            <div class="fr-preview-field"><span class="lbl">اسم المنسّق</span><span class="val"><?= esc($ag['coordinator_name'] ?? '—') ?></span></div>
          </div>
          <?php if (!empty($section2Data['responses'])): ?>
            <div class="obs-table-wrap" style="margin-top:12px;">
              <table class="obs-table">
                <thead><tr><th>البند</th><th style="width:100px;">موافق</th><th>ملاحظة</th></tr></thead>
                <tbody>
                  <?php foreach ($section2Data['responses'] as $r): ?>
                    <tr><td><?= esc($r['row_text']) ?></td><td><?= !empty($r['agree']) ? 'نعم' : (!empty($r['disagree']) ? 'لا' : '—') ?></td><td><?= esc($r['note'] ?: '—') ?></td></tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php elseif ($stepUrl): ?>
          <div class="fr-preview-grid"><div class="fr-preview-field span2">
            <span class="lbl"><?= esc($expandedItem['section_title']) ?></span>
            <span class="val"><a href="<?= base_url($stepUrl) ?>?mission_id=<?= (int) $mission['id'] ?>" target="_blank" style="color:var(--p);text-decoration:underline;">فتح "<?= esc($expandedItem['section_title']) ?>" لهذه المهمة</a></span>
          </div></div>
        <?php endif; ?>
      </div>
      <?php if (!$expandedIsDone): ?><p class="fr-step-hint">لم تكتمل بيانات هذه المرحلة بعد</p><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="fr-phases-footer">
      <?php if ($report['status'] === 'sent'): ?>
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:12px;color:#6b7280;">معتمد</span>
          <a class="fr-action-pdf-btn" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/final-report/' . $mission['id']) ?>" title="تصدير PDF"><i data-lucide="file-down"></i> تصدير PDF</a>
        </div>
      <?php elseif ($isAuditHead): ?>
        <?php if ($report['status'] === 'pending_signatures'): ?>
          <form method="post" action="<?= base_url('dashboard/reports/api/approve') ?>">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
            <button type="submit" class="fr-submit-btn"><i data-lucide="check-check"></i> اعتماد التقرير</button>
          </form>
        <?php else: ?>
          <span style="font-size:12px;color:#6b7280;"><?= $report['status'] === 'pending_signatures' ? 'تحت المراجعة' : 'تحت الإعداد' ?></span>
        <?php endif; ?>
      <?php elseif ($readOnlyViewer): ?>
        <span style="font-size:12px;color:#6b7280;"><?= $report['status'] === 'pending_signatures' ? 'تحت المراجعة' : 'تحت الإعداد' ?></span>
      <?php elseif ($expandedItem): ?>
        <?php if ($isLastStep): ?>
          <form method="post" action="<?= base_url('dashboard/reports/api/finalize') ?>">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">
            <button type="submit" class="fr-submit-btn" <?= (!$expandedIsDone || !$priorChecked || $report['status'] !== 'draft') ? 'disabled' : '' ?>><i data-lucide="send"></i> <?= $report['status'] === 'draft' ? 'اعتماد التقرير وإرساله' : 'تم الإرسال' ?></button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= base_url('dashboard/reports/api/toggle-check') ?>">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
            <input type="hidden" name="section_number" value="<?= (int) $expandedItem['section_number'] ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">
            <button type="submit" class="fr-next-btn" <?= !$expandedIsDone ? 'disabled' : '' ?>>التالي <i data-lucide="chevron-left"></i></button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
