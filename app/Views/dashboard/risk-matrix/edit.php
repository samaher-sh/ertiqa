<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/riskmatrix.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$errorMsg = session()->getFlashdata('error');
$backUrl = base_url('dashboard/risk-matrix') . ($selectedMissionId ? '?mission_id=' . $selectedMissionId : '');
?>
<div class="flex flex-col gap-4">
  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/risk-matrix/edit'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $selectedMissionId ? '' : ' locked' ?>">
    <div class="obs-form-card">
      <div class="obs-form-head">
        <div class="obs-form-head-left">
          <a class="obs-form-back" href="<?= esc($backUrl) ?>"><i data-lucide="chevron-right"></i></a>
          <h3 class="obs-form-title">تعديل مصفوفة المخاطر</h3>
        </div>
      </div>

      <div class="obs-form-body">
        <?php if ($errorMsg): ?><div class="obs-alert obs-alert-error"><?= esc($errorMsg) ?></div><?php endif; ?>

        <form method="post" action="<?= base_url('dashboard/risk-matrix/api/save') ?>" id="rmEditForm">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">

          <div id="rmRowsWrap" style="display:flex;flex-direction:column;gap:16px;">
            <?php if (empty($rows)): ?>
              <div class="obs-empty" id="rmEmptyState"><i data-lucide="shield-alert"></i><p class="main">لا توجد صفوف بعد</p><p class="hint">اضغطي "إضافة صف" لبدء تعبئة الجدول</p></div>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
              <div class="rm-edit-row" data-rm-row>
                <div class="rm-edit-row-head">
                  <span class="rm-edit-row-num">#<?= $i + 1 ?></span>
                  <button type="submit" name="form_action" value="remove_row" formnovalidate class="obs-menu-item danger" style="width:auto;padding:4px 10px;" onclick="document.getElementById('rmRemoveIndex').value='<?= $i ?>'"><i data-lucide="trash-2"></i> حذف الصف</button>
                </div>
                <div class="wiz-field">
                  <label class="wiz-label">المخاطر <span class="wiz-req">*</span></label>
                  <textarea name="rows[<?= $i ?>][risk]" rows="2" class="wiz-textarea plain" placeholder="أدخل وصف الخطر..."><?= esc($row['risk'] ?? '') ?></textarea>
                </div>
                <div class="obs-grid-2">
                  <div class="wiz-field">
                    <label class="wiz-label">تقييم المخاطر</label>
                    <select name="rows[<?= $i ?>][risk_rating]" class="wiz-select">
                      <option value="">— اختر —</option>
                      <?php foreach (['عالي', 'متوسط', 'منخفض'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($row['risk_rating'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="wiz-field">
                    <label class="wiz-label">نوع النشاط</label>
                    <input type="text" name="rows[<?= $i ?>][activity_type]" class="wiz-input plain" value="<?= esc($row['activity_type'] ?? '') ?>">
                  </div>
                  <div class="wiz-field" style="grid-column:1/-1;">
                    <label class="wiz-label">وصف الضوابط</label>
                    <textarea name="rows[<?= $i ?>][controls]" rows="2" class="wiz-textarea plain"><?= esc($row['controls'] ?? '') ?></textarea>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <input type="hidden" name="remove_index" id="rmRemoveIndex" value="">

          <div class="obs-divider" style="margin-top:16px;"></div>

          <div style="display:flex;justify-content:space-between;gap:10px;margin-top:16px;">
            <button type="submit" name="form_action" value="add_row" formnovalidate class="wiz-btn wiz-btn-outline" id="rmAddRowBtn"><i data-lucide="plus"></i> إضافة صف</button>
            <button type="submit" name="form_action" value="save" class="wiz-btn wiz-btn-primary"><i data-lucide="check"></i> حفظ مصفوفة المخاطر</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/riskmatrix-page.js') ?>"></script>
<?php $this->endSection() ?>
