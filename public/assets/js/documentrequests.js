/* ============================================================
   قائمة المستندات المطلوبة — متصلة بالـ API الحقيقي
   صفحة مستقلة بالسايدبار لفريق المراجعة (رئيس/عضو المهمة) فقط: يشوفون قائمة
   المستندات المطلوبة وحالة رد الإدارة عليها (قراءة)، ويقدرون يضيفون طلبات
   مستندات جديدة بأي وقت بعد إنشاء المهمة. رد الإدارة الخاضعة للمراجعة (تحديد
   يوجد/لا يوجد ورفع الملف) يبقى حصرًا عبر المراسلات المشتركة → إكمال الحقول
   (missionreview.js) -- ما تغيّر شي هناك.
   نفس شكل جدول "قائمة المستندات" الأصلي (wiz-doc-table) المستخدم بمعالج "بدء
   مهمة" سابقًا وبصفحة مراجعة المهمة (missionreview.js) حاليًا -- مو تصميم بطاقات
   مختلف.
   ============================================================ */
let drSelectedTaskId = "";
let drRequests = [];
let drLoading = false;
let drShowAddRow = false;
let drAdding = false;

async function loadDocumentRequests(missionId) {
  if (!missionId) { drRequests = []; return; }
  drLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/document-requests/api/list?mission_id=" + encodeURIComponent(missionId));
    drRequests = data.requests || [];
  } catch (e) {
    drRequests = [];
    showToast(e.message || "تعذّر تحميل قائمة المستندات", "error");
  }
  drLoading = false;
}

