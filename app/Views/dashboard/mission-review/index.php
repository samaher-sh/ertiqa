<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$flash = session()->getFlashdata('success') ?? session()->getFlashdata('error');
$flashType = session()->getFlashdata('success') ? 'success' : 'error';
$locked = !$selectedMissionId;
$m = $mission ?? [];
$a = $agreement ?? [];
$created = substr((string) ($m['created_at'] ?? ''), 0, 10);

$channels = [
    ['active' => $a['channel_email'] ?? 0, 'value' => $a['channel_email_value'] ?? '', 'icon' => 'mail', 'label' => 'البريد الإلكتروني'],
    ['active' => $a['channel_memo'] ?? 0, 'value' => $a['channel_memo_value'] ?? '', 'icon' => 'message-square', 'label' => 'المذكرات الداخلية'],
    ['active' => $a['channel_phone'] ?? 0, 'value' => $a['channel_phone_value'] ?? '', 'icon' => 'phone', 'label' => 'الهاتف الداخلي'],
];
$activeChannels = array_values(array_filter($channels, fn($c) => (int) $c['active'] === 1 && $c['value']));
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/target-mission'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $locked ? ' locked' : '' ?>">
  <?php if ($mission): ?>

    <?php if (empty($embed)): ?>
    <div class="wiz-card">
      <div class="wiz-card-head">
        <i data-lucide="file-text"></i>
        <div><h2>نموذج الخطاب الرسمي</h2><p>عرض فقط</p></div>
      </div>
      <div class="wiz-letter-scroll">
        <div class="wiz-paper">
          <div class="wiz-paper-watermark"><img src="<?= base_url('assets/images/kamc.png') ?>" alt=""></div>
          <div class="wiz-paper-body">
            <div class="wiz-letterhead">
              <div>
                <img src="<?= base_url('assets/images/kamc.png') ?>" alt="مدينة الملك عبدالله الطبية">
                <p class="sub">إدارة المراجعة الداخلية</p>
              </div>
              <div class="wiz-letterhead-meta">
                <p>التاريخ: <?= esc($created) ?></p>
                <p>رقم المهمة: <strong dir="ltr" style="display:inline-block;"><?= esc($m['mission_code'] ?? '') ?></strong></p>
              </div>
            </div>
            <div class="wiz-divider-fade"></div>
            <p class="wiz-p" style="font-weight:700;color:#1f2937;">
              سعادة المدير التنفيذي لـ<mark class="wiz-mark"><?= esc($m['target_department_name'] ?? '') ?></mark> المحترم
            </p>
            <p class="wiz-p" style="font-weight:600;color:#4b5563;">السلام عليكم ورحمة الله وبركاته،،،</p>
            <p class="wiz-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة
              <mark class="wiz-mark small"><?= esc($m['target_department_name'] ?? '') ?></mark>،
              للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام
              <mark class="wiz-mark small"><?= esc((string) ($m['year'] ?? '')) ?></mark>م المعتمدة من قبل المدير العام التنفيذي.
            </p>
            <p class="wiz-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>
            <?php if (!empty($m['procedure_note'])): ?>
            <div class="wiz-procedure-box">
              <div class="wiz-procedure-head"><i data-lucide="clipboard-list"></i><span>المراد مناقشته في الاجتماع</span></div>
              <p class="wiz-procedure-body"><?= esc($m['procedure_note']) ?></p>
            </div>
            <?php endif; ?>
            <p class="wiz-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
            <p class="wiz-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
            <p class="wiz-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark class="wiz-mark small"><?= esc($m['reviewer_name'] ?? '') ?></mark></p>
            <p class="wiz-p" style="margin-bottom:2px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <div class="wiz-contact-row"><i data-lucide="mail"></i><span>البريد الإلكتروني:</span><span class="val" dir="ltr" style="unicode-bidi:embed;"><?= esc($m['reviewer_email'] ?? '') ?></span></div>
              <div class="wiz-contact-row"><i data-lucide="phone"></i><span>رقم الجوال:</span><span class="val" dir="ltr" style="unicode-bidi:embed;"><?= esc($m['reviewer_phone'] ?? '') ?></span></div>
            </div>
            <p class="wiz-p" style="font-weight:600;margin-top:12px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
            <p class="wiz-p" style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
            <?php if (!empty($m['director_name'])): ?><p class="wiz-p" style="font-weight:800;color:var(--pd);"><?= esc($m['director_name']) ?></p><?php endif; ?>
          </div>
          <div class="wiz-paper-footer-bar"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/target-mission/api/save-agreement') ?>" id="mrAgreementForm">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
      <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">

      <div class="wiz-card">
        <div class="wiz-card-head">
          <i data-lucide="file-text"></i>
          <h2 style="margin:0;">اتفاقية مستوى الخدمة</h2>
          <?php if (($a['status'] ?? '') === 'submitted'): ?><span class="dr-status-badge yes" style="margin-right:auto;">تم الإرسال</span><?php endif; ?>
        </div>

        <div class="wiz-sla-grid" style="padding:20px 24px;">
          <div class="wiz-field">
            <label class="wiz-label">اسم المنسّق <span class="wiz-req">*</span></label>
            <input name="coordinator_name" type="text" data-mask="letters" class="wiz-input plain" placeholder="اسم منسّق التواصل" value="<?= esc(old('coordinator_name') ?? ($a['coordinator_name'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">البريد الإلكتروني للمنسّق</label>
            <input name="coordinator_email" type="email" dir="ltr" style="text-align:left;" class="wiz-input plain" placeholder="example@kamc.med.sa" value="<?= esc(old('coordinator_email') ?? ($a['coordinator_email'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">رقم جوال المنسّق</label>
            <input name="coordinator_phone" type="tel" inputmode="numeric" maxlength="10" dir="ltr" style="text-align:left;" data-mask="phone" class="wiz-input plain" placeholder="05XXXXXXXX" value="<?= esc(old('coordinator_phone') ?? ($a['coordinator_phone'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>

        <div class="wiz-channels" style="padding:0 24px 20px;">
          <p class="wiz-channels-title">قنوات الاتصال المعتمدة</p>
          <?php if (empty($activeChannels)): ?>
            <p class="fr-preview-empty" style="padding:0;">لم تُحدَّد قنوات اتصال</p>
          <?php else: ?>
            <?php foreach ($activeChannels as $c): ?>
              <div class="wiz-channel active">
                <div class="wiz-channel-head" style="cursor:default;">
                  <span class="wiz-channel-check"><i data-lucide="check"></i></span>
                  <i class="ic" data-lucide="<?= esc($c['icon']) ?>"></i>
                  <span><?= esc($c['label']) ?></span>
                </div>
                <div class="wiz-channel-body">
                  <div class="msum-auto-field plain" <?= in_array($c['icon'], ['mail', 'phone'], true) ? 'dir="ltr" style="justify-content:flex-end;"' : '' ?>><?= esc($c['value']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="wiz-card">
        <div class="wiz-card-head">
          <i data-lucide="clipboard-list"></i>
          <span style="color:#fff;font-weight:700;font-size:14px;">بنود الاتفاقية</span>
          <?php if ($canEdit): ?><p style="margin-right:auto;font-size:11px;">وافق أو لا توافق على كل بند، وأضف ملاحظة إن وجدت</p><?php elseif (empty($embed)): ?><p style="margin-right:auto;font-size:11px;">عرض فقط</p><?php endif; ?>
        </div>
        <div class="wiz-table-wrap">
          <table class="wiz-table">
            <thead><tr>
              <th>الموضوع</th><th class="center">موافق</th><th class="center">غير موافق</th><th style="width:220px;">ملاحظات إن وجد</th>
            </tr></thead>
            <tbody>
              <?php $si = 0; foreach ($rowsBySection as $title => $sectionRows): $si++; ?>
                <tr class="wiz-sla-section-row"><td colspan="4"><span class="num"><?= $si ?></span><?= esc($title) ?></td></tr>
                <?php foreach ($sectionRows as $row): ?>
                  <?php
                  $oldAnswer = old('rows.' . $row['id'] . '.answer');
                  $isAgree = $oldAnswer !== null ? $oldAnswer === 'agree' : (int) $row['agree'] === 1;
                  $isDisagree = $oldAnswer !== null ? $oldAnswer === 'disagree' : (int) $row['disagree'] === 1;
                  $unanswered = $canEdit && !$isAgree && !$isDisagree;
                  ?>
                  <tr class="wiz-sla-row<?= $unanswered ? ' mr-row-unanswered' : '' ?>">
                    <td><div class="lbl"><span class="dot"></span><span><?= esc($row['row_text']) ?></span></div></td>
                    <td style="text-align:center;">
                      <?php if ($canEdit): ?>
                        <label class="wiz-checkbox-visual mr-toggle<?= $isAgree ? ' checked' : '' ?>" style="cursor:pointer;">
                          <input type="radio" name="rows[<?= (int) $row['id'] ?>][answer]" value="agree" <?= $isAgree ? 'checked' : '' ?> style="position:absolute;opacity:0;width:0;height:0;">
                          <?= $isAgree ? '<i data-lucide="check" style="width:14px;height:14px;color:var(--p);"></i>' : '' ?>
                        </label>
                      <?php else: ?>
                        <div class="wiz-checkbox-visual mr-toggle<?= $isAgree ? ' checked' : '' ?>" style="pointer-events:none;"><?= $isAgree ? '<i data-lucide="check" style="width:14px;height:14px;color:var(--p);"></i>' : '' ?></div>
                      <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                      <?php if ($canEdit): ?>
                        <label class="wiz-checkbox-visual no mr-toggle<?= $isDisagree ? ' checked' : '' ?>" style="cursor:pointer;">
                          <input type="radio" name="rows[<?= (int) $row['id'] ?>][answer]" value="disagree" <?= $isDisagree ? 'checked' : '' ?> style="position:absolute;opacity:0;width:0;height:0;">
                          <?= $isDisagree ? '<i data-lucide="x" style="width:14px;height:14px;color:#dc2626;"></i>' : '' ?>
                        </label>
                      <?php else: ?>
                        <div class="wiz-checkbox-visual no mr-toggle<?= $isDisagree ? ' checked' : '' ?>" style="pointer-events:none;"><?= $isDisagree ? '<i data-lucide="x" style="width:14px;height:14px;color:#dc2626;"></i>' : '' ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <input type="text" name="rows[<?= (int) $row['id'] ?>][note]" class="mr-sla-note-input" placeholder="ملاحظة..." value="<?= esc(old('rows.' . $row['id'] . '.note') ?? ($row['note'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
              <?php if (empty($rowsBySection)): ?>
                <tr><td colspan="4"><p class="dr-empty">لا توجد اتفاقية مستوى خدمة لهذه المهمة</p></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($canEdit && !empty($rowsBySection)): ?>
          <div class="dr-footer">
            <button type="submit" class="dr-submit-btn"><i data-lucide="check"></i> حفظ الاتفاقية</button>
          </div>
        <?php endif; ?>
      </div>
    </form>

    <?php if (empty($embed)): ?>
    <div class="wiz-card">
      <div class="wiz-card-head" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
          <i data-lucide="folder-check"></i>
          <div><h2>قائمة المستندات المطلوبة</h2></div>
        </div>
      </div>
      <div class="wiz-doc-footer" style="border-top:0;">
        <span class="wiz-doc-footer-count">تُدار الآن من صفحتها المستقلة</span>
        <a class="dr-submit-btn" style="text-decoration:none;" href="<?= base_url('dashboard/document-requests') ?>?mission_id=<?= (int) $mission['id'] ?>"><i data-lucide="folder-check"></i> فتح قائمة المستندات</a>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
