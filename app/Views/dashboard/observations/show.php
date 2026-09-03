<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$obsRiskColors = [
    'عالي'   => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'border' => '#fca5a5', 'dot' => '#ef4444'],
    'متوسط'  => ['bg' => '#fef9c3', 'text' => '#a16207', 'border' => '#fde047', 'dot' => '#eab308'],
    'منخفض'  => ['bg' => '#eaf4fa', 'text' => '#1f5f7a', 'border' => '#b3d4e5', 'dot' => '#3185b3'],
];
$obsStatusColors = [
    'بانتظار الرد'  => ['bg' => '#fefce8', 'text' => '#a16207', 'border' => '#fde68a', 'dot' => '#f59e0b'],
    'قيد المعالجة' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
    'مغلقة'        => ['bg' => '#f0fdf4', 'text' => '#1f5f7a', 'border' => '#b3d4e5', 'dot' => '#3185b3'],
];
$rc = $obsRiskColors[$observation['risk_severity']] ?? ['bg' => '#f3f4f6', 'text' => '#4b5563', 'border' => '#e5e7eb', 'dot' => '#9ca3af'];
$sc = $obsStatusColors[$observation['status']] ?? $obsStatusColors['بانتظار الرد'];
$flash = session()->getFlashdata('success');
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-success"><?= esc($flash) ?></div><?php endif; ?>

  <div class="obs-form-card">
    <div class="obs-form-head">
      <div class="obs-form-head-left">
        <a class="obs-form-back" href="<?= base_url('dashboard/observations') . '?mission_id=' . (int) $observation['mission_id'] ?>"><i data-lucide="chevron-right"></i></a>
        <h3 class="obs-form-title">عرض الملاحظة</h3>
      </div>
      <div style="display:flex;gap:8px;">
        <a class="obs-btn-pdf" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/observation/' . $observation['id']) ?>"><i data-lucide="file-text"></i> تصدير PDF</a>
        <?php if (!$readOnly): ?>
          <a class="obs-form-save" style="text-decoration:none;" href="<?= base_url('dashboard/observations/' . $observation['id'] . '/edit') ?>"><i data-lucide="pencil"></i></a>
        <?php endif; ?>
      </div>
    </div>

    <div class="obs-form-body">
      <div class="obs-view-grid cols-4">
        <div class="obs-view-field"><span class="lbl">الإدارة محل المراجعة</span><span class="val"><?= esc($observation['department_name'] ?? '—') ?></span></div>
        <div class="obs-view-field"><span class="lbl">عنوان الملاحظة</span><span class="val"><?= esc($observation['title'] ?: '—') ?></span></div>
        <div class="obs-view-field"><span class="lbl">التاريخ</span><span class="val"><?= esc($observation['observation_date']) ?></span></div>
        <div class="obs-view-field">
          <span class="lbl">تقييم الخطر</span>
          <span class="obs-pill" style="width:fit-content;background:<?= $rc['bg'] ?>;color:<?= $rc['text'] ?>;border:1px solid <?= $rc['border'] ?>;"><span class="dot" style="background:<?= $rc['dot'] ?>;"></span><?= esc($observation['risk_severity'] ?: '—') ?></span>
        </div>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-view-grid">
        <div class="obs-view-box"><span class="lbl">الملاحظة</span><p><?= esc($observation['observation_text'] ?: '—') ?></p></div>
        <div class="obs-view-box"><span class="lbl">المعيار أو النظام</span><p><?= esc($observation['standard_text'] ?: '—') ?></p></div>
        <div class="obs-view-box"><span class="lbl">السبب</span><p><?= esc($observation['reason_text'] ?: '—') ?></p></div>
        <div class="obs-view-box"><span class="lbl">الأثر</span><p><?= esc($observation['impact_text'] ?: '—') ?></p></div>
        <div class="obs-view-box" style="grid-column:1/-1;"><span class="lbl">التوصيات</span><p><?= esc($observation['recommendations_text'] ?: '—') ?></p></div>
      </div>

      <div class="obs-view-footer">
        <div class="obs-view-field">
          <span class="lbl">الحالة</span>
          <span class="obs-pill" style="width:fit-content;background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;border:1px solid <?= $sc['border'] ?>;"><span class="dot" style="background:<?= $sc['dot'] ?>;"></span><?= esc($observation['status']) ?></span>
        </div>
      </div>

      <div class="obs-divider"></div>

      <?= view('dashboard/observations/_attachments', [
          'observationId' => (int) $observation['id'],
          'attachments'   => $attachments ?? [],
          'canUpload'     => !$readOnly,
      ]) ?>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/observations-page.js') ?>"></script>
<?php $this->endSection() ?>
