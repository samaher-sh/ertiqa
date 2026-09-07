<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/recommendations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
$reportApproved = (bool) ($report['head_approved_at'] ?? null);
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?>
    <div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div>
  <?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/recommendations'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $selectedMissionId ? '' : ' locked' ?>" style="display:flex;flex-direction:column;gap:16px;">
    <?php if ($selectedMissionId): ?>
      <div class="obs-list-card">
        <div class="obs-list-header">
          <div class="obs-list-header-left">
            <i data-lucide="file-check-2"></i>
            <span class="obs-list-title">بيانات التقرير</span>
          </div>
        </div>
        <div class="rec-summary-grid">
          <div class="wiz-field">
            <label class="wiz-label">الإدارة الخاضعة للمراجعة</label>
            <div class="obs-auto-field"><?= esc($mission['target_department_name'] ?? '—') ?></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">رقم التقرير</label>
            <div class="obs-auto-field"><?= esc($mission['mission_code'] ?? '—') ?></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">تاريخ التقرير</label>
            <div class="obs-auto-field"><?= esc($report['head_approved_at'] ?? '—') ?></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">نوع التقرير</label>
            <div class="obs-auto-field">نهائي</div>
          </div>
        </div>
      </div>

      <div class="obs-list-card">
        <div class="obs-list-header">
          <div class="obs-list-header-left">
            <i data-lucide="list-checks"></i>
            <span class="obs-list-title">التوصيات</span>
          </div>
        </div>

        <?php if (!$reportApproved): ?>
          <div class="obs-empty">
            <i data-lucide="alert-circle"></i>
            <p class="main">لسا ما اعتمد رئيس إدارة المراجعة الداخلية التقرير النهائي لهذي المهمة</p>
            <p class="hint">صفحة التوصيات تظهر فقط بعد اعتماد التقرير وتحديد الملاحظات المضافة له</p>
          </div>
        <?php elseif (empty($items)): ?>
          <div class="obs-empty">
            <i data-lucide="alert-circle"></i>
            <p class="main">ما فيه ملاحظات اختار الرئيس إضافتها للتقرير النهائي بعد</p>
          </div>
        <?php else: ?>
          <form method="post" action="<?= base_url('dashboard/recommendations/api/save') ?>" class="rec-form">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
            <?php foreach ($items as $i => $obs): ?>
              <?php $status = $obs['fulfillment_status'] ?? null; ?>
              <div class="rec-item">
                <p class="rec-item-title">الملاحظة بالتقرير (<?= $i + 1 ?>)</p>

                <div class="wiz-field">
                  <label class="wiz-label">نص الملاحظة</label>
                  <div class="obs-auto-field" style="white-space:pre-wrap;height:auto;min-height:42px;padding:10px 12px;"><?= esc($obs['observation_text'] ?: ($obs['title'] ?: $obs['ref_code'])) ?></div>
                </div>

                <div class="wiz-field">
                  <label class="wiz-label">رد الجهة</label>
                  <textarea name="dept_reply[<?= (int) $obs['id'] ?>]" rows="2" class="wiz-textarea plain" <?= $canEditDeptReply ? '' : 'readonly' ?>><?= esc($obs['dept_reply'] ?? '') ?></textarea>
                </div>

                <div class="wiz-field">
                  <label class="wiz-label">الحالة</label>
                  <?php if ($canEditFollowUp): ?>
                    <div class="mr-exists-toggle" style="justify-content:flex-start;">
                      <label class="mr-exists-pill yes"><input type="radio" name="follow_up[<?= (int) $obs['id'] ?>][status]" value="fulfilled" <?= $status === 'fulfilled' ? 'checked' : '' ?>> تم الاستيفاء</label>
                      <label class="mr-exists-pill no"><input type="radio" name="follow_up[<?= (int) $obs['id'] ?>][status]" value="not_fulfilled" <?= $status === 'not_fulfilled' ? 'checked' : '' ?>> لم يتم الاستيفاء</label>
                    </div>
                  <?php else: ?>
                    <div class="obs-auto-field"><?= $status === 'fulfilled' ? 'تم الاستيفاء' : ($status === 'not_fulfilled' ? 'لم يتم الاستيفاء' : 'غير محدد بعد') ?></div>
                  <?php endif; ?>
                </div>

                <div class="wiz-field">
                  <label class="wiz-label">المطلوب لاستيفاء الملاحظة</label>
                  <textarea name="follow_up[<?= (int) $obs['id'] ?>][requirement]" rows="2" class="wiz-textarea plain" <?= $canEditFollowUp ? '' : 'readonly' ?>><?= esc($obs['fulfillment_requirement'] ?? '') ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if ($canEditDeptReply || $canEditFollowUp): ?>
              <button type="submit" class="obs-form-save-bottom"><i data-lucide="save"></i> حفظ</button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
