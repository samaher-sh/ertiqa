<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingsummary.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
$errorMsg = session()->getFlashdata('error');
$locked = !$selectedMissionId;
$canEditMeeting = !$allReadOnly;
$canEditAttendance = !$allReadOnly;
$canEditPointText = !$isHrUser && !$allReadOnly;
$canEditStatement = !$allReadOnly;
$canEditHrResponse = $isHrUser;
$canAddRemovePoints = !$isHrUser && !$allReadOnly;
$hrOpinionLabels = ['agree' => 'موافق', 'reserved' => 'متحفظ'];
$showApprovals = !$isHrUser;
$deptName = $mission['target_department_name'] ?? '';
?>
<div class="flex flex-col gap-5">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/meetings'),
  ]) ?>

  <form method="post" action="<?= base_url('dashboard/meetings/api/save') ?>" id="msumForm">
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
    <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
    <input type="hidden" name="remove_index" id="msumRemoveIndex" value="">

    <div class="msum-locked-wrap<?= $locked ? ' locked' : '' ?>" style="display:flex;flex-direction:column;gap:20px;">

      <!-- 1. بيانات الاجتماع -->
      <div class="wiz-card">
        <div class="wiz-card-head">
          <i data-lucide="users"></i>
          <div><h2>ملخص الاجتماع</h2></div>
          <?php if ($allReadOnly && empty($embed)): ?><span class="msum-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span><?php endif; ?>
          <div style="display:flex;gap:8px;margin-right:auto;">
            <?php if ($selectedMissionId && empty($embed)): ?><a class="obs-btn-pdf" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/meeting-summary/' . $selectedMissionId) ?>"><i data-lucide="file-text"></i> تصدير PDF</a><?php endif; ?>
          </div>
        </div>
        <?php if ($isHrUser && empty($embed)): ?><div class="msum-auto-banner"><span><i data-lucide="zap"></i> الإدارة محل المراجعة تُملأ تلقائياً من المهمة المرتبطة</span></div><?php endif; ?>

        <div class="wiz-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <?php if ($errorMsg): ?><div class="obs-alert obs-alert-error" style="grid-column:1/-1;"><?= esc($errorMsg) ?></div><?php endif; ?>
          <div class="wiz-field">
            <label class="wiz-label">تاريخ الاجتماع</label>
            <input name="date" type="date" class="wiz-input plain" value="<?= esc($meeting['meeting_date'] ?? '') ?>" <?= $canEditMeeting ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">الوقت</label>
            <input name="time" type="time" class="wiz-input plain" value="<?= esc($meeting['meeting_time'] ?? '') ?>" <?= $canEditMeeting ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">مكان الاجتماع</label>
            <textarea name="location" rows="1" class="wiz-textarea plain msum-growfield" placeholder="أدخل مكان الاجتماع" <?= $canEditMeeting ? '' : 'readonly' ?>><?= esc($meeting['location'] ?? '') ?></textarea>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">الإدارة محل المراجعة</label>
            <div class="msum-auto-field hr"><span class="val"><?= esc($deptName ?: '— اختر المهمة أولاً —') ?></span></div>
          </div>
          <div class="wiz-field" style="grid-column:1/-1;">
            <label class="wiz-label">عنوان المهمة</label>
            <div class="msum-edit-wrap"><textarea name="title" rows="1" class="msum-growfield" placeholder="عنوان مهمة المراجعة" <?= $canEditMeeting ? '' : 'readonly' ?>><?= esc($meeting['title'] ?? '') ?></textarea></div>
          </div>
          <div class="wiz-field" style="grid-column:1/-1;">
            <label class="wiz-label">الهدف من الاجتماع</label>
            <textarea name="objective" rows="2" class="wiz-textarea plain" <?= $canEditMeeting ? '' : 'readonly' ?>><?= esc($meeting['objective'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- 2. جدول الحضور -->
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;"><i data-lucide="users"></i><span style="color:#fff;font-weight:700;font-size:14px;">جدول الحضور</span></div>
          <?php if ($canEditAttendance): ?><button type="submit" name="form_action" value="add_attendee" formnovalidate class="msum-attach-btn" id="msumAddAttendanceBtn" style="padding:6px 12px;font-size:12px;box-shadow:none;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);"><i data-lucide="plus" style="width:14px;height:14px;"></i> إضافة حضور</button><?php endif; ?>
        </div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr><th class="c" style="width:50px;">الرقم</th><th>الاسم</th><th>الإدارة</th><th>الوظيفة</th><th style="width:40px;"></th></tr></thead>
            <tbody id="msumAttendanceBody">
              <?php foreach ($attendees as $i => $row): ?>
                <tr data-msum-attendee-row>
                  <td class="msum-row-num"><?= $i + 1 ?></td>
                  <td><input type="text" name="attendees[<?= $i ?>][name]" class="msum-plain-input" placeholder="أدخل الاسم" value="<?= esc($row['external_name'] ?? $row['name'] ?? '') ?>" <?= $canEditAttendance ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="attendees[<?= $i ?>][dept]" class="msum-plain-input" placeholder="أدخل الإدارة" value="<?= esc($row['attendee_dept'] ?? $row['dept'] ?? '') ?>" <?= $canEditAttendance ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="attendees[<?= $i ?>][position]" class="msum-plain-input" placeholder="أدخل الوظيفة" value="<?= esc($row['attendee_position'] ?? $row['position'] ?? '') ?>" <?= $canEditAttendance ? '' : 'readonly' ?>></td>
                  <td style="text-align:center;"><?php if ($canEditAttendance): ?><button type="submit" name="form_action" value="remove_attendee" formnovalidate class="msum-del-btn" data-msum-del-attendee onclick="document.getElementById('msumRemoveIndex').value='<?= $i ?>'"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button><?php endif; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 3. ملخص ما تم مناقشته -->
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;">
            <i data-lucide="message-square"></i><span style="color:#fff;font-weight:700;font-size:14px;">ملخص ما تم مناقشته خلال الاجتماع</span>
            <?php if ($isHrUser && empty($embed)): ?><span class="msum-auto-chip" style="background:rgba(255,255,255,.2);color:#fff;border:none;"><i data-lucide="lock"></i>النقاط تلقائية</span><?php endif; ?>
          </div>
          <?php if ($canAddRemovePoints): ?><button type="submit" name="form_action" value="add_point" formnovalidate class="msum-attach-btn" id="msumAddPointBtn" style="padding:6px 12px;font-size:12px;box-shadow:none;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);"><i data-lucide="plus" style="width:14px;height:14px;"></i> إضافة نقطة</button><?php endif; ?>
        </div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr>
              <th style="width:50%;">النقطة</th><th style="width:50%;">الإفادة</th>
              <?php if ($canAddRemovePoints): ?><th style="width:40px;"></th><?php endif; ?>
            </tr></thead>
            <tbody id="msumPointsBody">
              <?php foreach ($points as $i => $pt): ?>
                <?php $text = $pt['point_text'] ?? $pt['text'] ?? ''; $statement = $pt['statement'] ?? ''; ?>
                <tr data-msum-point-row>
                  <td>
                    <?php if ($isHrUser): ?>
                      <div class="msum-point-hr-box"><span class="msum-point-num"><?= $i + 1 ?></span><span><?= esc($text) ?></span></div>
                      <input type="hidden" name="points[<?= $i ?>][text]" value="<?= esc($text) ?>">
                    <?php else: ?>
                      <textarea rows="2" name="points[<?= $i ?>][text]" class="wiz-textarea plain" placeholder="النقطة <?= $i + 1 ?>..." <?= $canEditPointText ? '' : 'readonly' ?>><?= esc($text) ?></textarea>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($canEditStatement): ?>
                      <textarea rows="2" class="wiz-textarea" style="border:1.5px solid <?= $statement ? '#b3d4e5' : 'var(--pb)' ?>;background:<?= $statement ? '#f0fdf4' : '#f0f8fd' ?>;" name="points[<?= $i ?>][statement]" placeholder="اكتب الإفادة..."><?= esc($statement) ?></textarea>
                    <?php else: ?>
                      <div class="msum-opinion-readonly <?= $statement ? 'has' : 'empty' ?>"><?= esc($statement ?: '—') ?></div>
                      <input type="hidden" name="points[<?= $i ?>][statement]" value="<?= esc($statement) ?>">
                    <?php endif; ?>
                  </td>
                  <?php if ($canAddRemovePoints): ?><td style="text-align:center;"><button type="submit" name="form_action" value="remove_point" formnovalidate class="msum-del-btn" data-msum-del-point onclick="document.getElementById('msumRemoveIndex').value='<?= $i ?>'"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button></td><?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($points)): ?><tr><td colspan="3" class="msum-empty-points">لا توجد نقاط. اضغط "إضافة نقطة" للبدء.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php $mtgHrOpinion = $meeting['hr_opinion'] ?? ''; $mtgHrReason = $meeting['hr_reason'] ?? ''; ?>
        <div class="msum-point-response msum-overall-response">
          <label>الرأي</label>
          <?php if ($canEditHrResponse): ?>
            <select name="hr_opinion" class="msum-hr-opinion-select">
              <option value="">اختر...</option>
              <option value="agree" <?= $mtgHrOpinion === 'agree' ? 'selected' : '' ?>>موافق</option>
              <option value="reserved" <?= $mtgHrOpinion === 'reserved' ? 'selected' : '' ?>>متحفظ</option>
            </select>
          <?php else: ?>
            <div class="msum-opinion-readonly <?= $mtgHrOpinion ? 'has' : 'empty' ?>"><?= esc($hrOpinionLabels[$mtgHrOpinion] ?? '—') ?></div>
            <input type="hidden" name="hr_opinion" value="<?= esc($mtgHrOpinion) ?>">
          <?php endif; ?>
          <label>السبب</label>
          <?php if ($canEditHrResponse): ?>
            <input type="text" name="hr_reason" class="msum-plain-input" placeholder="اكتب السبب..." value="<?= esc($mtgHrReason) ?>">
          <?php else: ?>
            <div class="msum-opinion-readonly empty" style="color:<?= $mtgHrReason ? '#152c33' : '#9ca3af' ?>;"><?= esc($mtgHrReason ?: '—') ?></div>
            <input type="hidden" name="hr_reason" value="<?= esc($mtgHrReason) ?>">
          <?php endif; ?>
        </div>
      </div>

      <!-- 4. إعداد واعتماد — لغير مستخدمي HR فقط -->
      <?php if ($showApprovals): ?>
      <div class="wiz-card">
        <div class="wiz-card-head"><i data-lucide="check"></i><span style="color:#fff;font-weight:700;font-size:14px;">إعداد واعتماد</span></div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr><th>الاسم</th><th>الوظيفة</th><th style="width:140px;">الاعتماد</th></tr></thead>
            <tbody>
              <?php foreach ($approvals as $row): ?>
                <?php $approved = !empty($row['signature_data'] ?? $row['signature'] ?? ''); ?>
                <input type="hidden" name="approvals[0][statement]" value="<?= esc($row['statement'] ?? 'إعداد واعتماد') ?>">
                <input type="hidden" name="approvals[0][date]" id="msumApprovalDate" value="<?= esc($row['approval_date'] ?? $row['date'] ?? '') ?>">
                <tr>
                  <td><input type="text" name="approvals[0][name]" class="msum-plain-input" placeholder="الاسم" value="<?= esc($row['signer_name'] ?? $row['name'] ?? '') ?>" <?= $allReadOnly ? 'readonly' : '' ?>></td>
                  <td><input type="text" name="approvals[0][position]" class="msum-plain-input" placeholder="الوظيفة" value="<?= esc($row['position'] ?? '') ?>" <?= $allReadOnly ? 'readonly' : '' ?>></td>
                  <td id="msumSigCell" style="text-align:center;">
                    <?php if ($allReadOnly): ?>
                      <?php if ($approved): ?><span class="msum-approved-badge"><i data-lucide="check-circle"></i> معتمد</span><?php else: ?><span class="msum-sig-empty">لم يُعتمد بعد</span><?php endif; ?>
                    <?php else: ?>
                      <input type="hidden" name="approvals[0][signature]" id="msumSignatureInput" value="<?= esc($approved ? '1' : '') ?>">
                      <label class="msum-approve-checkbox"><input type="checkbox" id="msumApproveCheckbox" <?= $approved ? 'checked' : '' ?>> اعتماد</label>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- 5. المرفقات -->
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;"><i data-lucide="paperclip"></i><span style="color:#fff;font-weight:700;font-size:14px;">المرفقات</span></div>
          <?php if (!$allReadOnly): ?><span id="msumAttachMount"></span><?php endif; ?>
        </div>
        <div id="msumAttachListWrap">
          <?php if (!empty($attachments)): ?>
            <div class="msum-attach-list">
              <?php foreach ($attachments as $d): ?>
                <div class="msum-attach-row" data-attach-name="<?= esc($d['file_name'], 'attr') ?>" data-attach-url="<?= base_url('dashboard/documents/download/' . $d['id']) ?>">
                  <span class="msum-attach-name"><i data-lucide="paperclip"></i> <?= esc($d['file_name']) ?></span>
                  <div class="msum-attach-actions">
                    <button type="button" class="msum-attach-view-btn" title="عرض"><i data-lucide="eye"></i></button>
                    <?php if (!$allReadOnly && (int) ($d['uploaded_by'] ?? 0) === (int) session()->get('user_id')): ?>
                      <button type="button" class="msum-attach-del-btn" data-attach-id="<?= (int) $d['id'] ?>" title="حذف"><i data-lucide="trash-2"></i></button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="padding:16px 24px;"><span class="msum-attach-empty" style="margin-right:0;">لا توجد مرفقات</span></div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <?php if (!$allReadOnly): ?>
    <div class="msum-bottom-row">
      <div class="msum-submit-wrap">
        <button type="submit" name="form_action" value="save" class="msum-submit-btn dirty" <?= $locked ? 'disabled' : '' ?>>
          <i data-lucide="send"></i> حفظ التغييرات
        </button>
      </div>
    </div>
    <?php endif; ?>
  </form>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/meetingsummary-page.js') ?>"></script>
<?php $this->endSection() ?>
