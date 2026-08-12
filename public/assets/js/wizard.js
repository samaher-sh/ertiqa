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
let wizP1, wizP2;

function initWizardState() {
  const cy = new Date().getFullYear().toString();
  wizP1 = {
    deptId: "", deptName: "", targetId: "", targetName: "", year: cy, procedure: "",
    reviewer: "", director: "", email: "", phone: "", touched: false,
  };
  wizP2 = {
    subjectDept: "", date: "",
    desc: "تهدف هذه الخدمة إلى عقد اجتماعات المراجعة الداخلية مع الإدارات الخاضعة للمراجعة وتنفيذ العمليات المتعلقة بأعمال المراجعة حسب خطة المراجعة خلال تنسيق على أن تتم تنفيذ الخدمة وفق الجودة المتوقعة.",
    ch: { email: true, memo: true, phone: true },
    chVals: { email: "", memo: "", phone: "" },
    sigName: "", sigDate: "", sigSignature: "",
  };
  wizardPage = 1;
}
initWizardState();

function isPage1Valid() {
  const s = wizP1;
  return !!(s.deptId && s.targetId && s.procedure && s.reviewer && s.director && s.email && s.phone);
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
      ${wizardPage === 1 ? renderWizPage1() : renderWizPage2()}
    </div>
    ${renderWizardNav()}
  `;
}

function renderWizardSteps() {
  return `<div class="wiz-steps">
    ${WIZ_STEPS.map((st, i) => `
      <div class="wiz-step">
        <button class="wiz-step-btn" data-goto-step="${st.n}">
          <span class="wiz-step-circle ${wizardPage === st.n ? "current" : wizardPage > st.n ? "done" : ""}">
            ${wizardPage > st.n ? '<i data-lucide="check"></i>' : st.n}
          </span>
          <span class="wiz-step-label ${wizardPage === st.n ? "current" : wizardPage > st.n ? "done" : ""}">${st.label}</span>
        </button>
        ${i < WIZ_STEPS.length - 1 ? `<span class="wiz-step-line ${wizardPage > st.n ? "done" : ""}"></span>` : ""}
      </div>
    `).join("")}
  </div>`;
}

function renderWizardNav() {
  const isFirst = wizardPage === WIZ_STEPS[0].n;
  const isLast = wizardPage === WIZ_STEPS[WIZ_STEPS.length - 1].n;
  return `<div class="wiz-nav">
    <button class="wiz-btn wiz-btn-outline" id="wizPrevBtn" ${isFirst ? "disabled" : ""} style="min-width:150px;justify-content:center;">
      <i data-lucide="chevron-right"></i>السابق
    </button>
    <div class="wiz-dots">
      ${WIZ_STEPS.map(s => `<button class="wiz-dot ${wizardPage === s.n ? "current" : ""}" data-goto-step="${s.n}"></button>`).join("")}
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
    const idx = WIZ_STEPS.findIndex(s => s.n === wizardPage);
    wizardPage = WIZ_STEPS[Math.max(0, idx - 1)].n;
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
    const idx = WIZ_STEPS.findIndex(s => s.n === wizardPage);
    wizardPage = WIZ_STEPS[Math.min(WIZ_STEPS.length - 1, idx + 1)].n;
    if (wizardPage === 2 && !wizP2.subjectDept) { wizP2.subjectDept = wizP1.targetName; }
    rerenderWizardContent();
  });

  const sendBtn = document.getElementById("wizSendBtn");
  if (sendBtn) sendBtn.addEventListener("click", handleSendTask);

  if (wizardPage === 1) bindWizPage1();
  else bindWizPage2();
}

