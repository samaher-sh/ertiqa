/* ============================================================
   التقرير النهائي (Final Reports) — متصل بالـ API الحقيقي

   ملاحظة: زر اعتماد الرئيس التنفيذي معطّل حاليًا (مافيه endpoint لهذي
   الخطوة بالباك-إند بعد).

   زر "عرض" بعمود "إجراء" بجدول مراحل الاعتماد يفتح معاينة القراءة-فقط
   للبيانات الفعلية المرتبطة بنفس المهمة المختارة (frCreateSelectedTask) —
   عبر GET /dashboard/reports/api/preview?mission_id=X&section=N، حيث N
   رقم الصف (1-6) نفسه المستخدم بجدول report_checklist_items.
   ============================================================ */

let frReportsList = [];
let frLoading = false;

let frView = "list"; // list | create
let frActiveMissionId = "";

let frFiltersOpen = false;
let frFilterYears = []; // اختيار أكثر من سنة (multi-select)
let frFilterStatus = "";
let frFilterDeptId = "", frFilterTargetDeptId = ""; // نفس الإدارات الرئيسية/الفرعية الحقيقية المستخدمة ببدء مهمة (WIZ_MAIN_DEPTS)
let frYearDdOpen = false, frDeptDdOpen = false, frTargetDdOpen = false, frStatusDdOpen = false;

let frCreateSelectedTask = "";
let frCurrentReport = null;
let frCurrentItems = [];
let frCurrentCompletion = {};
let frExpandedStep = null; // رقم المرحلة المفتوحة حاليًا بمدرّج "مراحل الاعتماد" (null = افتراضيًا أول مرحلة غير معتمدة)

let frPreviewOpen = false;
let frPreviewSection = null;
let frPreviewData = null;
let frPreviewLoading = false;

const frIsHrUser = () => isHrDept || isHrCoordinator;

function rerenderFRContent() {
  const ca = document.getElementById("contentArea");
  ca.innerHTML = renderFinalReportsPage();
  bindFinalReportsEvents();
  lucide.createIcons();
}

async function initFinalReportsData() {
  frLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/reports/api/list");
    frReportsList = data.reports || [];
  } catch (e) {
    frReportsList = [];
  }
  frLoading = false;
}

function frStatusLabel(status) {
  return status === "sent" ? "معتمد" : status === "pending_signatures" ? "تحت المراجعة" : "تحت الإعداد";
}
function frIsApproved(status) { return status === "sent"; }

/* ============================================================
   الحاوية العامة
   ============================================================ */
function renderFinalReportsPage() {
  if (frView === "create") return renderCreateReportView();

  const hrUser = frIsHrUser();
  const reportsToShow = isPresident
    ? frReportsList.filter(r => r.status === "pending_signatures")
    : hrUser
    ? frReportsList.filter(r => r.status === "sent")
    : frReportsList;

  return `<div class="flex flex-col gap-4">${renderFRTable(reportsToShow)}</div>`;
}
function bindFinalReportsEvents() {
  if (frView === "create") { bindCreateReportEvents(); return; }
  bindFRTableEvents();
}

/* ============================================================
   FinalReportsTable
   ============================================================ */
/* أقل شي أربع سنين بالفلتر: من السنة الحالية إلى 2030 (حتى لو ما فيه تقارير
   فعلية بهذي السنين بعد -- الفلتر يعرض المدى الكامل مو بس السنين الموجودة) */
function frYearRangeOptions() {
  const start = new Date().getFullYear();
  const end = Math.max(2030, start + 3);
  const out = [];
  for (let y = start; y <= end; y++) out.push(y);
  return out;
}

/* قائمة منسدلة مخصّصة (مو <select> الأصلية) عشان تكون حوافها منحنية زي باقي
   السيستم -- المتصفح ما يسمح بتنعيم حواف قائمة <select> الأصلية إطلاقًا */
function frRenderDropdown({ id, open, label, filled, panelHtml }) {
  return `
  <div class="fr-dd-wrap">
    <button type="button" class="fr-dd-trigger ${filled ? "filled" : ""}" id="${id}Trigger">
      <span>${escapeHtml(label)}</span>
      <i data-lucide="chevron-down"></i>
    </button>
    ${open ? `<div class="fr-dd-panel" id="${id}Panel">${panelHtml}</div>` : ""}
  </div>`;
}

function frBindDropdown(triggerId, toggleFn) {
  const btn = document.getElementById(triggerId);
  if (!btn) return;
  btn.addEventListener("click", e => {
    e.stopPropagation();
    toggleFn();
    rerenderFRContent();
  });
}

