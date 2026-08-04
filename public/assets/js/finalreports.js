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
let frFilterYear = "", frFilterStatus = "", frFilterDept = "", frFilterTargetDept = "";

let frCreateSelectedTask = "";
let frCurrentReport = null;
let frCurrentItems = [];
let frCurrentCompletion = {};

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
        ${depts.map(d => `<option value="${escapeHtml(d)}" ${frFilterDept === d ? "selected" : ""}>${escapeHtml(d)}</option>`).join("")}
      </select>` : ""}
      <select id="frFilterTargetDept" class="wiz-select ${frFilterTargetDept ? "filled" : ""}">
        <option value="">الإدارة المستهدفة</option>
        ${targetDepts.map(d => `<option value="${escapeHtml(d)}" ${frFilterTargetDept === d ? "selected" : ""}>${escapeHtml(d)}</option>`).join("")}
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
          ${missionsForSelector.map(m => `<option value="${m.id}" ${String(frCreateSelectedTask) === String(m.id) ? "selected" : ""}>${escapeHtml(m.mission_code)} — ${escapeHtml(m.target_department_name || "")}</option>`).join("")}
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
          <thead><tr><th>تفاصيل المهمة</th><th class="center">إجراء</th><th class="center">اعتماد</th></tr></thead>
          <tbody>
            ${frCurrentItems.map(item => {
              const isChecked = Number(item.is_checked) === 1;
              const isDone = !!frCurrentCompletion[item.section_number];
              return `
              <tr style="background:${isChecked ? "#f0fdf4" : "#fff"};">
                <td>
                  <div style="display:flex;align-items:center;gap:12px;">
                    <span class="fr-phase-num ${isChecked ? "done" : ""}">${isChecked ? '<i data-lucide="check" style="width:13px;height:13px;"></i>' : item.section_number}</span>
                    <span class="fr-phase-name ${isChecked ? "done" : ""}">${escapeHtml(item.section_title)}</span>
                  </div>
                </td>
                <td style="text-align:center;"><button type="button" class="fr-phase-view-link" data-fr-preview="${item.section_number}"><i data-lucide="eye" style="width:13px;height:13px;"></i> عرض</button></td>
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
    ${frRenderPreviewModal()}
  </div>`;
}

/* ============================================================
   معاينة مرحلة (Modal) — بيانات حقيقية حسب رقم الصف المختار
   ============================================================ */
function frRenderPreviewModal() {
  if (!frPreviewOpen) return "";
  const item = frCurrentItems.find(it => it.section_number === frPreviewSection);
  return `
  <div class="fr-modal-overlay" id="frPreviewOverlay">
    <div class="fr-modal-box">
      <div class="fr-modal-head">
        <span>${escapeHtml(item ? item.section_title : "")}</span>
        <button type="button" class="fr-modal-close" id="frPreviewCloseBtn"><i data-lucide="x"></i></button>
      </div>
      <div class="fr-modal-body">
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
    frPreviewOpen = false; frPreviewSection = null; frPreviewData = null;
    rerenderFRContent();
  });

  const taskSelect = document.getElementById("frCreateTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    frCreateSelectedTask = e.target.value;
    frPreviewOpen = false; frPreviewSection = null; frPreviewData = null;
    if (frCreateSelectedTask) await frLoadChecklist(frCreateSelectedTask);
    rerenderFRContent();
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

