<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php if ($showInclusionCard): ?>
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
<?php endif; ?>
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
          <?php elseif (empty($embed)): ?>
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

    <?php if ($showInclusionCard): ?>
      <div class="wiz-card" id="frInclusionCard">
        <div class="wiz-card-head"><i data-lucide="list-checks"></i><span style="color:#fff;font-weight:700;font-size:14px;">الملاحظات المضمَّنة بالتقرير النهائي</span></div>
        <form method="post" action="<?= base_url('dashboard/reports/api/observations-inclusion') ?>" class="fr-decision-panel">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
          <div class="fr-inclusion-list">
            <?php foreach ($items as $o): ?>
              <?php $included = (int) ($o['add_to_report'] ?? 1) !== 0; ?>
              <div class="fr-inclusion-row">
                <span class="fr-inclusion-title"><?= esc($o['title'] ?: $o['ref_code']) ?></span>
                <div class="mr-exists-toggle">
                  <label class="mr-exists-pill yes"><input type="radio" name="add_to_report[<?= (int) $o['id'] ?>]" value="1" <?= $included ? 'checked' : '' ?>> تضاف</label>
                  <label class="mr-exists-pill no"><input type="radio" name="add_to_report[<?= (int) $o['id'] ?>]" value="0" <?= !$included ? 'checked' : '' ?>> لا تضاف</label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div><button type="submit" class="fr-next-btn"><i data-lucide="save"></i> حفظ</button></div>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($reportApproved && !empty($approvedItems)): ?>
      <div class="obs-list-card">
        <div class="obs-list-header">
          <div class="obs-list-header-left">
            <i data-lucide="check-circle"></i>
            <span class="obs-list-title">بيانات ما بعد اعتماد الرئيس</span>
          </div>
          <?php if (!$canEditFinalReportFields): ?>
            <span class="obs-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= base_url('dashboard/observations/api/save-final-report-fields') ?>" class="obs-frf-form">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
          <?php foreach ($approvedItems as $obs): ?>
            <div class="obs-frf-item">
              <p class="obs-frf-item-title"><?= esc($obs['title']) ?></p>
              <div class="wiz-field">
                <label class="wiz-label">الربط بمستهدفات المدينة الطبية</label>
                <textarea name="report_fields[<?= (int) $obs['id'] ?>][kamc]" rows="2" class="wiz-textarea plain" <?= $canEditFinalReportFields ? '' : 'readonly' ?>><?= esc($obs['kamc_targets_link'] ?? '') ?></textarea>
              </div>
              <div class="wiz-field">
                <label class="wiz-label">الربط بمستهدفات التحول الصحي الوطني</label>
                <textarea name="report_fields[<?= (int) $obs['id'] ?>][health]" rows="2" class="wiz-textarea plain" <?= $canEditFinalReportFields ? '' : 'readonly' ?>><?= esc($obs['health_transformation_targets_link'] ?? '') ?></textarea>
              </div>
              <div class="wiz-field">
                <label class="wiz-label">رد الإدارة (خطة تنفيذ التوصيات)</label>
                <textarea name="report_fields[<?= (int) $obs['id'] ?>][response]" rows="2" class="wiz-textarea plain" <?= $canEditFinalReportFields ? '' : 'readonly' ?>><?= esc($obs['dept_response_plan'] ?? '') ?></textarea>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if ($canEditFinalReportFields): ?>
            <button type="submit" class="obs-form-save-bottom"><i data-lucide="save"></i> حفظ</button>
          <?php endif; ?>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/observations-page.js') ?>"></script>
<?php $this->endSection() ?>
