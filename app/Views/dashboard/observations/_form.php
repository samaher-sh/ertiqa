<?php
/**
 * حقول نموذج إضافة/تعديل ملاحظة — نفس حقول renderObsForm() بـ observations.js
 * بالضبط (نفس التسميات، القيود، والقيم الافتراضية). $observation = null يعني إضافة جديدة.
 * المتغيرات المتوقعة: $observation, $mission, $selectedMissionId, $backUrl
 */
$isEdit = $observation !== null;
$d = [
    'title'                => old('title')                ?? ($observation['title'] ?? ''),
    'observation_date'     => old('observation_date')      ?? ($observation['observation_date'] ?? date('Y-m-d')),
    'observation_text'     => old('observation_text')      ?? ($observation['observation_text'] ?? ''),
    'standard_text'        => old('standard_text')         ?? ($observation['standard_text'] ?? ''),
    'reason_text'          => old('reason_text')           ?? ($observation['reason_text'] ?? ''),
    'impact_text'          => old('impact_text')           ?? ($observation['impact_text'] ?? ''),
    'recommendations_text' => old('recommendations_text')  ?? ($observation['recommendations_text'] ?? ''),
    'risk_severity'        => old('risk_severity')          ?? ($observation['risk_severity'] ?? ''),
];
$addToReport = old('add_to_report');
if ($addToReport === null) {
    $addToReport = isset($observation['add_to_report']) && $observation['add_to_report'] !== null
        ? (string) (int) $observation['add_to_report']
        : null;
}
/* التعديل يستخدم الإدارة المخزّنة أصلًا بالملاحظة نفسها (obs.dept/deptId بالجافاسكربت
   عبر {...obs} بـ obsOpenEdit) لا إدارة المهمة الحالية -- فقط الإضافة الجديدة تشتق
   الإدارة من المهمة المختارة حاليًا (obsMissionDept() بـ obsOpenNew) */
$deptId = $isEdit ? ($observation['department_id'] ?? '') : ($mission['target_department_id'] ?? '');
$deptName = $isEdit ? ($observation['department_name'] ?? '') : ($mission['target_department_name'] ?? '');
$errorMsg = session()->getFlashdata('error');
?>
<div class="obs-form-card">
  <div class="obs-form-head">
    <div class="obs-form-head-left">
      <a class="obs-form-back" href="<?= esc($backUrl) ?>"><i data-lucide="chevron-right"></i></a>
      <h3 class="obs-form-title"><?= $isEdit ? 'تعديل الملاحظة' : 'إضافة ملاحظة جديدة' ?></h3>
    </div>
  </div>

  <div class="obs-form-body">
    <?php if ($errorMsg): ?><div class="obs-alert obs-alert-error"><?= esc($errorMsg) ?></div><?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/observations/api/save') ?>">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $observation['id'] ?>"><?php endif; ?>
      <input type="hidden" name="mission_id" value="<?= (int) $selectedMissionId ?>">
      <input type="hidden" name="department_id" value="<?= (int) $deptId ?>">

      <div class="obs-grid-3">
        <div class="wiz-field">
          <label class="wiz-label">الإدارة محل المراجعة</label>
          <div class="obs-auto-field"><span class="val"><?= esc($deptName ?: '— اختر المهمة أولاً —') ?></span></div>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsTitle">عنوان الملاحظة <span class="wiz-req">*</span></label>
          <input id="obsTitle" name="title" type="text" class="wiz-input plain" placeholder="عنوان مختصر..." value="<?= esc($d['title']) ?>">
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsDate">التاريخ</label>
          <input id="obsDate" name="observation_date" type="date" class="wiz-input plain" value="<?= esc($d['observation_date']) ?>">
        </div>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-grid-2">
        <div class="wiz-field">
          <label class="wiz-label" for="obsObservation">الملاحظة <span class="wiz-req">*</span></label>
          <textarea id="obsObservation" name="observation_text" rows="4" class="wiz-textarea plain" placeholder="أدخل نص الملاحظة المكتشفة بوضوح..."><?= esc($d['observation_text']) ?></textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsStandard">المعيار أو النظام <span class="wiz-req">*</span></label>
          <textarea id="obsStandard" name="standard_text" rows="4" class="wiz-textarea plain" placeholder="المادة النظامية أو السياسة التي تمت مخالفتها..."><?= esc($d['standard_text']) ?></textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsReason">السبب <span class="wiz-req">*</span></label>
          <textarea id="obsReason" name="reason_text" rows="3" class="wiz-textarea plain" placeholder="الأسباب الجذرية لحدوث هذه الملاحظة..."><?= esc($d['reason_text']) ?></textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsImpact">الأثر <span class="wiz-req">*</span></label>
          <textarea id="obsImpact" name="impact_text" rows="3" class="wiz-textarea plain" placeholder="الأثر المالي أو التشغيلي المترتب..."><?= esc($d['impact_text']) ?></textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsRecommendations">التوصيات <span class="wiz-req">*</span></label>
          <textarea id="obsRecommendations" name="recommendations_text" rows="2" class="wiz-textarea plain" placeholder="الإجراءات التصحيحية المقترحة..."><?= esc($d['recommendations_text']) ?></textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label" for="obsRisk">الحالة (الخطر):</label>
          <input id="obsRisk" name="risk_severity" type="text" class="wiz-input plain" placeholder="اكتب حالة/خطورة الملاحظة..." value="<?= esc($d['risk_severity']) ?>">
        </div>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-attach-row">
        <div class="wiz-field" style="gap:8px;">
          <label class="wiz-label">تضاف للتقرير؟</label>
          <div class="obs-radio-group">
            <label class="obs-radio-label"><input type="radio" name="add_to_report" value="1" <?= $addToReport === '1' ? 'checked' : '' ?>> نعم</label>
            <label class="obs-radio-label"><input type="radio" name="add_to_report" value="0" <?= $addToReport === '0' ? 'checked' : '' ?>> لا</label>
          </div>
        </div>
      </div>

      <div class="obs-divider"></div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <a class="wiz-btn wiz-btn-outline" style="text-decoration:none;" href="<?= esc($backUrl) ?>">إلغاء</a>
        <button type="submit" class="wiz-btn wiz-btn-primary"><i data-lucide="check"></i> حفظ واعتماد</button>
      </div>
    </form>
  </div>
</div>
