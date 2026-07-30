/* ============================================================
   ملخص الاجتماع (Meeting Summary) — متصل بالـ API الحقيقي
   ============================================================ */

let msumSelectedTaskId = "";
let msumDirty = false;
let msumLoading = false;

let msumDate = "", msumTime = "", msumLocation = "";
let msumDept = "", msumTitle = "", msumObjective = "";
let msumAttendance = [{ id: 1, name: "", dept: "", position: "" }];
let msumSummaryPoints = [{ id: 1, text: "", opinion: "", reason: "" }];
let msumApprovals = [{ id: 1, statement: "إعداد واعتماد", name: "", position: "رئيس المهمة", date: "", signature: "" }];
let msumAttachments = [];

const msumIsHrUser = () => isHrDept || isHrCoordinator;
const msumAllReadOnly = () => isAuditHead;

function escHtmlMSum(str) {
  return String(str == null ? "" : str)
    .replace(/&/g, "&amp;").replace(/"/g, "&quot;")
    .replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function rerenderMSumContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  ca.innerHTML = renderMeetingSummaryPage();
  bindMeetingSummaryEvents();
  lucide.createIcons();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) { el.focus(); if (selStart !== null && el.setSelectionRange) { try { el.setSelectionRange(selStart, selEnd); } catch (e) {} } }
  }
  if (ca) ca.scrollTop = scrollTop;
}

async function msumLoadData(missionId) {
  msumLoading = true;
  const mission = missionsForSelector.find(m => String(m.id) === String(missionId));
  msumDept = mission ? (mission.target_department_name || "") : "";

  try {
    const [dataRes, attRes] = await Promise.all([
      fetch(base + "/dashboard/meetings/api/data?mission_id=" + missionId),
      fetch(base + "/dashboard/meetings/api/attachments?mission_id=" + missionId),
    ]);
    const data = await dataRes.json();
    const attData = await attRes.json();

    const m = data.meeting || {};
    msumDate = m.meeting_date || "";
    msumTime = m.meeting_time || "";
    msumLocation = m.location || "";
    msumTitle = m.title || "";
    msumObjective = m.objective || "";

    msumAttendance = (data.attendees || []).map((a, i) => ({ id: a.id || (i + 1), name: a.external_name || "", dept: a.attendee_dept || "", position: a.attendee_position || "" }));
    msumSummaryPoints = (data.points || []).map((p, i) => ({ id: p.id || (i + 1), text: p.point_text || "", opinion: p.opinion || "", reason: p.reason || "" }));
    msumApprovals = (data.approvals || []).map((a, i) => ({ id: a.id || (i + 1), statement: a.statement || "إعداد واعتماد", name: a.signer_name || "", position: a.position || "رئيس المهمة", date: a.approval_date || "", signature: a.signature_data || "" }));
    msumAttachments = attData.documents || [];
  } catch (e) {
    msumAttendance = [{ id: 1, name: "", dept: "", position: "" }];
    msumSummaryPoints = [{ id: 1, text: "", opinion: "", reason: "" }];
    msumApprovals = [{ id: 1, statement: "إعداد واعتماد", name: "", position: "رئيس المهمة", date: "", signature: "" }];
    msumAttachments = [];
  }
  msumDirty = false;
  msumLoading = false;
}

/* ============================================================
   لوحة توقيع بسيطة (Canvas)
   ============================================================ */