async function handleSendTask() {
  if (!isPage1Valid()) {
    wizP1.touched = true;
    wizardPage = 1;
    showToast("يرجى إكمال جميع حقول الخطوة الأولى", "error");
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
            ${err("email") ? '<p class="wiz-error-text">هذا الحقل مطلوب</p>' : '<p class="wiz-hint">يجب أن يحتوي على @، ويُكتب بأحرف وأرقام إنجليزية فقط</p>'}
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
          <div class="wiz-paper-watermark"><img src="${base}/assets/images/kamc.png" alt=""></div>
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
            <p class="wiz-p" style="font-weight:600;color:#4b5563;">السلام عليكم ورحمة الله وبركاته،،،</p>
            <p class="wiz-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة
              <mark class="wiz-mark small">${escapeHtml(s.targetName || "الإدارة المستهدفة")}</mark>،
              للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام
              <mark class="wiz-mark small">${escapeHtml(s.year)}</mark>م المعتمدة من قبل المدير العام التنفيذي.
            </p>
            <p class="wiz-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>
            <div class="wiz-procedure-box" id="procedureBox" ${s.procedure ? "" : "hidden"}>
              <div class="wiz-procedure-head"><i data-lucide="clipboard-list"></i><span>المراد مناقشته في الاجتماع</span></div>
              <p class="wiz-procedure-body" id="procedureText">${escapeHtml(s.procedure)}</p>
            </div>
            <p class="wiz-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
            <p class="wiz-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
            <p class="wiz-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark class="wiz-mark small" id="mReviewer">${escapeHtml(s.reviewer || "...............")}</mark></p>
            <p class="wiz-p" style="margin-bottom:2px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <div class="wiz-contact-row"><i data-lucide="mail"></i><span>البريد الإلكتروني:</span><span class="val" id="mEmail" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(s.email || "........................")}</span></div>
              <div class="wiz-contact-row"><i data-lucide="phone"></i><span>رقم الجوال:</span><span class="val" id="mPhone" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(s.phone || "........................")}</span></div>
            </div>
            <p class="wiz-p" style="font-weight:600;margin-top:12px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
            <p class="wiz-p" style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
            <p class="wiz-p" id="mDirector" style="font-weight:800;color:var(--pd);" ${s.director ? "" : "hidden"}>${escapeHtml(s.director)}</p>
          </div>
          <div class="wiz-paper-footer-bar"></div>
        </div>
      </div>
    </div>
  </div>
  `;
}

/* تصدير PDF لنموذج الخطاب الرسمي (صفحة 1) بحالته الحالية غير المحفوظة بعد --
   نفس نمط exportObservationToPDF() بصفحة الملاحظات بالضبط (نافذة طباعة مستقلة
   بمحتوى نظيف فقط، بدون شريط جانبي/عناصر تحكم التطبيق) لأن المهمة لسا ما اتحفظت
   بقاعدة البيانات (ما فيه mission_id بعد لاستخدام /dashboard/pdf/mission-letter
   الحقيقي، المخصص للمهام المُنشأة فعليًا) */
function exportWizP1ToPDF() {
  const printWindow = window.open("", "_blank");
  if (!printWindow) { showToast("يرجى السماح بالنوافذ المنبثقة للتصدير", "error"); return; }

  const s = wizP1;
  const todayD = new Date();
  const refNumber = String((todayD.getMonth() + 1) * 100 + todayD.getDate()).padStart(4, "0");
  const deptAbbr = DEPT_ABBR[s.deptName] || "AUD";
  const missionCode = deptAbbr + refNumber;

  printWindow.document.write(`
    <html dir="rtl">
      <head>
        <title>الخطاب الرسمي - ${escapeHtml(missionCode)}</title>
        <style>
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #152c33; line-height: 1.8; }
          .letterhead { display:flex; justify-content:space-between; align-items:center; gap:14px; border-bottom:2px solid #3185b3; padding-bottom:15px; margin-bottom:25px; }
          .letterhead-brand { display:flex; align-items:center; gap:12px; }
          .letterhead-brand img { height:40px; }
          .letterhead h1 { font-size: 16px; color: #196b7f; margin:0; }
          .letterhead .sub { font-size:11px; color:#6b8c95; margin:4px 0 0; }
          .letterhead-meta { text-align:left; font-size:12px; color:#4b5563; }
          .letterhead-meta p { margin:2px 0; }
          p { margin: 0 0 14px; font-size: 13px; }
          mark { background:#eaf4fa; color:#196b7f; padding:1px 6px; border-radius:4px; font-weight:700; }
          .procedure-box { background:#f8fbfd; border:1px solid #d8e6eb; border-radius:8px; padding:14px; margin-bottom:14px; }
          .procedure-box .head { font-weight:700; color:#196b7f; margin-bottom:6px; font-size:12px; }
          .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top:1px solid #d8e6eb; padding-top:15px; }
          @media print { body { padding: 0; } }
        </style>
      </head>
      <body>
        <div class="letterhead">
          <div class="letterhead-brand">
            <img src="${base}/assets/images/kamc.png" alt="مدينة الملك عبدالله الطبية">
            <div>
              <h1>إدارة المراجعة الداخلية</h1>
              <p class="sub">مدينة الملك عبدالله الطبية — نظام ارتقاء</p>
            </div>
          </div>
          <div class="letterhead-meta">
            <p>التاريخ: ${escapeHtml(todayD.toLocaleDateString("en-GB"))}</p>
            <p>رقم المهمة: <strong dir="ltr">${escapeHtml(missionCode)}</strong></p>
          </div>
        </div>
        <p style="font-weight:700;">سعادة المدير التنفيذي لـ${s.deptName ? `<mark>${escapeHtml(s.deptName)}</mark>` : ""} المحترم</p>
        <p style="font-weight:600;">السلام عليكم ورحمة الله وبركاته،،،</p>
        <p>نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة <mark>${escapeHtml(s.targetName || "الإدارة المستهدفة")}</mark>، للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام <mark>${escapeHtml(s.year)}</mark>م المعتمدة من قبل المدير العام التنفيذي.</p>
        <p>عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز (7) أيام عمل من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>
        ${s.procedure ? `<div class="procedure-box"><div class="head">المراد مناقشته في الاجتماع</div><p style="margin:0;">${escapeHtml(s.procedure)}</p></div>` : ""}
        <p>كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
        <p>حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
        <p>علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark>${escapeHtml(s.reviewer || "...............")}</mark></p>
        <p style="margin-bottom:4px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
        <p style="margin:0 0 4px;">البريد الإلكتروني: <span dir="ltr">${escapeHtml(s.email || "........................")}</span></p>
        <p>رقم الجوال: <span dir="ltr">${escapeHtml(s.phone || "........................")}</span></p>
        <p style="font-weight:600;margin-top:20px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
        <p style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
        ${s.director ? `<p style="font-weight:800;color:#196b7f;">${escapeHtml(s.director)}</p>` : ""}
        <div class="footer">تم إنشاء هذا المستند تلقائياً من نظام ارتقاء © ${new Date().getFullYear()}</div>
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

function bindWizPage1() {
  const exportBtn = document.getElementById("wizP1ExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", exportWizP1ToPDF);
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
    const lettersOnly = e.target.value.replace(/[0-9٠-٩]/g, "");
    e.target.value = lettersOnly;
    wizP1.reviewer = lettersOnly;
    const el = document.getElementById("mReviewer");
    if (el) el.textContent = lettersOnly || "...............";
  });
  $("p1Email").addEventListener("input", e => {
    // ما يسمح إلا بأحرف/أرقام إنجليزية ورموز البريد الإلكتروني القياسية (بدون عربي)
    const englishOnly = e.target.value.replace(/[^A-Za-z0-9@._+-]/g, "");
    e.target.value = englishOnly;
    wizP1.email = englishOnly;
    const el = document.getElementById("mEmail");
    if (el) el.textContent = englishOnly || "........................";
  });
  $("p1Phone").addEventListener("input", e => {
    const digitsOnly = e.target.value.replace(/[^0-9]/g, "").slice(0, 10);
    e.target.value = digitsOnly;
    wizP1.phone = digitsOnly;
    const el = document.getElementById("mPhone");
    if (el) el.textContent = digitsOnly || "........................";
  });
  $("p1Director").addEventListener("input", e => {
    const lettersOnly = e.target.value.replace(/[0-9٠-٩]/g, "");
    e.target.value = lettersOnly;
    wizP1.director = lettersOnly;
    const el = document.getElementById("mDirector");
    if (el) { el.hidden = !lettersOnly.trim(); el.textContent = lettersOnly; }
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
        <div class="msum-auto-field plain"></div>
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
                <td><div class="wiz-checkbox-visual readonly" title="يُعتمد لاحقًا من قِبل ممثل الإدارة المستهدفة"></div></td>
                <td><div class="wiz-checkbox-visual readonly" title="يُعتمد لاحقًا من قِبل ممثل الإدارة المستهدفة"></div></td>
                <td><div class="wiz-note-line"></div></td>
              </tr>
            `).join("")}
          `).join("")}
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
          <input id="p2SigName" type="text" class="wiz-input plain" placeholder="اسم المراجع الرئيسي" value="${escapeHtml(s.sigName)}">
        </div>
        <div>
          <p class="wiz-sig-mini-label">التاريخ</p>
          <input id="p2SigDate" type="date" class="wiz-input plain" value="${s.sigDate}"
            onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
        <div>
          <p class="wiz-sig-mini-label">التوقيع</p>
          <div class="wiz-sig-pad-card">
            <canvas id="p2SigPad" class="wiz-sig-pad-canvas" width="440" height="130"></canvas>
            ${!s.sigSignature ? `<span class="wiz-sig-pad-hint" id="p2SigPadHint"><i data-lucide="pen-line"></i> وقّع هنا</span>` : ""}
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

  `;
}

/* تصدير PDF لاتفاقية مستوى الخدمة (صفحة 2) بحالتها الحالية غير المحفوظة بعد --
   نفس السبب والنمط المستخدم بـ exportWizP1ToPDF() أعلاه بالضبط */
function exportWizP2ToPDF() {
  const printWindow = window.open("", "_blank");
  if (!printWindow) { showToast("يرجى السماح بالنوافذ المنبثقة للتصدير", "error"); return; }

  const s = wizP2;
  const channels = [
    { key: "email", label: "البريد الإلكتروني" },
    { key: "memo", label: "المذكرات الداخلية" },
    { key: "phone", label: "الهاتف الداخلي" },
  ];
  const activeChannels = channels.filter(c => s.ch[c.key]);

  const rowsHTML = SLA_SECTIONS.map((sec, si) => `
    <tr><td colspan="4" style="font-weight:700;color:#196b7f;padding:8px;border:1px solid #b3d4e5;background:#f0f7fa;">${si + 1}. ${escapeHtml(sec.title)}</td></tr>
    ${sec.rows.map(row => `
      <tr>
        <td style="padding:8px;border:1px solid #d8e6eb;">${escapeHtml(row)}</td>
        <td style="padding:8px;border:1px solid #d8e6eb;text-align:center;width:60px;">▢</td>
        <td style="padding:8px;border:1px solid #d8e6eb;text-align:center;width:60px;">▢</td>
        <td style="padding:8px;border:1px solid #d8e6eb;width:150px;"></td>
      </tr>
    `).join("")}
  `).join("");

  printWindow.document.write(`
    <html dir="rtl">
      <head>
        <title>اتفاقية مستوى الخدمة</title>
        <style>
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #152c33; line-height: 1.6; }
          .letterhead { display:flex; justify-content:space-between; align-items:center; gap:14px; border-bottom:2px solid #3185b3; padding-bottom:15px; margin-bottom:25px; }
          .letterhead-brand { display:flex; align-items:center; gap:12px; }
          .letterhead-brand img { height:40px; }
          .letterhead h1 { font-size: 16px; color: #196b7f; margin:0; }
          .letterhead .sub { font-size:11px; color:#6b8c95; margin:4px 0 0; }
          .letterhead-meta { text-align:left; font-size:11px; color:#4b5563; }
          .letterhead-meta p { margin:2px 0; }
          .info-row { display:flex; gap:30px; margin-bottom:20px; }
          .info-row .field { flex:1; }
          .label { font-size:11px; color:#6b8c95; font-weight:bold; display:block; margin-bottom:4px; }
          .value { font-size:13px; font-weight:600; }
          table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size:12px; }
          th { background: #f0f7fa; color: #196b7f; padding: 8px; border: 1px solid #b3d4e5; text-align:right; }
          .sig-grid { display:flex; gap:30px; margin-top:30px; }
          .sig-box { flex:1; border:1px solid #d8e6eb; border-radius:8px; padding:16px; font-size:13px; }
          .sig-box .t { font-weight:700; margin-bottom:10px; color:#196b7f; }
          .disclosure { margin-top:24px; padding:14px 16px; border-radius:8px; background:#eaf4fa; border:1px solid #b3d4e5; }
          .disclosure .t { font-weight:800; color:#196b7f; margin:0 0 8px; font-size:13px; }
          .disclosure p { margin:0; font-size:12px; line-height:1.9; }
          .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9ca3af; border-top:1px solid #d8e6eb; padding-top:15px; }
          @media print { body { padding: 0; } }
        </style>
      </head>
      <body>
        <div class="letterhead">
          <div class="letterhead-brand">
            <img src="${base}/assets/images/kamc.png" alt="مدينة الملك عبدالله الطبية">
            <div>
              <h1>إدارة المراجعة الداخلية</h1>
              <p class="sub">اتفاقية مستوى الخدمة — Service Level Agreement</p>
            </div>
          </div>
          <div class="letterhead-meta">
            <p>التاريخ: ${escapeHtml(new Date().toLocaleDateString("en-GB"))}</p>
          </div>
        </div>
        <div class="info-row">
          <div class="field"><span class="label">الإدارة الخاضعة للمراجعة</span><span class="value">${escapeHtml(s.subjectDept || "—")}</span></div>
          <div class="field"><span class="label">تاريخ الاتفاقية</span><span class="value">${escapeHtml(s.date || "—")}</span></div>
        </div>
        <p><strong>وصف الخدمة:</strong> ${escapeHtml(s.desc)}</p>
        <p><strong>قنوات الاتصال المعتمدة:</strong> ${activeChannels.length ? activeChannels.map(c => escapeHtml(c.label)).join("، ") : "—"}</p>
        <table>
          <thead><tr><th>الموضوع</th><th style="width:60px;">موافق</th><th style="width:60px;">غير موافق</th><th style="width:150px;">ملاحظات</th></tr></thead>
          <tbody>${rowsHTML}</tbody>
        </table>
        <div class="sig-grid">
          <div class="sig-box">
            <p class="t">المراجع الرئيسي</p>
            <p>الاسم: ${escapeHtml(s.sigName || "—")}</p>
            ${s.sigSignature ? `<img src="${s.sigSignature}" alt="التوقيع" style="max-width:180px;max-height:70px;display:block;margin:6px 0;">` : ""}
            <p>التاريخ: ${escapeHtml(s.sigDate || "—")}</p>
          </div>
          <div class="sig-box">
            <p class="t">ممثل الإدارة</p>
            <p style="color:#9ca3af;">تُملأ من قِبل الإدارة المستهدفة</p>
          </div>
        </div>
        <div class="disclosure">
          <p class="t">المسؤولية والإفصاح</p>
          <p>تؤكد إدارة المراجعة الداخلية، بأن جميع المعلومات المستلمة سوف تتعامل معها الإدارة بسرية عالية، وفقاً للمادة التاسعة عشرة من قرار مجلس الوزراء 129 بتاريخ 06/04/1428هـ اللائحة الموحدة لوحدات المراجعة الداخلية.</p>
        </div>
        <div class="footer">تم إنشاء هذا المستند تلقائياً من نظام ارتقاء © ${new Date().getFullYear()}</div>
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

function bindWizPage2() {
  const exportBtn2 = document.getElementById("wizP2ExportBtn");
  if (exportBtn2) exportBtn2.addEventListener("click", exportWizP2ToPDF);
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
      } else if (el.dataset.chVal === "email") {
        // ما يسمح إلا بأحرف/أرقام إنجليزية ورموز البريد الإلكتروني القياسية (بدون عربي)
        v = v.replace(/[^A-Za-z0-9@._+-]/g, "");
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

  const sigPad = $("p2SigPad");
  if (sigPad) msumInitSignaturePad(sigPad, s.sigSignature, dataUrl => {
    s.sigSignature = dataUrl;
    const hint = $("p2SigPadHint");
    if (hint) hint.style.display = "none";
  });
  const sigPadClear = $("p2SigPadClear");
  if (sigPadClear) sigPadClear.addEventListener("click", () => {
    s.sigSignature = "";
    const canvas = $("p2SigPad");
    if (canvas) canvas.getContext("2d").clearRect(0, 0, canvas.width, canvas.height);
    const hint = $("p2SigPadHint");
    if (hint) hint.style.display = "";
  });
}

/* ═══ تمدد تلقائي للـ textarea بدل ظهور شريط تمرير داخلي ═══ */
function autoGrowTextarea(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = (el.scrollHeight) + "px";
}
