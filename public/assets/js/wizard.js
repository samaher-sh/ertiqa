/* ============================================================
   ويزارد "بدء مهمة رقابية" — متصل بالـ API الحقيقي
   يعتمد على GET /api/departments (بدل DEPT_TREE الوهمية)
   ويرسل النتيجة النهائية عبر POST /dashboard/new-task
   ============================================================ */

/* ---------- بيانات الإدارات الحقيقية (تُجلب مرة عند فتح التبويب) ---------- */
let WIZ_MAIN_DEPTS = [];      // [{id, name_ar, ...}]
let WIZ_SUBS_BY_PARENT = {};  // {parentId: [{id, name_ar}, ...]}
let wizDataLoaded = false;

async function initWizardData() {
  if (wizDataLoaded) return;
  try {
    const data = await apiGet(base + "/api/departments");
    WIZ_MAIN_DEPTS = data.main || [];
    WIZ_SUBS_BY_PARENT = data.subs_by_parent || {};
    wizDataLoaded = true;
  } catch (e) {
    WIZ_MAIN_DEPTS = [];
    WIZ_SUBS_BY_PARENT = {};
  }
}

function wizSubDepts(mainDeptId) {
  return WIZ_SUBS_BY_PARENT[mainDeptId] || [];
}

/* ---------- الحالة العامة للويزارد ---------- */
let wizardPage = 1;
let wizP1, wizP2, wizP3;

function initWizardState() {
  const cy = new Date().getFullYear().toString();
  wizP1 = {
    deptId: "", deptName: "", targetId: "", targetName: "", year: cy, procedure: "",
    reviewer: "", director: "", email: "", phone: "", touched: false,
  };
  wizP2 = {
    subjectDept: "", date: "",
    desc: "تهدف هذه الخدمة إلى عقد اجتماعات المراجعة الداخلية مع الإدارات الخاضعة للمراجعة وتنفيذ العمليات المتعلقة بأعمال المراجعة حسب خطة المراجعة.",
    ch: { email: true, memo: true, phone: true },
    chVals: { email: "", memo: "", phone: "" },
    sigName: "", sigDate: "",
  };
  wizP3 = { rows: [], saved: false, touched: false };
  wizardPage = 1;
}
initWizardState();

function isPage1Valid() {
  const s = wizP1;
  return !!(s.deptId && s.targetId && s.procedure && s.reviewer && s.director && s.email && s.phone);
}
function isPage3Valid() {
  return wizP3.rows.length > 0 && wizP3.rows.every(r => r.name.trim() !== "");
}

/* إعادة رسم منطقة المحتوى مع الحفاظ على التركيز/المؤشر داخل الحقل النشط
   (بديل vanilla لآلية إعادة العرض التفاعلية في React) */
function rerenderWizardContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  ca.innerHTML = renderWizardPage();
  bindWizardEvents();
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

/* ============================================================
   الحاوية العامة: Steps + الصفحة الحالية + أزرار التنقل
   ============================================================ */
function renderWizardPage() {
  return `
    ${renderWizardSteps()}
    <div class="wiz-page-container">
      ${wizardPage === 1 ? renderWizPage1() : wizardPage === 2 ? renderWizPage2() : renderWizPage3()}
    </div>
    ${renderWizardNav()}
  `;
}

function renderWizardSteps() {
  return `<div class="wiz-steps">
    ${STEPS.map((st, i) => `
      <div class="wiz-step">
        <button class="wiz-step-btn" data-goto-step="${st.n}">
          <span class="wiz-step-circle ${wizardPage === st.n ? "current" : wizardPage > st.n ? "done" : ""}">
            ${wizardPage > st.n ? '<i data-lucide="check"></i>' : st.n}
          </span>
          <span class="wiz-step-label ${wizardPage === st.n ? "current" : wizardPage > st.n ? "done" : ""}">${st.label}</span>
        </button>
        ${i < STEPS.length - 1 ? `<span class="wiz-step-line ${wizardPage > st.n ? "done" : ""}"></span>` : ""}
      </div>
    `).join("")}
  </div>`;
}

