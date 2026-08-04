/* ============================================================
   سجل الملاحظات الرقابية (Observations Log) — متصل بالـ API الحقيقي
   ملاحظة: "الملاحظات الفرعية" (sub-observations) مو موجودة كجدول منفصل بقاعدة
   البيانات — كل ملاحظة فرعية تُحفظ كصف مستقل إضافي بنفس جدول audit_notes
   لنفس المهمة عند الحفظ.
   المرفقات وتوقيعات المراجع/رئيس الفريق: مافي endpoint حقيقي لهم بعد
   (خلافًا لمرفقات الاجتماعات)، فتبقى بهذي الصفحة عرض/تتبع محلي فقط بدون رفع فعلي.
   ============================================================ */

/* ---------- الحالة العامة ---------- */
let obsList = [];
let obsSelectedTaskId = "";
let obsView = "list"; // list | new | edit | view
let obsDraft = null;
let obsViewTarget = null;
let obsLoading = false;

let obsSearchQuery = "";
let obsFilterDept = "";
let obsFilterRisk = "";
let obsFilterStatus = "";
let obsShowAdvanced = false;
let obsAdvDateFrom = "";
let obsAdvDateTo = "";
let obsOpenMenuId = null;

let obsSubItems = [];
let obsSubExpanded = null;

const isObsHrUser = () => isHrDept || isHrCoordinator;
const obsIsReadOnly = () => isObsHrUser() || isAuditHead;

/* الإدارة محل المراجعة تُشتق دائمًا من المهمة المختارة حاليًا (target_department_id
   الحقيقي بالمهمة) — بدون اختيار يدوي منفصل بالنموذج */
function obsMissionDept() {
  const m = missionsForSelector.find(x => String(x.id) === String(obsSelectedTaskId));
  return m ? { deptId: m.target_department_id, dept: m.target_department_name || "" } : { deptId: "", dept: "" };
}


function rerenderObsContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  ca.innerHTML = renderObservationsPage();
  bindObservationsEvents();
  lucide.createIcons();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) {
      el.focus();
      if (selStart !== null && el.setSelectionRange) {
        try { el.setSelectionRange(selStart, selEnd); } catch (e) {}
      }
    }
  }
  if (ca) ca.scrollTop = scrollTop;
}

async function obsLoadList(missionId) {
  obsLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/observations/api/list?mission_id=" + missionId);
    obsList = (data.items || []).map(o => ({
      id: o.id, ref: o.ref_code, deptId: o.department_id, dept: o.department_name || "",
      title: o.title || "", date: o.observation_date, risk: o.risk_severity, status: o.status,
      observation: o.observation_text || "", standard: o.standard_text || "", reason: o.reason_text || "",
      impact: o.impact_text || "", recommendations: o.recommendations_text || "",
      addToReport: o.add_to_report === null ? null : !!Number(o.add_to_report),
    }));
  } catch (e) {
    obsList = [];
  }
  obsLoading = false;
}

/* تصدير PDF لملاحظة واحدة بعينها عبر نافذة طباعة (لا يوجد endpoint حقيقي بالباك-إند
   لتوليد PDF مباشرة، فنستخدم نافذة طباعة كما بباقي الصفحات) — يستخدم بيانات نفس
   الملاحظة المفتوحة بالنموذج فقط، وليس قائمة الملاحظات كاملة */
function exportObservationToPDF(obs) {
  const printWindow = window.open("", "_blank");
  if (!printWindow) { showToast("يرجى السماح بالنوافذ المنبثقة للتصدير", "error"); return; }

  const today = new Date().toLocaleDateString("ar-SA");
  const refLabel = obs.ref || "سيُحدَّد بعد الحفظ";

  const fields = [
    ["عنوان الملاحظة", obs.title],
    ["الإدارة محل المراجعة", obs.dept],
    ["تاريخ المراجعة", obs.date],
    ["الحالة (الخطر)", obs.risk],
    ["الملاحظة", obs.observation],
    ["المعيار أو النظام", obs.standard],
    ["السبب", obs.reason],
    ["الأثر", obs.impact],
    ["التوصيات", obs.recommendations],
  ];

  const fieldsHTML = fields.map(([label, value]) => `
    <tr>
      <td style="padding: 12px; border: 1px solid #d8e6eb; font-weight: bold; width: 30%; color: #6b8c95;">${escapeHtml(label)}</td>
      <td style="padding: 12px; border: 1px solid #d8e6eb; color: #152c33;">${escapeHtml(value || "—")}</td>
    </tr>
  `).join("");

  printWindow.document.write(`
    <html dir="rtl">
      <head>
        <title>ملاحظة رقابية - ${escapeHtml(refLabel)}</title>
        <style>
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #152c33; line-height: 1.6; }
          h1 { color: #3185b3; border-bottom: 2px solid #3185b3; padding-bottom: 15px; margin-bottom: 30px; font-size: 24px;}
          .header-info { display: flex; justify-content: space-between; margin-bottom: 40px; background: #f8fbfd; padding: 20px; border-radius: 8px; border: 1px solid #d8e6eb; }
          .header-info div { display: flex; flex-direction: column; gap: 5px; }
          .label { font-size: 12px; color: #6b8c95; font-weight: bold; }
          .value { font-size: 16px; font-weight: bold; color: #152c33; }
          table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          .footer { margin-top: 60px; text-align: center; font-size: 12px; color: #6b8c95; border-top: 1px solid #d8e6eb; padding-top: 20px; }
          @media print {
            body { padding: 0; }
            button { display: none; }
          }
        </style>
      </head>
      <body>
        <h1>ملاحظة رقابية</h1>

        <div class="header-info">
          <div>
            <span class="label">المرجع</span>
            <span class="value" dir="ltr">${escapeHtml(refLabel)}</span>
          </div>
          <div>
            <span class="label">تاريخ التصدير</span>
            <span class="value">${today}</span>
          </div>
        </div>

        <table>${fieldsHTML}</table>

        <div class="footer">
          تم إنشاء هذا المستند تلقائياً من نظام ارتقاء © ${new Date().getFullYear()}
        </div>
        <script>
          window.onload = () => {
            window.print();
            setTimeout(() => window.close(), 500);
          }
        </script>
      </body>
    </html>
  `);
  printWindow.document.close();
}

