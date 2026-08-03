/* ============================================================
   التقرير النهائي (Final Reports) — متصل بالـ API الحقيقي

   ملاحظة مهمة: عارض التقرير التفصيلي بـ 6 أقسام (خطاب/اتفاقية/مستندات/
   مخاطر/اجتماع/ملاحظات) بالتصميم الأصلي كان مبني بالكامل على بيانات HR_*
   وهمية ثابتة، ومافي endpoint حقيقي بالباك-إند يجمع كل هذي البيانات
   لمهمة معيّنة بمكان واحد. لذلك زر "عرض" هنا يفتح نفس شاشة "قائمة
   الاعتماد" الحقيقية (الحالة الفعلية لكل مرحلة) بدل العارض التفصيلي
   الوهمي، وزر اعتماد الرئيس التنفيذي معطّل حاليًا (مافيه endpoint لهذي
   الخطوة بالباك-إند بعد).
   ============================================================ */

let frReportsList = [];
let frLoading = false;

let frView = "list"; // list | create
let frActiveMissionId = "";

let frFiltersOpen = false;
let frFilterYear = "", frFilterStatus = "", frFilterDept = "", frFilterTargetDept = "";

let frCreateSelectedTask = "";
let frCurrentReport = null;
let frCurrentItems = [];
let frCurrentCompletion = {};

const frIsHrUser = () => isHrDept || isHrCoordinator;

function escHtmlFR(str) {
  return String(str == null ? "" : str)
    .replace(/&/g, "&amp;").replace(/"/g, "&quot;")
    .replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function rerenderFRContent() {
  const ca = document.getElementById("contentArea");
  ca.innerHTML = renderFinalReportsPage();
  bindFinalReportsEvents();
  lucide.createIcons();
}

async function initFinalReportsData() {
  frLoading = true;
  try {
    const res = await fetch(base + "/dashboard/reports/api/list");
    const data = await res.json();
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
function renderFRTable(reports) {
  const hrUser = frIsHrUser() || isPresident;
  const years = [...new Set(frReportsList.map(r => r.year))].sort().reverse();
  const depts = [...new Set(frReportsList.map(r => r.audit_dept_name))];
  const targetDepts = [...new Set(frReportsList.map(r => r.target_dept_name))];

  const filtered = reports.filter(r => {
    if (frFilterYear && String(r.year) !== frFilterYear) return false;
    if (frFilterStatus === "معتمد" && !frIsApproved(r.status)) return false;
    if (frFilterStatus === "تحت المراجعة" && frIsApproved(r.status)) return false;
    if (frFilterDept && r.audit_dept_name !== frFilterDept) return false;
    if (frFilterTargetDept && r.target_dept_name !== frFilterTargetDept) return false;
    return true;
  });

  const hasFilter = !!(frFilterYear || frFilterStatus || frFilterDept || frFilterTargetDept);
  const activeCount = [frFilterYear, frFilterStatus, frFilterDept, frFilterTargetDept].filter(Boolean).length;
  const COLS = ["رقم المهمة", "الإدارة", "الإدارة المستهدفة", "السنة", "التاريخ", "الحالة", ""];

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
      <select id="frFilterYear" class="wiz-select ${frFilterYear ? "filled" : ""}">
        <option value="">جميع السنوات</option>
        ${years.map(y => `<option value="${y}" ${frFilterYear === String(y) ? "selected" : ""}>${y}</option>`).join("")}
      </select>
      ${!hrUser ? `
      <select id="frFilterDept" class="wiz-select ${frFilterDept ? "filled" : ""}">
        <option value="">الإدارة</option>
        ${depts.map(d => `<option value="${escHtmlFR(d)}" ${frFilterDept === d ? "selected" : ""}>${escHtmlFR(d)}</option>`).join("")}
      </select>` : ""}
      <select id="frFilterTargetDept" class="wiz-select ${frFilterTargetDept ? "filled" : ""}">
        <option value="">الإدارة المستهدفة</option>
        ${targetDepts.map(d => `<option value="${escHtmlFR(d)}" ${frFilterTargetDept === d ? "selected" : ""}>${escHtmlFR(d)}</option>`).join("")}
      </select>
      ${!hrUser ? `
      <select id="frFilterStatus" class="wiz-select ${frFilterStatus ? "filled" : ""}">
        <option value="">كل الحالات</option>
        <option value="معتمد" ${frFilterStatus === "معتمد" ? "selected" : ""}>معتمد</option>
        <option value="تحت المراجعة" ${frFilterStatus === "تحت المراجعة" ? "selected" : ""}>تحت المراجعة</option>
      </select>` : ""}
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
                <td><span class="fr-taskid-pill" dir="ltr">${escHtmlFR(r.mission_code)}</span></td>
                <td style="font-size:12px;font-weight:600;color:#374151;">${escHtmlFR(r.audit_dept_name)}</td>
                <td style="font-size:12px;color:#6b7280;">${escHtmlFR(r.target_dept_name)}</td>
                <td style="font-size:12px;color:#6b7280;">${r.year}</td>
                <td style="font-size:12px;color:#6b7280;">${escHtmlFR((r.created_at || "").slice(0, 10))}</td>
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
    frFilterYear = ""; frFilterStatus = ""; frFilterDept = ""; frFilterTargetDept = "";
    rerenderFRContent();
  });
  ["frFilterYear", "frFilterDept", "frFilterTargetDept", "frFilterStatus"].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("change", e => {
      if (id === "frFilterYear") frFilterYear = e.target.value;
      if (id === "frFilterDept") { frFilterDept = e.target.value; frFilterTargetDept = ""; }
      if (id === "frFilterTargetDept") frFilterTargetDept = e.target.value;
      if (id === "frFilterStatus") frFilterStatus = e.target.value;
      rerenderFRContent();
    });
  });

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
    const res = await fetch(base + "/dashboard/reports/api/checklist?mission_id=" + missionId);
    const data = await res.json();
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
  const allChecked = frCurrentReport && frCurrentItems.length > 0 && frCurrentItems.every(it => Number(it.is_checked) === 1);
  const checkedCount = frCurrentItems.filter(it => Number(it.is_checked) === 1).length;

  return `
  <div class="flex flex-col gap-5">
    <div class="fr-topbar">
      <button class="fr-back-btn" id="frBackToListBtn"><i data-lucide="chevron-right"></i> التقارير النهائية</button>
      <div class="fr-topbar-sep"></div>
      <div><h2>إنشاء تقرير / متابعة الاعتماد</h2><p>Report Checklist</p></div>
    </div>

    <div class="fr-task-card">
      <div class="fr-task-card-head">
        <div class="fr-task-card-icon"><i data-lucide="file-text"></i></div>
        <div><h3>المهمة / الإدارة المرتبطة</h3><p>اختر المهمة التي سيُبنى عليها التقرير</p></div>
      </div>
      <div class="wiz-field">
        <label class="wiz-label">اختر المهمة / الإدارة المرتبطة <span class="wiz-req">*</span></label>
        <select id="frCreateTaskSelect" class="wiz-select ${frCreateSelectedTask ? "filled" : ""}" style="${!frCreateSelectedTask ? "border-color:#fde68a;" : ""}">
          <option value="">--- اختر المهمة ---</option>
          ${missionsForSelector.map(m => `<option value="${m.id}" ${String(frCreateSelectedTask) === String(m.id) ? "selected" : ""}>${escHtmlFR(m.mission_code)} — ${escHtmlFR(m.target_department_name || "")}</option>`).join("")}
        </select>
      </div>
    </div>

    ${frCreateSelectedTask ? `
    <div class="fr-phases-card">
      <div class="fr-phases-head">
        <i data-lucide="clipboard-list"></i><span class="t">مراحل الاعتماد</span>
        <span class="fr-phases-count">${checkedCount} / ${frCurrentItems.length}</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="fr-phases-table">
          <thead><tr><th>تفاصيل المهمة</th><th class="center">الحالة الفعلية</th><th class="center">اعتماد</th></tr></thead>
          <tbody>
            ${frCurrentItems.map(item => {
              const isChecked = Number(item.is_checked) === 1;
              const isDone = !!frCurrentCompletion[item.section_number];
              return `
              <tr style="background:${isChecked ? "#f0fdf4" : "#fff"};">
                <td>
                  <div style="display:flex;align-items:center;gap:12px;">
                    <span class="fr-phase-num ${isChecked ? "done" : ""}">${isChecked ? '<i data-lucide="check" style="width:13px;height:13px;"></i>' : item.section_number}</span>
                    <span class="fr-phase-name ${isChecked ? "done" : ""}">${escHtmlFR(item.section_title)}</span>
                  </div>
                </td>
                <td style="text-align:center;"><span class="fr-mini-pill" style="background:${isDone ? "#f0fdf4" : "#fef2f2"};color:${isDone ? "#166534" : "#b91c1c"};">${isDone ? "مكتملة" : "غير مكتملة"}</span></td>
                <td style="text-align:center;">
                  <input type="checkbox" class="fr-phase-check" data-fr-check="${item.section_number}" ${isChecked ? "checked" : ""} ${(!isDone || isAuditHead || isPresident) ? "disabled" : ""}>
                </td>
              </tr>`;
            }).join("")}
          </tbody>
        </table>
      </div>
      <div class="fr-phases-footer">
        ${!isAuditHead && !isPresident ? `
        <button class="fr-submit-btn" id="frSubmitReportBtn" ${!allChecked || frCurrentReport.status !== "draft" ? "disabled" : ""}>
          <i data-lucide="send"></i> ${frCurrentReport.status === "draft" ? "اعتماد التقرير وإرساله" : "تم الإرسال"}
        </button>` : `<span style="font-size:12px;color:#6b7280;">${frStatusLabel(frCurrentReport ? frCurrentReport.status : "draft")}</span>`}
      </div>
    </div>` : ""}
  </div>`;
}