function renderWizardNav() {
  const isFirst = wizardPage === STEPS[0].n;
  const isLast = wizardPage === STEPS[STEPS.length - 1].n;
  return `<div class="wiz-nav">
    <button class="wiz-btn wiz-btn-outline" id="wizPrevBtn" ${isFirst ? "disabled" : ""} style="min-width:150px;justify-content:center;">
      <i data-lucide="chevron-right"></i>السابق
    </button>
    <div class="wiz-dots">
      ${STEPS.map(s => `<button class="wiz-dot ${wizardPage === s.n ? "current" : ""}" data-goto-step="${s.n}"></button>`).join("")}
    </div>
    ${isLast
      ? `<button class="wiz-btn wiz-btn-success" id="wizSendBtn" style="min-width:150px;justify-content:center;"><i data-lucide="check"></i>إرسال المهمة</button>`
      : `<button class="wiz-btn wiz-btn-primary" id="wizNextBtn">التالي<i data-lucide="chevron-left"></i></button>`}
  </div>`;
}

/* ============================================================
   ربط الأحداث — يُستدعى بعد إدراج HTML داخل contentArea
   ============================================================ */
function bindWizardEvents() {
  document.querySelectorAll("[data-goto-step]").forEach(btn => {
    btn.addEventListener("click", () => {
      wizardPage = parseInt(btn.dataset.gotoStep, 10);
      if (wizardPage === 2 && !wizP2.subjectDept) { wizP2.subjectDept = wizP1.targetName; }
      rerenderWizardContent();
    });
  });

  const prevBtn = document.getElementById("wizPrevBtn");
  if (prevBtn) prevBtn.addEventListener("click", () => {
    const idx = STEPS.findIndex(s => s.n === wizardPage);
    wizardPage = STEPS[Math.max(0, idx - 1)].n;
    rerenderWizardContent();
  });

  const nextBtn = document.getElementById("wizNextBtn");
  if (nextBtn) nextBtn.addEventListener("click", () => {
    if (wizardPage === 1 && !isPage1Valid()) {
      wizP1.touched = true;
      showToast("يرجى إكمال جميع الحقول المطلوبة قبل المتابعة", "error");
      rerenderWizardContent();
      return;
    }
    const idx = STEPS.findIndex(s => s.n === wizardPage);
    wizardPage = STEPS[Math.min(STEPS.length - 1, idx + 1)].n;
    if (wizardPage === 2 && !wizP2.subjectDept) { wizP2.subjectDept = wizP1.targetName; }
    rerenderWizardContent();
  });

  const sendBtn = document.getElementById("wizSendBtn");
  if (sendBtn) sendBtn.addEventListener("click", handleSendTask);

  if (wizardPage === 1) bindWizPage1();
  else if (wizardPage === 2) bindWizPage2();
  else bindWizPage3();
}

async function handleSendTask() {
  if (!isPage1Valid()) {
    wizP1.touched = true;
    wizardPage = 1;
    showToast("يرجى إكمال جميع حقول الخطوة الأولى", "error");
    rerenderWizardContent();
    return;
  }
  if (!isPage3Valid()) {
    wizP3.touched = true;
    wizardPage = 3;
    showToast("يرجى إضافة مستند واحد على الأقل", "error");
    rerenderWizardContent();
    return;
  }

  const sendBtn = document.getElementById("wizSendBtn");
  if (sendBtn) { sendBtn.disabled = true; sendBtn.textContent = "جارٍ الإرسال..."; }

  try {
    const data = await apiPost(base + "/dashboard/new-task", {
      main_dept_id:   wizP1.deptId,
      target_dept_id: wizP1.targetId,
      year:           wizP1.year,
      procedure:      wizP1.procedure,
      reviewer_name:  wizP1.reviewer,
      reviewer_email: wizP1.email,
      reviewer_phone: wizP1.phone,
      director_name:  wizP1.director,
      doc_names:      wizP3.rows.map(r => r.name),
    });

    if (data.success) {
      showToast(`تم إرسال المهمة إلى ${wizP1.targetName}`, "success");
      initWizardState();
      activeContent = "home";
      activeStatCard = null;
      renderSidebar();
      await renderContent();
      lucide.createIcons();
      return;
    }

    showToast(data.message || (data.errors ? Object.values(data.errors)[0] : "تعذّر إرسال المهمة."), "error");
  } catch (e) {
    showToast("تعذّر الاتصال بالخادم. حاول مرة أخرى.", "error");
  } finally {
    if (sendBtn) { sendBtn.disabled = false; sendBtn.textContent = "إرسال المهمة للإدارة"; }
  }
}


