<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingschedule.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
$locked = !$selectedMissionId;

$lastConfirmedIndex = -1;
if ($meeting && ($meeting['status'] ?? '') === 'scheduled') {
    foreach ($messages as $i => $m) {
        if ($m['type'] === 'confirmed' && $m['proposed_date'] === $meeting['meeting_date'] && $m['proposed_time'] === $meeting['meeting_time']) {
            $lastConfirmedIndex = $i;
        }
    }
}
?>
<div class="flex flex-col gap-4 mc-page-wrap">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/meeting-schedule'),
  ]) ?>

  <div class="mc-locked-wrap mc-schedule-wrap<?= $locked ? ' locked' : '' ?>">
    <div class="wiz-card mc-card">
      <div class="wiz-card-head">
        <i data-lucide="calendar"></i>
        <div><h2>جدولة اجتماع</h2></div>
      </div>

      <div class="mc-chat-body" id="mcChatBody" data-mission-id="<?= (int) $selectedMissionId ?>" data-my-user-id="<?= (int) $myUserId ?>">
        <?php if (empty($messages)): ?>
          <p class="mc-empty">لا توجد رسائل بعد — ابدأ المحادثة لتحديد موعد الاجتماع</p>
        <?php else: ?>
          <?php foreach ($messages as $i => $m): ?>
            <?php
            $isMine = (int) $m['sender_id'] === (int) $myUserId;
            $side = $isMine ? 'mine' : 'theirs';
            $time = substr($m['created_at'] ?? '', 11, 5);
            ?>
            <?php if ($m['type'] === 'proposal'): ?>
              <div class="mc-bubble-row <?= $side ?>">
                <div class="mc-bubble mc-bubble-proposal">
                  <p class="mc-bubble-sender"><?= esc($m['sender_name'] ?? '') ?></p>
                  <div class="mc-proposal-card">
                    <i data-lucide="calendar-clock"></i>
                    <div>
                      <p class="mc-proposal-title">اقترح موعدًا للاجتماع</p>
                      <p class="mc-proposal-detail"><?= esc($m['proposed_date']) ?> — <?= esc($m['proposed_time']) ?><?= $m['proposed_location'] ? ' · ' . esc($m['proposed_location']) : '' ?></p>
                    </div>
                  </div>
                  <?php if (!$isMine): ?>
                    <div class="mc-proposal-actions">
                      <form method="post" action="<?= base_url('dashboard/meeting-schedule/api/confirm') ?>" style="display:inline;">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
                        <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                        <button type="submit" class="mc-confirm-btn"><i data-lucide="check"></i> تأكيد الموعد</button>
                      </form>
                      <form method="post" action="<?= base_url('dashboard/meeting-schedule/api/cancel') ?>" style="display:inline;">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
                        <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                        <button type="submit" class="mc-cancel-btn"><i data-lucide="x"></i> إلغاء الموعد</button>
                      </form>
                    </div>
                  <?php else: ?>
                    <span class="mc-waiting-hint">بانتظار تأكيد الطرف الآخر</span>
                  <?php endif; ?>
                  <span class="mc-bubble-time"><?= esc($time) ?></span>
                </div>
              </div>
            <?php elseif ($m['type'] === 'confirmed'): ?>
              <div class="mc-bubble-row center">
                <div class="mc-bubble-confirmed">
                  <i data-lucide="check-circle"></i>
                  <span>تم تأكيد الموعد: <?= esc($m['proposed_date']) ?> — <?= esc($m['proposed_time']) ?><?= $m['proposed_location'] ? ' · ' . esc($m['proposed_location']) : '' ?></span>
                  <?php if ($i === $lastConfirmedIndex): ?>
                    <form method="post" action="<?= base_url('dashboard/meeting-schedule/api/cancel-confirmed') ?>" style="display:inline;">
                      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                      <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
                      <button type="submit" class="mc-cancel-confirmed-btn" title="إلغاء الموعد المؤكد"><i data-lucide="x"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php elseif ($m['type'] === 'cancelled'): ?>
              <div class="mc-bubble-row center">
                <div class="mc-bubble-cancelled"><i data-lucide="x-circle"></i> <?= esc($m['message']) ?></div>
              </div>
            <?php else: ?>
              <div class="mc-bubble-row <?= $side ?>">
                <div class="mc-bubble mc-bubble-text">
                  <p class="mc-bubble-sender"><?= esc($m['sender_name'] ?? '') ?></p>
                  <p class="mc-bubble-msg"><?= esc($m['message']) ?></p>
                  <span class="mc-bubble-time"><?= esc($time) ?></span>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="mc-compose">
        <div class="mc-compose-row">
          <details id="mcProposeDetails" class="mc-propose-details">
            <summary class="mc-propose-btn" title="اقترح موعد"><i data-lucide="calendar-plus"></i></summary>
            <form method="post" action="<?= base_url('dashboard/meeting-schedule/api/propose') ?>" class="mc-propose-form">
              <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
              <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
              <div class="mc-propose-row">
                <input name="date" type="date" class="wiz-input plain">
                <input name="time" type="time" class="wiz-input plain">
              </div>
              <input name="location" type="text" class="wiz-input plain" placeholder="مكان الاجتماع (اختياري)">
              <div class="mc-propose-actions">
                <button type="submit" class="mc-propose-submit"><i data-lucide="send"></i> اقترح هذا الموعد</button>
              </div>
            </form>
          </details>
          <form method="post" action="<?= base_url('dashboard/meeting-schedule/api/send') ?>" style="display:flex;flex:1;gap:8px;">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
            <input name="message" type="text" class="mc-text-input" placeholder="اكتب رسالة...">
            <button type="submit" class="mc-send-btn"><i data-lucide="send"></i></button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/meetingschedule-page.js') ?>"></script>
<?php $this->endSection() ?>