function renderDocumentRequestsPage() {
  const locked = !drSelectedTaskId;

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(drSelectedTaskId, "drTaskSelect")}

    <div class="mc-locked-wrap ${locked ? "locked" : ""}">
      <div class="wiz-card">
        <div class="wiz-card-head" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:10px;">
            <i data-lucide="folder-check"></i>
            <div><h2>قائمة المستندات المطلوبة</h2><p>Required Documents Checklist</p></div>
          </div>
          <button type="button" class="wiz-add-doc-btn" id="drToggleAddBtn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);">
            ${drShowAddRow ? '<i data-lucide="x"></i> إلغاء' : '<i data-lucide="plus"></i> إضافة مستند'}
          </button>
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
              ${renderDocumentRequestsBody()}
            </tbody>
          </table>
        </div>
        <div class="wiz-doc-footer">
          <span class="wiz-doc-footer-count">الإجمالي: <strong>${drRequests.length}</strong></span>
        </div>
      </div>
    </div>
  </div>`;
}

function renderDocumentRequestsBody() {
  if (drLoading) return `<tr><td colspan="5"><div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>جارِ التحميل...</div></td></tr>`;
  if (drRequests.length === 0 && !drShowAddRow) return `<tr><td colspan="5"><div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>لا توجد مستندات مطلوبة لهذه المهمة</div></td></tr>`;

  return drRequests.map((r, i) => renderDocumentRequestRow(r, i)).join("") + (drShowAddRow ? renderDrAddRow(drRequests.length) : "");
}

function renderDocumentRequestRow(r, idx) {
  const hasResponse = r.exists_flag !== null && r.exists_flag !== undefined;
  const existsVal = hasResponse ? Number(r.exists_flag) : null;

  return `
  <tr data-request-id="${r.id}">
    <td style="text-align:center;"><span class="wiz-doc-row-num">${idx + 1}</span></td>
    <td><input type="text" class="wiz-doc-name-input" value="${escapeHtml(r.doc_name)}" readonly></td>
    <td style="text-align:center;">
      <span class="wiz-pill">${hasResponse ? (existsVal ? "يوجد" : "لا يوجد") : "بانتظار الرد"}</span>
    </td>
    <td style="text-align:center;">
      ${r.file ? `<a class="dr-file-link" href="${base}/dashboard/documents/download/${r.file.id}" target="_blank"><i data-lucide="paperclip"></i> ${escapeHtml(r.file.file_name)}</a>` : `<span class="wiz-pill">لا يوجد ملف</span>`}
    </td>
    <td><input type="text" class="wiz-doc-note-input" value="${escapeHtml(r.response_note || "")}" readonly placeholder="لا توجد ملاحظات"></td>
  </tr>`;
}

function renderDrAddRow(nextIndex) {
  return `
  <tr>
    <td style="text-align:center;"><span class="wiz-doc-row-num">${nextIndex + 1}</span></td>
    <td>
      <div style="display:flex;gap:6px;align-items:center;">
        <input type="text" id="drNewDocInput" class="wiz-doc-name-input" placeholder="أدخل اسم المستند الجديد..." ${drAdding ? "disabled" : ""}>
        <button type="button" class="wiz-doc-del-btn" id="drSaveNewDocBtn" style="color:var(--p);" title="حفظ" ${drAdding ? "disabled" : ""}><i data-lucide="check"></i></button>
        <button type="button" class="wiz-doc-del-btn" id="drCancelNewDocBtn" title="إلغاء" ${drAdding ? "disabled" : ""}><i data-lucide="x"></i></button>
      </div>
    </td>
    <td style="text-align:center;background:#fafafa;">
      <div class="wiz-locked-cell"><div class="inner"><span class="wiz-pill">—</span></div><div class="overlay"></div></div>
    </td>
    <td style="text-align:center;background:#fafafa;">
      <div class="wiz-locked-cell"><div class="inner"><span class="wiz-upload-pill"><i data-lucide="upload"></i> رفع</span></div><div class="overlay"></div></div>
    </td>
    <td style="background:#fafafa;">
      <div class="wiz-locked-cell"><div class="inner"><input type="text" class="wiz-doc-note-input" readonly placeholder="ملاحظة..."></div><div class="overlay"></div></div>
    </td>
  </tr>`;
}

async function drHandleAddDoc() {
  const input = document.getElementById("drNewDocInput");
  if (!input) return;
  const docName = input.value.trim();
  if (!docName) { showToast("يرجى إدخال اسم المستند", "error"); return; }

  drAdding = true;
  rerenderDRContent();
  try {
    const res = await apiPost(base + "/dashboard/document-requests/api/add", {
      mission_id: drSelectedTaskId,
      doc_name: docName,
    });
    if (!res || !res.success) throw new Error(res && res.message || "تعذّر إضافة المستند");
    showToast("تمت إضافة المستند بنجاح", "success");
    drShowAddRow = false;
    await loadDocumentRequests(drSelectedTaskId);
  } catch (e) {
    showToast(e.message || "تعذّر إضافة المستند", "error");
  }
  drAdding = false;
  rerenderDRContent();
}

function bindDocumentRequestsEvents() {
  const taskSelect = document.getElementById("drTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    drSelectedTaskId = e.target.value;
    drShowAddRow = false;
    await loadDocumentRequests(drSelectedTaskId);
    rerenderDRContent();
  });

  const toggleAddBtn = document.getElementById("drToggleAddBtn");
  if (toggleAddBtn) toggleAddBtn.addEventListener("click", () => {
    drShowAddRow = !drShowAddRow;
    rerenderDRContent();
    if (drShowAddRow) {
      const input = document.getElementById("drNewDocInput");
      if (input) input.focus();
    }
  });

  const saveBtn = document.getElementById("drSaveNewDocBtn");
  if (saveBtn) saveBtn.addEventListener("click", drHandleAddDoc);

  const cancelBtn = document.getElementById("drCancelNewDocBtn");
  if (cancelBtn) cancelBtn.addEventListener("click", () => {
    drShowAddRow = false;
    rerenderDRContent();
  });

  const newDocInput = document.getElementById("drNewDocInput");
  if (newDocInput) newDocInput.addEventListener("keydown", e => {
    if (e.key === "Enter") { e.preventDefault(); drHandleAddDoc(); }
  });

  if (taskSelect && taskSelect.options.length === 2 && !drSelectedTaskId) {
    taskSelect.selectedIndex = 1;
    taskSelect.dispatchEvent(new Event("change"));
  }
}

function rerenderDRContent() {
  const ca = document.getElementById("contentArea");
  if (!ca) return;
  ca.innerHTML = renderDocumentRequestsPage();
  bindDocumentRequestsEvents();
  lucide.createIcons();
}
