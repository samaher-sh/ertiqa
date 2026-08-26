<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingsummary.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<?php
$errorMsg = session()->getFlashdata('error');
$v = fn($f, $default = '') => old($f) ?? $default;
$vChecked = fn($f) => old($f) !== null ? (bool) old($f) : true; // القنوات الثلاث مفعّلة افتراضيًا (نفس wizP2.ch الأصلية)
$selectedDept = $selectedDeptId ? (current(array_filter($mainDepts, fn($d) => (int) $d['id'] === $selectedDeptId)) ?: null) : null;
$deptName = $selectedDept['name_ar'] ?? '';
$selectedTargetId = $v('target_dept_id');
$selectedTarget = $selectedTargetId ? (current(array_filter($subDepts, fn($d) => (string) $d['id'] === (string) $selectedTargetId)) ?: null) : null;
$targetName = $selectedTarget['name_ar'] ?? '';
$procedure = $v('procedure');
$reviewerName = $v('reviewer_name');
$reviewerEmail = $v('reviewer_email');
$reviewerPhone = $v('reviewer_phone');
$directorName = $v('director_name');

$channelsMeta = [
    'email' => ['البريد الإلكتروني', 'email', 'mail', 'أدخل عنوان البريد الإلكتروني'],
    'memo'  => ['المذكرات الداخلية', 'textarea', 'message-square', 'أدخل تفاصيل المذكرات الداخلية'],
    'phone' => ['الهاتف الداخلي', 'tel', 'phone', 'أدخل رقم الهاتف الداخلي'],
];
?>
<div class="flex flex-col gap-4">
  <?php if ($errorMsg): ?><div class="obs-alert obs-alert-error"><?= esc($errorMsg) ?></div><?php endif; ?>

  <div class="wiz-steps" id="wizSteps">
    <div class="wiz-step">
      <button type="button" class="wiz-step-btn" data-goto-step="1">
        <span class="wiz-step-circle current" id="wizStepCircle1">1</span>
        <span class="wiz-step-label current">طلب المراجعة الداخلية</span>
      </button>
      <span class="wiz-step-line"></span>
    </div>
    <div class="wiz-step">
      <button type="button" class="wiz-step-btn" data-goto-step="2">
        <span class="wiz-step-circle" id="wizStepCircle2">2</span>
        <span class="wiz-step-label">اتفاقية مستوى الخدمة</span>
      </button>
    </div>
  </div>

  <!-- نموذج GET مستقل (غير متداخل داخل newTaskForm) لتحديث قائمة الإدارات
       الفرعية عند تغيير الإدارة -- <select id="mainDeptSelect"> بالأسفل
       مرتبط به عبر form="deptCascadeForm" رغم إنه فعليًا متمركز بصريًا
       داخل newTaskForm (تعشيش <form> داخل <form> HTML غير صالح: لو كان
       مباشرة متداخل، المتصفح يربط عناصره بالنموذج الخارجي POST بدل الداخلي
       GET، فيرسل newTaskForm فارغًا عند أي تغيير للإدارة) -->
  <form method="get" action="<?= base_url('dashboard/new-task') ?>" id="deptCascadeForm"></form>

  <form method="post" action="<?= base_url('dashboard/new-task') ?>" id="newTaskForm" style="display:flex;flex-direction:column;gap:20px;">
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
    <input type="hidden" name="main_dept_id" value="<?= $selectedDeptId ?>">

    <div class="wiz-page-container">
      <div id="wizStep1">
        <div class="wiz-page1-grid">
          <!-- RIGHT: النموذج -->
          <div class="wiz-card">
            <div class="wiz-card-head">
              <i data-lucide="plus"></i>
              <div><h2>طلب المراجعة الداخلية</h2></div>
            </div>
            <div class="wiz-card-body">
              <div class="wiz-section">
                <p class="wiz-section-title">بيانات الإدارة</p>
                <div class="wiz-field">
                  <label class="wiz-label">الإدارة <span class="wiz-req">*</span></label>
                  <select name="main_dept_id" form="deptCascadeForm" id="mainDeptSelect" class="wiz-select<?= $selectedDeptId ? ' filled' : '' ?>" onchange="this.form.submit()">
                    <option value="">— اختر —</option>
                    <?php foreach ($mainDepts as $d): ?>
                      <option value="<?= (int) $d['id'] ?>" <?= $selectedDeptId === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['name_ar']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <noscript><button type="submit" form="deptCascadeForm" class="wiz-btn wiz-btn-outline" style="margin-top:8px;">تحديث قائمة الإدارات الفرعية</button></noscript>
                </div>
                <div class="wiz-field">
                  <label class="wiz-label">الإدارة المستهدفة <span class="wiz-req">*</span></label>
                  <select id="p1Target" name="target_dept_id" class="wiz-select" <?= $selectedDeptId ? '' : 'disabled' ?>>
                    <option value="">— اختر —</option>
                    <?php foreach ($subDepts as $sd): ?>
                      <option value="<?= (int) $sd['id'] ?>" <?= $selectedTargetId == $sd['id'] ? 'selected' : '' ?>><?= esc($sd['name_ar']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (!$selectedDeptId): ?><p class="wiz-hint">يُرجى اختيار الإدارة أولاً لتفعيل هذا الحقل</p><?php endif; ?>
                </div>
                <div class="wiz-field">
                  <label class="wiz-label">السنة</label>
                  <select id="p1Year" name="year" class="wiz-select filled">
                    <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $v('year', (string) date('Y')) === $y ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="wiz-section">
                <p class="wiz-section-title">المراد مناقشته في الاجتماع <span class="wiz-req">*</span></p>
                <textarea id="p1Procedure" name="procedure" rows="4" class="wiz-textarea" placeholder="أدخل المراد مناقشته في الاجتماع هنا..."><?= esc($procedure) ?></textarea>
              </div>

              <div class="wiz-section">
                <p class="wiz-section-title">بيانات المراجع</p>
                <div class="wiz-field">
                  <label class="wiz-label">اسم المراجع الرئيسي <span class="wiz-req">*</span></label>
                  <input id="p1Reviewer" name="reviewer_name" type="text" data-mask="letters" class="wiz-input plain" placeholder="الاسم كاملاً" value="<?= esc($reviewerName) ?>">
                </div>
                <div class="wiz-field">
                  <label class="wiz-label">البريد الإلكتروني <span class="wiz-req">*</span></label>
                  <input id="p1Email" name="reviewer_email" type="email" dir="ltr" style="text-align:left;" data-mask="email" class="wiz-input plain" placeholder="example@kamc.med.sa" value="<?= esc($reviewerEmail) ?>">
                </div>
                <div class="wiz-field">
                  <label class="wiz-label">رقم الجوال <span class="wiz-req">*</span></label>
                  <input id="p1Phone" name="reviewer_phone" type="tel" inputmode="numeric" maxlength="10" dir="ltr" style="text-align:left;" data-mask="phone" class="wiz-input plain" placeholder="05XXXXXXXX" value="<?= esc($reviewerPhone) ?>">
                </div>
              </div>

              <div class="wiz-section">
                <p class="wiz-section-title">بيانات المدير</p>
                <div class="wiz-field">
                  <label class="wiz-label">اسم المدير</label>
                  <input id="p1Director" name="director_name" type="text" data-mask="letters" class="wiz-input plain" placeholder="الاسم كاملاً" value="<?= esc($directorName) ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- LEFT: معاينة حية للخطاب -->
          <div class="wiz-card">
            <div class="wiz-card-head">
              <i data-lucide="file-text"></i>
              <div><h2>نموذج الخطاب الرسمي</h2><p>يتم ملؤه تلقائياً من النموذج</p></div>
              <button type="button" class="obs-btn-pdf" id="wizP1ExportBtn" style="margin-right:auto;"><i data-lucide="file-text"></i> تصدير PDF</button>
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
                      <p>التاريخ: <?= esc(date('d/m/Y')) ?></p>
                      <p>رقم المهمة: <strong dir="ltr" style="display:inline-block;">سيُحدَّد بعد الحفظ</strong></p>
                    </div>
                  </div>
                  <div class="wiz-divider-fade"></div>
                  <p class="wiz-p" style="font-weight:700;color:#1f2937;">
                    سعادة المدير التنفيذي لـ<?= $deptName ? '<mark class="wiz-mark" id="mDept">' . esc($deptName) . '</mark>' : '<span id="mDept"></span>' ?> المحترم
                  </p>
                  <p class="wiz-p" style="font-weight:600;color:#4b5563;">السلام عليكم ورحمة الله وبركاته،،،</p>
                  <p class="wiz-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة
                    <mark class="wiz-mark small" id="mTarget"><?= esc($targetName ?: 'الإدارة المستهدفة') ?></mark>،
                    للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام
                    <mark class="wiz-mark small" id="mYear"><?= esc($v('year', (string) date('Y'))) ?></mark>م المعتمدة من قبل المدير العام التنفيذي.
                  </p>
                  <p class="wiz-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>
                  <div class="wiz-procedure-box" id="procedureBox" <?= $procedure ? '' : 'hidden' ?>>
                    <div class="wiz-procedure-head"><i data-lucide="clipboard-list"></i><span>المراد مناقشته في الاجتماع</span></div>
                    <p class="wiz-procedure-body" id="procedureText"><?= esc($procedure) ?></p>
                  </div>
                  <p class="wiz-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
                  <p class="wiz-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
                  <p class="wiz-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark class="wiz-mark small" id="mReviewer"><?= esc($reviewerName ?: '...............') ?></mark></p>
                  <p class="wiz-p" style="margin-bottom:2px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
                  <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wiz-contact-row"><i data-lucide="mail"></i><span>البريد الإلكتروني:</span><span class="val" id="mEmail" dir="ltr" style="unicode-bidi:embed;"><?= esc($reviewerEmail ?: '........................') ?></span></div>
                    <div class="wiz-contact-row"><i data-lucide="phone"></i><span>رقم الجوال:</span><span class="val" id="mPhone" dir="ltr" style="unicode-bidi:embed;"><?= esc($reviewerPhone ?: '........................') ?></span></div>
                  </div>
                  <p class="wiz-p" style="font-weight:600;margin-top:12px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
                  <p class="wiz-p" style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
                  <p class="wiz-p" id="mDirector" style="font-weight:800;color:var(--pd);" <?= $directorName ? '' : 'hidden' ?>><?= esc($directorName) ?></p>
                </div>
                <div class="wiz-paper-footer-bar"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="wizStep2" style="display:flex;flex-direction:column;gap:20px;">
        <div class="wiz-card">
          <div class="wiz-card-head">
            <i data-lucide="file-text"></i>
            <h2 style="margin:0;">اتفاقية مستوى الخدمة</h2>
            <button type="button" class="obs-btn-pdf" id="wizP2ExportBtn" style="margin-right:auto;"><i data-lucide="file-text"></i> تصدير PDF</button>
          </div>
          <div class="wiz-sla-grid">
            <div class="wiz-field">
              <label class="wiz-label">الإدارة الخاضعة للمراجعة</label>
              <div class="msum-auto-field plain"><span class="val" id="p2TargetName"><?= esc($targetName ?: '—') ?></span></div>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">تاريخ الاتفاقية</label>
              <input id="p2Date" type="date" class="wiz-input plain" value="<?= esc($v('agreement_date')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
            </div>
            <div class="wiz-field span2">
              <label class="wiz-label">وصف الخدمة</label>
              <textarea id="p2Desc" rows="3" class="wiz-textarea plain"><?= esc($v('agreement_desc', 'تهدف هذه الخدمة إلى عقد اجتماعات المراجعة الداخلية مع الإدارات الخاضعة للمراجعة وتنفيذ العمليات المتعلقة بأعمال المراجعة حسب خطة المراجعة خلال تنسيق على أن تتم تنفيذ الخدمة وفق الجودة المتوقعة.')) ?></textarea>
            </div>
          </div>

          <div class="wiz-channels">
            <p class="wiz-channels-title">قنوات الاتصال المعتمدة</p>
            <?php foreach ($channelsMeta as $chKey => $chMeta): [$chLabel, $chType, $chIcon, $chPh] = $chMeta; $chActive = $vChecked('channel_' . $chKey . '_active'); ?>
              <div class="wiz-channel<?= $chActive ? ' active' : '' ?>" data-wiz-channel="<?= $chKey ?>">
                <div class="wiz-channel-head" data-ch-toggle="<?= $chKey ?>" style="cursor:pointer;">
                  <span class="wiz-channel-check"><?php if ($chActive): ?><i data-lucide="check"></i><?php endif; ?></span>
                  <i class="ic" data-lucide="<?= $chIcon ?>"></i>
                  <span><?= $chLabel ?></span>
                  <input type="checkbox" name="channel_<?= $chKey ?>_active" value="1" <?= $chActive ? 'checked' : '' ?> style="position:absolute;opacity:0;width:0;height:0;">
                </div>
                <div class="wiz-channel-body" <?= $chActive ? '' : 'hidden' ?>>
                  <?php if ($chType === 'textarea'): ?>
                    <textarea name="channel_<?= $chKey ?>_value" rows="3" class="wiz-textarea plain" placeholder="<?= esc($chPh) ?>"><?= esc($v('channel_' . $chKey . '_value')) ?></textarea>
                  <?php else: ?>
                    <input type="<?= $chType ?>" class="wiz-input plain" <?= $chType === 'email' || $chType === 'tel' ? 'dir="ltr" style="text-align:left;"' : '' ?> <?= $chType === 'tel' ? 'inputmode="numeric" maxlength="10" data-mask="phone"' : ($chType === 'email' ? 'data-mask="email"' : '') ?> name="channel_<?= $chKey ?>_value" placeholder="<?= esc($chPh) ?>" value="<?= esc($v('channel_' . $chKey . '_value')) ?>">
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="wiz-card" id="wizSlaSectionsCard" data-sla-sections="<?= esc(json_encode($slaSections), 'attr') ?>">
          <div class="wiz-card-head">
            <i data-lucide="clipboard-list"></i>
            <span style="color:#fff;font-weight:700;font-size:14px;">بنود الاتفاقية</span>
            <p style="margin-right:auto;font-size:11px;">تُرسل للإدارة المستهدفة للموافقة عليها</p>
          </div>
          <div class="wiz-table-wrap">
            <table class="wiz-table">
              <thead><tr>
                <th>الموضوع</th><th class="center">موافق</th><th class="center">غير موافق</th><th style="width:200px;">ملاحظات إن وجد</th>
              </tr></thead>
              <tbody>
                <?php foreach ($slaSections as $si => $sec): ?>
                  <tr class="wiz-sla-section-row"><td colspan="4"><span class="num"><?= $si + 1 ?></span><?= esc($sec['title']) ?></td></tr>
                  <?php foreach ($sec['rows'] as $row): ?>
                    <tr class="wiz-sla-row">
                      <td><div class="lbl"><span class="dot"></span><span><?= esc($row) ?></span></div></td>
                      <td><div class="wiz-checkbox-visual readonly" title="يُعتمد لاحقًا من قِبل ممثل الإدارة المستهدفة"></div></td>
                      <td><div class="wiz-checkbox-visual readonly" title="يُعتمد لاحقًا من قِبل ممثل الإدارة المستهدفة"></div></td>
                      <td><div class="wiz-note-line"></div></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="wiz-card">
          <div class="wiz-card-head"><i data-lucide="file-text"></i><span style="color:#fff;font-weight:700;font-size:14px;">التوقيعات</span></div>
          <div class="wiz-sig-grid">
            <div class="wiz-sig-card active">
              <p class="wiz-sig-title">المراجع الرئيسي</p>
              <div class="wiz-input-icon-wrap">
                <input id="p2SigName" type="text" class="wiz-input plain" placeholder="اسم المراجع الرئيسي" value="<?= esc($v('sig_name')) ?>">
              </div>
              <div>
                <p class="wiz-sig-mini-label">التاريخ</p>
                <input id="p2SigDate" type="date" class="wiz-input plain" value="<?= esc($v('sig_date')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
              </div>
              <div>
                <p class="wiz-sig-mini-label">التوقيع</p>
                <div class="wiz-sig-pad-card">
                  <canvas id="p2SigPad" class="wiz-sig-pad-canvas" width="440" height="130"></canvas>
                  <span class="wiz-sig-pad-hint" id="p2SigPadHint"><i data-lucide="pen-line"></i> وقّع هنا</span>
                  <button type="button" class="wiz-sig-pad-clear" id="p2SigPadClear" title="مسح التوقيع"><i data-lucide="eraser"></i></button>
                </div>
              </div>
            </div>
            <div class="wiz-sig-card locked">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <p class="wiz-sig-title">ممثل الإدارة</p>
                <span class="wiz-sig-locked-badge">تُملأ من قِبل الإدارة المستهدفة</span>
              </div>
              <div><p class="wiz-sig-mini-label">الاسم</p><div class="wiz-sig-name-line"><span class="bar"></span></div></div>
              <div><p class="wiz-sig-mini-label">التاريخ</p><div class="wiz-sig-blank-box solid"></div></div>
              <div><p class="wiz-sig-mini-label">التوقيع</p><div class="wiz-sig-pad-card locked-pad"></div></div>
            </div>
          </div>
          <div class="wiz-disclosure">
            <p class="wiz-disclosure-title">المسؤولية والإفصاح</p>
            <p class="wiz-disclosure-text">تؤكد إدارة المراجعة الداخلية، بأن جميع المعلومات المستلمة سوف تتعامل معها الإدارة بسرية عالية، وفقاً للمادة التاسعة عشرة من قرار مجلس الوزراء 129 بتاريخ 06/04/1428هـ اللائحة الموحدة لوحدات المراجعة الداخلية.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="wiz-nav">
      <button type="button" class="wiz-btn wiz-btn-outline" id="wizPrevBtn" style="min-width:150px;justify-content:center;display:none;">
        <i data-lucide="chevron-right"></i>السابق
      </button>
      <div class="wiz-dots">
        <button type="button" class="wiz-dot current" data-goto-step="1"></button>
        <button type="button" class="wiz-dot" data-goto-step="2"></button>
      </div>
      <button type="button" class="wiz-btn wiz-btn-primary" id="wizNextBtn">التالي<i data-lucide="chevron-left"></i></button>
      <button type="submit" class="wiz-btn wiz-btn-success" id="wizSendBtn" style="min-width:150px;justify-content:center;display:none;"><i data-lucide="check"></i>إرسال المهمة</button>
    </div>
  </form>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/new-task-page.js') ?>"></script>
<?php $this->endSection() ?>
