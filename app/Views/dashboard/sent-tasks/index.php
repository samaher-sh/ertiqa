<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/senttasks.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<div class="flex flex-col gap-5">
  <div class="st-card">
    <div class="st-card-head">
      <div class="st-card-head-left">
        <i data-lucide="send"></i>
        <div><h2>المراسلات المشتركة</h2></div>
      </div>
      <span class="st-count-badge"><?= count($missions) ?> مهمة</span>
    </div>
    <table class="st-table">
      <thead><tr>
        <th>رقم المهمة</th><th>العنوان</th><th>الإدارة الخاضعة</th><th>المراجع المسؤول</th><th>تاريخ الإنشاء</th><th>المرحلة الحالية</th><th class="center">إجراء</th>
      </tr></thead>
      <tbody>
        <?php if (empty($missions)): ?>
          <tr><td colspan="7" class="st-empty-row">لا توجد مهام مرسلة حالياً</td></tr>
        <?php else: ?>
          <?php foreach ($missions as $task): ?>
            <tr>
              <td class="st-id-cell"><?= esc($task['mission_code']) ?></td>
              <td class="st-title-cell"><?= esc($task['title']) ?></td>
              <td class="st-dept-cell"><?= esc($task['target_department_name']) ?></td>
              <td class="st-sentby-cell"><?= esc($task['reviewer_name'] ?: '—') ?></td>
              <td class="st-sentat-cell" dir="ltr"><?= esc($task['created_at'] ?: '—') ?></td>
              <td><span class="st-status-pill"><?= esc($task['stage_badge_text']) ?></span></td>
              <td style="text-align:center;"><a class="st-view-btn" style="text-decoration:none;" href="<?= base_url('dashboard/sent-tasks/' . $task['id']) ?>" title="عرض التفاصيل والسجل">عرض</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