/* ============================================================
   شريط اختيار المهمة المرتبطة (LinkedTaskSelector) — مشترك مع باقي الصفحات
   ============================================================ */
function renderLinkedTaskSelector(value, selectId, list) {
  const missions = list || missionsForSelector;
  const selected = missions.find(m => String(m.id) === String(value));
  return `
  <div class="obs-linked-card" style="border-color:${value ? "var(--pb)" : "#fbbf24"};">
    <div class="obs-linked-band" style="background:${value ? "var(--p)" : "#fffbeb"};border-bottom-color:${value ? "var(--pb)" : "#fde68a"};">
      <i data-lucide="clipboard-list" style="color:${value ? "#fff" : "#b45309"};"></i>
      <p class="obs-linked-title" style="color:${value ? "#fff" : "#92400e"};">المهمة / الإدارة المرتبطة</p>
      ${!value ? '<span class="obs-linked-badge-req">مطلوب</span>' : ""}
      ${value && selected ? `<span class="obs-linked-badge-sel" dir="ltr">${escapeHtml(selected.mission_code)}</span>` : ""}
    </div>
    <div class="obs-linked-body">
      <label class="wiz-label">اختر المهمة / الإدارة المرتبطة <span class="wiz-req">*</span></label>
      <select id="${selectId}" class="wiz-select ${value ? "filled" : ""}" style="${!value ? "border-color:#fcd34d;background:#fffbeb;" : ""}">
        <option value="">— اختر المهمة المرتبطة —</option>
        ${missions.map(m => `<option value="${m.id}" ${String(value) === String(m.id) ? "selected" : ""}>${escapeHtml(m.mission_code)} — ${escapeHtml(m.target_department_name || "")} (${m.year})</option>`).join("")}
      </select>
      ${!value ? '<p class="wiz-error-text" style="color:#b45309;">يرجى تحديد المهمة المرتبطة قبل تعبئة النموذج</p>' : ""}
    </div>
  </div>`;
}

/* ============================================================
   الحاوية العامة
   ============================================================ */
function renderObservationsPage() {
  if (obsView === "view" && obsViewTarget) return renderObsViewMode();
  if ((obsView === "new" || obsView === "edit") && obsDraft) return renderObsFormMode();
  return renderObsListMode();
}

function bindObservationsEvents() {
  if (obsView === "view" && obsViewTarget) { bindObsViewEvents(); return; }
  if ((obsView === "new" || obsView === "edit") && obsDraft) { bindObsFormEvents(); return; }
  bindObsListEvents();
}

/* ============================================================
   وضع القائمة (List)
   ============================================================ */
function obsFullyFiltered() {
  return obsList.filter(o => {
    if (obsSearchQuery && !o.title.includes(obsSearchQuery) && !o.dept.includes(obsSearchQuery) && !o.ref.includes(obsSearchQuery)) return false;
    if (obsFilterDept && o.dept !== obsFilterDept) return false;
    if (obsFilterRisk && o.risk !== obsFilterRisk) return false;
    if (obsFilterStatus && o.status !== obsFilterStatus) return false;
    if (obsAdvDateFrom && o.date < obsAdvDateFrom) return false;
    if (obsAdvDateTo && o.date > obsAdvDateTo) return false;
    return true;
  });
}
function obsHasFilters() {
  return !!(obsSearchQuery || obsFilterDept || obsFilterRisk || obsFilterStatus || obsAdvDateFrom || obsAdvDateTo);
}

