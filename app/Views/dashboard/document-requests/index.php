<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/documentrequests.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
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
          <div><h2>قائمة الطلبات المطلوبة</h2></div>
        </div>
        <?php if ($canAdd): ?>
        <details class="wiz-add-doc-details" id="drAddDetails" style="position:relative;">
          <summary class="wiz-add-doc-btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);width:fit-content;list-style:none;cursor:pointer;"><i data-lucide="plus"></i> إضافة مستند</summary>
          <form method="post" action="<?= base_url('dashboard/document-requests/api/add') ?>" id="drAddForm" style="position:absolute;left:0;top:100%;margin-top:8px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:20;min-width:280px;">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
            <div style="display:flex;gap:8px;">
              <input type="text" name="doc_name" class="wiz-doc-name-input" placeholder="أدخل اسم المستند الجديد..." style="flex:1;">
              <button type="submit" class="wiz-doc-confirm-btn" title="حفظ"><i data-lucide="check"></i></button>
            </div>
          </form>
        </details>
        <?php endif; ?>
      </div>

      <?php if ($canSubmit && !empty($requests)): ?>
      <form method="post" action="<?= base_url('dashboard/document-requests/api/submit') ?>" enctype="multipart/form-data" id="drSubmitForm">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
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
                  <td style="text-align:center;">
                    <?php if ($canSubmit): ?>
                      <input type="hidden" name="responses[<?= $i ?>][document_request_id]" value="<?= (int) $r['id'] ?>">
                      <div class="mr-exists-toggle">
                        <label class="mr-exists-pill yes"><input type="radio" name="responses[<?= $i ?>][exists_flag]" value="1" <?= (int) $r['exists_flag'] === 1 ? 'checked' : '' ?>> يوجد</label>
                        <label class="mr-exists-pill no"><input type="radio" name="responses[<?= $i ?>][exists_flag]" value="0" <?= $r['exists_flag'] !== null && (int) $r['exists_flag'] === 0 ? 'checked' : '' ?>> لا يوجد</label>
                      </div>
                    <?php elseif ($hasResponse): ?>
                      <span class="wiz-pill"><?= (int) $r['exists_flag'] ? 'يوجد' : 'لا يوجد' ?></span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;">
                    <?php $files = $r['files'] ?? []; ?>
                    <?php if (!empty($files)): ?>
                      <div class="dr-file-list">
                        <?php foreach ($files as $f): ?>
                          <div class="dr-file-row">
                            <a class="dr-file-link" href="<?= base_url('dashboard/documents/download/' . $f['id']) ?>" target="_blank"><i data-lucide="paperclip"></i> <?= esc($f['file_name']) ?></a>
                            <?php if ($canSubmit && (int) ($f['uploaded_by'] ?? 0) === (int) session()->get('user_id')): ?>
                              <button type="button" class="dr-file-del-btn" data-doc-id="<?= (int) $f['id'] ?>" title="حذف"><i data-lucide="x"></i></button>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php elseif (!$canSubmit): ?>
                      <span class="wiz-pill">لا يوجد ملف</span>
                    <?php endif; ?>
                    <?php if ($canSubmit): ?>
                      <label class="wiz-upload-pill" for="dr-file-<?= (int) $r['id'] ?>" style="cursor:pointer;">
                        <i data-lucide="upload"></i> <span>رفع ملف</span>
                      </label>
                      <input type="file" name="file_<?= (int) $r['id'] ?>[]" id="dr-file-<?= (int) $r['id'] ?>" multiple style="display:none;">
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($canSubmit): ?>
                      <input type="text" name="responses[<?= $i ?>][note]" class="wiz-doc-note-input" value="<?= esc($r['response_note'] ?? '') ?>" placeholder="ملاحظة...">
                    <?php else: ?>
                      <input type="text" class="wiz-doc-note-input" value="<?= esc($r['response_note'] ?? '') ?>" readonly placeholder="لا توجد ملاحظات">
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="wiz-doc-footer">
        <span class="wiz-doc-footer-count">الإجمالي: <strong><?= count($requests) ?></strong></span>
        <?php if ($canSubmit && !empty($requests)): ?>
          <button type="submit" class="dr-submit-btn"><i data-lucide="upload"></i> إرسال المستندات</button>
        <?php endif; ?>
      </div>
      <?php if ($canSubmit && !empty($requests)): ?>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/documentrequests-page.js') ?>"></script>
<?php $this->endSection() ?>