function bindCreateReportEvents() {
  const backBtn = document.getElementById("frBackToListBtn");
  if (backBtn) backBtn.addEventListener("click", () => {
    frView = "list"; frCreateSelectedTask = ""; frCurrentReport = null;
    rerenderFRContent();
  });

  const taskSelect = document.getElementById("frCreateTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    frCreateSelectedTask = e.target.value;
    if (frCreateSelectedTask) await frLoadChecklist(frCreateSelectedTask);
    rerenderFRContent();
  });

  document.querySelectorAll("[data-fr-check]").forEach(cb => {
    cb.addEventListener("change", async () => {
      const section = parseInt(cb.dataset.frCheck, 10);
      const item = frCurrentItems.find(it => it.section_number === section);
      if (!item) return;
      const newVal = Number(item.is_checked) === 1 ? 0 : 1;
      try {
        await fetch(base + "/dashboard/reports/api/toggle-check", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ report_id: frCurrentReport.id, section_number: section, checked: !!newVal, [csrfName()]: csrfValue() }),
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
      const res = await fetch(base + "/dashboard/reports/api/finalize", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ report_id: frCurrentReport.id, [csrfName()]: csrfValue() }),
      });
      const data = await res.json();
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

function csrfName()  { return document.querySelector('meta[name="csrf-token-name"]').content; }
function csrfValue() { return document.querySelector('meta[name="csrf-token-value"]').content; }