function renderObsListMode() {
  const filteredObs = obsFullyFiltered();
  const readOnly = obsIsReadOnly();
  const locked = !obsSelectedTaskId;
  const depts = [...new Set(obsList.map(o => o.dept).filter(Boolean))].sort();

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(obsSelectedTaskId, "obsTaskSelect")}

    <div class="obs-disabled-wrap ${locked ? "locked" : ""}" style="display:flex;flex-direction:column;gap:16px;">
      <div class="obs-list-card">

        <div class="obs-list-header">
          <div class="obs-list-header-left">
            <i data-lucide="book-open"></i>
            <span class="obs-list-title">ملاحظات</span>
            ${obsHasFilters() ? '<span class="obs-filters-badge">فلاتر نشطة</span>' : ""}
          </div>
          <div class="obs-header-actions">
            ${!readOnly ? `<button class="obs-btn-add" id="obsNewBtn"><i data-lucide="plus"></i> إضافة ملاحظة</button>` : `
              <span class="obs-readonly-badge"><i data-lucide="lock"></i> ${isObsHrUser() ? "ملاحظات إدارتك — عرض فقط" : "عرض فقط"}</span>`}
            <button class="obs-btn-pdf" id="obsExportBtn"><i data-lucide="file-text"></i> تصدير PDF</button>
          </div>
        </div>

        ${isAuditMember ? "" : `
        <div class="obs-filters-bar">
          <div class="obs-filter-field grow-2">
            <span class="obs-filter-label">بحث</span>
            <div class="obs-search-wrap">
              <i data-lucide="search"></i>
              <input id="obsSearchInput" type="text" placeholder="بحث بعنوان الملاحظة، الإدارة، أو المرجع..." value="${escapeHtml(obsSearchQuery)}">
            </div>
          </div>
          <div class="obs-filter-field">
            <span class="obs-filter-label">الإدارة</span>
            <select id="obsFilterDept" class="wiz-select ${obsFilterDept ? "filled" : ""}">
              <option value="">كل الإدارات</option>
              ${depts.map(d => `<option value="${escapeHtml(d)}" ${obsFilterDept === d ? "selected" : ""}>${escapeHtml(d)}</option>`).join("")}
            </select>
          </div>
          <div class="obs-filter-field">
            <span class="obs-filter-label">مستوى الخطر</span>
            <div class="obs-risk-toggle">
              ${["عالي", "متوسط", "منخفض"].map(r => `
                <button class="obs-risk-btn ${obsFilterRisk === r ? "sel-" + r : ""}" data-risk-filter="${r}">${r}</button>
              `).join("")}
            </div>
          </div>
          <div class="obs-filter-field">
            <span class="obs-filter-label">الحالة</span>
            <select id="obsFilterStatus" class="wiz-select ${obsFilterStatus ? "filled" : ""}">
              <option value="">كل الحالات</option>
              ${["بانتظار الرد", "قيد المعالجة", "مغلقة"].map(s => `<option value="${s}" ${obsFilterStatus === s ? "selected" : ""}>${s}</option>`).join("")}
            </select>
          </div>
          <button class="obs-adv-toggle-btn" id="obsAdvToggle"><i data-lucide="${obsShowAdvanced ? "chevron-up" : "sliders-horizontal"}"></i> متقدم</button>
          ${obsHasFilters() ? `<button class="obs-clear-btn" id="obsClearFilters"><i data-lucide="x"></i> مسح الفلاتر</button>` : ""}

          ${obsShowAdvanced ? `
          <div class="obs-adv-row">
            <div class="obs-filter-field">
              <span class="obs-filter-label">من تاريخ</span>
              <input id="obsAdvFrom" type="date" class="wiz-input" value="${obsAdvDateFrom}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
            </div>
            <div class="obs-filter-field">
              <span class="obs-filter-label">إلى تاريخ</span>
              <input id="obsAdvTo" type="date" class="wiz-input" value="${obsAdvDateTo}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
            </div>
          </div>` : ""}
        </div>` }

        ${obsLoading ? `<div class="obs-empty"><p class="main">جارِ التحميل...</p></div>` :
          filteredObs.length === 0 ? `
          <div class="obs-empty">
            <i data-lucide="alert-circle"></i>
            <p class="main">${obsList.length === 0 ? "لا توجد ملاحظات مسجلة لهذه المهمة" : "لا توجد ملاحظات مطابقة للتصفية"}</p>
            <p class="hint">${obsList.length === 0 ? "ابدأ بإضافة ملاحظة جديدة" : "جرّب تغيير معايير البحث"}</p>
          </div>
        ` : `
          <div class="obs-table-wrap">
            <table class="obs-table">
              <thead><tr>
                <th>موضوع الملاحظة</th>
                <th style="width:160px;">الإدارة المعنية</th>
                <th style="width:110px;">التاريخ</th>
                <th style="width:110px;">التصنيف</th>
                ${!isAuditHead ? '<th style="width:60px;">الإجراء</th>' : ""}
              </tr></thead>
              <tbody>
                ${filteredObs.map((obs, i) => {
                  const rc = OBS_RISK_COLORS[obs.risk] || { bg: "#f3f4f6", text: "#4b5563", border: "#e5e7eb", dot: "#9ca3af" };
                  const rcLabel = obs.risk || "—";
                  const menuOpen = obsOpenMenuId === obs.id;
                  return `
                  <tr style="background:${i % 2 === 0 ? "#fff" : "#f6fcfe"};">
                    <td><span class="obs-title-cell">${escapeHtml(obs.title)}</span></td>
                    <td><span class="obs-dept-cell"><i data-lucide="building-2"></i>${escapeHtml(obs.dept || "—")}</span></td>
                    <td><span class="obs-date-cell">${obs.date}</span></td>
                    <td><span class="obs-pill" style="background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="dot" style="background:${rc.dot};"></span>${escapeHtml(rcLabel)}</span></td>
                    ${!isAuditHead ? `
                    <td class="obs-menu-cell">
                      <button class="obs-menu-btn" data-menu-toggle="${obs.id}"><i data-lucide="more-vertical"></i></button>
                      ${menuOpen ? `
                        <div class="obs-menu-dropdown">
                          <button class="obs-menu-item" data-view-obs="${obs.id}"><i data-lucide="eye"></i> عرض</button>
                          ${!readOnly ? `
                            <button class="obs-menu-item" data-edit-obs="${obs.id}"><i data-lucide="pencil"></i> تعديل</button>
                            <div class="obs-menu-sep"></div>
                            <button class="obs-menu-item danger" data-delete-obs="${obs.id}"><i data-lucide="trash-2"></i> حذف</button>
                          ` : ""}
                        </div>` : ""}
                    </td>` : ""}
                  </tr>`;
                }).join("")}
              </tbody>
            </table>
          </div>
        `}
      </div>
    </div>
  </div>
  `;
}

function bindObsListEvents() {
  const taskSelect = document.getElementById("obsTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    obsSelectedTaskId = e.target.value;
    if (obsSelectedTaskId) await obsLoadList(obsSelectedTaskId);
    else obsList = [];
    rerenderObsContent();
  });

  const newBtn = document.getElementById("obsNewBtn");
  if (newBtn) newBtn.addEventListener("click", obsOpenNew);

  const exportBtn = document.getElementById("obsExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", () => window.print());

  const searchInput = document.getElementById("obsSearchInput");
  if (searchInput) searchInput.addEventListener("input", e => { obsSearchQuery = e.target.value; rerenderObsContent(); });

  const fDept = document.getElementById("obsFilterDept");
  if (fDept) fDept.addEventListener("change", e => { obsFilterDept = e.target.value; rerenderObsContent(); });

  const fStatus = document.getElementById("obsFilterStatus");
  if (fStatus) fStatus.addEventListener("change", e => { obsFilterStatus = e.target.value; rerenderObsContent(); });

  document.querySelectorAll("[data-risk-filter]").forEach(btn => {
    btn.addEventListener("click", () => {
      const r = btn.dataset.riskFilter;
      obsFilterRisk = obsFilterRisk === r ? "" : r;
      rerenderObsContent();
    });
  });

  const advToggle = document.getElementById("obsAdvToggle");
  if (advToggle) advToggle.addEventListener("click", () => { obsShowAdvanced = !obsShowAdvanced; rerenderObsContent(); });

  const clearBtn = document.getElementById("obsClearFilters");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    obsSearchQuery = ""; obsFilterDept = ""; obsFilterRisk = ""; obsFilterStatus = "";
    obsAdvDateFrom = ""; obsAdvDateTo = "";
    rerenderObsContent();
  });

  const advFrom = document.getElementById("obsAdvFrom");
  if (advFrom) advFrom.addEventListener("change", e => { obsAdvDateFrom = e.target.value; rerenderObsContent(); });
  const advTo = document.getElementById("obsAdvTo");
  if (advTo) advTo.addEventListener("change", e => { obsAdvDateTo = e.target.value; rerenderObsContent(); });

  document.querySelectorAll("[data-menu-toggle]").forEach(btn => {
    btn.addEventListener("click", e => {
      e.stopPropagation();
      const id = parseInt(btn.dataset.menuToggle, 10);
      obsOpenMenuId = obsOpenMenuId === id ? null : id;
      rerenderObsContent();
    });
  });
  document.querySelectorAll("[data-view-obs]").forEach(btn => {
    btn.addEventListener("click", () => obsOpenView(parseInt(btn.dataset.viewObs, 10)));
  });
  document.querySelectorAll("[data-edit-obs]").forEach(btn => {
    btn.addEventListener("click", () => obsOpenEdit(parseInt(btn.dataset.editObs, 10)));
  });
  document.querySelectorAll("[data-delete-obs]").forEach(btn => {
    btn.addEventListener("click", () => obsDelete(parseInt(btn.dataset.deleteObs, 10)));
  });

  /* إغلاق القائمة المنسدلة عند الضغط خارجها */
  if (obsOpenMenuId !== null) {
    setTimeout(() => {
      document.addEventListener("click", function closeObsMenu() {
        obsOpenMenuId = null;
        rerenderObsContent();
        document.removeEventListener("click", closeObsMenu);
      }, { once: true });
    }, 0);
  }
}

/* ---------- إجراءات القائمة ---------- */
function obsOpenNew() {
  const md = obsMissionDept();
  obsDraft = {
    id: 0, ref: "", deptId: md.deptId, dept: md.dept, title: "", date: new Date().toISOString().slice(0, 10),
    risk: "", status: "بانتظار الرد",
    observation: "", standard: "", reason: "", impact: "",
    recommendations: "", addToReport: null, attachments: [],
  };
  obsSubItems = [];
  obsSubExpanded = null;
  obsView = "new";
  rerenderObsContent();
}
function obsOpenEdit(id) {
  const obs = obsList.find(o => o.id === id);
  if (!obs) return;
  const md = obsMissionDept();
  obsDraft = { ...obs, deptId: md.deptId, dept: md.dept, attachments: [] };
  obsSubItems = [];
  obsSubExpanded = null;
  obsOpenMenuId = null;
  obsView = "edit";
  rerenderObsContent();
}
function obsOpenView(id) {
  const obs = obsList.find(o => o.id === id);
  if (!obs) return;
  obsViewTarget = obs;
  obsOpenMenuId = null;
  obsView = "view";
  rerenderObsContent();
}
async function obsDelete(id) {
  obsOpenMenuId = null;
  try {
    const data = await apiPost(base + "/dashboard/observations/api/delete/" + id, {});
    if (data.success) {
      showToast("تم حذف الملاحظة", "success");
      await obsLoadList(obsSelectedTaskId);
    } else {
      showToast("تعذّر حذف الملاحظة", "error");
    }
  } catch (e) {
    showToast("تعذّر الاتصال بالخادم", "error");
  }
  rerenderObsContent();
}
function obsCancel() {
  obsView = "list"; obsDraft = null; obsViewTarget = null;
  rerenderObsContent();
}

async function obsSaveOne(draft) {
  return apiPost(base + "/dashboard/observations/api/save", {
    id: draft.id || null,
    mission_id: obsSelectedTaskId,
    department_id: draft.deptId,
    title: draft.title,
    observation_date: draft.date,
    risk_severity: draft.risk,
    observation_text: draft.observation,
    standard_text: draft.standard,
    reason_text: draft.reason,
    impact_text: draft.impact,
    recommendations_text: draft.recommendations,
    add_to_report: draft.addToReport,
  });
}

async function obsSave() {
  if (!obsDraft) return;
  if (!obsDraft.deptId || !obsDraft.observation.trim()) {
    showToast("يرجى تعبئة الإدارة محل المراجعة ونص الملاحظة على الأقل.", "error");
    return;
  }

  const saveBtn = document.getElementById("obsFormSave");
  if (saveBtn) saveBtn.disabled = true;

  try {
    const result = await obsSaveOne(obsDraft);
    if (!result.success) {
      showToast(result.message || "تعذّر الحفظ", "error");
      return;
    }

    // كل "ملاحظة فرعية" تُحفظ كصف مستقل إضافي بنفس المهمة (audit_notes لا يدعم علاقة أب/ابن)
    for (const sub of obsSubItems) {
      if (!sub.deptId || !sub.observation.trim()) continue;
      await obsSaveOne(sub);
    }

    showToast(obsView === "new" ? "تم رصد الملاحظة واعتمادها" : "تم حفظ التعديلات", "success");
    await obsLoadList(obsSelectedTaskId);
    obsCancel();
  } catch (e) {
    showToast("تعذّر الاتصال بالخادم", "error");
  } finally {
    if (saveBtn) saveBtn.disabled = false;
  }
}

/* ============================================================
   وضع النموذج (رصد/تعديل) — ObsForm + SubObservations
   ============================================================ */
function renderObsFormMode() {
  const locked = !obsSelectedTaskId;
  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(obsSelectedTaskId, "obsTaskSelect")}
    <div class="obs-disabled-wrap ${locked ? "locked" : ""}">
      ${renderObsForm()}
    </div>
  </div>`;
}