/* missions.audit_department_id ثابت دائمًا = المراجعة الداخلية، والإدارة الرئيسية
   المختارة ببدء مهمة (main_dept_id) ما تُحفظ إطلاقًا بالمهمة -- بس target_department_id
   الفرعي. فلحساب الإدارة الرئيسية الفعلية لتقرير معيّن، لازم نبحث عن أب
   target_department_id ضمن WIZ_SUBS_BY_PARENT (نفس شجرة الإدارات ببدء مهمة) */
function frTargetDeptParentId(targetDeptId) {
  for (const parentId in WIZ_SUBS_BY_PARENT) {
    if (WIZ_SUBS_BY_PARENT[parentId].some(d => String(d.id) === String(targetDeptId))) return parentId;
  }
  return null;
}

function renderFRTable(reports) {
  const hrUser = frIsHrUser() || isPresident;
  const years = frYearRangeOptions();
  const targetDepts = frFilterDeptId ? wizSubDepts(frFilterDeptId) : [];

  const filtered = reports.filter(r => {
    if (frFilterYears.length && !frFilterYears.includes(String(r.year))) return false;
    if (frFilterStatus === "معتمد" && !frIsApproved(r.status)) return false;
    if (frFilterStatus === "تحت المراجعة" && frIsApproved(r.status)) return false;
    // audit_department_id ثابت دائمًا = المراجعة الداخلية (لا علاقة له بالإدارة
    // الرئيسية المختارة ببدء مهمة -- تلك ما تُحفظ إطلاقًا بالمهمة، فقط target_department_id
    // الفرعي)، فنحدد الإدارة الرئيسية الفعلية بالبحث عن أب target_department_id
    if (frFilterDeptId && String(frTargetDeptParentId(r.target_department_id)) !== String(frFilterDeptId)) return false;
    if (frFilterTargetDeptId && String(r.target_department_id) !== String(frFilterTargetDeptId)) return false;
    return true;
  });

  const hasFilter = !!(frFilterYears.length || frFilterStatus || frFilterDeptId || frFilterTargetDeptId);
  const activeCount = (frFilterYears.length ? 1 : 0) + [frFilterStatus, frFilterDeptId, frFilterTargetDeptId].filter(Boolean).length;
  const COLS = ["رقم المهمة", "الإدارة", "الإدارة المستهدفة", "السنة", "التاريخ", "الحالة", ""];

  const yearLabel = frFilterYears.length === 0 ? "جميع السنوات"
    : frFilterYears.length === 1 ? frFilterYears[0]
    : frFilterYears.length + " سنوات مختارة";
  const selectedMainDept = WIZ_MAIN_DEPTS.find(d => String(d.id) === String(frFilterDeptId));
  const selectedTargetDept = targetDepts.find(d => String(d.id) === String(frFilterTargetDeptId));

  return `
  <div class="fr-header-card">
    <div class="fr-header-bar">
      <i class="main" data-lucide="file-text"></i>
      <div>
        <h2>${isPresident ? "التقارير التي تتطلب المراجعة" : "التقارير النهائية"}</h2>
        <p>${isPresident ? "تقارير تحت المراجعة تنتظر الاعتماد" : "Final Reports"}</p>
      </div>
      <span class="fr-count-badge">${frLoading ? "..." : filtered.length + " تقرير"}</span>
      <div class="fr-header-actions">
        <button class="fr-filters-icon-btn" id="frFiltersToggle" title="الفلاتر">
          <i data-lucide="filter"></i>
          ${activeCount > 0 ? `<span class="fr-filter-count">${activeCount}</span>` : ""}
        </button>
        ${!isAuditHead && !hrUser ? `<button class="fr-create-btn" id="frCreateBtn"><i data-lucide="plus"></i> إنشاء تقرير</button>` : ""}
        ${hrUser ? `<span class="fr-readonly-badge"><i data-lucide="lock" style="width:9px;height:9px;"></i> عرض فقط</span>` : ""}
      </div>
    </div>
  </div>

  <div class="fr-filters-acc ${frFiltersOpen ? "open" : "closed"}" ${frFiltersOpen ? "" : "hidden"}>
    ${hasFilter ? `<div style="display:flex;justify-content:flex-end;padding:6px 16px 0;"><button class="fr-filter-clear" id="frClearFilters"><i data-lucide="x" style="width:9px;height:9px;"></i> مسح الفلاتر</button></div>` : ""}
    ${frFiltersOpen ? `
    <div class="fr-filters-body">
      ${frRenderDropdown({
        id: "frYearDd", open: frYearDdOpen, label: yearLabel, filled: frFilterYears.length > 0,
        panelHtml: years.map(y => `
          <label class="fr-dd-check-row">
            <input type="checkbox" data-fr-year-cb="${y}" ${frFilterYears.includes(String(y)) ? "checked" : ""}>
            <span>${y}</span>
          </label>`).join(""),
      })}
      ${!hrUser ? frRenderDropdown({
        id: "frDeptDd", open: frDeptDdOpen, label: selectedMainDept ? selectedMainDept.name_ar : "الإدارة", filled: !!frFilterDeptId,
        panelHtml: `
          <div class="fr-dd-item ${!frFilterDeptId ? "active" : ""}" data-fr-dept-opt="">كل الإدارات</div>
          ${WIZ_MAIN_DEPTS.map(d => `<div class="fr-dd-item ${String(frFilterDeptId) === String(d.id) ? "active" : ""}" data-fr-dept-opt="${d.id}">${escapeHtml(d.name_ar)}</div>`).join("")}`,
      }) : ""}
      ${frRenderDropdown({
        id: "frTargetDd", open: frTargetDdOpen, label: selectedTargetDept ? selectedTargetDept.name_ar : "الإدارة المستهدفة", filled: !!frFilterTargetDeptId,
        panelHtml: !frFilterDeptId
          ? `<div class="fr-dd-empty">اختر الإدارة الرئيسية أولاً</div>`
          : `
          <div class="fr-dd-item ${!frFilterTargetDeptId ? "active" : ""}" data-fr-target-opt="">كل الإدارات المستهدفة</div>
          ${targetDepts.map(d => `<div class="fr-dd-item ${String(frFilterTargetDeptId) === String(d.id) ? "active" : ""}" data-fr-target-opt="${d.id}">${escapeHtml(d.name_ar)}</div>`).join("")}`,
      })}
      ${!hrUser ? frRenderDropdown({
        id: "frStatusDd", open: frStatusDdOpen, label: frFilterStatus || "كل الحالات", filled: !!frFilterStatus,
        panelHtml: `
          <div class="fr-dd-item ${!frFilterStatus ? "active" : ""}" data-fr-status-opt="">كل الحالات</div>
          <div class="fr-dd-item ${frFilterStatus === "معتمد" ? "active" : ""}" data-fr-status-opt="معتمد">معتمد</div>
          <div class="fr-dd-item ${frFilterStatus === "تحت المراجعة" ? "active" : ""}" data-fr-status-opt="تحت المراجعة">تحت المراجعة</div>`,
      }) : ""}
    </div>` : ""}
  </div>

  <div class="fr-table-card">
    <div style="overflow-x:auto;">
      <table class="fr-table">
        <thead><tr>${COLS.map(h => `<th>${h}</th>`).join("")}</tr></thead>
        <tbody>
          ${frLoading ? `<tr><td colspan="${COLS.length}" class="fr-empty-row">جارِ التحميل...</td></tr>` :
            filtered.length === 0 ? `<tr><td colspan="${COLS.length}" class="fr-empty-row">لا توجد تقارير مطابقة للفلاتر المحددة</td></tr>` :
            filtered.map((r, i) => {
              const approved = frIsApproved(r.status);
              const statusLabel = approved ? "معتمد" : (isPresident && r.status === "pending_signatures" ? "بانتظار الاعتماد" : frStatusLabel(r.status));
              return `
              <tr style="background:${i % 2 === 0 ? "#fff" : "#f5fafd"};">
                <td><span class="fr-taskid-pill" dir="ltr">${escapeHtml(r.mission_code)}</span></td>
                <td style="font-size:12px;font-weight:600;color:#374151;">${escapeHtml(r.audit_dept_name)}</td>
                <td style="font-size:12px;color:#6b7280;">${escapeHtml(r.target_dept_name)}</td>
                <td style="font-size:12px;color:#6b7280;">${r.year}</td>
                <td style="font-size:12px;color:#6b7280;">${escapeHtml((r.created_at || "").slice(0, 10))}</td>
                <td><span class="fr-status-pill" style="background:${approved ? "#f0fdf4" : "#fef9ec"};color:${approved ? "#1f5f7a" : "#b45309"};"><span class="dot" style="background:${approved ? "#3185b3" : "#f59e0b"};"></span>${statusLabel}</span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    ${isPresident
                      ? `<button class="fr-action-view-btn" data-fr-view="${r.mission_id}">عرض</button>`
                      : `<button class="fr-action-view-btn" data-fr-view="${r.mission_id}">عرض</button>`}
                  </div>
                </td>
              </tr>`;
            }).join("")}
        </tbody>
      </table>
    </div>
    ${isAuditHead ? `<div class="fr-audithead-note"><i data-lucide="check"></i><p>بصفتك رئيس إدارة المراجعة الداخلية، يمكنك متابعة حالة اعتماد التقارير النهائية من هذه الصفحة.</p></div>` : ""}
  </div>
  `;
}