function attachSignaturePad(canvasId, wrapId, onSign) {
  const canvas = document.getElementById(canvasId);
  const wrap = document.getElementById(wrapId);
  if (!canvas) return;
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width; canvas.height = rect.height;
  const ctx = canvas.getContext("2d");
  ctx.lineWidth = 2; ctx.lineCap = "round"; ctx.lineJoin = "round"; ctx.strokeStyle = "#152c33";
  let drawing = false, hasSig = false;

  const pos = e => {
    const r = canvas.getBoundingClientRect();
    const t = e.touches ? e.touches[0] : e;
    return { x: t.clientX - r.left, y: t.clientY - r.top };
  };
  const start = e => { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
  const draw = e => {
    if (!drawing) return;
    if (e.cancelable) e.preventDefault();
    const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke();
    if (!hasSig) { hasSig = true; if (wrap) wrap.classList.add("has-sig"); }
  };
  const end = () => { if (!drawing) return; drawing = false; if (hasSig) onSign(canvas.toDataURL()); };

  canvas.addEventListener("mousedown", start);
  canvas.addEventListener("mousemove", draw);
  canvas.addEventListener("mouseup", end);
  canvas.addEventListener("mouseleave", end);
  canvas.addEventListener("touchstart", start);
  canvas.addEventListener("touchmove", draw);
  canvas.addEventListener("touchend", end);
}

/* ============================================================
   الحاوية العامة
   ============================================================ */
function msumVisibleMissions() {
  if (!msumIsHrUser()) return missionsForSelector;
  const deptId = currentUser && currentUser.department_id;
  return missionsForSelector.filter(m => String(m.target_department_id) === String(deptId));
}

function renderMeetingSummaryPage() {
  const hrUser = msumIsHrUser();
  const allReadOnly = msumAllReadOnly();
  const locked = !msumSelectedTaskId;
  const visibleTasks = msumVisibleMissions();

  return `
  <div class="flex flex-col gap-5">
    ${renderLinkedTaskSelector(msumSelectedTaskId, "msumTaskSelect", visibleTasks)}

    <div class="msum-locked-wrap ${locked ? "locked" : ""}">

      <!-- 1. بيانات الاجتماع -->
      <div class="wiz-card">
        <div class="wiz-card-head">
          <i data-lucide="file-text"></i>
          <div><h2>ملخص الاجتماع</h2><p>Meeting Summary</p></div>
          ${allReadOnly ? `<span class="msum-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span>` : ""}
          <button class="obs-btn-pdf" id="msumExportBtn" style="margin-right:auto;" ${locked ? "disabled" : ""}><i data-lucide="file-text"></i> تصدير PDF</button>
        </div>
        ${hrUser ? `<div class="msum-auto-banner"><span><i data-lucide="zap"></i> الإدارة محل المراجعة تُملأ تلقائياً من المهمة المرتبطة</span></div>` : ""}

        <div class="wiz-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="wiz-field">
            <label class="wiz-label">تاريخ الاجتماع</label>
            <input id="msumDate" type="date" class="wiz-input plain" value="${msumDate}" ${allReadOnly ? "readonly" : ""} onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
          </div>
          <div class="wiz-field">
            <label class="wiz-label">الوقت</label>
            <input id="msumTime" type="time" class="wiz-input plain" value="${msumTime}" ${allReadOnly ? "readonly" : ""} onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
          </div>
          <div class="wiz-field">
            <label class="wiz-label">مكان الاجتماع</label>
            <textarea id="msumLocation" rows="1" class="wiz-textarea plain msum-growfield" placeholder="أدخل مكان الاجتماع" ${allReadOnly ? "readonly" : ""}>${escHtmlMSum(msumLocation)}</textarea>
          </div>

          <div class="wiz-field">
            <label class="wiz-label">الإدارة محل المراجعة</label>
            <div class="msum-auto-field hr"><span class="val">${escHtmlMSum(msumDept) || "— اختر المهمة أولاً —"}</span></div>
          </div>

          <div class="wiz-field" style="grid-column:1/-1;">
            <label class="wiz-label">عنوان المهمة</label>
            <div class="msum-edit-wrap"><textarea id="msumTitle" rows="1" class="msum-growfield" placeholder="عنوان مهمة المراجعة" ${allReadOnly ? "readonly" : ""}>${escHtmlMSum(msumTitle)}</textarea></div>
          </div>

          <div class="wiz-field" style="grid-column:1/-1;">
            <label class="wiz-label">الهدف من الاجتماع</label>
            <textarea id="msumObjective" rows="2" class="wiz-textarea plain" ${allReadOnly ? "readonly" : ""}>${escHtmlMSum(msumObjective)}</textarea>
          </div>
        </div>
      </div>

      <!-- 2. جدول الحضور -->
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;"><i data-lucide="users"></i><span style="color:#fff;font-weight:700;font-size:14px;">جدول الحضور</span></div>
          ${!allReadOnly ? `<button class="msum-attach-btn" id="msumAddAttendanceBtn" style="padding:6px 12px;font-size:12px;box-shadow:none;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);"><i data-lucide="plus" style="width:14px;height:14px;"></i> إضافة حضور</button>` : ""}
        </div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr><th class="c" style="width:50px;">الرقم</th><th>الاسم</th><th>الإدارة</th><th>الوظيفة</th><th style="width:40px;"></th></tr></thead>
            <tbody>
              ${msumAttendance.map((row, i) => `
                <tr>
                  <td class="msum-row-num">${i + 1}</td>
                  <td><input type="text" id="att-${row.id}-name" class="msum-plain-input" placeholder="أدخل الاسم" data-att-field="name" data-att-id="${row.id}" value="${escHtmlMSum(row.name)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td><input type="text" id="att-${row.id}-dept" class="msum-plain-input" placeholder="أدخل الإدارة" data-att-field="dept" data-att-id="${row.id}" value="${escHtmlMSum(row.dept)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td><input type="text" id="att-${row.id}-position" class="msum-plain-input" placeholder="أدخل الوظيفة" data-att-field="position" data-att-id="${row.id}" value="${escHtmlMSum(row.position)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td style="text-align:center;">${!allReadOnly ? `<button class="msum-del-btn" data-att-del="${row.id}"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button>` : ""}</td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      </div>

      <!-- 3. ملخص ما تم مناقشته -->
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;">
            <i data-lucide="message-square"></i><span style="color:#fff;font-weight:700;font-size:14px;">ملخص ما تم مناقشته خلال الاجتماع</span>
            ${hrUser ? `<span class="msum-auto-chip" style="background:rgba(255,255,255,.2);color:#fff;border:none;"><i data-lucide="lock"></i>النقاط تلقائية</span>` : ""}
          </div>
          ${(!hrUser && !allReadOnly) ? `<button class="msum-attach-btn" id="msumAddPointBtn" style="padding:6px 12px;font-size:12px;box-shadow:none;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);"><i data-lucide="plus" style="width:14px;height:14px;"></i> إضافة نقطة</button>` : ""}
        </div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr>
              <th style="width:50%;">النقطة</th><th>الرأي</th><th style="width:33%;">السبب / التوضيح</th>
              ${(!hrUser && !allReadOnly) ? '<th style="width:40px;"></th>' : ""}
            </tr></thead>
            <tbody>
              ${msumSummaryPoints.map((pt, i) => `
                <tr>
                  <td>
                    ${hrUser
                      ? `<div class="msum-point-hr-box"><span class="msum-point-num">${i + 1}</span><span>${escHtmlMSum(pt.text)}</span></div>`
                      : `<textarea rows="2" id="pt-${pt.id}-text" class="wiz-textarea plain" placeholder="النقطة ${i + 1}..." data-pt-field="text" data-pt-id="${pt.id}" ${allReadOnly ? "readonly" : ""}>${escHtmlMSum(pt.text)}</textarea>`}
                  </td>
                  <td>
                    ${(hrUser && !allReadOnly)
                      ? `<textarea rows="2" class="wiz-textarea" style="border:1.5px solid ${pt.opinion ? "#86efac" : "var(--pb)"};background:${pt.opinion ? "#f0fdf4" : "#f0f8fd"};" id="pt-${pt.id}-opinion" placeholder="اكتب الرأي..." data-pt-field="opinion" data-pt-id="${pt.id}">${escHtmlMSum(pt.opinion)}</textarea>`
                      : `<div class="msum-opinion-readonly ${pt.opinion ? "has" : "empty"}">${escHtmlMSum(pt.opinion || "—")}</div>`}
                  </td>
                  <td>
                    ${(hrUser && !allReadOnly)
                      ? `<textarea rows="2" class="wiz-textarea plain" id="pt-${pt.id}-reason" placeholder="اكتب السبب أو التوضيح..." data-pt-field="reason" data-pt-id="${pt.id}">${escHtmlMSum(pt.reason)}</textarea>`
                      : `<div class="msum-opinion-readonly empty" style="color:${pt.reason ? "#152c33" : "#9ca3af"};">${escHtmlMSum(pt.reason || "—")}</div>`}
                  </td>
                  ${(!hrUser && !allReadOnly) ? `<td style="text-align:center;"><button class="msum-del-btn" data-pt-del="${pt.id}"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button></td>` : ""}
                </tr>
              `).join("")}
              ${msumSummaryPoints.length === 0 ? `<tr><td colspan="4" class="msum-empty-points">لا توجد نقاط. اضغط "إضافة نقطة" للبدء.</td></tr>` : ""}
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. الاعتماد — لغير مستخدمي HR فقط -->
      ${!hrUser ? `
      <div class="wiz-card">
        <div class="wiz-card-head"><i data-lucide="check"></i><span style="color:#fff;font-weight:700;font-size:14px;">الاعتماد</span></div>
        <div class="msum-table-wrap">
          <table class="msum-table">
            <thead><tr><th>البيان</th><th>الاسم</th><th>الوظيفة</th><th class="c" style="width:120px;">التوقيع</th><th style="width:140px;">التاريخ</th></tr></thead>
            <tbody>
              ${msumApprovals.map(row => `
                <tr>
                  <td><input type="text" id="ap-${row.id}-statement" class="msum-plain-input" data-ap-field="statement" data-ap-id="${row.id}" value="${escHtmlMSum(row.statement)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td><input type="text" id="ap-${row.id}-name" class="msum-plain-input" placeholder="الاسم" data-ap-field="name" data-ap-id="${row.id}" value="${escHtmlMSum(row.name)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td><input type="text" id="ap-${row.id}-position" class="msum-plain-input" data-ap-field="position" data-ap-id="${row.id}" value="${escHtmlMSum(row.position)}" ${allReadOnly ? "readonly" : ""}></td>
                  <td>
                    ${!allReadOnly ? (
                      row.signature
                        ? `<div class="msum-sig-wrap has-sig"><img src="${row.signature}" alt="توقيع" style="width:100%;height:100%;object-fit:contain;"><button class="msum-sig-clear" data-sig-clear="${row.id}"><i data-lucide="x" style="width:12px;height:12px;"></i></button></div>`
                        : `<div class="msum-sig-wrap" id="sigWrap-${row.id}"><canvas id="sigCanvas-${row.id}"></canvas><span class="msum-sig-hint">وقّع هنا</span></div>`
                    ) : `<div class="msum-sig-readonly">—</div>`}
                  </td>
                  <td><input type="date" id="ap-${row.id}-date" class="msum-plain-input" data-ap-field="date" data-ap-id="${row.id}" value="${row.date}" ${allReadOnly ? "readonly" : ""} onclick="try{this.showPicker&&this.showPicker()}catch(e){}"></td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
        <div class="msum-approve-footer">
          <div class="msum-approve-meta">الرقم: م.م / ${new Date().getFullYear()} / .... <br> التاريخ: ${new Date().toLocaleDateString("en-GB")}</div>
          <div style="display:flex;align-items:center;gap:12px;">
            <label class="msum-attach-btn" style="cursor:pointer;">
              <i data-lucide="upload"></i> إرفاق ملفات
              <input type="file" id="msumAttachInput" hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" ${locked ? "disabled" : ""}>
            </label>
            ${msumAttachments.length === 0
              ? `<span class="msum-attach-empty">لا يوجد مرفقات</span>`
              : `<span class="msum-attach-empty">${msumAttachments.map(d => `<a href="${base}/dashboard/documents/download/${d.id}" target="_blank" style="color:var(--p);text-decoration:underline;">${escHtmlMSum(d.file_name)}</a>`).join("، ")}</span>`}
          </div>
        </div>
      </div>` : ""}

    </div>

    <div class="msum-bottom-row">
      ${!allReadOnly ? `
      <div class="msum-submit-wrap">
        <button class="msum-submit-btn ${msumDirty && !locked ? "dirty" : ""}" id="msumSubmitBtn" ${(!msumDirty || locked) ? "disabled" : ""}>
          <i data-lucide="send"></i> إرسال
        </button>
        ${!msumDirty ? `<span class="msum-submit-hint">لا توجد تغييرات للإرسال</span>` : ""}
      </div>` : ""}
    </div>
  </div>`;
}

function bindMeetingSummaryEvents() {
  const $ = id => document.getElementById(id);
  const hrUser = msumIsHrUser();
  const allReadOnly = msumAllReadOnly();

  const taskSelect = $("msumTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    msumSelectedTaskId = e.target.value;
    if (msumSelectedTaskId) await msumLoadData(msumSelectedTaskId);
    rerenderMSumContent();
  });

  const mark = () => {
    msumDirty = true;
    const isLocked = !msumSelectedTaskId;
    const btn = $("msumSubmitBtn");
    if (btn) { btn.disabled = isLocked; btn.classList.toggle("dirty", !isLocked); }
    const hint = document.querySelector(".msum-submit-hint");
    if (hint) hint.hidden = true;
  };

  const dateEl = $("msumDate"); if (dateEl && !allReadOnly) dateEl.addEventListener("change", e => { msumDate = e.target.value; mark(); rerenderMSumContent(); });
  const timeEl = $("msumTime"); if (timeEl && !allReadOnly) timeEl.addEventListener("change", e => { msumTime = e.target.value; mark(); rerenderMSumContent(); });
  const locEl = $("msumLocation");
  if (locEl) {
    autoGrowTextarea(locEl);
    if (!allReadOnly) locEl.addEventListener("input", e => { msumLocation = e.target.value; mark(); autoGrowTextarea(e.target); });
  }
  const titleEl = $("msumTitle");
  if (titleEl) {
    autoGrowTextarea(titleEl);
    if (!allReadOnly) titleEl.addEventListener("input", e => { msumTitle = e.target.value; mark(); autoGrowTextarea(e.target); });
  }
  const objEl = $("msumObjective");
  if (objEl) {
    autoGrowTextarea(objEl);
    if (!allReadOnly) objEl.addEventListener("input", e => { msumObjective = e.target.value; mark(); autoGrowTextarea(e.target); });
  }

  const addAttBtn = $("msumAddAttendanceBtn");
  if (addAttBtn) addAttBtn.addEventListener("click", () => { msumAttendance.push({ id: "new-" + Date.now(), name: "", dept: "", position: "" }); mark(); rerenderMSumContent(); });
  document.querySelectorAll("[data-att-del]").forEach(btn => btn.addEventListener("click", () => {
    msumAttendance = msumAttendance.filter(r => String(r.id) !== String(btn.dataset.attDel)); mark(); rerenderMSumContent();
  }));
  document.querySelectorAll("[data-att-field]").forEach(el => el.addEventListener("input", () => {
    const row = msumAttendance.find(r => String(r.id) === String(el.dataset.attId));
    if (row) { row[el.dataset.attField] = el.value; mark(); rerenderMSumContent(); }
  }));

  const addPtBtn = $("msumAddPointBtn");
  if (addPtBtn) addPtBtn.addEventListener("click", () => { msumSummaryPoints.push({ id: "new-" + Date.now(), text: "", opinion: "", reason: "" }); mark(); rerenderMSumContent(); });
  document.querySelectorAll("[data-pt-del]").forEach(btn => btn.addEventListener("click", () => {
    msumSummaryPoints = msumSummaryPoints.filter(p => String(p.id) !== String(btn.dataset.ptDel)); mark(); rerenderMSumContent();
  }));
  document.querySelectorAll("[data-pt-field]").forEach(el => {
    if (el.tagName === "TEXTAREA") autoGrowTextarea(el);
    el.addEventListener("input", () => {
      const pt = msumSummaryPoints.find(p => String(p.id) === String(el.dataset.ptId));
      if (pt) { pt[el.dataset.ptField] = el.value; mark(); }
      if (el.tagName === "TEXTAREA") autoGrowTextarea(el);
    });
  });

  document.querySelectorAll("[data-ap-field]").forEach(el => {
    const evt = el.tagName === "INPUT" && el.type === "date" ? "change" : "input";
    el.addEventListener(evt, () => {
      const row = msumApprovals.find(r => String(r.id) === String(el.dataset.apId));
      if (row) { row[el.dataset.apField] = el.value; mark(); rerenderMSumContent(); }
    });
  });
  document.querySelectorAll("[data-sig-clear]").forEach(btn => btn.addEventListener("click", () => {
    const row = msumApprovals.find(r => String(r.id) === String(btn.dataset.sigClear));
    if (row) { row.signature = ""; mark(); rerenderMSumContent(); }
  }));
  msumApprovals.forEach(row => {
    if (!row.signature && !allReadOnly) {
      attachSignaturePad(`sigCanvas-${row.id}`, `sigWrap-${row.id}`, dataUrl => {
        row.signature = dataUrl; mark(); rerenderMSumContent();
      });
    }
  });

  const attachInput = $("msumAttachInput");
  if (attachInput) attachInput.addEventListener("change", async () => {
    if (!msumSelectedTaskId || !attachInput.files.length) return;
    const formData = new FormData();
    formData.append("mission_id", msumSelectedTaskId);
    formData.append("file", attachInput.files[0]);
    formData.append(csrfName(), csrfValue());
    try {
      const res = await fetch(base + "/dashboard/meetings/api/upload", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        const attRes = await fetch(base + "/dashboard/meetings/api/attachments?mission_id=" + msumSelectedTaskId);
        const attData = await attRes.json();
        msumAttachments = attData.documents || [];
        rerenderMSumContent();
      } else {
        showToast(data.message || "تعذّر رفع الملف", "error");
      }
    } catch (e) {
      showToast("تعذّر الاتصال بالخادم", "error");
    } finally {
      attachInput.value = "";
    }
  });

  const exportBtn = $("msumExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", () => {
    if (!msumSelectedTaskId) return;
    window.open(base + "/dashboard/pdf/meeting-summary/" + msumSelectedTaskId, "_blank");
  });

  const submitBtn = $("msumSubmitBtn");
  if (submitBtn) submitBtn.addEventListener("click", async () => {
    if (!msumSelectedTaskId) return;
    submitBtn.disabled = true;
    try {
      const res = await fetch(base + "/dashboard/meetings/api/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          mission_id: msumSelectedTaskId,
          date: msumDate, time: msumTime, location: msumLocation,
          title: msumTitle, objective: msumObjective,
          attendees: msumAttendance.map(a => ({ name: a.name, dept: a.dept, position: a.position })),
          points: msumSummaryPoints.map(p => ({ text: p.text, opinion: p.opinion, reason: p.reason })),
          approvals: msumApprovals.map(a => ({ statement: a.statement, name: a.name, position: a.position, date: a.date, signature: a.signature })),
          [csrfName()]: csrfValue(),
        }),
      });
      const data = await res.json();
      if (data.success) {
        msumDirty = false;
        showToast("تم حفظ التغييرات بنجاح", "success");
        rerenderMSumContent();
      } else {
        showToast(data.message || "تعذّر الحفظ", "error");
      }
    } catch (e) {
      showToast("تعذّر الاتصال بالخادم", "error");
    } finally {
      submitBtn.disabled = false;
    }
  });
}

function csrfName()  { return document.querySelector('meta[name="csrf-token-name"]').content; }
function csrfValue() { return document.querySelector('meta[name="csrf-token-value"]').content; }