/* ============================================================
   PAGE 1 — طلب المراجعة الداخلية + الخطاب الرسمي الحي
   ============================================================ */
function renderWizPage1() {
  const s = wizP1;
  const subs = wizSubDepts(s.deptId);
  const isLocked = !s.deptId;
  const err = f => s.touched && !s[f];
  const todayD = new Date();
  const currentYear = todayD.getFullYear().toString();
  const refNumber = String((todayD.getMonth() + 1) * 100 + todayD.getDate()).padStart(4, "0");
  const deptAbbr = DEPT_ABBR[s.deptName] || "AUD";
  const missionCode = deptAbbr + refNumber;

  return `
  <div class="wiz-page1-grid">
    <!-- RIGHT: form -->
    <div class="wiz-card">
      <div class="wiz-card-head">
        <i data-lucide="plus"></i>
        <div><h2>طلب المراجعة الداخلية</h2><p>Internal Audit Request</p></div>
      </div>
      <div class="wiz-card-body">

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات الإدارة</p>

          <div class="wiz-field">
            <label class="wiz-label ${err("deptId") ? "error" : ""}">الإدارة ${err("deptId") ? '<span class="wiz-req">*</span>' : ""}</label>
            <select id="p1Dept" class="wiz-select ${s.deptId ? "filled" : ""} ${err("deptId") ? "err" : ""}">
              <option value="">— اختر —</option>
              ${WIZ_MAIN_DEPTS.map(d => `<option value="${d.id}" ${String(s.deptId) === String(d.id) ? "selected" : ""}>${escapeHtml(d.name_ar)}</option>`).join("")}
            </select>
            ${err("deptId") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>

          <div class="wiz-field">
            <label class="wiz-label ${err("targetId") ? "error" : ""}">الإدارة المستهدفة ${err("targetId") ? '<span class="wiz-req">*</span>' : ""}</label>
            <div class="wiz-cascade-wrap">
              <select id="p1Target" class="wiz-select ${s.targetId ? "filled" : ""} ${err("targetId") ? "err" : ""}" ${isLocked ? "disabled" : ""}>
                <option value="">${isLocked ? "— اختر الإدارة أولاً —" : `— اختر من ${subs.length} إدارة —`}</option>
                ${subs.map(sub => `<option value="${sub.id}" ${String(s.targetId) === String(sub.id) ? "selected" : ""}>${escapeHtml(sub.name_ar)}</option>`).join("")}
              </select>
              ${(s.deptId && !s.targetId) ? `<span class="wiz-cascade-badge">${subs.length} خيار</span>` : ""}
            </div>
            ${!s.deptId ? '<p class="wiz-hint">يُرجى اختيار الإدارة أولاً لتفعيل هذا الحقل</p>' : ""}
            ${err("targetId") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>

          <div class="wiz-field">
            <label class="wiz-label">السنة</label>
            <select id="p1Year" class="wiz-select filled">
              ${YEARS.map(y => `<option value="${y}" ${s.year === y ? "selected" : ""}>${y}</option>`).join("")}
            </select>
          </div>
        </div>

        <div class="wiz-section ${err("procedure") ? "error" : ""}">
          <p class="wiz-section-title ${err("procedure") ? "error" : ""}">المراد مناقشته في الاجتماع ${err("procedure") ? '<span class="wiz-req">*</span>' : ""}</p>
          <textarea id="p1Procedure" rows="4" class="wiz-textarea ${err("procedure") ? "err" : ""}" placeholder="أدخل المراد مناقشته في الاجتماع هنا...">${escapeHtml(s.procedure)}</textarea>
          ${err("procedure") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
        </div>

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات المراجع</p>
          <div class="wiz-field">
            <label class="wiz-label ${err("reviewer") ? "error" : ""}">اسم المراجع الرئيسي ${err("reviewer") ? '<span class="wiz-req">*</span>' : ""}</label>
            <div class="wiz-input-icon-wrap"><i data-lucide="user"></i>
              <input id="p1Reviewer" type="text" class="wiz-input plain ${err("reviewer") ? "err" : ""}" placeholder="الاسم كاملاً" value="${escapeHtml(s.reviewer)}">
            </div>
            ${err("reviewer") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>
          <div class="wiz-field">
            <label class="wiz-label ${err("email") ? "error" : ""}">البريد الإلكتروني ${err("email") ? '<span class="wiz-req">*</span>' : ""}</label>
            <div class="wiz-input-icon-wrap"><i data-lucide="mail"></i>
              <input id="p1Email" type="email" dir="ltr" style="text-align:left;" class="wiz-input plain ${err("email") ? "err" : ""}" placeholder="example@kamc.med.sa" value="${escapeHtml(s.email)}">
            </div>
            ${err("email") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>
          <div class="wiz-field">
            <label class="wiz-label ${err("phone") ? "error" : ""}">رقم الجوال ${err("phone") ? '<span class="wiz-req">*</span>' : ""}</label>
            <div class="wiz-input-icon-wrap"><i data-lucide="phone"></i>
              <input id="p1Phone" type="tel" inputmode="numeric" maxlength="10" dir="ltr" style="text-align:left;" class="wiz-input plain ${err("phone") ? "err" : ""}" placeholder="05XXXXXXXX" value="${escapeHtml(s.phone)}">
            </div>
            ${err("phone") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>
        </div>

        <div class="wiz-section">
          <p class="wiz-section-title">بيانات المدير</p>
          <div class="wiz-field">
            <label class="wiz-label ${err("director") ? "error" : ""}">اسم المدير ${err("director") ? '<span class="wiz-req">*</span>' : ""}</label>
            <div class="wiz-input-icon-wrap"><i data-lucide="user"></i>
              <input id="p1Director" type="text" class="wiz-input plain ${err("director") ? "err" : ""}" placeholder="الاسم كاملاً" value="${escapeHtml(s.director)}">
            </div>
            ${err("director") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : ""}
          </div>
        </div>

      </div>
    </div>

    <!-- LEFT: live letter preview -->
    <div class="wiz-card">
      <div class="wiz-card-head">
        <i data-lucide="file-text"></i>
        <div><h2>نموذج الخطاب الرسمي</h2><p>يتم ملؤه تلقائياً من النموذج</p></div>
        <button type="button" class="obs-btn-pdf" id="wizP1ExportBtn" style="margin-right:auto;"><i data-lucide="file-text"></i> تصدير PDF</button>
      </div>
      <div class="wiz-letter-scroll">
        <div class="wiz-paper">
          <div class="wiz-paper-watermark"><i data-lucide="building-2"></i></div>
          <div class="wiz-paper-body">
            <div class="wiz-letterhead">
              <div>
                <img src="${base}/assets/images/kamc.png" alt="مدينة الملك عبدالله الطبية">
                <p class="sub">إدارة المراجعة الداخلية</p>
              </div>
              <div class="wiz-letterhead-meta">
                <p>التاريخ: ${todayD.toLocaleDateString("en-GB")}</p>
                <p>رقم المهمة: <strong dir="ltr" style="display:inline-block;">${missionCode}</strong></p>
              </div>
            </div>
            <div class="wiz-divider-fade"></div>
            <p class="wiz-p" style="font-weight:700;color:#1f2937;">
              سعادة المدير التنفيذي لـ${s.deptName ? `<mark class="wiz-mark">${escapeHtml(s.deptName)}</mark>` : ""} المحترم
            </p>
            <p class="wiz-p" style="font-weight:600;color:#4b5563;">السلام عليكم ورحمة الله وبركاته،</p>
            <p class="wiz-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة
              <mark class="wiz-mark small">${escapeHtml(s.targetName || "الإدارة المستهدفة")}</mark>
              للقيام بعملية المراجعة الداخلية الشاملة وفق الخطة السنوية المعتمدة لعام
              <mark class="wiz-mark small">${escapeHtml(s.year)}</mark>.
            </p>
            <p class="wiz-p">عليه نأمل التكرم بتوجيه من يلزم للعمل على التنسيق خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخ استلام هذا الإشعار.</p>
            <div class="wiz-procedure-box" id="procedureBox" ${s.procedure ? "" : "hidden"}>
              <div class="wiz-procedure-head"><i data-lucide="clipboard-list"></i><span>المراد مناقشته في الاجتماع</span></div>
              <p class="wiz-procedure-body" id="procedureText">${escapeHtml(s.procedure)}</p>
            </div>
            <p class="wiz-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة حتى يتسنى لنا البدء بعملية المراجعة.</p>
            <p class="wiz-p">إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة.</p>
            <p class="wiz-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
            <p class="wiz-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark class="wiz-mark small" id="mReviewer">${escapeHtml(s.reviewer || "...............")}</mark></p>
            <p class="wiz-p" style="margin-bottom:2px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <div class="wiz-contact-row"><i data-lucide="mail"></i><span>البريد الإلكتروني:</span><span class="val" id="mEmail" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(s.email || "........................")}</span></div>
              <div class="wiz-contact-row"><i data-lucide="phone"></i><span>رقم الجوال:</span><span class="val" id="mPhone" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(s.phone || "........................")}</span></div>
            </div>
            <p class="wiz-p" style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
            <p class="wiz-p" id="mDirector" style="font-weight:800;color:var(--pd);" ${s.director ? "" : "hidden"}>${escapeHtml(s.director)}</p>
            <p class="wiz-p" style="font-weight:600;">وتقبلوا وافر التحية والتقدير،،.</p>
          </div>
          <div class="wiz-paper-footer-bar"></div>
        </div>
      </div>
    </div>
  </div>
  `;
}