function bindFRTableEvents() {
  const createBtn = document.getElementById("frCreateBtn");
  if (createBtn) createBtn.addEventListener("click", () => { frView = "create"; frCreateSelectedTask = ""; frCurrentReport = null; rerenderFRContent(); });

  const filtersToggle = document.getElementById("frFiltersToggle");
  if (filtersToggle) filtersToggle.addEventListener("click", () => { frFiltersOpen = !frFiltersOpen; rerenderFRContent(); });
  const clearBtn = document.getElementById("frClearFilters");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    frFilterYears = []; frFilterStatus = ""; frFilterDeptId = ""; frFilterTargetDeptId = "";
    rerenderFRContent();
  });

  frBindDropdown("frYearDdTrigger", () => { frYearDdOpen = !frYearDdOpen; frDeptDdOpen = false; frTargetDdOpen = false; frStatusDdOpen = false; });
  frBindDropdown("frDeptDdTrigger", () => { frDeptDdOpen = !frDeptDdOpen; frYearDdOpen = false; frTargetDdOpen = false; frStatusDdOpen = false; });
  frBindDropdown("frTargetDdTrigger", () => { frTargetDdOpen = !frTargetDdOpen; frYearDdOpen = false; frDeptDdOpen = false; frStatusDdOpen = false; });
  frBindDropdown("frStatusDdTrigger", () => { frStatusDdOpen = !frStatusDdOpen; frYearDdOpen = false; frDeptDdOpen = false; frTargetDdOpen = false; });

  // الضغط داخل أي لوحة قائمة منسدلة ما لازم يوصل لمستمع "إغلاق عند الضغط خارجها"
  // بالأسفل -- كل خيار يدير إغلاق قائمته بنفسه، وقائمة السنوات لازم تفضل مفتوحة
  // عشان تقدر تؤشر أكثر من سنة بجلسة فتح وحدة
  ["frYearDdPanel", "frDeptDdPanel", "frTargetDdPanel", "frStatusDdPanel"].forEach(id => {
    const panel = document.getElementById(id);
    if (panel) panel.addEventListener("click", e => e.stopPropagation());
  });

  document.querySelectorAll("[data-fr-year-cb]").forEach(cb => {
    cb.addEventListener("change", () => {
      const y = cb.dataset.frYearCb;
      frFilterYears = cb.checked ? [...frFilterYears, y] : frFilterYears.filter(v => v !== y);
      rerenderFRContent();
    });
  });
  document.querySelectorAll("[data-fr-dept-opt]").forEach(el => {
    el.addEventListener("click", () => {
      frFilterDeptId = el.dataset.frDeptOpt;
      frFilterTargetDeptId = ""; // الإدارة المستهدفة تابعة للإدارة الرئيسية -- تُصفَّر عند تغييرها
      frDeptDdOpen = false;
      rerenderFRContent();
    });
  });
  document.querySelectorAll("[data-fr-target-opt]").forEach(el => {
    el.addEventListener("click", () => {
      frFilterTargetDeptId = el.dataset.frTargetOpt;
      frTargetDdOpen = false;
      rerenderFRContent();
    });
  });
  document.querySelectorAll("[data-fr-status-opt]").forEach(el => {
    el.addEventListener("click", () => {
      frFilterStatus = el.dataset.frStatusOpt;
      frStatusDdOpen = false;
      rerenderFRContent();
    });
  });

  if (frYearDdOpen || frDeptDdOpen || frTargetDdOpen || frStatusDdOpen) {
    setTimeout(() => {
      document.addEventListener("click", function closeFrDd(e) {
        // مرحلة capture تسبق أي stopPropagation داخل اللوحة نفسها (زي قائمة
        // السنوات اللي تحتاج تفضل مفتوحة عند تأشير أكثر من سنة) -- فبدل الاعتماد
        // على stopPropagation نتحقق مباشرة إن الضغط ما صار جوّا أي لوحة/زر فتح
        if (e.target.closest(".fr-dd-wrap")) return;
        frYearDdOpen = false; frDeptDdOpen = false; frTargetDdOpen = false; frStatusDdOpen = false;
        rerenderFRContent();
        document.removeEventListener("click", closeFrDd, true);
      }, { once: true, capture: true });
    }, 0);
  }

  document.querySelectorAll("[data-fr-view]").forEach(btn => {
    btn.addEventListener("click", async () => {
      frView = "create";
      frCreateSelectedTask = btn.dataset.frView;
      await frLoadChecklist(frCreateSelectedTask);
      rerenderFRContent();
    });
  });
}