function renderObsForm() {
  const d = obsDraft;
  return `
  <div class="obs-form-card">
    <div class="obs-form-head">
      <div class="obs-form-head-left">
        <button class="obs-form-back" id="obsFormBack"><i data-lucide="chevron-right"></i></button>
        <h3 class="obs-form-title">${obsView === "new" ? "إضافة ملاحظة جديدة" : "تعديل الملاحظة"}</h3>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="obs-btn-pdf" id="obsFormExportBtn"><i data-lucide="file-text"></i> تصدير PDF</button>
        <button class="obs-form-save" id="obsFormSave"><i data-lucide="check"></i> حفظ واعتماد</button>
      </div>
    </div>

    <div class="obs-form-body">
      <div class="obs-grid-3">
        <div class="wiz-field">
          <label class="wiz-label">الإدارة محل المراجعة</label>
          <div class="obs-auto-field"><span class="val">${escapeHtml(d.dept) || "— اختر المهمة أولاً —"}</span></div>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">عنوان الملاحظة <span class="wiz-req">*</span></label>
          <input id="obsTitle" type="text" class="wiz-input plain" placeholder="عنوان مختصر..." value="${escapeHtml(d.title)}">
        </div>
        <div class="wiz-field">
          <label class="wiz-label">التاريخ</label>
          <input id="obsDate" type="date" class="wiz-input plain" value="${d.date}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-grid-2">
        <div class="wiz-field">
          <label class="wiz-label">الملاحظة <span class="wiz-req">*</span></label>
          <textarea id="obsObservation" rows="4" class="wiz-textarea plain" placeholder="أدخل نص الملاحظة المكتشفة بوضوح...">${escapeHtml(d.observation)}</textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">المعيار أو النظام <span class="wiz-req">*</span></label>
          <textarea id="obsStandard" rows="4" class="wiz-textarea plain" placeholder="المادة النظامية أو السياسة التي تمت مخالفتها...">${escapeHtml(d.standard)}</textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">السبب <span class="wiz-req">*</span></label>
          <textarea id="obsReason" rows="3" class="wiz-textarea plain" placeholder="الأسباب الجذرية لحدوث هذه الملاحظة...">${escapeHtml(d.reason)}</textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">الأثر <span class="wiz-req">*</span></label>
          <textarea id="obsImpact" rows="3" class="wiz-textarea plain" placeholder="الأثر المالي أو التشغيلي المترتب...">${escapeHtml(d.impact)}</textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">التوصيات <span class="wiz-req">*</span></label>
          <textarea id="obsRecommendations" rows="2" class="wiz-textarea plain" placeholder="الإجراءات التصحيحية المقترحة...">${escapeHtml(d.recommendations)}</textarea>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">الحالة (الخطر):</label>
          <input id="obsRisk" type="text" class="wiz-input plain" placeholder="اكتب حالة/خطورة الملاحظة..." value="${escapeHtml(d.risk)}">
        </div>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-attach-row">
        <div>
          <input type="file" id="obsFileInput" style="display:none;">
          <button class="obs-attach-btn" id="obsAttachBtn"><i data-lucide="paperclip"></i> إرفاق</button>
          ${d.attachments.length > 0 ? `
            <div class="obs-attach-list">
              ${d.attachments.map((f, i) => `
                <div class="obs-attach-chip">
                  <i class="pin" data-lucide="paperclip"></i>
                  <span>${escapeHtml(f)}</span>
                  <button data-remove-attach="${i}"><i data-lucide="x" style="width:12px;height:12px;"></i></button>
                </div>
              `).join("")}
            </div>` : ""}
        </div>
        <div class="wiz-field" style="gap:8px;">
          <label class="wiz-label">تضاف للتقرير؟</label>
          <div class="obs-radio-group">
            <label class="obs-radio-label"><input type="radio" name="obsAddToReport" ${d.addToReport === true ? "checked" : ""} data-add-report="true"> نعم</label>
            <label class="obs-radio-label"><input type="radio" name="obsAddToReport" ${d.addToReport === false ? "checked" : ""} data-add-report="false"> لا</label>
          </div>
        </div>
      </div>

      <div class="obs-divider"></div>

      ${renderSubObservations()}
    </div>
  </div>`;
}

