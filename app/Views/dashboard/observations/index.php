<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?>
    <div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div>
  <?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/observations'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $selectedMissionId ? '' : ' locked' ?>" style="display:flex;flex-direction:column;gap:16px;">
    <div class="obs-list-card">
      <div class="obs-list-header">
        <div class="obs-list-header-left">
          <i data-lucide="book-open"></i>
          <span class="obs-list-title">ملاحظات</span>
        </div>
        <div class="obs-header-actions">
          <?php if (!$readOnly): ?>
            <a class="obs-btn-add" href="<?= base_url('dashboard/observations/create') . ($selectedMissionId ? '?mission_id=' . $selectedMissionId : '') ?>"><i data-lucide="plus"></i> إضافة ملاحظة</a>
          <?php else: ?>
            <span class="obs-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span>
          <?php endif; ?>
          <?php if ($selectedMissionId && empty($embed)): ?>
            <a class="obs-btn-pdf" id="obsExportBtn" href="<?= base_url('dashboard/pdf/observations/' . $selectedMissionId) ?>" style="text-decoration:none;"><i data-lucide="file-text"></i> تصدير PDF</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$isAuditMember): ?><div id="obsFiltersMount"></div><?php endif; ?>

      <?php if (empty($items)): ?>
        <div class="obs-empty" id="obsEmptyState">
          <i data-lucide="alert-circle"></i>
          <p class="main"><?= $selectedMissionId ? 'لا توجد ملاحظات مسجلة لهذه المهمة' : 'اختر مهمة أولاً' ?></p>
          <?php if ($selectedMissionId && !$readOnly): ?><p class="hint">ابدأ بإضافة ملاحظة جديدة</p><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="obs-table-wrap">
          <table class="obs-table" id="obsTable">
            <thead><tr>
              <th>موضوع الملاحظة</th>
              <th style="width:160px;">الإدارة المعنية</th>
              <th style="width:110px;">التاريخ</th>
              <?php if (!$isAuditHead): ?><th style="width:60px;">الإجراءات</th><?php endif; ?>
            </tr></thead>
            <tbody>
              <?php foreach ($items as $i => $obs): ?>
                <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#f6fcfe' ?>;"
                    data-obs-row
                    data-title="<?= esc($obs['title']) ?>"
                    data-dept="<?= esc($obs['department_name'] ?? '') ?>"
                    data-ref="<?= esc($obs['ref_code'] ?? '') ?>"
                    data-risk="<?= esc($obs['risk_severity'] ?? '') ?>"
                    data-status="<?= esc($obs['status'] ?? '') ?>"
                    data-date="<?= esc($obs['observation_date'] ?? '') ?>">
                  <td><span class="obs-title-cell"><?= esc($obs['title']) ?></span></td>
                  <td><span class="obs-dept-cell"><?= esc($obs['department_name'] ?? '—') ?></span></td>
                  <td><span class="obs-date-cell"><?= esc($obs['observation_date'] ?? '—') ?></span></td>
                  <?php if (!$isAuditHead): ?>
                  <td class="obs-menu-cell">
                    <details class="obs-menu-native">
                      <summary class="obs-menu-btn"><i data-lucide="more-vertical"></i></summary>
                      <div class="obs-menu-dropdown">
                        <a class="obs-menu-item" href="<?= base_url('dashboard/observations/' . $obs['id']) ?>"><i data-lucide="eye"></i> عرض</a>
                        <?php if (!$readOnly): ?>
                          <a class="obs-menu-item" href="<?= base_url('dashboard/observations/' . $obs['id'] . '/edit') ?>"><i data-lucide="pencil"></i> تعديل</a>
                          <div class="obs-menu-sep"></div>
                          <form method="post" action="<?= base_url('dashboard/observations/api/delete/' . $obs['id']) ?>" onsubmit="return confirm('هل أنت متأكد من حذف هذه الملاحظة؟');">
                            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                            <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
                            <button type="submit" class="obs-menu-item danger"><i data-lucide="trash-2"></i> حذف</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </details>
                  </td>
                  <?php endif; ?>
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
