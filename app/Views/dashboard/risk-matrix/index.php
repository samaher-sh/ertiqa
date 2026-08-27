<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/riskmatrix.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$classColors = [
    'عالي'  => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'border' => '#fca5a5', 'dot' => '#ef4444'],
    'متوسط' => ['bg' => '#fef9c3', 'text' => '#a16207', 'border' => '#fde047', 'dot' => '#eab308'],
    'منخفض' => ['bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#86efac', 'dot' => '#22c55e'],
];
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/risk-matrix'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $selectedMissionId ? '' : ' locked' ?>">
    <div class="obs-list-card">
      <div class="obs-list-header">
        <div class="obs-list-header-left">
          <i data-lucide="bar-chart-2"></i>
          <span class="obs-list-title">مصفوفة المخاطر</span>
        </div>
        <div class="obs-header-actions">
          <?php if (!$readOnly): ?>
            <a class="obs-btn-add" href="<?= base_url('dashboard/risk-matrix/edit') . ($selectedMissionId ? '?mission_id=' . $selectedMissionId : '') ?>"><i data-lucide="pencil"></i> تعديل الجدول</a>
          <?php else: ?>
            <span class="obs-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span>
          <?php endif; ?>
          <?php if ($selectedMissionId && empty($embed)): ?>
            <a class="obs-btn-pdf" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/risk-matrix/' . $selectedMissionId) ?>"><i data-lucide="file-text"></i> تصدير PDF</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (empty($rows)): ?>
        <div class="obs-empty">
          <i data-lucide="shield-alert"></i>
          <p class="main"><?= $selectedMissionId ? 'لا توجد مخاطر مسجلة لهذه المهمة' : 'اختر مهمة أولاً' ?></p>
          <?php if ($selectedMissionId && !$readOnly): ?><p class="hint">ابدأ بإضافة خطر جديد</p><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="obs-table-wrap rm-table-scroll">
          <table class="obs-table">
            <thead><tr>
              <th style="width:50px;">الرقم</th>
              <th>المخاطر</th>
              <th style="width:130px;">تقييم المخاطر</th>
              <th style="width:160px;">نوع النشاط</th>
              <th>وصف الضوابط</th>
            </tr></thead>
            <tbody>
              <?php foreach ($rows as $i => $row): ?>
                <?php $rc = !empty($row['risk_rating']) ? ($classColors[$row['risk_rating']] ?? null) : null; ?>
                <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#f6fcfe' ?>;">
                  <td style="text-align:center;"><?= $i + 1 ?></td>
                  <td><span class="obs-title-cell"><?= esc($row['risk'] ?: '—') ?></span></td>
                  <td><?php if ($rc): ?><span class="obs-pill" style="background:<?= $rc['bg'] ?>;color:<?= $rc['text'] ?>;border:1px solid <?= $rc['border'] ?>;"><span class="dot" style="background:<?= $rc['dot'] ?>;"></span><?= esc($row['risk_rating']) ?></span><?php else: ?>—<?php endif; ?></td>
                  <td><span class="obs-date-cell"><?= esc($row['activity_type'] ?: '—') ?></span></td>
                  <td><span class="obs-date-cell"><?= esc($row['controls'] ?: '—') ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/observations-page.js') ?>"></script>
<?php $this->endSection() ?>