function bindObsFormEvents() {
  const $ = id => document.getElementById(id);
  const d = obsDraft;

  $("obsFormBack").addEventListener("click", obsCancel);
  $("obsFormSave").addEventListener("click", obsSave);
  $("obsFormExportBtn").addEventListener("click", () => exportObservationToPDF(d));

  $("obsDate").addEventListener("change", e => { d.date = e.target.value; rerenderObsContent(); });
  $("obsTitle").addEventListener("input", e => { d.title = e.target.value; });
  $("obsRisk").addEventListener("input", e => { d.risk = e.target.value; });

  const obsGrowFields = { obsObservation: "observation", obsStandard: "standard", obsReason: "reason", obsImpact: "impact", obsRecommendations: "recommendations" };
  Object.keys(obsGrowFields).forEach(id => {
    const el = $(id);
    autoGrowTextarea(el);
    el.addEventListener("input", e => { d[obsGrowFields[id]] = e.target.value; autoGrowTextarea(e.target); });
  });

  $("obsAttachBtn").addEventListener("click", () => $("obsFileInput").click());
  $("obsFileInput").addEventListener("change", e => {
    const file = e.target.files && e.target.files[0];
    if (file) { d.attachments.push(file.name); rerenderObsContent(); }
  });
  document.querySelectorAll("[data-remove-attach]").forEach(btn => {
    btn.addEventListener("click", () => {
      const i = parseInt(btn.dataset.removeAttach, 10);
      d.attachments.splice(i, 1);
      rerenderObsContent();
    });
  });

  document.querySelectorAll("[data-add-report]").forEach(radio => {
    radio.addEventListener("change", () => { d.addToReport = radio.dataset.addReport === "true"; rerenderObsContent(); });
  });

  bindSubObservationsEvents();
}

