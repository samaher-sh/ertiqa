/* ============================================================
   مراجعة المهمة — لمستخدمي الإدارة الخاضعة للمراجعة (dept_coordinator وما شابه)
   نفس تصميم "بدء مهمة" (3 خطوات: الخطاب، اتفاقية مستوى الخدمة، قائمة المستندات)
   لكن بصلاحيات مختلفة تمامًا لكل خطوة: عرض فقط / تعبئة فعلية / رفع مستندات فعلي.
   ============================================================ */
let mrSelectedTaskId = "";
let mrPage = 1;
let mrLoading = false;
let mrMission = null;
let mrAgreement = null;
let mrRows = [];
let mrDocRequests = [];
let mrCanEdit = false;
let mrSlaSaving = false;
let mrDocsSaving = false;

async function loadMissionReviewData(missionId) {
  if (!missionId) {
    mrMission = null; mrAgreement = null; mrRows = []; mrDocRequests = []; mrCanEdit = false;
    return;
  }
  mrLoading = true;
  try {
    const [data, docsData] = await Promise.all([
      apiGet(base + "/dashboard/target-mission/api/data?mission_id=" + encodeURIComponent(missionId)),
      apiGet(base + "/dashboard/document-requests/api/list?mission_id=" + encodeURIComponent(missionId)),
    ]);
    mrMission = data.mission || null;
    mrAgreement = data.agreement || null;
    mrRows = data.rows || [];
    mrDocRequests = docsData.requests || [];
    mrCanEdit = !!docsData.can_submit;
  } catch (e) {
    mrMission = null; mrAgreement = null; mrRows = []; mrDocRequests = [];
    mrCanEdit = false;
    showToast(e.message || "تعذّر تحميل بيانات المهمة", "error");
  }
  mrLoading = false;
}