function bindWizPage1() {
  const exportBtn = document.getElementById("wizP1ExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", () => window.print());
  const $ = id => document.getElementById(id);

  $("p1Dept").addEventListener("change", e => {
    wizP1.deptId = e.target.value;
    const d = WIZ_MAIN_DEPTS.find(x => String(x.id) === String(wizP1.deptId));
    wizP1.deptName = d ? d.name_ar : "";
    wizP1.targetId = ""; wizP1.targetName = "";
    rerenderWizardContent();
  });
  $("p1Target").addEventListener("change", e => {
    wizP1.targetId = e.target.value;
    const sub = wizSubDepts(wizP1.deptId).find(x => String(x.id) === String(wizP1.targetId));
    wizP1.targetName = sub ? sub.name_ar : "";
    rerenderWizardContent();
  });
  $("p1Year").addEventListener("change", e => {
    wizP1.year = e.target.value;
    rerenderWizardContent();
  });
  $("p1Procedure").addEventListener("input", e => {
    wizP1.procedure = e.target.value;
    const box = document.getElementById("procedureBox");
    const txt = document.getElementById("procedureText");
    if (box && txt) { box.hidden = !e.target.value.trim(); txt.textContent = e.target.value; }
  });
  $("p1Reviewer").addEventListener("input", e => {
    wizP1.reviewer = e.target.value;
    const el = document.getElementById("mReviewer");
    if (el) el.textContent = e.target.value || "...............";
  });
  $("p1Email").addEventListener("input", e => {
    wizP1.email = e.target.value;
    const el = document.getElementById("mEmail");
    if (el) el.textContent = e.target.value || "........................";
  });
  $("p1Phone").addEventListener("input", e => {
    const digitsOnly = e.target.value.replace(/[^0-9]/g, "").slice(0, 10);
    e.target.value = digitsOnly;
    wizP1.phone = digitsOnly;
    const el = document.getElementById("mPhone");
    if (el) el.textContent = digitsOnly || "........................";
  });
  $("p1Director").addEventListener("input", e => {
    wizP1.director = e.target.value;
    const el = document.getElementById("mDirector");
    if (el) { el.hidden = !e.target.value.trim(); el.textContent = e.target.value; }
  });
}

