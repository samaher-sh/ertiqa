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
$locked = !$selectedMissionId;
$v = fn($f, $default = '') => old($f) ?? ($planning[$f] ?? $default);

$oldMilestones = old('milestones');
$milestoneRows = [];
foreach (($milestones ?: []) as $i => $m) {
    $milestoneRows[] = [
        'label' => $oldMilestones[$i]['label'] ?? ($m['row_label'] ?? ''),
        'date'  => $oldMilestones[$i]['date'] ?? ($m['milestone_date'] ?? ''),
        'days'  => $oldMilestones[$i]['days'] ?? ($m['days_required'] ?? ''),
        'note'  => $oldMilestones[$i]['note'] ?? ($m['note'] ?? ''),
    ];
}
?>
<div class="flex flex-col gap-4">
  <?php if ($flash): ?><div class="obs-alert obs-alert-<?= $flashType ?>"><?= esc($flash) ?></div><?php endif; ?>

  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/mission-planning'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $locked ? ' locked' : '' ?>" style="display:flex;flex-direction:column;gap:20px;">
    <?php if ($selectedMissionId): ?>
    <form method="post" action="<?= base_url('dashboard/mission-planning/api/save') ?>" id="planningForm" style="display:flex;flex-direction:column;gap:20px;">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
      <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">

      <div class="wiz-card">
        <div class="wiz-card-head">
          <i data-lucide="clipboard-list"></i>
          <h2 style="margin:0;">مذكرة تخطيط المهمة</h2>
          <?php if (!$canEdit): ?><span class="msum-readonly-badge" style="margin-right:auto;"><i data-lucide="lock"></i> عرض فقط</span><?php endif; ?>
        </div>
        <div class="wiz-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="wiz-field">
            <label class="wiz-label">رقم المهمة</label>
            <div class="msum-auto-field plain"><span class="val" dir="ltr" style="unicode-bidi:embed;"><?= esc($mission['mission_code'] ?? '—') ?></span></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">الإدارة الخاضعة للمراجعة</label>
            <div class="msum-auto-field plain"><span class="val"><?= esc($mission['target_department_name'] ?? '—') ?></span></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">تاريخ بدأ المشروع</label>
            <input name="project_start_date" type="date" class="wiz-input plain" value="<?= esc($v('project_start_date')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">مدير الإدارة الخاضعة للمراجعة</label>
            <input name="target_dept_manager_name" type="text" data-mask="letters" class="wiz-input plain" placeholder="الاسم كاملاً" value="<?= esc($v('target_dept_manager_name')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">عدد أيام التنفيذ ( خطة المراجعة)</label>
            <input name="plan_execution_days" type="text" class="wiz-input plain" value="<?= esc($v('plan_execution_days')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">المراجع الرئيسي</label>
            <div class="msum-auto-field plain"><span class="val"><?= esc($mission['reviewer_name'] ?? '—') ?></span></div>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">عدد أيام التنفيذ ( المقترحة)</label>
            <input name="proposed_execution_days" type="text" class="wiz-input plain" value="<?= esc($v('proposed_execution_days')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">المراجعين المشاركين</label>
            <input name="participating_reviewers" type="text" class="wiz-input plain" value="<?= esc($v('participating_reviewers')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
      </div>

      <div class="wiz-card">
        <div class="wiz-table-wrap">
          <table class="wiz-table wiz-table-fields">
            <thead><tr><th style="width:220px;">البند</th><th>التفاصيل</th></tr></thead>
            <tbody>
              <tr>
                <td class="wiz-table-row-label">نبذة عن المهمة</td>
                <td><textarea name="mission_brief" rows="1" class="wiz-textarea plain" placeholder="عرض تقديم عن مشروع المراجعة والإدارة الخاضعة للمراجعة" <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('mission_brief')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">عمليات المراجعة السابقة</td>
                <td><textarea name="previous_audits" rows="2" class="wiz-textarea plain" placeholder="رقم التقرير&#10;نبذة عن الملاحظات" <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('previous_audits')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">تاريخ إصدار التقرير</td>
                <td><input name="previous_audit_report_date" type="date" class="wiz-input plain" value="<?= esc($v('previous_audit_report_date')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}" <?= $canEdit ? '' : 'readonly' ?>></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">ملاحظات الجهات الرقابية</td>
                <td><textarea name="regulatory_notes" rows="1" class="wiz-textarea plain" placeholder="نبذة عن الملاحظات حسب توفرها." <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('regulatory_notes')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">أهداف المراجعة</td>
                <td><textarea name="audit_objectives" rows="3" class="wiz-textarea plain" placeholder="أهداف المراجعة&#10;الهدف الأول: &#10;الهدف الثاني: &#10;..." <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('audit_objectives')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">نطاق المراجعة</td>
                <td><textarea name="scope_included" rows="2" class="wiz-textarea plain" placeholder="نطاق المشروع يتضمن التالي: &#10;.......&#10;......." <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('scope_included')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">النطاق المستثنى من المراجعة</td>
                <td><textarea name="scope_excluded" rows="2" class="wiz-textarea plain" placeholder="نطاق المشروع لن يشمل التالي: &#10;...&#10;..." <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('scope_excluded')) ?></textarea></td>
              </tr>
              <tr>
                <td class="wiz-table-row-label">الإجراءات الفرعية المشمولة بالمراجعة</td>
                <td><textarea name="sub_procedures" rows="1" class="wiz-textarea plain" placeholder="...&#10;..." <?= $canEdit ? '' : 'readonly' ?>><?= esc($v('sub_procedures')) ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="wiz-card">
        <div class="wiz-card-head"><i data-lucide="list-checks"></i><span style="color:#fff;font-weight:700;font-size:14px;">النقاط الهامة في المراجعة</span></div>
        <div class="wiz-table-wrap">
          <table class="wiz-table">
            <thead><tr>
              <th style="width:40px;">#</th><th>تخطيط المهمة</th><th style="width:150px;">التاريخ</th><th style="width:150px;">عدد الأيام المطلوبة</th><th>ملاحظة</th>
            </tr></thead>
            <tbody>
              <?php foreach ($milestoneRows as $i => $m): ?>
                <tr>
                  <td style="text-align:center;"><?= $i + 1 ?></td>
                  <td>
                    <?php if ($i < 4): ?>
                      <input type="hidden" name="milestones[<?= $i ?>][label]" value="<?= esc($m['label']) ?>">
                      <span><?= esc($m['label']) ?></span>
                    <?php else: ?>
                      <input type="text" name="milestones[<?= $i ?>][label]" class="wiz-input plain" placeholder="نقطة إضافية..." value="<?= esc($m['label']) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    <?php endif; ?>
                  </td>
                  <td><input type="date" name="milestones[<?= $i ?>][date]" class="wiz-input plain" value="<?= esc($m['date']) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}" <?= $canEdit ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="milestones[<?= $i ?>][days]" class="wiz-input plain" value="<?= esc($m['days']) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="milestones[<?= $i ?>][note]" class="wiz-input plain" value="<?= esc($m['note']) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="wiz-card">
        <div class="wiz-card-head"><i data-lucide="check-check"></i><span style="color:#fff;font-weight:700;font-size:14px;">الاعتماد</span></div>
        <div class="wiz-card-body" style="display:flex;flex-direction:column;gap:16px;">
          <p class="wiz-p" style="margin:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span>تم الاجتماع الداخلي فريق العمل بتاريخ</span>
            <input type="date" name="team_meeting_date" class="wiz-input plain" style="width:170px;display:inline-block;" value="<?= esc($v('team_meeting_date')) ?>" onclick="try{this.showPicker&&this.showPicker()}catch(e){}" <?= $canEdit ? '' : 'readonly' ?>>
            <span>كأحد مخرجات مرحلة التخطيط للمهمة، وبناء عليه تم الاتفاق على ما تم ذكره أعلاه.</span>
          </p>
          <div class="wiz-table-wrap">
            <table class="wiz-table wiz-table-spacious">
              <thead><tr><th style="width:160px;">الإجراء</th><th>المسمى الوظيفي</th><th>الاسم</th></tr></thead>
              <tbody>
                <tr>
                  <td class="wiz-table-row-label">الاعداد</td>
                  <td><input type="text" name="prepared_by_title" class="wiz-input plain" value="<?= esc($v('prepared_by_title')) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="prepared_by_name" data-mask="letters" class="wiz-input plain" value="<?= esc($v('prepared_by_name')) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                </tr>
                <tr>
                  <td class="wiz-table-row-label">الاعتماد</td>
                  <td><input type="text" name="approved_by_title" class="wiz-input plain" value="<?= esc($v('approved_by_title')) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                  <td><input type="text" name="approved_by_name" data-mask="letters" class="wiz-input plain" value="<?= esc($v('approved_by_name')) ?>" <?= $canEdit ? '' : 'readonly' ?>></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php if ($canEdit): ?>
        <div class="msum-bottom-row">
          <div class="msum-submit-wrap">
            <button type="submit" class="msum-submit-btn dirty"><i data-lucide="send"></i> حفظ التغييرات</button>
          </div>
        </div>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<?php $this->endSection() ?>
