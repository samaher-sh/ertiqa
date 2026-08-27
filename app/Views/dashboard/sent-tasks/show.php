<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/senttasks.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/taskdetail.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$isHrUser = in_array(session()->get('role_code'), ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
$stageInfo = [
    2 => ['label' => 'استكمال الاتفاقية والمستندات', 'forRole' => 'target', 'url' => base_url('dashboard/target-mission')],
    3 => ['label' => 'مصفوفة المخاطر',              'forRole' => 'audit',  'url' => base_url('dashboard/risk-matrix')],
    4 => ['label' => 'ملخص الاجتماع',                'forRole' => 'audit',  'url' => base_url('dashboard/meetings')],
    5 => ['label' => 'الملاحظات',                    'forRole' => 'audit',  'url' => base_url('dashboard/observations')],
    7 => ['label' => 'التقرير النهائي',               'forRole' => 'audit',  'url' => null],
];
$info = $stageInfo[$nextStage] ?? null;
$myTurn = $info ? (($info['forRole'] === 'target' && $isHrUser) || ($info['forRole'] === 'audit' && !$isHrUser)) : false;
$badgeText = $info ? (($myTurn ? 'بانتظارك — ' : 'بانتظار الطرف الآخر — ') . $info['label']) : ($nextStage === 7 ? 'التقرير النهائي' : 'المرحلة ' . $nextStage);
$flashSuccess = session()->getFlashdata('success');

/* المراحل اللي فعليًا وصلتها المهمة (نفس ST_TOUR_STAGES بـ senttasks.js
   بالضبط) -- كل مرحلة تظهر هنا كرابط حقيقي لصفحتها المخصصة المكتملة (بدل
   "جولة" مضمَّنة خطوة-بخطوة كانت بالـ SPA القديمة)، عشان الطرف المنتظِر
   يقدر يراجع أي مرحلة سابقة أُنجزت، مو بس المرحلة القادمة التي عليه دوره فيها */
$tourStages = [
    ['label' => 'الخطاب الرسمي',          'always' => true, 'actions' => [], 'url' => base_url('dashboard/pdf/mission-letter/' . $mission['id']) . '?inline=1'],
    ['label' => 'اتفاقية مستوى الخدمة',    'always' => false, 'actions' => ['sla_submitted'], 'url' => base_url('dashboard/target-mission') . '?mission_id=' . $mission['id'] . '&embed=1'],
    ['label' => 'قائمة المستندات المرسلة', 'always' => false, 'actions' => ['documents_submitted'], 'url' => base_url('dashboard/document-requests') . '?mission_id=' . $mission['id'] . '&embed=1'],
    ['label' => 'مصفوفة المخاطر',         'always' => false, 'actions' => ['risk_matrix_saved'], 'url' => base_url('dashboard/risk-matrix') . '?mission_id=' . $mission['id'] . '&embed=1'],
    ['label' => 'الاجتماع',               'always' => false, 'actions' => ['meeting_confirmed', 'meeting_summary_saved'], 'url' => base_url('dashboard/meetings') . '?mission_id=' . $mission['id'] . '&embed=1'],
    ['label' => 'الملاحظات',              'always' => false, 'actions' => ['observation_added'], 'url' => base_url('dashboard/observations') . '?mission_id=' . $mission['id'] . '&embed=1'],
    ['label' => 'التقرير النهائي',         'always' => false, 'actions' => ['report_finalized', 'report_approved'], 'url' => base_url('dashboard/reports/' . $mission['id'])],
];
$eventActions = array_column($events, 'action');
$reachedStages = array_values(array_filter($tourStages, fn($s) => $s['always'] || array_intersect($s['actions'], $eventActions)));
?>
<div class="flex flex-col gap-5" dir="rtl">
  <?php if ($flashSuccess): ?><div class="obs-alert obs-alert-success"><?= esc($flashSuccess) ?></div><?php endif; ?>
  <div class="st-detail-header">
    <a class="st-detail-back" style="text-decoration:none;" href="<?= base_url('dashboard/sent-tasks') ?>"><i data-lucide="chevron-right"></i></a>
    <div class="st-detail-icon"><i data-lucide="history"></i></div>
    <div>
      <h2 class="st-detail-title"><?= esc($mission['title']) ?></h2>
      <p class="st-detail-sub"><?= esc($mission['mission_code']) ?> · <?= esc($mission['target_department_name'] ?? '') ?></p>
    </div>
    <span class="st-detail-status"><?= esc($badgeText) ?></span>
  </div>

  <div class="st-detail-grid">
    <div class="st-detail-left">
      <div class="st-phase-card">
        <div class="st-phase-head">
          <div class="st-phase-icon"><i data-lucide="file-text"></i></div>
          <span>بيانات المهمة</span>
        </div>
        <div class="st-phase-fields">
          <div class="st-phase-field"><span class="lbl">الإدارة الخاضعة</span><span class="val"><?= esc($mission['target_department_name'] ?? '') ?></span></div>
          <div class="st-phase-field"><span class="lbl">المراجع المسؤول</span><span class="val"><?= esc($mission['reviewer_name'] ?: '—') ?></span></div>
          <div class="st-phase-field"><span class="lbl">مدير الإدارة</span><span class="val"><?= esc($mission['director_name'] ?: '—') ?></span></div>
          <div class="st-phase-field"><span class="lbl">تاريخ الإنشاء</span><span class="val" dir="ltr"><?= esc($mission['created_at'] ?: '—') ?></span></div>
        </div>
      </div>

      <div class="st-complete-card">
        <?php if (!$info): ?>
          <div class="st-complete-hint"><i data-lucide="info"></i><span>لا يوجد نموذج مرتبط مباشرة بالمرحلة الحالية لهذه المهمة</span></div>
        <?php elseif ($myTurn && $info['url']): ?>
          <div class="st-complete-hint"><i data-lucide="pencil"></i><span>أكمل الحقول المتبقية الخاصة بك في نموذج "<?= esc($info['label']) ?>"</span></div>
          <a class="st-complete-btn" style="text-decoration:none;" href="<?= esc($info['url']) ?>?mission_id=<?= (int) $mission['id'] ?>"><i data-lucide="pencil"></i> إكمال الحقول</a>
        <?php elseif ($myTurn): ?>
          <div class="st-complete-hint"><i data-lucide="pencil"></i><span>أكمل الحقول المتبقية الخاصة بك في نموذج "<?= esc($info['label']) ?>" من صفحته المخصصة بالقائمة الجانبية</span></div>
        <?php elseif ($info['url']): ?>
          <div class="st-complete-hint"><i data-lucide="eye"></i><span>بانتظار الطرف الآخر لإكمال "<?= esc($info['label']) ?>" — تقدر تطّلع على ما أُنجز حتى الآن</span></div>
          <a class="st-complete-btn" style="text-decoration:none;" href="<?= esc($info['url']) ?>?mission_id=<?= (int) $mission['id'] ?>"><i data-lucide="eye"></i> عرض</a>
        <?php else: ?>
          <div class="st-complete-hint"><i data-lucide="eye"></i><span>بانتظار الطرف الآخر لإكمال "<?= esc($info['label']) ?>"</span></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($reachedStages)): ?>
      <div class="st-phase-card">
        <div class="st-phase-head">
          <div class="st-phase-icon"><i data-lucide="list-checks"></i></div>
          <span>المراحل المنجزة</span>
        </div>
        <div class="st-phase-fields">
          <?php foreach ($reachedStages as $stage): ?>
            <div class="st-phase-field">
              <span class="lbl"><?= esc($stage['label']) ?></span>
              <a class="val st-stage-preview-btn" style="color:var(--p);text-decoration:underline;" href="<?= esc($stage['url']) ?>" target="_blank" data-preview-title="<?= esc($stage['label'], 'attr') ?>"><i data-lucide="eye" style="width:12px;height:12px;vertical-align:middle;"></i> عرض</a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="st-log-card">
      <details open>
        <summary class="st-log-head" style="cursor:pointer;list-style:none;">
          <i data-lucide="clock"></i><div><p class="t">سجل النشاط والتدقيق</p><p class="s">سجل زمني لجميع الإجراءات</p></div>
        </summary>
        <?php if (empty($events)): ?>
          <div class="td-timeline"><p style="padding:16px;color:var(--muted);">لا يوجد سجل بعد لهذه المهمة</p></div>
        <?php else: ?>
          <div class="td-timeline">
            <div class="td-timeline-rail"></div>
            <?php foreach ($events as $ev): ?>
              <div class="td-activity-row">
                <div class="td-activity-avatar"><?= esc(mb_substr($ev['user_name'], 0, 1)) ?></div>
                <div class="td-activity-body">
                  <div class="td-activity-user"><?= esc($ev['user_name']) ?></div>
                  <div class="td-activity-meta">
                    <span class="td-activity-btn-tag"><?= esc($ev['stage_name']) ?></span>
                    <span class="td-activity-sep">—</span>
                    <span class="td-activity-time"><?= esc($ev['entered_at']) ?></span>
                  </div>
                  <?php if (!empty($ev['detail'])): ?><p class="td-activity-detail"><?= esc($ev['detail']) ?></p><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </details>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/senttasks-show-page.js') ?>"></script>
<?php $this->endSection() ?>