/* ============================================================
   PAGE 2 — اتفاقية مستوى الخدمة
   ============================================================ */
function renderWizPage2() {
  const s = wizP2;
  const channels = [
    { key: "email", icon: "mail", label: "البريد الإلكتروني", ph: "أدخل عنوان البريد الإلكتروني", type: "email" },
    { key: "memo", icon: "message-square", label: "المذكرات الداخلية", ph: "أدخل تفاصيل المذكرات الداخلية", type: "textarea" },
    { key: "phone", icon: "phone", label: "الهاتف الداخلي", ph: "أدخل رقم الهاتف الداخلي", type: "tel" },
  ];

  return `
  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="file-text"></i>
      <h2 style="margin:0;">اتفاقية مستوى الخدمة</h2>
      <p style="margin:2px 0 0 6px;">— Service Level Agreement</p>
      <button type="button" class="obs-btn-pdf" id="wizP2ExportBtn" style="margin-right:auto;"><i data-lucide="file-text"></i> تصدير PDF</button>
    </div>
    <div class="wiz-sla-grid">
      <div class="wiz-field">
        <label class="wiz-label">الإدارة الخاضعة للمراجعة</label>
        <div class="msum-auto-field plain"><span class="val">${escapeHtml(s.subjectDept) || "— يُحدَّد تلقائيًا من الخطوة السابقة —"}</span></div>
      </div>
      <div class="wiz-field">
        <label class="wiz-label">تاريخ الاتفاقية</label>
        <input id="p2Date" type="date" class="wiz-input plain" value="${s.date}"
          onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
      </div>
      <div class="wiz-field span2">
        <label class="wiz-label">وصف الخدمة</label>
        <textarea id="p2Desc" rows="3" class="wiz-textarea plain">${escapeHtml(s.desc)}</textarea>
      </div>
    </div>

    <div class="wiz-channels">
      <p class="wiz-channels-title">قنوات الاتصال المعتمدة</p>
      ${channels.map(c => {
        const active = s.ch[c.key];
        return `<div class="wiz-channel ${active ? "active" : ""}">
          <div class="wiz-channel-head" data-ch-toggle="${c.key}">
            <span class="wiz-channel-check">${active ? '<i data-lucide="check"></i>' : ""}</span>
            <i class="ic" data-lucide="${c.icon}"></i>
            <span>${c.label}</span>
          </div>
          ${active ? `<div class="wiz-channel-body">
            ${c.type === "textarea"
              ? `<textarea id="wizCh-${c.key}" rows="3" class="wiz-textarea plain" data-ch-val="${c.key}" placeholder="${c.ph}">${escapeHtml(s.chVals[c.key])}</textarea>`
              : `<input id="wizCh-${c.key}" type="${c.type}" class="wiz-input plain" ${c.type === "email" || c.type === "tel" ? 'dir="ltr" style="text-align:left;"' : ""} ${c.type === "tel" ? 'inputmode="numeric" maxlength="10"' : ""} data-ch-val="${c.key}" placeholder="${c.ph}" value="${escapeHtml(s.chVals[c.key])}">`}
          </div>` : ""}
        </div>`;
      }).join("")}
    </div>
  </div>

  <div class="wiz-card">
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
          ${SLA_SECTIONS.map((sec, si) => `
            <tr class="wiz-sla-section-row"><td colspan="4"><span class="num">${si + 1}</span>${sec.title}</td></tr>
            ${sec.rows.map(row => `
              <tr class="wiz-sla-row">
                <td><div class="lbl"><span class="dot"></span><span>${row}</span></div></td>
                <td><div class="wiz-checkbox-visual"></div></td>
                <td><div class="wiz-checkbox-visual no"></div></td>
                <td><div class="wiz-note-line"></div></td>
              </tr>
            `).join("")}
          `).join("")}
        </tbody>
      </table>
    </div>
    <div class="wiz-table-footnote">تُملأ خانتا "موافق / غير موافق" من قِبل ممثل الإدارة المستهدفة عند الاستلام</div>
  </div>

  <div class="wiz-card">
    <div class="wiz-card-head"><i data-lucide="file-text"></i><span style="color:#fff;font-weight:700;font-size:14px;">التوقيعات</span></div>
    <div class="wiz-sig-grid">
      <div class="wiz-sig-card active">
        <p class="wiz-sig-title">المراجع الرئيسي</p>
        <div class="wiz-input-icon-wrap">
          <input id="p2SigName" type="text" class="wiz-input plain" placeholder="اسم المراجع الرئيسي" value="${escapeHtml(s.sigName)}">
        </div>
        <div>
          <p class="wiz-sig-mini-label">التاريخ</p>
          <input id="p2SigDate" type="date" class="wiz-input plain" value="${s.sigDate}"
            onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
      </div>
      <div class="wiz-sig-card locked">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <p class="wiz-sig-title">ممثل الإدارة</p>
          <span class="wiz-sig-locked-badge">تُملأ من قِبل الإدارة المستهدفة</span>
        </div>
        <div><p class="wiz-sig-mini-label">الاسم</p><div class="wiz-sig-name-line"><span class="bar"></span></div></div>
        <div><p class="wiz-sig-mini-label">التاريخ</p><div class="wiz-sig-blank-box solid"></div></div>
      </div>
    </div>
  </div>

  `;
}

