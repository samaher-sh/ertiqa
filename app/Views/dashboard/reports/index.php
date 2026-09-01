<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/documentrequests.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>
  <div class="fr-header-card">
    <div class="fr-header-bar">
      <i class="main" data-lucide="file-text"></i>
      <div>
        <h2><?= $isPresident ? 'التقارير التي تتطلب المراجعة' : 'التقارير النهائية' ?></h2>
        <?php if ($isPresident): ?><p>تقارير تحت المراجعة تنتظر الاعتماد</p><?php endif; ?>
      </div>
      <span class="fr-count-badge"><?= count($reports) ?> تقرير</span>
      <div class="fr-header-actions">
        <details class="wiz-add-doc-details" style="margin:0;position:relative;">
          <summary class="fr-filters-icon-btn" style="list-style:none;cursor:pointer;" title="الفلاتر"><i data-lucide="filter"></i></summary>
          <form method="get" action="<?= base_url('dashboard/reports') ?>" class="fr-filters-body" style="display:flex;gap:10px;flex-wrap:wrap;position:absolute;left:0;top:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:20;min-width:220px;">
            <select name="year" class="wiz-select<?= $yearFilter ? ' filled' : '' ?>" onchange="this.form.submit()">
              <option value="">جميع السنوات</option>
              <?php $curYear = (int) date('Y'); for ($y = $curYear; $y <= max(2030, $curYear + 3); $y++): ?>
                <option value="<?= $y ?>" <?= $yearFilter === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
            <?php if (!empty($deptOptions)): ?>
            <select name="dept" class="wiz-select<?= $deptFilter ? ' filled' : '' ?>" onchange="this.form.submit()">
              <option value="">كل الإدارات</option>
              <?php foreach ($deptOptions as $deptId => $deptName): ?>
                <option value="<?= (int) $deptId ?>" <?= $deptFilter === (string) $deptId ? 'selected' : '' ?>><?= esc($deptName) ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (!$canCreate): ?>
            <select name="status" class="wiz-select<?= $statusFilter ? ' filled' : '' ?>" onchange="this.form.submit()">
              <option value="">كل الحالات</option>
              <option value="pending_signatures" <?= $statusFilter === 'pending_signatures' ? 'selected' : '' ?>>بانتظار الاعتماد</option>
              <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>معتمد</option>
            </select>
            <?php endif; ?>
            <noscript><button type="submit" class="wiz-btn wiz-btn-outline">تصفية</button></noscript>
          </form>
        </details>
        <?php if ($canCreate): ?>
          <details class="wiz-add-doc-details" style="margin:0;position:relative;">
            <summary class="fr-create-btn" style="list-style:none;cursor:pointer;"><i data-lucide="plus"></i> إنشاء تقرير</summary>
            <div style="position:absolute;left:0;top:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:20;min-width:280px;">
              <?= view('dashboard/observations/_linked_task_selector', [
                  'missions'          => $missions,
                  'selectedMissionId' => '',
                  'formAction'        => base_url('dashboard/reports'),
              ]) ?>
            </div>
          </details>
        <?php endif; ?>
        <?php if ($isReadOnlyViewer): ?><span class="fr-readonly-badge"><i data-lucide="lock" style="width:9px;height:9px;"></i> عرض فقط</span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="fr-table-card">
    <table class="fr-table">
      <thead><tr><th>رقم المهمة</th><th>الإدارة</th><th>الإدارة المستهدفة</th><th>السنة</th><th>التاريخ</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr><td colspan="7" class="fr-empty-row">لا توجد تقارير مطابقة</td></tr>
        <?php else: ?>
          <?php foreach ($reports as $i => $r): ?>
            <?php $approved = $r['status'] === 'sent'; ?>
            <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#f5fafd' ?>;">
              <td><span class="fr-taskid-pill" dir="ltr"><?= esc($r['mission_code']) ?></span></td>
              <td style="font-size:12px;font-weight:600;color:#374151;"><?= esc($r['audit_dept_name']) ?></td>
              <td style="font-size:12px;color:#6b7280;"><?= esc($r['target_dept_name']) ?></td>
              <td style="font-size:12px;color:#6b7280;"><?= esc($r['year']) ?></td>
              <td style="font-size:12px;color:#6b7280;"><?= esc(substr($r['created_at'] ?? '', 0, 10)) ?></td>
              <?php
              $rejected = $r['status'] === 'draft' && !empty($r['head_rejection_note']);
              if ($approved) {
                  $statusLabel = 'معتمد'; $pillBg = '#f0fdf4'; $pillColor = '#1f5f7a'; $dotColor = '#3185b3';
              } elseif ($rejected) {
                  $statusLabel = 'مرفوض'; $pillBg = '#fef2f2'; $pillColor = '#dc2626'; $dotColor = '#dc2626';
              } elseif ($r['status'] === 'pending_signatures') {
                  $statusLabel = 'بانتظار الاعتماد'; $pillBg = '#fef9ec'; $pillColor = '#b45309'; $dotColor = '#f59e0b';
              } else {
                  $statusLabel = 'تحت الإعداد'; $pillBg = '#fef9ec'; $pillColor = '#b45309'; $dotColor = '#f59e0b';
              }
              ?>
              <td><span class="fr-status-pill" style="background:<?= $pillBg ?>;color:<?= $pillColor ?>;"><span class="dot" style="background:<?= $dotColor ?>;"></span><?= esc($statusLabel) ?></span></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                  <a class="fr-action-view-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports/' . $r['mission_id']) ?>">عرض</a>
                  <?php if ($approved): ?><a class="fr-action-pdf-btn" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/final-report/' . $r['mission_id']) ?>" title="تصدير PDF"><i data-lucide="file-down"></i></a><?php endif; ?>
                </div>
              </td>
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