function renderMissionReviewPage() {
  const locked = !mrSelectedTaskId;
  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(mrSelectedTaskId, "mrTaskSelect")}
    <div class="mc-locked-wrap ${locked ? "locked" : ""}">
      ${mrLoading ? `<p class="dr-empty">جارِ التحميل...</p>` : `
        <div class="wiz-steps">
          ${STEPS.map((st, i) => `
            <div class="wiz-step">
              <button class="wiz-step-btn" data-mr-goto-step="${st.n}">
                <span class="wiz-step-circle ${mrPage === st.n ? "current" : mrPage > st.n ? "done" : ""}">
                  ${mrPage > st.n ? '<i data-lucide="check"></i>' : st.n}
                </span>
                <span class="wiz-step-label ${mrPage === st.n ? "current" : mrPage > st.n ? "done" : ""}">${st.label}</span>
              </button>
              ${i < STEPS.length - 1 ? `<span class="wiz-step-line ${mrPage > st.n ? "done" : ""}"></span>` : ""}
            </div>
          `).join("")}
        </div>
        <div class="wiz-page-container">
          ${mrPage === 1 ? renderMrPage1() : mrPage === 2 ? renderMrPage2() : renderMrPage3()}
        </div>
      `}
    </div>
  </div>`;
}

/* ============================================================
   الخطوة 1 — نموذج الخطاب الرسمي (عرض فقط بالكامل)
   ============================================================ */
function renderMrPage1() {
  const m = mrMission || {};
  const created = (m.created_at || "").slice(0, 10);
  return `
  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="file-text"></i>
      <div><h2>نموذج الخطاب الرسمي</h2><p>عرض فقط</p></div>
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
              <p>التاريخ: ${escapeHtml(created)}</p>
              <p>رقم المهمة: <strong dir="ltr" style="display:inline-block;">${escapeHtml(m.mission_code || "")}</strong></p>
            </div>
          </div>
          <div class="wiz-divider-fade"></div>
          <p class="wiz-p" style="font-weight:700;color:#1f2937;">
            سعادة المدير التنفيذي لـ<mark class="wiz-mark">${escapeHtml(m.target_department_name || "")}</mark> المحترم
          </p>
          <p class="wiz-p" style="font-weight:600;color:#4b5563;">السلام عليكم ورحمة الله وبركاته،،،</p>
          <p class="wiz-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة
            <mark class="wiz-mark small">${escapeHtml(m.target_department_name || "")}</mark>،
            للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام
            <mark class="wiz-mark small">${escapeHtml(String(m.year || ""))}</mark>م المعتمدة من قبل المدير العام التنفيذي.
          </p>
          <p class="wiz-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>
          <div class="wiz-procedure-box" ${m.procedure_note ? "" : "hidden"}>
            <div class="wiz-procedure-head"><i data-lucide="clipboard-list"></i><span>المراد مناقشته في الاجتماع</span></div>
            <p class="wiz-procedure-body">${escapeHtml(m.procedure_note || "")}</p>
          </div>
          <p class="wiz-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
          <p class="wiz-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
          <p class="wiz-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark class="wiz-mark small">${escapeHtml(m.reviewer_name || "")}</mark></p>
          <p class="wiz-p" style="margin-bottom:2px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <div class="wiz-contact-row"><i data-lucide="mail"></i><span>البريد الإلكتروني:</span><span class="val" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(m.reviewer_email || "")}</span></div>
            <div class="wiz-contact-row"><i data-lucide="phone"></i><span>رقم الجوال:</span><span class="val" dir="ltr" style="unicode-bidi:embed;">${escapeHtml(m.reviewer_phone || "")}</span></div>
          </div>
          <p class="wiz-p" style="font-weight:600;margin-top:12px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
          <p class="wiz-p" style="font-weight:600;margin-top:4px;">مدير إدارة المراجعة الداخلية</p>
          <p class="wiz-p" style="font-weight:800;color:var(--pd);" ${m.director_name ? "" : "hidden"}>${escapeHtml(m.director_name || "")}</p>
        </div>
        <div class="wiz-paper-footer-bar"></div>
      </div>
    </div>
  </div>`;
}

/* ============================================================
   الخطوة 2 — اتفاقية مستوى الخدمة (قابلة للتعبئة فعليًا من طرف الإدارة)
   ============================================================ */
function renderMrPage2() {
  const a = mrAgreement || {};
  const rowsBySection = {};
  mrRows.forEach(r => {
    rowsBySection[r.section_title] = rowsBySection[r.section_title] || [];
    rowsBySection[r.section_title].push(r);
  });

  // قنوات الاتصال المعتمدة اللي عبّاها فريق المراجعة وقت إنشاء المهمة (خطوة 2
  // بويزارد "بدء مهمة") -- عرض فقط هنا دائمًا، لا تُعبَّأ من الإدارة المستهدفة
  const mrChannels = [
    { active: a.channel_email, value: a.channel_email_value, icon: "mail", label: "البريد الإلكتروني" },
    { active: a.channel_memo, value: a.channel_memo_value, icon: "message-square", label: "المذكرات الداخلية" },
    { active: a.channel_phone, value: a.channel_phone_value, icon: "phone", label: "الهاتف الداخلي" },
  ].filter(c => Number(c.active) === 1 && c.value);

  return `
  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="file-text"></i>
      <h2 style="margin:0;">اتفاقية مستوى الخدمة</h2>
      <p style="margin:2px 0 0 6px;">— Service Level Agreement</p>
      ${a.status === "submitted" ? `<span class="dr-status-badge yes" style="margin-right:auto;">تم الإرسال</span>` : ""}
    </div>

    <div class="wiz-sla-grid" style="padding:20px 24px;">
      <div class="wiz-field">
        <label class="wiz-label">اسم المنسّق <span class="wiz-req">*</span></label>
        <input id="mrCoordName" type="text" class="wiz-input plain" placeholder="اسم منسّق التواصل" value="${escapeHtml(a.coordinator_name || "")}" ${mrCanEdit ? "" : "readonly"}>
      </div>
      <div class="wiz-field">
        <label class="wiz-label">البريد الإلكتروني للمنسّق</label>
        <input id="mrCoordEmail" type="email" dir="ltr" style="text-align:left;" class="wiz-input plain" placeholder="example@kamc.med.sa" value="${escapeHtml(a.coordinator_email || "")}" ${mrCanEdit ? "" : "readonly"}>
      </div>
      <div class="wiz-field">
        <label class="wiz-label">رقم جوال المنسّق</label>
        <input id="mrCoordPhone" type="tel" dir="ltr" style="text-align:left;" class="wiz-input plain" placeholder="05XXXXXXXX" value="${escapeHtml(a.coordinator_phone || "")}" ${mrCanEdit ? "" : "readonly"}>
      </div>
    </div>

    <div class="wiz-channels" style="padding:0 24px 20px;">
      <p class="wiz-channels-title">قنوات الاتصال المعتمدة</p>
      ${mrChannels.length === 0 ? `<p class="fr-preview-empty" style="padding:0;">لم تُحدَّد قنوات اتصال</p>` : mrChannels.map(c => `
        <div class="wiz-channel active">
          <div class="wiz-channel-head" style="cursor:default;">
            <span class="wiz-channel-check"><i data-lucide="check"></i></span>
            <i class="ic" data-lucide="${c.icon}"></i>
            <span>${c.label}</span>
          </div>
          <div class="wiz-channel-body">
            <div class="msum-auto-field plain" ${c.icon === "mail" || c.icon === "phone" ? 'dir="ltr" style="justify-content:flex-end;"' : ""}>${escapeHtml(c.value)}</div>
          </div>
        </div>
      `).join("")}
    </div>
  </div>

  <div class="wiz-card">
    <div class="wiz-card-head">
      <i data-lucide="clipboard-list"></i>
      <span style="color:#fff;font-weight:700;font-size:14px;">بنود الاتفاقية</span>
      <p style="margin-right:auto;font-size:11px;">${mrCanEdit ? "وافق أو لا توافق على كل بند، وأضف ملاحظة إن وجدت" : "عرض فقط"}</p>
    </div>
    <div class="wiz-table-wrap">
      <table class="wiz-table">
        <thead><tr>
          <th>الموضوع</th><th class="center">موافق</th><th class="center">غير موافق</th><th style="width:220px;">ملاحظات إن وجد</th>
        </tr></thead>
        <tbody>
          ${Object.keys(rowsBySection).map((title, si) => `
            <tr class="wiz-sla-section-row"><td colspan="4"><span class="num">${si + 1}</span>${escapeHtml(title)}</td></tr>
            ${rowsBySection[title].map(row => `
              <tr class="wiz-sla-row ${mrCanEdit && !Number(row.agree) && !Number(row.disagree) ? "mr-row-unanswered" : ""}" data-row-id="${row.id}">
                <td><div class="lbl"><span class="dot"></span><span>${escapeHtml(row.row_text)}</span></div></td>
                <td style="text-align:center;">
                  <div class="wiz-checkbox-visual mr-toggle ${Number(row.agree) ? "checked" : ""}" data-mr-agree="${row.id}" ${mrCanEdit ? "" : "style=\"pointer-events:none;\""}>
                    ${Number(row.agree) ? '<i data-lucide="check" style="width:14px;height:14px;color:var(--p);"></i>' : ""}
                  </div>
                </td>
                <td style="text-align:center;">
                  <div class="wiz-checkbox-visual no mr-toggle ${Number(row.disagree) ? "checked" : ""}" data-mr-disagree="${row.id}" ${mrCanEdit ? "" : "style=\"pointer-events:none;\""}>
                    ${Number(row.disagree) ? '<i data-lucide="x" style="width:14px;height:14px;color:#dc2626;"></i>' : ""}
                  </div>
                </td>
                <td>
                  <input type="text" class="mr-sla-note-input" data-mr-note="${row.id}" placeholder="ملاحظة..." value="${escapeHtml(row.note || "")}" ${mrCanEdit ? "" : "readonly"}>
                </td>
              </tr>
            `).join("")}
          `).join("")}
        </tbody>
      </table>
    </div>
    ${mrCanEdit ? `
      <div class="dr-footer">
        <button type="button" class="dr-submit-btn" id="mrSaveAgreementBtn" ${mrSlaSaving ? "disabled" : ""}>
          <i data-lucide="check"></i> ${mrSlaSaving ? "جارِ الحفظ..." : "حفظ الاتفاقية"}
        </button>
      </div>
    ` : ""}
  </div>`;
}

/* ============================================================
   الخطوة 3 — قائمة المستندات (نفس عمود اسم المستند للعرض، وبقية الأعمدة
   فعليًا قابلة للاستخدام من طرف الإدارة الخاضعة للمراجعة)
   ============================================================ */
function renderMrPage3() {
  return `
  <div class="wiz-card">
    <div class="wiz-card-head" style="justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i data-lucide="file-text"></i>
        <div><h2>قائمة المستندات المطلوبة</h2><p>Required Documents Checklist</p></div>
      </div>
    </div>
    <div class="wiz-table-wrap">
      <table class="wiz-doc-table">
        <thead><tr>
          <th style="width:60px;text-align:center;">الرقم</th>
          <th style="text-align:right;min-width:260px;">المستند</th>
          <th style="width:170px;text-align:center;">يوجد / لا يوجد</th>
          <th style="width:200px;text-align:center;">رفع الملف</th>
          <th style="width:240px;text-align:right;">الملاحظات</th>
        </tr></thead>
        <tbody>
          ${mrDocRequests.length === 0 ? `
            <tr><td colspan="5"><div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>لا توجد مستندات مطلوبة لهذه المهمة</div></td></tr>
          ` : mrDocRequests.map((r, i) => `
            <tr data-mr-doc-row="${r.id}">
              <td style="text-align:center;"><span class="wiz-doc-row-num">${i + 1}</span></td>
              <td><input type="text" class="wiz-doc-name-input" value="${escapeHtml(r.doc_name)}" readonly></td>
              <td style="text-align:center;">
                ${mrCanEdit ? `
                  <div class="mr-exists-toggle">
                    <label class="dr-radio"><input type="radio" name="mr-exists-${r.id}" value="1" ${Number(r.exists_flag) === 1 ? "checked" : ""}> يوجد</label>
                    <label class="dr-radio"><input type="radio" name="mr-exists-${r.id}" value="0" ${r.exists_flag !== null && Number(r.exists_flag) === 0 ? "checked" : ""}> لا يوجد</label>
                  </div>
                ` : `<span class="wiz-pill">${r.exists_flag === null || r.exists_flag === undefined ? "—" : (Number(r.exists_flag) ? "يوجد" : "لا يوجد")}</span>`}
              </td>
              <td style="text-align:center;">
                ${mrCanEdit ? `
                  <input type="file" class="mr-doc-file-input" id="mr-file-${r.id}">
                ` : ""}
                ${r.file ? `<a class="dr-file-link" href="${base}/dashboard/documents/download/${r.file.id}" target="_blank"><i data-lucide="paperclip"></i> ${escapeHtml(r.file.file_name)}</a>` : (mrCanEdit ? "" : "<span class=\"wiz-pill\">لا يوجد ملف</span>")}
              </td>
              <td>
                <input type="text" class="wiz-doc-note-input" data-mr-doc-note="${r.id}" placeholder="ملاحظة..." value="${escapeHtml(r.response_note || "")}" ${mrCanEdit ? "" : "readonly"}>
              </td>
            </tr>
          `).join("")}
        </tbody>
      </table>
    </div>
    ${mrCanEdit && mrDocRequests.length > 0 ? `
      <div class="dr-footer">
        <button type="button" class="dr-submit-btn" id="mrSubmitDocsBtn" ${mrDocsSaving ? "disabled" : ""}>
          <i data-lucide="upload"></i> ${mrDocsSaving ? "جارِ الإرسال..." : "إرسال المستندات"}
        </button>
      </div>
    ` : ""}
  </div>`;
}

/* ============================================================
   ربط الأحداث
   ============================================================ */
function bindMissionReviewEvents() {
  const taskSelect = document.getElementById("mrTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    mrSelectedTaskId = e.target.value;
    mrPage = 1;
    await loadMissionReviewData(mrSelectedTaskId);
    rerenderMRContent();
  });

  document.querySelectorAll("[data-mr-goto-step]").forEach(btn => {
    btn.addEventListener("click", () => {
      mrPage = parseInt(btn.dataset.mrGotoStep, 10);
      rerenderMRContent();
    });
  });

  if (mrPage === 2) bindMrPage2Events();
  if (mrPage === 3) bindMrPage3Events();

  if (taskSelect && taskSelect.options.length === 2 && !mrSelectedTaskId) {
    taskSelect.selectedIndex = 1;
    taskSelect.dispatchEvent(new Event("change"));
  }
}

function bindMrPage2Events() {
  if (!mrCanEdit) return;
  mrAgreement = mrAgreement || {};

  // مربوطة بالحالة (مو قراءة مباشرة من DOM وقت الحفظ بس) لأن أزرار موافق/غير موافق
  // تستدعي rerenderMRContent() كاملة، ولو ما كانت هذي الحقول مربوطة بالحالة كان
  // أي ضغطة عليها بتمسح أي اسم/بريد/جوال لسا المستخدم يكتبه قبل الحفظ
  const coordName = document.getElementById("mrCoordName");
  if (coordName) coordName.addEventListener("input", e => { mrAgreement.coordinator_name = e.target.value; });
  const coordEmail = document.getElementById("mrCoordEmail");
  if (coordEmail) coordEmail.addEventListener("input", e => { mrAgreement.coordinator_email = e.target.value; });
  const coordPhone = document.getElementById("mrCoordPhone");
  if (coordPhone) coordPhone.addEventListener("input", e => { mrAgreement.coordinator_phone = e.target.value; });

  document.querySelectorAll("[data-mr-agree]").forEach(el => {
    el.addEventListener("click", () => {
      const row = mrRows.find(r => String(r.id) === String(el.dataset.mrAgree));
      if (!row) return;
      row.agree = row.agree ? 0 : 1;
      if (row.agree) row.disagree = 0;
      rerenderMRContent();
    });
  });
  document.querySelectorAll("[data-mr-disagree]").forEach(el => {
    el.addEventListener("click", () => {
      const row = mrRows.find(r => String(r.id) === String(el.dataset.mrDisagree));
      if (!row) return;
      row.disagree = row.disagree ? 0 : 1;
      if (row.disagree) row.agree = 0;
      rerenderMRContent();
    });
  });
  document.querySelectorAll("[data-mr-note]").forEach(el => {
    el.addEventListener("input", () => {
      const row = mrRows.find(r => String(r.id) === String(el.dataset.mrNote));
      if (row) row.note = el.value;
    });
  });

  const saveBtn = document.getElementById("mrSaveAgreementBtn");
  if (saveBtn) saveBtn.addEventListener("click", async () => {
    const coordName = (mrAgreement.coordinator_name || "").trim();
    if (!coordName) {
      showToast("يرجى إدخال اسم المنسّق.", "error");
      const coordNameInput = document.getElementById("mrCoordName");
      if (coordNameInput) { coordNameInput.focus(); coordNameInput.classList.add("err"); }
      return;
    }

    // لازم رد فعلي (موافق أو غير موافق) على كل بند قبل الإرسال -- وإلا الباك-إند
    // يرفض الطلب أصلًا الآن، لكن نتحقق هنا أول عشان المستخدم يشوف بالضبط وين
    // البنود الناقصة بدل ما ينتظر رحلة الشبكة كاملة عشان يعرف
    const unanswered = mrRows.filter(r => !r.agree && !r.disagree);
    if (unanswered.length > 0) {
      showToast("يرجى الرد (موافق أو غير موافق) على كل بند بالاتفاقية قبل الإرسال.", "error");
      const firstRow = document.querySelector(`[data-mr-agree="${unanswered[0].id}"]`);
      const firstRowEl = firstRow && firstRow.closest(".wiz-sla-row");
      if (firstRowEl && typeof firstRowEl.scrollIntoView === "function") {
        firstRowEl.scrollIntoView({ behavior: "smooth", block: "center" });
      }
      return;
    }

    mrSlaSaving = true;
    rerenderMRContent();
    try {
      const res = await apiPost(base + "/dashboard/target-mission/api/save-agreement", {
        mission_id: mrSelectedTaskId,
        coordinator_name: coordName,
        coordinator_email: (mrAgreement.coordinator_email || "").trim(),
        coordinator_phone: (mrAgreement.coordinator_phone || "").trim(),
        rows: mrRows.map(r => ({ id: r.id, agree: r.agree, disagree: r.disagree, note: r.note })),
      });
      if (!res || !res.success) throw new Error(res && res.message || "تعذّر حفظ الاتفاقية");
      showToast("تم حفظ اتفاقية مستوى الخدمة بنجاح", "success");
      await loadMissionReviewData(mrSelectedTaskId);
    } catch (e) {
      showToast(e.message || "تعذّر حفظ الاتفاقية", "error");
    }
    mrSlaSaving = false;
    rerenderMRContent();
  });
}

function bindMrPage3Events() {
  if (!mrCanEdit) return;

  const submitBtn = document.getElementById("mrSubmitDocsBtn");
  if (submitBtn) submitBtn.addEventListener("click", async () => {
    const formData = new FormData();
    formData.append("mission_id", mrSelectedTaskId);
    let i = 0;
    document.querySelectorAll("[data-mr-doc-row]").forEach(row => {
      const reqId = row.dataset.mrDocRow;
      const checked = row.querySelector(`input[name="mr-exists-${reqId}"]:checked`);
      formData.append(`responses[${i}][document_request_id]`, reqId);
      formData.append(`responses[${i}][exists_flag]`, checked ? checked.value : "");
      formData.append(`responses[${i}][note]`, row.querySelector(`[data-mr-doc-note="${reqId}"]`).value.trim());
      const fileInput = document.getElementById("mr-file-" + reqId);
      if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append("file_" + reqId, fileInput.files[0]);
      }
      i++;
    });

    mrDocsSaving = true;
    rerenderMRContent();
    try {
      const res = await apiPostFile(base + "/dashboard/document-requests/api/submit", formData);
      if (!res || !res.success) throw new Error(res && res.message || "تعذّر إرسال المستندات");
      showToast("تم إرسال المستندات بنجاح", "success");
      await loadMissionReviewData(mrSelectedTaskId);
    } catch (e) {
      showToast(e.message || "تعذّر إرسال المستندات", "error");
    }
    mrDocsSaving = false;
    rerenderMRContent();
  });
}

function rerenderMRContent() {
  const ca = document.getElementById("contentArea");
  if (!ca) return;
  ca.innerHTML = renderMissionReviewPage();
  bindMissionReviewEvents();
  lucide.createIcons();
}