function bindWizPage2() {
  const exportBtn2 = document.getElementById("wizP2ExportBtn");
  if (exportBtn2) exportBtn2.addEventListener("click", () => window.print());
  const $ = id => document.getElementById(id);
  const s = wizP2;

  $("p2Date").addEventListener("change", e => { s.date = e.target.value; rerenderWizardContent(); });
  $("p2Desc").addEventListener("input", e => { s.desc = e.target.value; autoGrowTextarea(e.target); });
  requestAnimationFrame(() => autoGrowTextarea($("p2Desc")));

  document.querySelectorAll("[data-ch-toggle]").forEach(el => {
    el.addEventListener("click", () => {
      const k = el.dataset.chToggle;
      s.ch[k] = !s.ch[k];
      rerenderWizardContent();
    });
  });
  document.querySelectorAll("[data-ch-val]").forEach(el => {
    if (el.tagName === "TEXTAREA") autoGrowTextarea(el);
    el.addEventListener("input", () => {
      let v = el.value;
      if (el.dataset.chVal === "phone") {
        v = v.replace(/[^0-9]/g, "").slice(0, 10);
        el.value = v;
      }
      s.chVals[el.dataset.chVal] = v;
      if (el.tagName === "TEXTAREA") autoGrowTextarea(el);
    });
  });

  const sigName = $("p2SigName");
  if (sigName) sigName.addEventListener("input", e => { s.sigName = e.target.value; });
  const sigDate = $("p2SigDate");
  if (sigDate) sigDate.addEventListener("change", e => { s.sigDate = e.target.value; rerenderWizardContent(); });
}