/* ============================================================
   الملاحظات الفرعية (SubObservations)
   ============================================================ */
function renderSubObservations() {
  return `
  <div class="obs-sub-wrap">
    <div class="obs-sub-header">
      <span class="lbl">ملاحظات إضافية${obsSubItems.length > 0 ? ` (${obsSubItems.length})` : ""}</span>
      <button class="obs-sub-add-btn" id="obsSubAddBtn"><i data-lucide="plus"></i> إضافة ملاحظة</button>
    </div>

    ${obsSubItems.map((item, idx) => {
      const open = obsSubExpanded === item.id;
      const rc = item.risk ? (OBS_RISK_COLORS[item.risk] || { bg: "#f3f4f6", text: "#4b5563" }) : null;
      return `
      <div class="obs-sub-card ${open ? "open" : ""}">
        <div class="obs-sub-card-head" data-sub-toggle="${item.id}">
          <span class="obs-sub-num">${idx + 1}</span>
          <span class="obs-sub-title">${escapeHtml(item.title || "ملاحظة غير معنونة")}</span>
          ${rc ? `<span class="obs-pill" style="background:${rc.bg};color:${rc.text};">${escapeHtml(item.risk)}</span>` : ""}
          <button class="obs-sub-del" data-sub-del="${item.id}"><i data-lucide="x"></i></button>
          <i data-lucide="chevron-left" class="obs-sub-chevron ${open ? "open" : ""}"></i>
        </div>
        ${open ? `
        <div class="obs-sub-body">
          <div class="obs-grid-3">
            <div class="wiz-field">
              <label class="wiz-label">الإدارة محل المراجعة</label>
              <div class="obs-auto-field"><span class="val">${escapeHtml(item.dept) || "—"}</span></div>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">عنوان الملاحظة <span class="wiz-req">*</span></label>
              <input type="text" id="sub-${item.id}-title" class="wiz-input plain" data-sub-field="title" data-sub-id="${item.id}" placeholder="عنوان مختصر..." value="${escapeHtml(item.title)}">
            </div>
            <div class="wiz-field">
              <label class="wiz-label">التاريخ</label>
              <input type="date" id="sub-${item.id}-date" class="wiz-input plain" data-sub-field="date" data-sub-id="${item.id}" value="${item.date}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
            </div>
          </div>

          <div class="obs-divider"></div>

          <div class="obs-grid-2">
            <div class="wiz-field">
              <label class="wiz-label">الملاحظة <span class="wiz-req">*</span></label>
              <textarea rows="4" id="sub-${item.id}-observation" class="wiz-textarea plain" data-sub-field="observation" data-sub-id="${item.id}" placeholder="أدخل نص الملاحظة المكتشفة بوضوح...">${escapeHtml(item.observation)}</textarea>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">المعيار أو النظام <span class="wiz-req">*</span></label>
              <textarea rows="4" id="sub-${item.id}-standard" class="wiz-textarea plain" data-sub-field="standard" data-sub-id="${item.id}" placeholder="المادة النظامية أو السياسة التي تمت مخالفتها...">${escapeHtml(item.standard)}</textarea>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">السبب <span class="wiz-req">*</span></label>
              <textarea rows="3" id="sub-${item.id}-reason" class="wiz-textarea plain" data-sub-field="reason" data-sub-id="${item.id}" placeholder="الأسباب الجذرية لحدوث هذه الملاحظة...">${escapeHtml(item.reason)}</textarea>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">الأثر <span class="wiz-req">*</span></label>
              <textarea rows="3" id="sub-${item.id}-impact" class="wiz-textarea plain" data-sub-field="impact" data-sub-id="${item.id}" placeholder="الأثر المالي أو التشغيلي المترتب...">${escapeHtml(item.impact)}</textarea>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">التوصيات <span class="wiz-req">*</span></label>
              <textarea rows="2" id="sub-${item.id}-recommendations" class="wiz-textarea plain" data-sub-field="recommendations" data-sub-id="${item.id}" placeholder="الإجراءات التصحيحية المقترحة...">${escapeHtml(item.recommendations)}</textarea>
            </div>
            <div class="wiz-field">
              <label class="wiz-label">الحالة (الخطر):</label>
              <input type="text" id="sub-${item.id}-risk" class="wiz-input plain" data-sub-field="risk" data-sub-id="${item.id}" placeholder="اكتب حالة/خطورة الملاحظة..." value="${escapeHtml(item.risk)}">
            </div>
          </div>

          <div class="obs-divider"></div>

          <div class="obs-attach-row">
            <div>
              <input type="file" id="obsSubFile-${item.id}" style="display:none;">
              <button class="obs-attach-btn" data-sub-attach-btn="${item.id}"><i data-lucide="paperclip"></i> إرفاق</button>
              ${item.attachments.length > 0 ? `
                <div class="obs-attach-list">
                  ${item.attachments.map((f, ai) => `
                    <div class="obs-attach-chip">
                      <i class="pin" data-lucide="paperclip"></i>
                      <span>${escapeHtml(f)}</span>
                      <button data-sub-remove-attach="${item.id}" data-attach-idx="${ai}"><i data-lucide="x" style="width:12px;height:12px;"></i></button>
                    </div>
                  `).join("")}
                </div>` : ""}
            </div>
            <div class="wiz-field" style="gap:8px;">
              <label class="wiz-label">تضاف للتقرير؟</label>
              <div class="obs-radio-group">
                <label class="obs-radio-label"><input type="radio" name="subAddToReport-${item.id}" ${item.addToReport === true ? "checked" : ""} data-sub-add-report="${item.id}" data-add-val="true"> نعم</label>
                <label class="obs-radio-label"><input type="radio" name="subAddToReport-${item.id}" ${item.addToReport === false ? "checked" : ""} data-sub-add-report="${item.id}" data-add-val="false"> لا</label>
              </div>
            </div>
          </div>
        </div>` : ""}
      </div>`;
    }).join("")}
  </div>`;
}