/* ============================================================
   إنشاء تقرير / متابعة الاعتماد — متصل بـ /dashboard/reports/api/*
   ============================================================ */
async function frLoadChecklist(missionId) {
  try {
    const data = await apiGet(base + "/dashboard/reports/api/checklist?mission_id=" + missionId);
    frCurrentReport = data.report;
    frCurrentItems = data.items || [];
    frCurrentCompletion = data.completion || {};
  } catch (e) {
    frCurrentReport = null;
    frCurrentItems = [];
    frCurrentCompletion = {};
  }
}

function renderCreateReportView() {
  return `
  <div class="flex flex-col gap-5">
    <div class="fr-topbar">
      <button class="fr-back-btn" id="frBackToListBtn"><i data-lucide="chevron-right"></i> التقارير النهائية</button>
      <div class="fr-topbar-sep"></div>
      <div><h2>إنشاء تقرير / متابعة الاعتماد</h2><p>Report Checklist</p></div>
    </div>

    ${renderLinkedTaskSelector(frCreateSelectedTask, "frCreateTaskSelect")}

    ${frCreateSelectedTask ? renderApprovalStepper() : ""}
    ${frRenderPreviewModal()}
  </div>`;
}

/* ============================================================
   مراحل الاعتماد — Vertical Stepper/Timeline
   كل مرحلة: معتمدة (done، أخضر) / الحالية (active، لون المنصة، مفتوحة تلقائيًا) /
   قادمة (locked، رمادية مقفولة لحد ما تكتمل اللي قبلها). المشاهدون (رئيس المراجعة/
   الرئيس التنفيذي/الإدارة الخاضعة للمراجعة) ما فيه عندهم قفل -- كل مرحلة قابلة
   للعرض بأي وقت لأنهم بس يستعرضون، مو يعتمدون بالترتيب
   ============================================================ */
