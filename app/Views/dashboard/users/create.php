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

  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="user-plus"></i>
      <div><h2>إضافة مستخدم جديد</h2><p>يُضاف المستخدم من الدليل الموحّد (LDAP) بعد التحقق من وجوده فعليًا</p></div>
    </div>

    <form method="post" action="<?= base_url('dashboard/users') ?>" id="addUserForm" style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

      <div class="wiz-field">
        <label class="wiz-label">الرقم الوظيفي <span class="wiz-req">*</span></label>
        <div style="display:flex;gap:8px;">
          <input type="text" name="employee_number" id="employeeNumberInput" inputmode="numeric" class="wiz-input" style="flex:1;" placeholder="أدخل الرقم الوظيفي" value="<?= esc(old('employee_number') ?? '') ?>" required>
          <button type="button" class="wiz-btn wiz-btn-outline" id="searchLdapBtn">بحث</button>
        </div>
      </div>

      <div id="ldapPreviewBox" class="fr-preview-grid" style="display:none;">
        <div class="fr-preview-field"><span class="lbl">الاسم</span><span class="val" id="ldapPreviewName">—</span></div>
        <div class="fr-preview-field"><span class="lbl">البريد الإلكتروني</span><span class="val" id="ldapPreviewEmail">—</span></div>
        <div class="fr-preview-field span2"><span class="lbl">الإدارة (حسب الدليل الموحّد)</span><span class="val" id="ldapPreviewDept">—</span></div>
      </div>
      <p id="ldapPreviewEmpty" class="fr-step-hint" style="display:none;">لم يُعثر على موظف بهذا الرقم الوظيفي بالدليل الموحّد.</p>

      <div class="wiz-field">
        <label class="wiz-label">الدور بنظام ارتقاء <span class="wiz-req">*</span></label>
        <select name="role_id" class="wiz-select" required>
          <option value="">اختر الدور</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int) $r['id'] ?>" <?= (string) old('role_id') === (string) $r['id'] ? 'selected' : '' ?>><?= esc($r['name_ar']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="wiz-field">
        <label class="wiz-label">الإدارة بنظام ارتقاء <span class="wiz-req">*</span></label>
        <select name="department_id" class="wiz-select" required>
          <option value="">اختر الإدارة</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= (string) old('department_id') === (string) $d['id'] ? 'selected' : '' ?>><?= esc(($d['parent_id'] ? '— ' : '') . $d['name_ar']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="dr-submit-btn" style="align-self:flex-start;"><i data-lucide="user-plus"></i> إضافة المستخدم</button>
    </form>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/users-create-page.js') ?>"></script>
<?php $this->endSection() ?>
