/* ============================================================
   قائمة المستندات المطلوبة — متصلة بالـ API الحقيقي
   صفحة مستقلة بالسايدبار لفريق المراجعة (رئيس/عضو المهمة) فقط: يشوفون قائمة
   المستندات المطلوبة وحالة رد الإدارة عليها (قراءة)، ويقدرون يضيفون طلبات
   مستندات جديدة بأي وقت بعد إنشاء المهمة. رد الإدارة الخاضعة للمراجعة (تحديد
   يوجد/لا يوجد ورفع الملف) يبقى حصرًا عبر المراسلات المشتركة → إكمال الحقول
   (missionreview.js) -- ما تغيّر شي هناك.
   ============================================================ */
let drSelectedTaskId = "";
let drRequests = [];
let drLoading = false;
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
      <div class="wiz-card dr-card">
        <div class="wiz-card-head">
          <i data-lucide="folder-check"></i>
          <div><h2>قائمة المستندات المطلوبة</h2><p>Required Documents</p></div>
        </div>

        <div class="dr-add-row">
          <input type="text" id="drNewDocInput" class="wiz-input plain" placeholder="أدخل اسم المستند الجديد...">
          <button type="button" class="wiz-btn wiz-btn-primary" id="drAddDocBtn" ${drAdding ? "disabled" : ""}>
            <i data-lucide="plus"></i> ${drAdding ? "جارِ الإضافة..." : "إضافة مستند"}
          </button>
        </div>

        <div class="dr-body">
          ${renderDocumentRequestsBody()}
        </div>
      </div>
    </div>
  </div>`;
}

function renderDocumentRequestsBody() {
  if (drLoading) return `<p class="dr-empty">جارِ التحميل...</p>`;
  if (drRequests.length === 0) return `<p class="dr-empty">لا توجد مستندات مطلوبة لهذه المهمة</p>`;

  return drRequests.map((r, idx) => renderDocumentRequestRow(r, idx)).join("");
}

function renderDocumentRequestRow(r, idx) {
  const hasResponse = r.exists_flag !== null && r.exists_flag !== undefined;
  const existsVal = hasResponse ? Number(r.exists_flag) : null;

  return `
  <div class="dr-row" data-request-id="${r.id}">
    <div class="dr-row-head">
      <span class="dr-row-name">${escapeHtml(r.doc_name)}</span>
      ${hasResponse
        ? `<span class="dr-status-badge ${existsVal ? "yes" : "no"}">${existsVal ? "موجود" : "غير موجود"}</span>`
        : `<span class="dr-status-badge pending">بانتظار الرد</span>`}
    </div>
    ${r.response_note ? `<p class="dr-row-note">${escapeHtml(r.response_note)}</p>` : ""}
    ${r.file ? `<a class="dr-file-link" href="${base}/dashboard/documents/download/${r.file.id}" target="_blank"><i data-lucide="paperclip"></i> ${escapeHtml(r.file.file_name)}</a>` : ""}
  </div>`;
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
    await loadDocumentRequests(drSelectedTaskId);
    rerenderDRContent();
  });

  const addBtn = document.getElementById("drAddDocBtn");
  if (addBtn) addBtn.addEventListener("click", drHandleAddDoc);

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