function frStepState(item, items, readOnlyViewer) {
  if (Number(item.is_checked) === 1) return "done";
  if (readOnlyViewer) return "active";
  const firstUnchecked = items.find(it => Number(it.is_checked) !== 1);
  return firstUnchecked && firstUnchecked.section_number === item.section_number ? "active" : "locked";
}

function frEffectiveExpandedStep(items) {
  if (frExpandedStep !== null && items.some(it => it.section_number === frExpandedStep)) return frExpandedStep;
  const firstUnchecked = items.find(it => Number(it.is_checked) !== 1);
  if (firstUnchecked) return firstUnchecked.section_number;
  return items.length ? items[items.length - 1].section_number : null;
}

function renderApprovalStepper() {
  const readOnlyViewer = isAuditHead || isPresident || frIsHrUser();
  const items = frCurrentItems;
  const checkedCount = items.filter(it => Number(it.is_checked) === 1).length;
  const total = items.length;
  const pct = total ? Math.round((checkedCount / total) * 100) : 0;
  const expandedNum = frEffectiveExpandedStep(items);

  return `
  <div class="fr-vstep-card">
    <div class="fr-vstep-head">
      <div class="fr-vstep-head-top">
        <i data-lucide="clipboard-list"></i><span class="t">مراحل الاعتماد</span>
        <span class="fr-phases-count" dir="ltr">${checkedCount} / ${total}</span>
      </div>
      <div class="fr-vstep-progress"><div class="fr-vstep-progress-fill" style="width:${pct}%;"></div></div>
    </div>
    <div class="fr-vstep-list">
      ${items.map((item, idx) => {
        const state = frStepState(item, items, readOnlyViewer);
        const isExpanded = expandedNum === item.section_number;
        const isDone = !!frCurrentCompletion[item.section_number];
        const isLast = idx === items.length - 1;
        const checkDisabled = !isDone || readOnlyViewer;
        return `
        <div class="fr-vstep ${state} ${isExpanded ? "expanded" : ""}">
          <div class="fr-vstep-rail">
            <div class="fr-vstep-circle">${state === "done" ? '<i data-lucide="check"></i>' : item.section_number}</div>
            ${!isLast ? `<div class="fr-vstep-line"></div>` : ""}
          </div>
          <div class="fr-vstep-body">
            <button type="button" class="fr-vstep-header" data-fr-step-toggle="${item.section_number}" ${state === "locked" ? "disabled" : ""}>
              <span class="fr-vstep-title">${escapeHtml(item.section_title)}</span>
              <span class="fr-vstep-status ${state}">${state === "done" ? "معتمدة" : state === "active" ? "الحالية" : "قادمة"}</span>
              <i data-lucide="chevron-down" class="fr-vstep-chev"></i>
            </button>
            ${isExpanded ? `
            <div class="fr-vstep-detail">
              <button type="button" class="fr-vstep-view-btn" data-fr-preview="${item.section_number}"><i data-lucide="eye"></i> عرض التفاصيل</button>
              <label class="fr-round-check-wrap ${checkDisabled ? "disabled" : ""}">
                <input type="checkbox" data-fr-check="${item.section_number}" ${state === "done" ? "checked" : ""} ${checkDisabled ? "disabled" : ""}>
                <span class="fr-round-check"><i data-lucide="check"></i></span>
                <span class="fr-round-check-label">${state === "done" ? "تم اعتماد هذه المرحلة" : "تأشير باعتماد هذه المرحلة"}</span>
              </label>
              ${!isDone ? `<p class="fr-vstep-hint">لم تكتمل بيانات هذه المرحلة بعد</p>` : ""}
            </div>` : ""}
          </div>
        </div>`;
      }).join("")}
    </div>
    <div class="fr-phases-footer">
      ${readOnlyViewer ? `<span style="font-size:12px;color:#6b7280;">${frStatusLabel(frCurrentReport ? frCurrentReport.status : "draft")}</span>` : frRenderStepperActionBtn(items, expandedNum)}
    </div>
  </div>`;
}

