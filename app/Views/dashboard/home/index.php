<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
/** رابط "إغلاق لوحة التفاصيل" -- نفس رابط الصفحة الحالي بدون panel= */
$homePanelUrl = fn(string $panel = '') => $panel ? base_url('dashboard') . '?panel=' . $panel : base_url('dashboard');

$reportsPending  = (int) ($stats['reports_pending_count'] ?? 0);
$reportsApproved = (int) ($stats['reports_approved_count'] ?? 0);
?>
<div class="flex flex-col gap-4">
  <div class="stats-grid <?= $isAuditMember ? '' : 'two-col' ?>">
    <?php if ($isAuditMember): ?>
      <a class="stat-action-card" style="text-decoration:none;" href="<?= base_url('dashboard/new-task') ?>">
        <div class="stat-action-top"><span class="stat-dot light"></span></div>
        <div><p class="stat-action-label">بدء مهمة</p></div>
        <p class="stat-action-cta"><i data-lucide="plus"></i> ابدأ</p>
      </a>
    <?php endif; ?>

    <?php if ($isAuditHead): ?>
      <a class="stat-card" style="text-decoration:none;color:inherit;" href="<?= base_url('dashboard/reports') ?>?status=pending_signatures">
        <div class="stat-card-top"><span class="stat-dot"></span></div>
        <div><p class="stat-label">تقارير تحتاج اعتماد</p></div>
        <p class="stat-value"><?= $reportsPending ?></p>
      </a>
      <a class="stat-card" style="text-decoration:none;color:inherit;" href="<?= base_url('dashboard/reports') ?>?status=sent">
        <div class="stat-card-top"><span class="stat-dot"></span></div>
        <div><p class="stat-label">التقارير المعتمدة</p></div>
        <p class="stat-value"><?= $reportsApproved ?></p>
      </a>
    <?php else: ?>
      <a class="stat-card <?= $panel === 'missions' ? 'active' : '' ?>" style="text-decoration:none;color:inherit;" href="<?= $panel === 'missions' ? $homePanelUrl() : $homePanelUrl('missions') ?>">
        <div class="stat-card-top"><span class="stat-dot"></span><?php if ($panel === 'missions'): ?><i data-lucide="chevron-down" class="stat-card-chevron"></i><?php endif; ?></div>
        <div><p class="stat-label">المهام النشطة</p></div>
        <p class="stat-value"><?= count($missions) ?></p>
      </a>
      <a class="stat-card <?= $panel === 'meetings' ? 'active' : '' ?>" style="text-decoration:none;color:inherit;" href="<?= $panel === 'meetings' ? $homePanelUrl() : $homePanelUrl('meetings') ?>">
        <div class="stat-card-top"><span class="stat-dot"></span><?php if ($panel === 'meetings'): ?><i data-lucide="chevron-down" class="stat-card-chevron"></i><?php endif; ?></div>
        <div><p class="stat-label">اجتماعات مجدولة</p></div>
        <p class="stat-value"><?= count($meetings) ?></p>
      </a>
    <?php endif; ?>
  </div>

  <?php if ($showNotifications): ?>
    <?php $activeNotifs = $notifications; ?>
    <details class="home-banner" id="homeNotifDetails">
      <summary class="home-banner-head notif-trigger" style="cursor:pointer;list-style:none;">
        <i data-lucide="bell" class="home-banner-head-icon"></i>
        <div class="home-banner-head-text">
          <p class="t1">إخطارات</p>
          <p class="t2"><?= count($activeNotifs) === 0 ? 'لا توجد إخطارات جديدة حاليًا' : ('لديك ' . count($activeNotifs) . ' ' . (count($activeNotifs) === 1 ? 'إخطار جديد' : 'إخطارات جديدة')) ?></p>
        </div>
        <?php if (count($activeNotifs) > 0): ?><span class="home-banner-badge"><?= count($activeNotifs) ?></span><?php endif; ?>
        <i data-lucide="chevron-down" class="notif-trigger-chevron"></i>
      </summary>
      <?php if (empty($activeNotifs)): ?>
        <p class="notif-empty">لا توجد إخطارات حاليًا</p>
      <?php else: ?>
        <?php foreach ($activeNotifs as $n):
          $icon = $n['type'] === 'meeting' ? 'calendar-check' : ($n['type'] === 'meeting_proposal' ? 'calendar-clock' : ($n['type'] === 'report_approval' ? 'file-check' : 'bell'));
          $href = ($n['type'] === 'meeting' || $n['type'] === 'meeting_proposal')
              ? base_url('dashboard/meeting-schedule') . '?mission_id=' . (int) $n['mission_id']
              : ($n['type'] === 'report_approval'
                  ? base_url('dashboard/reports/' . (int) $n['mission_id'])
                  : base_url('dashboard/sent-tasks/' . (int) $n['mission_id']));
        ?>
          <div class="home-banner-body notif-item">
            <div class="home-banner-icon-box"><i data-lucide="<?= $icon ?>"></i></div>
            <div class="home-banner-content">
              <div class="home-banner-title-row"><span class="home-banner-item-title"><?= esc($n['title']) ?></span></div>
              <p class="home-banner-desc"><?= esc($n['body'] ?? '') ?></p>
            </div>
            <a class="home-banner-open-btn" style="text-decoration:none;" href="<?= $href ?>">فتح</a>
            <button type="button" class="home-banner-dismiss-btn notif-item-dismiss" title="إخفاء"><i data-lucide="x"></i></button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </details>
  <?php endif; ?>

  <?php if ($isAuditHead && $reportsPending > 0): ?>
    <div class="home-banner">
      <div class="home-banner-head">
        <i data-lucide="clipboard-check" class="home-banner-head-icon"></i>
        <div class="home-banner-head-text">
          <p class="t1">التقارير التي تحتاج اعتماد</p>
          <p class="t2">يوجد تقارير نهائية قيد الانتظار لاعتمادها</p>
        </div>
        <span class="home-banner-badge">تقارير جديدة</span>
      </div>
      <div class="home-banner-body">
        <div class="home-banner-icon-box"><i data-lucide="file-text"></i></div>
        <div class="home-banner-content">
          <div class="home-banner-title-row">
            <span class="home-banner-item-title">تقارير تحتاج اعتماد — المراجعة الداخلية</span>
            <span class="home-banner-dot"></span>
            <span class="home-banner-tag">بانتظار الاعتماد</span>
          </div>
          <p class="home-banner-desc">يوجد <?= $reportsPending ?> <?= $reportsPending === 1 ? 'تقرير' : 'تقارير' ?> جاهزة وتنتظر اعتمادك للبدء بتعميمها بشكل نهائي.</p>
        </div>
        <a class="home-banner-open-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports') ?>?status=pending_signatures">عرض التقارير</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$isAuditHead && $panel === 'missions'): ?>
    <div class="detail-panel" style="border-color:var(--pb);">
      <div class="detail-head" style="background:var(--pl); border-color:var(--pb);">
        <span class="detail-dot" style="background:var(--p);"></span>
        <p class="detail-title" style="color:var(--p);">المهام النشطة</p>
        <a class="detail-close" style="color:var(--p);text-decoration:none;" href="<?= $homePanelUrl() ?>"><i data-lucide="x"></i></a>
      </div>
      <div class="detail-body">
        <?php if (empty($missions)): ?>
          <p class="empty-hint">لا توجد بيانات لعرضها حالياً</p>
        <?php else: ?>
          <?php foreach ($missions as $m): ?>
            <a class="task-row" style="text-decoration:none;color:inherit;" href="<?= base_url('dashboard/sent-tasks/' . (int) $m['id']) ?>">
              <div class="task-row-icon"><i data-lucide="eye"></i></div>
              <div class="task-row-body">
                <p class="task-row-title"><?= esc($m['target_department_name'] ?? '') ?></p>
                <p class="task-row-sub"><?= esc($m['mission_code']) ?> · <?= esc((string) $m['year']) ?></p>
              </div>
              <div class="task-row-badges"><span class="task-phase-badge">المرحلة <?= (int) $m['current_stage'] ?></span></div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php elseif (!$isAuditHead && $panel === 'meetings'): ?>
    <div class="detail-panel" style="border-color:var(--pb);">
      <div class="detail-head" style="background:var(--pl); border-color:var(--pb);">
        <span class="detail-dot" style="background:var(--p);"></span>
        <p class="detail-title" style="color:var(--p);">اجتماعات مجدولة</p>
        <a class="detail-close" style="color:var(--p);text-decoration:none;" href="<?= $homePanelUrl() ?>"><i data-lucide="x"></i></a>
      </div>
      <div class="detail-body">
        <?php if (empty($meetings)): ?>
          <p class="empty-hint">لا توجد بيانات لعرضها حالياً</p>
        <?php else: ?>
          <div class="ms-meeting-row">
            <?php foreach ($meetings as $m): ?>
              <div class="ms-meeting-card">
                <p class="ms-meeting-title"><?= esc($m['mission_title'] ?? $m['title'] ?? $m['meeting_code'] ?? '') ?></p>
                <p class="ms-meeting-code"><?= esc($m['mission_code'] ?? '') ?></p>
                <div class="ms-meeting-meta">
                  <span><i data-lucide="map-pin"></i> <?= esc($m['location'] ?? 'لم يُحدَّد المكان بعد') ?></span>
                  <?php if (!empty($m['meeting_date'])): ?>
                    <span><i data-lucide="calendar"></i> <?= esc($m['meeting_date']) ?></span>
                    <span><i data-lucide="clock"></i> <?= esc($m['meeting_time'] ?? '') ?></span>
                  <?php else: ?>
                    <span>بانتظار تحديد الموعد</span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($m['meeting_date'])): ?>
                  <a class="ms-meeting-postpone-btn" style="text-decoration:none;" href="<?= base_url('dashboard/meeting-schedule') ?>?mission_id=<?= (int) $m['mission_id'] ?>">
                    <i data-lucide="calendar-clock"></i> تأجيل الموعد
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/home-page.js') ?>"></script>
<?php $this->endSection() ?>
