<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$errorMsg = session()->getFlashdata('error');
$v = fn($f, $default = '') => old($f) ?? $default;
?>
<div class="flex flex-col gap-4">
  <?php if ($errorMsg): ?><div class="obs-alert obs-alert-error"><?= esc($errorMsg) ?></div><?php endif; ?>

  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="plus"></i>
      <div><h2>طلب المراجعة الداخلية</h2></div>
    </div>
    <div class="wiz-card-body">
      <form method="get" action="<?= base_url('dashboard/new-task') ?>" id="deptCascadeForm">
        <div class="wiz-field">
          <label class="wiz-label">الإدارة <span class="wiz-req">*</span></label>
          <select name="main_dept_id" id="mainDeptSelect" class="wiz-select<?= $selectedDeptId ? ' filled' : '' ?>" onchange="this.form.submit()">
            <option value="">— اختر —</option>
            <?php foreach ($mainDepts as $d): ?>
              <option value="<?= (int) $d['id'] ?>" <?= $selectedDeptId === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['name_ar']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <noscript><button type="submit" class="wiz-btn wiz-btn-outline" style="margin-top:8px;">تحديث قائمة الإدارات الفرعية</button></noscript>
      </form>

      <form method="post" action="<?= base_url('dashboard/new-task') ?>" id="newTaskForm" style="display:flex;flex-direction:column;gap:20px;margin-top:16px;">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات الإدارة</p>
          <div class="wiz-field">
            <label class="wiz-label">الإدارة <span class="wiz-req">*</span></label>
            <div class="obs-auto-field"><span class="val"><?= $selectedDeptId ? esc(current(array_filter($mainDepts, fn($d) => (int) $d['id'] === $selectedDeptId))['name_ar'] ?? '') : '— اختاري الإدارة من الحقل أعلاه أولًا —' ?></span></div>
            <input type="hidden" name="main_dept_id" value="<?= $selectedDeptId ?>">
          </div>
          <div class="wiz-field">
            <label class="wiz-label">الإدارة المستهدفة <span class="wiz-req">*</span></label>
            <select name="target_dept_id" class="wiz-select" <?= $selectedDeptId ? '' : 'disabled' ?>>
              <option value="">— اختر —</option>
              <?php foreach ($subDepts as $sd): ?>
                <option value="<?= (int) $sd['id'] ?>" <?= $v('target_dept_id') == $sd['id'] ? 'selected' : '' ?>><?= esc($sd['name_ar']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!$selectedDeptId): ?><p class="wiz-hint">يُرجى اختيار الإدارة أولاً لتفعيل هذا الحقل</p><?php endif; ?>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">السنة</label>
            <select name="year" class="wiz-select filled">
              <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $v('year', (string) date('Y')) === $y ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="wiz-section">
          <p class="wiz-section-title">المراد مناقشته في الاجتماع <span class="wiz-req">*</span></p>
          <textarea name="procedure" rows="4" class="wiz-textarea" placeholder="أدخل المراد مناقشته في الاجتماع هنا..."><?= esc($v('procedure')) ?></textarea>
        </div>

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات المراجع</p>
          <div class="wiz-field">
            <label class="wiz-label">اسم المراجع الرئيسي <span class="wiz-req">*</span></label>
            <input name="reviewer_name" type="text" class="wiz-input plain" placeholder="الاسم كاملاً" value="<?= esc($v('reviewer_name')) ?>">
          </div>
          <div class="wiz-field">
            <label class="wiz-label">البريد الإلكتروني <span class="wiz-req">*</span></label>
            <input name="reviewer_email" type="email" dir="ltr" style="text-align:left;" class="wiz-input plain" placeholder="example@kamc.med.sa" value="<?= esc($v('reviewer_email')) ?>">
          </div>
          <div class="wiz-field">
            <label class="wiz-label">رقم الجوال <span class="wiz-req">*</span></label>
            <input name="reviewer_phone" type="tel" dir="ltr" style="text-align:left;" class="wiz-input plain" placeholder="05XXXXXXXX" value="<?= esc($v('reviewer_phone')) ?>">
          </div>
        </div>

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات المدير</p>
          <div class="wiz-field">
            <label class="wiz-label">اسم المدير</label>
            <input name="director_name" type="text" class="wiz-input plain" placeholder="الاسم كاملاً" value="<?= esc($v('director_name')) ?>">
          </div>
        </div>

        <div class="wiz-channels" style="padding:0;">
          <p class="wiz-channels-title">قنوات الاتصال المعتمدة (اتفاقية مستوى الخدمة)</p>
          <?php foreach (['email' => ['البريد الإلكتروني', 'email'], 'memo' => ['المذكرات الداخلية', 'text'], 'phone' => ['الهاتف الداخلي', 'tel']] as $chKey => $chMeta): ?>
            <div class="wiz-channel">
              <label class="wiz-channel-head" style="cursor:pointer;">
                <input type="checkbox" name="channel_<?= $chKey ?>_active" value="1" <?= $v('channel_' . $chKey . '_active') ? 'checked' : '' ?> style="width:16px;height:16px;">
                <span><?= $chMeta[0] ?></span>
              </label>
              <div class="wiz-channel-body">
                <input type="<?= $chMeta[1] ?>" name="channel_<?= $chKey ?>_value" class="wiz-input plain" value="<?= esc($v('channel_' . $chKey . '_value')) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;justify-content:flex-end;">
          <button type="submit" class="wiz-btn wiz-btn-primary"><i data-lucide="check"></i> إنشاء المهمة</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