/* ============================================================
   PAGE 3 — قائمة المستندات المطلوبة
   (منظور إدارة المراجعة الداخلية: إضافة/حذف أسماء المستندات فقط،
   بينما "توجد/رفع/ملاحظات" مقفلة — تُملأ لاحقاً من الإدارة المستهدفة)
   ============================================================ */
function renderWizPage3() {
  const s = wizP3;
  return `
  <div class="wiz-card">
    <div class="wiz-card-head" style="justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i data-lucide="file-text"></i>
        <div><h2>قائمة المستندات المطلوبة</h2><p>Required Documents Checklist</p></div>
      </div>
      <button class="wiz-add-doc-btn" id="wizAddDocBtn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);"><i data-lucide="plus"></i> إضافة مستند</button>
    </div>
    <div class="wiz-table-wrap">
      <table class="wiz-doc-table">
        <thead><tr>
          <th style="width:60px;text-align:center;">الرقم</th>
          <th style="text-align:right;min-width:320px;">المستند</th>
          <th class="locked" style="width:170px;"><span style="display:flex;align-items:center;justify-content:center;gap:4px;">يوجد / لا يوجد</span></th>
          <th class="locked" style="width:180px;"><span style="display:flex;align-items:center;justify-content:center;gap:4px;">رفع الملف</span></th>
          <th class="locked" style="width:240px;text-align:right;"><span style="display:flex;align-items:center;gap:4px;">الملاحظات</span></th>
          <th style="width:44px;"></th>
        </tr></thead>
        <tbody>
          ${s.rows.length === 0 ? `
            <tr><td colspan="6">
              <div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>لا توجد مستندات — اضغط «إضافة مستند» لإضافة صف جديد</div>
            </td></tr>
          ` : s.rows.map((row, i) => `
            <tr>
              <td style="text-align:center;"><span class="wiz-doc-row-num">${i + 1}</span></td>
              <td>
                <input type="text" id="doc-${row.id}-name" class="wiz-doc-name-input ${s.touched && !row.name.trim() ? "err" : ""}"
                  data-doc-name="${row.id}" placeholder="أدخل اسم المستند..." value="${escapeHtml(row.name)}">
                ${s.touched && !row.name.trim() ? '<p class="wiz-error-text" style="padding:4px 4px 0;">اسم المستند مطلوب</p>' : ""}
              </td>
              <td style="text-align:center;background:#fafafa;">
                <div class="wiz-locked-cell">
                  <div class="inner" style="display:flex;justify-content:center;gap:8px;">
                    <span class="wiz-pill">يوجد</span><span class="wiz-pill">لا يوجد</span>
                  </div>
                  <div class="overlay"></div>
                </div>
              </td>
              <td style="text-align:center;background:#fafafa;">
                <div class="wiz-locked-cell">
                  <div class="inner"><span class="wiz-upload-pill"><i data-lucide="upload"></i> رفع</span></div>
                  <div class="overlay"></div>
                </div>
              </td>
              <td style="background:#fafafa;">
                <div class="wiz-locked-cell">
                  <div class="inner"><input type="text" class="wiz-doc-note-input" readonly placeholder="ملاحظة..."></div>
                  <div class="overlay"></div>
                </div>
              </td>
              <td style="text-align:center;"><button class="wiz-doc-del-btn" data-doc-del="${row.id}"><i data-lucide="trash-2"></i></button></td>
            </tr>
          `).join("")}
        </tbody>
      </table>
    </div>
    <div class="wiz-doc-footer">
      <span class="wiz-doc-footer-count">الإجمالي: <strong>${s.rows.length}</strong></span>
    </div>
  </div>
  `;
}

function bindWizPage3() {
  const s = wizP3;

  document.getElementById("wizAddDocBtn").addEventListener("click", () => {
    s.rows.push({ id: Date.now() + Math.random(), name: "", exists: null, fileName: "", note: "" });
    s.saved = false;
    rerenderWizardContent();
  });

  document.querySelectorAll("[data-doc-del]").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.docDel;
      s.rows = s.rows.filter(r => String(r.id) !== String(id));
      rerenderWizardContent();
    });
  });

  document.querySelectorAll("[data-doc-name]").forEach(inp => {
    inp.addEventListener("input", () => {
      const id = inp.dataset.docName;
      const row = s.rows.find(r => String(r.id) === String(id));
      if (row) {
        row.name = inp.value;
        rerenderWizardContent();
      }
    });
  });

}

/* ═══ تمدد تلقائي للـ textarea بدل ظهور شريط تمرير داخلي ═══ */
function autoGrowTextarea(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = (el.scrollHeight) + "px";
}
