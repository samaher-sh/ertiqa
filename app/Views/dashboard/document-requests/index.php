<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/documentrequests.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
$locked = !$selectedMissionId;
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/document-requests'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $locked ? ' locked' : '' ?>">
    <div class="wiz-card">
      <div class="wiz-card-head" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
          <i data-lucide="folder-check"></i>
          <div><h2>قائمة المستندات المطلوبة</h2></div>
        </div>
      </div>

      <?php if ($canAdd): ?>
      <details class="wiz-add-doc-details" id="drAddDetails" style="padding:12px 20px 0;">
        <summary class="wiz-add-doc-btn" style="background:rgba(255,255,255,.2);color:var(--p);border:1px solid var(--pb);width:fit-content;list-style:none;cursor:pointer;"><i data-lucide="plus"></i> إضافة مستند</summary>
        <form method="post" action="<?= base_url('dashboard/document-requests/api/add') ?>" id="drAddForm" style="padding-top:10px;">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
          <div style="display:flex;gap:8px;">
            <input type="text" name="doc_name" class="wiz-doc-name-input" placeholder="أدخل اسم المستند الجديد..." style="flex:1;">
            <button type="submit" class="wiz-doc-del-btn" style="color:var(--p);" title="حفظ"><i data-lucide="check"></i></button>
          </div>
        </form>
      </details>
      <?php endif; ?>

      <div class="wiz-table-wrap">
        <table class="wiz-doc-table">
          <thead><tr>
            <th style="width:60px;text-align:center;">الرقم</th>
            <th style="text-align:right;min-width:260px;">المستند</th>
            <th style="width:170px;text-align:center;">يوجد / لا يوجد</th>
            <th style="width:200px;text-align:center;">رفع الملف</th>
            <th style="width:240px;text-align:right;">الملاحظات</th>
          </tr></thead>
          <tbody>
            <?php if (empty($requests)): ?>
              <tr><td colspan="5"><div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>لا توجد مستندات مطلوبة لهذه المهمة</div></td></tr>
            <?php else: ?>
              <?php foreach ($requests as $i => $r): ?>
                <?php $hasResponse = $r['exists_flag'] !== null; ?>
                <tr>
                  <td style="text-align:center;"><span class="wiz-doc-row-num"><?= $i + 1 ?></span></td>
                  <td><input type="text" class="wiz-doc-name-input" value="<?= esc($r['doc_name']) ?>" readonly></td>
                  <td style="text-align:center;"><span class="wiz-pill"><?= $hasResponse ? ((int) $r['exists_flag'] ? 'يوجد' : 'لا يوجد') : 'بانتظار الرد' ?></span></td>
                  <td style="text-align:center;">
                    <?php if (!empty($r['file'])): ?>
                      <a class="dr-file-link" href="<?= base_url('dashboard/documents/download/' . $r['file']['id']) ?>" target="_blank"><i data-lucide="paperclip"></i> <?= esc($r['file']['file_name']) ?></a>
                    <?php else: ?>
                      <span class="wiz-pill">لا يوجد ملف</span>
                    <?php endif; ?>
                  </td>
                  <td><input type="text" class="wiz-doc-note-input" value="<?= esc($r['response_note'] ?? '') ?>" readonly placeholder="لا توجد ملاحظات"></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="wiz-doc-footer">
        <span class="wiz-doc-footer-count">الإجمالي: <strong><?= count($requests) ?></strong></span>
      </div>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/documentrequests-page.js') ?>"></script>
<?php $this->endSection() ?>