function bindSubObservationsEvents() {
  document.getElementById("obsSubAddBtn").addEventListener("click", () => {
    const id = Date.now();
    const md = obsMissionDept();
    obsSubItems.push({
      id, date: new Date().toISOString().slice(0, 10),
      deptId: md.deptId, dept: md.dept, title: "", risk: "",
      observation: "", standard: "", reason: "", impact: "",
      recommendations: "", addToReport: null, attachments: [],
    });
    obsSubExpanded = id;
    rerenderObsContent();
  });

  document.querySelectorAll("[data-sub-toggle]").forEach(el => {
    el.addEventListener("click", e => {
      if (e.target.closest("[data-sub-del]")) return;
      const id = parseInt(el.dataset.subToggle, 10);
      obsSubExpanded = obsSubExpanded === id ? null : id;
      rerenderObsContent();
    });
  });
  document.querySelectorAll("[data-sub-del]").forEach(btn => {
    btn.addEventListener("click", e => {
      e.stopPropagation();
      const id = parseInt(btn.dataset.subDel, 10);
      obsSubItems = obsSubItems.filter(it => it.id !== id);
      rerenderObsContent();
    });
  });

  document.querySelectorAll("[data-sub-field]").forEach(el => {
    const evt = el.tagName === "SELECT" ? "change" : "input";
    el.addEventListener(evt, () => {
      const id = parseInt(el.dataset.subId, 10);
      const field = el.dataset.subField;
      const item = obsSubItems.find(it => it.id === id);
      if (item) { item[field] = el.value; rerenderObsContent(); }
    });
  });
  obsSubItems.forEach(item => {
    const btn = document.querySelector(`[data-sub-attach-btn="${item.id}"]`);
    const input = document.getElementById(`obsSubFile-${item.id}`);
    if (btn && input) {
      btn.addEventListener("click", () => input.click());
      input.addEventListener("change", e => {
        const file = e.target.files && e.target.files[0];
        if (file) { item.attachments.push(file.name); rerenderObsContent(); }
      });
    }
  });
  document.querySelectorAll("[data-sub-remove-attach]").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = parseInt(btn.dataset.subRemoveAttach, 10);
      const ai = parseInt(btn.dataset.attachIdx, 10);
      const item = obsSubItems.find(it => it.id === id);
      if (item) { item.attachments.splice(ai, 1); rerenderObsContent(); }
    });
  });
  document.querySelectorAll("[data-sub-add-report]").forEach(radio => {
    radio.addEventListener("change", () => {
      const id = parseInt(radio.dataset.subAddReport, 10);
      const item = obsSubItems.find(it => it.id === id);
      if (item) { item.addToReport = radio.dataset.addVal === "true"; rerenderObsContent(); }
    });
  });
}