function frRenderStepperActionBtn(items, expandedNum) {
  const expandedItem = items.find(it => it.section_number === expandedNum);
  if (!expandedItem) return "";
  const isLastStep = items.length > 0 && expandedNum === items[items.length - 1].section_number;
  const expandedChecked = Number(expandedItem.is_checked) === 1;

  if (isLastStep) {
    const allChecked = items.length > 0 && items.every(it => Number(it.is_checked) === 1);
    return `
    <button class="fr-submit-btn" id="frSubmitReportBtn" ${!allChecked || !frCurrentReport || frCurrentReport.status !== "draft" ? "disabled" : ""}>
      <i data-lucide="send"></i> ${frCurrentReport && frCurrentReport.status === "draft" ? "اعتماد التقرير وإرساله" : "تم الإرسال"}
    </button>`;
  }
  return `
  <button class="fr-next-btn" id="frNextStepBtn" ${!expandedChecked ? "disabled" : ""}>
    التالي <i data-lucide="chevron-left"></i>
  </button>`;
}

/* ============================================================
   معاينة مرحلة (Modal) — بيانات حقيقية حسب رقم الصف المختار
   ============================================================ */
/* ورقة منزلقة من الأسفل (Bottom Sheet) بدل نافذة منتصف الشاشة -- نفس محتوى
   frRenderPreviewBody() القراءة-فقط الفعلي، بس بحاوية/حركة مختلفة */
function frRenderPreviewModal() {
  if (!frPreviewOpen) return "";
  const item = frCurrentItems.find(it => it.section_number === frPreviewSection);
  return `
  <div class="fr-sheet-overlay" id="frPreviewOverlay">
    <div class="fr-sheet-box">
      <div class="fr-sheet-handle"></div>
      <div class="fr-sheet-head">
        <span>${escapeHtml(item ? item.section_title : "")}</span>
        <button type="button" class="fr-sheet-close" id="frPreviewCloseBtn"><i data-lucide="x"></i></button>
      </div>
      <div class="fr-sheet-body">
        ${frPreviewLoading ? `<p class="fr-preview-empty">جارِ التحميل...</p>` : frRenderPreviewBody()}
      </div>
    </div>
  </div>`;
}

