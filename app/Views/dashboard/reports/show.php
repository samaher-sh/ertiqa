<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';

$checkedCount = count(array_filter($items, fn($it) => (int) $it['is_checked'] === 1));
$total = count($items);
$expandedItem = null;
foreach ($items as $it) { if ((int) $it['section_number'] === $expandedStep) { $expandedItem = $it; break; } }

$firstUncheckedNum = null;
foreach ($items as $it) { if ((int) $it['is_checked'] !== 1) { $firstUncheckedNum = (int) $it['section_number']; break; } }

$stepState = function (array $it) use ($readOnlyViewer, $firstUncheckedNum): string {
    if ((int) $it['is_checked'] === 1) return 'done';
    if ($readOnlyViewer) return 'active';
    return ((int) $it['section_number'] === $firstUncheckedNum) ? 'active' : 'locked';
};
$expandedState = $expandedItem ? $stepState($expandedItem) : '';
$expandedIsDone = $expandedItem ? (bool) ($completion[$expandedItem['section_number']] ?? false) : false;

$isLastStep = !empty($items) && $expandedStep === (int) $items[count($items) - 1]['section_number'];
$priorChecked = true;
foreach (array_slice($items, 0, -1) as $it) { if ((int) $it['is_checked'] !== 1) { $priorChecked = false; break; } }
?>
<div class="flex flex-col gap-5">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <div class="fr-topbar">
    <a class="fr-back-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports') ?>"><i data-lucide="chevron-right"></i> التقارير النهائية</a>
    <div class="fr-topbar-sep"></div>
    <div><h2>إنشاء تقرير / متابعة الاعتماد</h2><p><?= esc($mission['mission_code']) ?> — <?= esc($mission['title']) ?></p></div>
  </div>

  <div class="fr-stepper-card">
    <div class="fr-stepper-head">
      <div class="fr-stepper-head-top">
        <i data-lucide="clipboard-list"></i><span class="t">مراحل الاعتماد</span>
        <span class="fr-phases-count" dir="ltr"><?= $checkedCount ?> / <?= $total ?></span>
      </div>
    </div>

    <div class="fr-hstep-track">
      <?php foreach ($items as $idx => $it): ?>
        <?php $state = $stepState($it); $isExpanded = (int) $it['section_number'] === $expandedStep; $isLast = $idx === count($items) - 1; ?>
        <div class="fr-hstep-node <?= $state ?><?= $isExpanded ? ' expanded' : '' ?>">
          <a class="fr-hstep-circle-btn" style="text-decoration:none;<?= $state === 'locked' ? 'pointer-events:none;opacity:.5;' : '' ?>" href="<?= base_url('dashboard/reports/' . $mission['id']) ?>?step=<?= (int) $it['section_number'] ?>" title="<?= esc($it['section_title']) ?>">
            <span class="fr-hstep-circle"><?= $state === 'done' ? '<i data-lucide="check"></i>' : (int) $it['section_number'] ?></span>
          </a>
          <span class="fr-hstep-label"><?= esc($it['section_title']) ?></span>
        </div>
        <?php if (!$isLast): ?><div class="fr-hstep-line <?= $state === 'done' ? 'done' : '' ?>"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($expandedItem): ?>
    <div class="fr-step-detail">
      <div class="fr-step-detail-head">
        <span class="fr-step-detail-title"><?= esc($expandedItem['section_title']) ?></span>
        <span class="fr-step-status <?= $expandedState ?>"><?= $expandedState === 'done' ? 'معتمدة' : ($expandedState === 'active' ? 'الحالية' : 'قادمة') ?></span>
      </div>
      <div class="fr-step-detail-body">
        <?php if ($expandedStep === 1): ?>
          <div class="fr-preview-grid"><div class="fr-preview-field span2">
            <span class="lbl">الخطاب الرسمي</span>
            <span class="val"><a href="<?= base_url('dashboard/pdf/mission-letter/' . $mission['id']) ?>" target="_blank" style="color:var(--p);text-decoration:underline;">فتح الخطاب الرسمي (PDF)</a></span>
          </div></div>
        <?php elseif ($expandedStep === 2 && $section2Data): ?>
          <?php $ag = $section2Data['agreement']; ?>
          <div class="fr-preview-grid">
            <div class="fr-preview-field"><span class="lbl">حالة الاتفاقية</span><span class="val"><?= $ag && $ag['status'] === 'submitted' ? 'مُرسَلة' : 'لم تُرسَل بعد' ?></span></div>
            <div class="fr-preview-field"><span class="lbl">اسم المنسّق</span><span class="val"><?= esc($ag['coordinator_name'] ?? '—') ?></span></div>
          </div>
          <?php if (!empty($section2Data['responses'])): ?>
            <div class="obs-table-wrap" style="margin-top:12px;">
              <table class="obs-table">
                <thead><tr><th>البند</th><th style="width:100px;">موافق</th><th>ملاحظة</th></tr></thead>
                <tbody>
                  <?php foreach ($section2Data['responses'] as $r): ?>
                    <tr><td><?= esc($r['row_text']) ?></td><td><?= !empty($r['agree']) ? 'نعم' : (!empty($r['disagree']) ? 'لا' : '—') ?></td><td><?= esc($r['note'] ?: '—') ?></td></tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php elseif ($expandedStep === 3 && $section3Data): ?>
          <div class="obs-table-wrap">
            <table class="obs-table">
              <thead><tr><th style="width:30px;">م</th><th>المستند</th><th style="width:90px;">يوجد</th><th>ملاحظات</th></tr></thead>
              <tbody>
                <?php if (empty($section3Data['docRequests'])): ?>
                  <tr><td colspan="4" style="text-align:center;color:#9ca3af;">لا توجد مستندات مطلوبة</td></tr>
                <?php else: foreach ($section3Data['docRequests'] as $i => $d): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($d['doc_name']) ?></td>
                    <td><?= $d['exists_flag'] === null ? '—' : ((int) $d['exists_flag'] === 1 ? 'يوجد' : 'لا يوجد') ?></td>
                    <td><?= esc($d['response_note'] ?: '—') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        <?php elseif ($expandedStep === 4 && $section4Data): ?>
          <div class="obs-table-wrap">
            <table class="obs-table">
              <thead><tr><th style="width:30px;">م</th><th>المخاطر</th><th style="width:80px;">التقييم</th><th>وصف الضوابط</th><th style="width:110px;">نوع النشاط</th></tr></thead>
              <tbody>
                <?php if (empty($section4Data['riskItems'])): ?>
                  <tr><td colspan="5" style="text-align:center;color:#9ca3af;">لا توجد مخاطر مسجّلة</td></tr>
                <?php else: foreach ($section4Data['riskItems'] as $i => $it): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($it['risk']) ?></td>
                    <td><?= esc($it['risk_rating'] ?: '—') ?></td>
                    <td><?= esc($it['controls']) ?></td>
                    <td><?= esc($it['activity_type']) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        <?php elseif ($expandedStep === 5 && $section5Data): ?>
          <div class="fr-preview-grid">
            <div class="fr-preview-field"><span class="lbl">تاريخ الاجتماع</span><span class="val"><?= esc($section5Data['meeting']['meeting_date'] ?? '—') ?></span></div>
            <div class="fr-preview-field"><span class="lbl">مكان الاجتماع</span><span class="val"><?= esc($section5Data['meeting']['location'] ?? '—') ?></span></div>
          </div>
          <div class="obs-table-wrap" style="margin-top:12px;">
            <table class="obs-table">
              <thead><tr><th style="width:30px;">م</th><th>الاسم</th><th>الإدارة</th><th>الوظيفة</th></tr></thead>
              <tbody>
                <?php if (empty($section5Data['attendees'])): ?>
                  <tr><td colspan="4" style="text-align:center;color:#9ca3af;">لا يوجد حضور مسجّل</td></tr>
                <?php else: foreach ($section5Data['attendees'] as $i => $a): ?>
                  <tr><td><?= $i + 1 ?></td><td><?= esc($a['external_name']) ?></td><td><?= esc($a['attendee_dept']) ?></td><td><?= esc($a['attendee_position']) ?></td></tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
          <?php if (!empty($section5Data['points'])): ?>
          <div class="obs-table-wrap" style="margin-top:12px;">
            <table class="obs-table">
              <thead><tr><th>النقطة</th><th>الرأي</th><th>السبب / التوضيح</th></tr></thead>
              <tbody>
                <?php foreach ($section5Data['points'] as $p): ?>
                  <tr><td><?= esc($p['point_text']) ?></td><td><?= esc($p['opinion'] ?: '—') ?></td><td><?= esc($p['reason'] ?: '—') ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        <?php elseif ($expandedStep === 6 && $section6Data): ?>
          <?php if (empty($section6Data['observations'])): ?>
            <p style="text-align:center;color:#9ca3af;">لا توجد ملاحظات مسجّلة</p>
          <?php else: foreach ($section6Data['observations'] as $i => $o): ?>
            <div class="fr-preview-grid" style="margin-bottom:12px;">
              <div class="fr-preview-field span2"><span class="lbl"><?= ($i + 1) . '. ' . esc($o['title']) ?></span></div>
              <div class="fr-preview-field"><span class="lbl">الإدارة المعنية</span><span class="val"><?= esc($o['department_name'] ?? '—') ?></span></div>
              <div class="fr-preview-field"><span class="lbl">التاريخ</span><span class="val"><?= esc($o['observation_date']) ?></span></div>
              <div class="fr-preview-field span2"><span class="lbl">الملاحظة</span><span class="val"><?= esc($o['observation_text'] ?: '—') ?></span></div>
              <div class="fr-preview-field span2"><span class="lbl">التوصيات</span><span class="val"><?= esc($o['recommendations_text'] ?: '—') ?></span></div>
            </div>
          <?php endforeach; endif; ?>
        <?php elseif ($stepUrl): ?>
          <div class="fr-preview-grid"><div class="fr-preview-field span2">
            <span class="lbl"><?= esc($expandedItem['section_title']) ?></span>
            <span class="val"><a href="<?= base_url($stepUrl) ?>?mission_id=<?= (int) $mission['id'] ?>" target="_blank" style="color:var(--p);text-decoration:underline;">فتح "<?= esc($expandedItem['section_title']) ?>" لهذه المهمة</a></span>
          </div></div>
        <?php endif; ?>
      </div>
      <?php if (!$expandedIsDone): ?><p class="fr-step-hint">لم تكتمل بيانات هذه المرحلة بعد</p><?php endif; ?>

      <?php if ($isAuditHead || $readOnlyViewer): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <?php $prevNum = $expandedStep > 1 ? $expandedStep - 1 : null; $nextNum = $expandedStep < 6 ? $expandedStep + 1 : null; ?>
        <?php if ($prevNum): ?>
          <a class="fr-back-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports/' . $mission['id']) ?>?step=<?= $prevNum ?>"><i data-lucide="chevron-right"></i> السابق</a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($nextNum): ?>
          <a class="fr-next-btn" style="text-decoration:none;" href="<?= base_url('dashboard/reports/' . $mission['id']) ?>?step=<?= $nextNum ?>">التالي <i data-lucide="chevron-left"></i></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="fr-phases-footer">
      <?php if ($report['status'] === 'sent'): ?>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span style="font-size:12px;color:#6b7280;">معتمد<?= !empty($report['head_name']) ? ' — ' . esc($report['head_name']) : '' ?><?= !empty($report['head_approved_at']) ? ' — ' . esc($report['head_approved_at']) : '' ?></span>
          <?php if (!empty($report['head_signature'])): ?><img src="<?= esc($report['head_signature']) ?>" alt="توقيع الرئيس" style="height:30px;"><?php endif; ?>
          <a class="fr-action-pdf-btn" style="text-decoration:none;" href="<?= base_url('dashboard/pdf/final-report/' . $mission['id']) ?>" title="تصدير PDF"><i data-lucide="file-down"></i> تصدير PDF</a>
        </div>
      <?php elseif ($isAuditHead): ?>
        <?php if ($report['status'] === 'pending_signatures'): ?>
          <span style="font-size:12px;color:#6b7280;">أدخل اسمك ووقّع بالأسفل لاعتماد التقرير</span>
        <?php else: ?>
          <span style="font-size:12px;color:#6b7280;"><?= $report['status'] === 'pending_signatures' ? 'تحت المراجعة' : 'تحت الإعداد' ?></span>
        <?php endif; ?>
      <?php elseif ($readOnlyViewer): ?>
        <span style="font-size:12px;color:#6b7280;"><?= $report['status'] === 'pending_signatures' ? 'تحت المراجعة' : 'تحت الإعداد' ?></span>
      <?php elseif ($expandedItem): ?>
        <?php if ($isLastStep): ?>
          <form method="post" action="<?= base_url('dashboard/reports/api/finalize') ?>">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">
            <button type="submit" class="fr-submit-btn" <?= (!$expandedIsDone || !$priorChecked || $report['status'] !== 'draft') ? 'disabled' : '' ?>><i data-lucide="send"></i> <?= $report['status'] === 'draft' ? 'اعتماد التقرير وإرساله' : 'تم الإرسال' ?></button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= base_url('dashboard/reports/api/toggle-check') ?>">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
            <input type="hidden" name="section_number" value="<?= (int) $expandedItem['section_number'] ?>">
            <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">
            <button type="submit" class="fr-next-btn" <?= !$expandedIsDone ? 'disabled' : '' ?>>التالي <i data-lucide="chevron-left"></i></button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isAuditHead && $report['status'] === 'pending_signatures'): ?>
  <div class="wiz-card" id="frHeadSignCard">
    <div class="wiz-card-head"><i data-lucide="check-check"></i><span style="color:#fff;font-weight:700;font-size:14px;">اعتماد رئيس إدارة المراجعة الداخلية</span></div>
    <form method="post" action="<?= base_url('dashboard/reports/api/approve') ?>" id="frApproveForm" style="padding:16px;display:flex;flex-direction:column;gap:14px;">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
      <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
      <input type="hidden" name="head_name" id="frHeadNameHidden">
      <input type="hidden" name="head_signature" id="frHeadSigHidden">

      <div style="display:flex;gap:14px;flex-wrap:wrap;">
        <div class="wiz-field" style="flex:1;min-width:160px;">
          <span class="wiz-label">اسم الرئيس</span>
          <input type="text" class="wiz-input" id="frHeadName" placeholder="اسم رئيس إدارة المراجعة الداخلية" value="<?= esc($report['head_name'] ?? '') ?>">
        </div>

        <div class="wiz-field" style="flex:1.4;min-width:200px;">
          <span class="wiz-label">التوقيع</span>
          <div class="wiz-sig-pad-card">
            <canvas id="frHeadSigPad" class="wiz-sig-pad-canvas" width="260" height="70" style="height:70px;"></canvas>
            <span class="wiz-sig-pad-hint" id="frHeadSigPadHint"><i data-lucide="pen-line"></i> وقّع هنا</span>
            <button type="button" class="wiz-sig-pad-clear" id="frHeadSigPadClear" title="مسح التوقيع"><i data-lucide="eraser"></i></button>
          </div>
        </div>

        <div class="wiz-field" style="flex:1;min-width:140px;">
          <span class="wiz-label">التاريخ</span>
          <input type="date" class="wiz-input" name="head_approved_at" value="<?= esc($report['head_approved_at'] ?? date('Y-m-d')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
      </div>

      <div>
        <button type="submit" class="fr-submit-btn" <?= !$isLastStep ? 'disabled' : '' ?>><i data-lucide="check-check"></i> اعتماد التقرير</button>
        <?php if (!$isLastStep): ?><p class="fr-step-hint" style="margin:6px 0 0;">تصفّح كل المراحل بالأعلى حتى "الملاحظات" لتفعيل زر الاعتماد</p><?php endif; ?>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php if ($isAuditHead && $report['status'] === 'pending_signatures'): ?>
<script src="<?= av('assets/js/finalreports-show-page.js') ?>"></script>
<?php endif; ?>
<?php $this->endSection() ?>