/* ============================================================
   وضع العرض (read-only)
   ============================================================ */
function renderObsViewMode() {
  const v = obsViewTarget;
  const rc = OBS_RISK_COLORS[v.risk] || { bg: "#f3f4f6", text: "#4b5563", border: "#e5e7eb", dot: "#9ca3af" };
  const sc = OBS_STATUS_COLORS[v.status] || OBS_STATUS_COLORS["بانتظار الرد"];
  const readOnly = obsIsReadOnly();

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(obsSelectedTaskId, "obsTaskSelect")}

    <div class="obs-form-card">
      <div class="obs-form-head">
        <div class="obs-form-head-left">
          <button class="obs-form-back" id="obsViewBack"><i data-lucide="chevron-right"></i></button>
          <h3 class="obs-form-title">عرض الملاحظة</h3>
        </div>
        ${!readOnly ? `<button class="obs-form-save" id="obsViewEditBtn"><i data-lucide="pencil"></i></button>` : ""}
      </div>

      <div class="obs-form-body">
        <div class="obs-view-grid cols-4">
          <div class="obs-view-field"><span class="lbl">الإدارة محل المراجعة</span><span class="val">${escapeHtml(v.dept || "—")}</span></div>
          <div class="obs-view-field"><span class="lbl">عنوان الملاحظة</span><span class="val">${escapeHtml(v.title || "—")}</span></div>
          <div class="obs-view-field"><span class="lbl">التاريخ</span><span class="val">${v.date}</span></div>
          <div class="obs-view-field">
            <span class="lbl">الحالة (الخطر)</span>
            <span class="obs-pill" style="width:fit-content;background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="dot" style="background:${rc.dot};"></span>${escapeHtml(v.risk || "—")}</span>
          </div>
        </div>

        <div class="obs-divider"></div>

        <div class="obs-view-grid">
          <div class="obs-view-box"><span class="lbl">الملاحظة</span><p>${escapeHtml(v.observation || "—")}</p></div>
          <div class="obs-view-box"><span class="lbl">المعيار أو النظام</span><p>${escapeHtml(v.standard || "—")}</p></div>
          <div class="obs-view-box"><span class="lbl">السبب</span><p>${escapeHtml(v.reason || "—")}</p></div>
          <div class="obs-view-box"><span class="lbl">الأثر</span><p>${escapeHtml(v.impact || "—")}</p></div>
          <div class="obs-view-box" style="grid-column:1/-1;"><span class="lbl">التوصيات</span><p>${escapeHtml(v.recommendations || "—")}</p></div>
        </div>

        <div class="obs-view-footer">
          <div class="obs-view-field">
            <span class="lbl">الحالة</span>
            <span class="obs-pill" style="width:fit-content;background:${sc.bg};color:${sc.text};border:1px solid ${sc.border};"><span class="dot" style="background:${sc.dot};"></span>${v.status}</span>
          </div>
          <div class="obs-view-field">
            <span class="lbl">تضاف للتقرير</span>
            <span class="val">${v.addToReport === true ? "نعم" : v.addToReport === false ? "لا" : "—"}</span>
          </div>
        </div>
      </div>
    </div>
  </div>`;
}

function bindObsViewEvents() {
  document.getElementById("obsViewBack").addEventListener("click", obsCancel);
  const editBtn = document.getElementById("obsViewEditBtn");
  if (editBtn) editBtn.addEventListener("click", () => {
    obsDraft = { ...obsViewTarget, attachments: [] };
    obsSubItems = []; obsSubExpanded = null;
    obsView = "edit";
    rerenderObsContent();
  });

  const taskSelect = document.getElementById("obsTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    obsSelectedTaskId = e.target.value;
    obsCancel();
  });
}