function frRenderPreviewBody() {
  const d = frPreviewData;
  if (!d) return `<p class="fr-preview-empty">تعذّر تحميل البيانات</p>`;

  if (frPreviewSection === 1) {
    const m = d.mission || {};
    const fields = [
      ["رقم المهمة", m.mission_code],
      ["الإدارة المستهدفة", m.target_department_name],
      ["السنة", m.year],
      ["اسم المراجع الرئيسي", m.reviewer_name],
      ["البريد الإلكتروني", m.reviewer_email],
      ["رقم الجوال", m.reviewer_phone],
      ["مدير الإدارة", m.director_name],
    ];
    return `
    <div class="fr-preview-grid">
      ${fields.map(([lbl, val]) => `<div class="fr-preview-field"><span class="lbl">${escapeHtml(lbl)}</span><span class="val">${escapeHtml(val || "—")}</span></div>`).join("")}
      <div class="fr-preview-field span2"><span class="lbl">المراد مناقشته بالمراجعة</span><span class="val">${escapeHtml(m.procedure_note || "—")}</span></div>
    </div>`;
  }

  if (frPreviewSection === 2) {
    const responses = d.responses || [];
    if (responses.length === 0) return `<p class="fr-preview-empty">لا توجد ردود على اتفاقية مستوى الخدمة بعد</p>`;
    const groups = {};
    responses.forEach(r => { (groups[r.section_title] = groups[r.section_title] || []).push(r); });
    return Object.keys(groups).map(sec => `
      <div class="fr-preview-sla-group">
        <h4>${escapeHtml(sec)}</h4>
        <ul>
          ${groups[sec].map(r => `
            <li>
              <span class="txt">${escapeHtml(r.row_text)}</span>
              <span class="fr-mini-pill" style="background:${Number(r.agree) ? "#f0fdf4" : Number(r.disagree) ? "#fef2f2" : "#f3f4f6"};color:${Number(r.agree) ? "#166534" : Number(r.disagree) ? "#b91c1c" : "#6b7280"};">${Number(r.agree) ? "موافق" : Number(r.disagree) ? "غير موافق" : "لم يُرد بعد"}</span>
              ${r.note ? `<span class="note">${escapeHtml(r.note)}</span>` : ""}
            </li>
          `).join("")}
        </ul>
      </div>
    `).join("");
  }

  if (frPreviewSection === 3) {
    const docs = d.documents || [];
    if (docs.length === 0) return `<p class="fr-preview-empty">لا توجد مستندات مطلوبة مسجّلة</p>`;
    return `
    <table class="fr-preview-table">
      <thead><tr><th>المستند</th><th>الحالة</th><th>ملاحظة</th></tr></thead>
      <tbody>
        ${docs.map(doc => `
          <tr>
            <td>${escapeHtml(doc.doc_name)}</td>
            <td>${doc.exists_flag === null ? "لم يُرد بعد" : Number(doc.exists_flag) ? "متوفر" : "غير متوفر"}</td>
            <td>${escapeHtml(doc.response_note || "—")}</td>
          </tr>
        `).join("")}
      </tbody>
    </table>`;
  }

  if (frPreviewSection === 4) {
    const items = d.items || [];
    if (items.length === 0) return `<p class="fr-preview-empty">لا توجد صفوف بمصفوفة المخاطر بعد</p>`;
    return `
    <table class="fr-preview-table">
      <thead><tr><th>المخاطر</th><th>التقييم</th><th>الضوابط</th><th>نوع النشاط</th></tr></thead>
      <tbody>
        ${items.map(r => `
          <tr>
            <td>${escapeHtml(r.risk)}</td>
            <td>${escapeHtml(r.risk_rating || "—")}</td>
            <td>${escapeHtml(r.controls)}</td>
            <td>${escapeHtml(r.activity_type)}</td>
          </tr>
        `).join("")}
      </tbody>
    </table>`;
  }

  if (frPreviewSection === 5) {
    const m = d.meeting;
    if (!m) return `<p class="fr-preview-empty">لم يُحدَّد اجتماع لهذه المهمة بعد</p>`;
    const attendees = d.attendees || [];
    const points = d.points || [];
    return `
    <div class="fr-preview-grid">
      <div class="fr-preview-field"><span class="lbl">العنوان</span><span class="val">${escapeHtml(m.title || "—")}</span></div>
      <div class="fr-preview-field"><span class="lbl">التاريخ</span><span class="val" dir="ltr">${escapeHtml(m.meeting_date || "—")}</span></div>
      <div class="fr-preview-field"><span class="lbl">الوقت</span><span class="val" dir="ltr">${escapeHtml(m.meeting_time || "—")}</span></div>
      <div class="fr-preview-field"><span class="lbl">المكان</span><span class="val">${escapeHtml(m.location || "—")}</span></div>
      <div class="fr-preview-field span2"><span class="lbl">الهدف</span><span class="val">${escapeHtml(m.objective || "—")}</span></div>
    </div>
    <div class="fr-preview-sub">
      <h4>الحضور (${attendees.length})</h4>
      ${attendees.length === 0 ? `<p class="fr-preview-empty">لا يوجد</p>` : `<ul>${attendees.map(a => `<li>${escapeHtml(a.external_name || "—")}${a.attendee_dept ? " — " + escapeHtml(a.attendee_dept) : ""}${a.attendee_position ? " — " + escapeHtml(a.attendee_position) : ""}</li>`).join("")}</ul>`}
    </div>
    <div class="fr-preview-sub">
      <h4>نقاط ملخص الاجتماع (${points.length})</h4>
      ${points.length === 0 ? `<p class="fr-preview-empty">لا يوجد</p>` : `<ul>${points.map(p => `<li>${escapeHtml(p.point_text || "—")}</li>`).join("")}</ul>`}
    </div>`;
  }

  if (frPreviewSection === 6) {
    const obs = d.observations || [];
    if (obs.length === 0) return `<p class="fr-preview-empty">لا توجد ملاحظات مسجّلة لهذه المهمة بعد</p>`;
    return `
    <table class="fr-preview-table">
      <thead><tr><th>العنوان</th><th>الإدارة</th><th>الخطورة</th><th>الحالة</th></tr></thead>
      <tbody>
        ${obs.map(o => `
          <tr>
            <td>${escapeHtml(o.title || "—")}</td>
            <td>${escapeHtml(o.department_name || "—")}</td>
            <td>${escapeHtml(o.risk_severity || "—")}</td>
            <td>${escapeHtml(o.status || "—")}</td>
          </tr>
        `).join("")}
      </tbody>
    </table>`;
  }

  return "";
}

async function frOpenPreview(section) {
  frPreviewOpen = true;
  frPreviewSection = section;
  frPreviewData = null;
  frPreviewLoading = true;
  rerenderFRContent();
  try {
    frPreviewData = await apiGet(base + "/dashboard/reports/api/preview?mission_id=" + frCreateSelectedTask + "&section=" + section);
  } catch (e) {
    frPreviewData = null;
  }
  frPreviewLoading = false;
  rerenderFRContent();
}

function frClosePreview() {
  frPreviewOpen = false;
  frPreviewSection = null;
  frPreviewData = null;
  rerenderFRContent();
}

function bindCreateReportEvents() {
  const backBtn = document.getElementById("frBackToListBtn");
  if (backBtn) backBtn.addEventListener("click", () => {
    frView = "list"; frCreateSelectedTask = ""; frCurrentReport = null;
    frPreviewOpen = false; frPreviewSection = null; frPreviewData = null; frExpandedStep = null;
    rerenderFRContent();
  });

  const taskSelect = document.getElementById("frCreateTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    frCreateSelectedTask = e.target.value;
    frPreviewOpen = false; frPreviewSection = null; frPreviewData = null; frExpandedStep = null;
    if (frCreateSelectedTask) await frLoadChecklist(frCreateSelectedTask);
    rerenderFRContent();
  });

  document.querySelectorAll("[data-fr-step-toggle]").forEach(btn => {
    if (btn.disabled) return;
    btn.addEventListener("click", () => {
      const num = parseInt(btn.dataset.frStepToggle, 10);
      const current = frEffectiveExpandedStep(frCurrentItems);
      frExpandedStep = current === num ? null : num;
      rerenderFRContent();
    });
  });

  const nextStepBtn = document.getElementById("frNextStepBtn");
  if (nextStepBtn) nextStepBtn.addEventListener("click", () => {
    const items = frCurrentItems;
    const current = frEffectiveExpandedStep(items);
    const idx = items.findIndex(it => it.section_number === current);
    if (idx > -1 && idx < items.length - 1) {
      frExpandedStep = items[idx + 1].section_number;
      rerenderFRContent();
    }
  });

  document.querySelectorAll("[data-fr-preview]").forEach(btn => {
    btn.addEventListener("click", () => {
      frOpenPreview(parseInt(btn.dataset.frPreview, 10));
    });
  });

  const previewOverlay = document.getElementById("frPreviewOverlay");
  if (previewOverlay) {
    previewOverlay.addEventListener("click", (e) => {
      if (e.target === previewOverlay) frClosePreview();
    });
  }
  const previewCloseBtn = document.getElementById("frPreviewCloseBtn");
  if (previewCloseBtn) previewCloseBtn.addEventListener("click", frClosePreview);

  document.querySelectorAll("[data-fr-check]").forEach(cb => {
    cb.addEventListener("change", async () => {
      const section = parseInt(cb.dataset.frCheck, 10);
      const item = frCurrentItems.find(it => it.section_number === section);
      if (!item) return;
      const newVal = Number(item.is_checked) === 1 ? 0 : 1;
      try {
        await apiPost(base + "/dashboard/reports/api/toggle-check", {
          report_id: frCurrentReport.id, section_number: section, checked: !!newVal,
        });
        item.is_checked = newVal;
      } catch (e) {
        showToast("تعذّر تحديث حالة المرحلة", "error");
      }
      rerenderFRContent();
    });
  });

  const submitBtn = document.getElementById("frSubmitReportBtn");
  if (submitBtn) submitBtn.addEventListener("click", async () => {
    submitBtn.disabled = true;
    try {
      const data = await apiPost(base + "/dashboard/reports/api/finalize", { report_id: frCurrentReport.id });
      if (data.success) {
        showToast("تم اعتماد التقرير وإرساله للمراجعة", "success");
        await frLoadChecklist(frCreateSelectedTask);
        await initFinalReportsData();
      } else {
        showToast(data.message || "تعذّر الاعتماد", "error");
      }
    } catch (e) {
      showToast("تعذّر الاتصال بالخادم", "error");
    }
    rerenderFRContent();
  });
}

