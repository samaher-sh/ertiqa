/* ============================================================
   قائمة المستندات المطلوبة — متصلة بالـ API الحقيقي
   تظهر لطرفي المهمة: الإدارة الخاضعة للمراجعة تعبّئها وترفع الملفات،
   وعضو/رئيس المراجعة يشوفها للمتابعة فقط (قراءة).
   ============================================================ */
let drSelectedTaskId = "";
let drRequests = [];
let drCanSubmit = false;
let drLoading = false;
let drSubmitting = false;

async function loadDocumentRequests(missionId) {
  if (!missionId) { drRequests = []; drCanSubmit = false; return; }
  drLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/document-requests/api/list?mission_id=" + encodeURIComponent(missionId));
    drRequests = data.requests || [];
    drCanSubmit = !!data.can_submit;
  } catch (e) {
    drRequests = [];
    drCanSubmit = false;
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

        <div class="dr-body">
          ${renderDocumentRequestsBody()}
        </div>

        ${drCanSubmit && !drLoading && drRequests.length > 0 ? `
          <div class="dr-footer">
            <button type="button" class="dr-submit-btn" id="drSubmitBtn" ${drSubmitting ? "disabled" : ""}>
              <i data-lucide="upload"></i> ${drSubmitting ? "جارِ الإرسال..." : "إرسال المستندات"}
            </button>
          </div>
        ` : ""}
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

  if (!drCanSubmit) {
    // قراءة فقط (عضو/رئيس المراجعة) — يشوف حالة الرد بدون أي حقول تعديل
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

  return `
  <div class="dr-row editable" data-request-id="${r.id}">
    <div class="dr-row-head">
      <span class="dr-row-name">${escapeHtml(r.doc_name)}</span>
      <div class="dr-exists-toggle">
        <label class="dr-radio"><input type="radio" name="dr-exists-${r.id}" value="1" ${existsVal === 1 ? "checked" : ""}> موجود</label>
        <label class="dr-radio"><input type="radio" name="dr-exists-${r.id}" value="0" ${existsVal === 0 ? "checked" : ""}> غير موجود</label>
      </div>
    </div>
    <textarea class="dr-note-input" placeholder="ملاحظة (اختياري)">${escapeHtml(r.response_note || "")}</textarea>
    <div class="dr-file-row">
      <input type="file" class="dr-file-input" id="dr-file-${r.id}">
      ${r.file ? `<a class="dr-file-link" href="${base}/dashboard/documents/download/${r.file.id}" target="_blank"><i data-lucide="paperclip"></i> ${escapeHtml(r.file.file_name)}</a>` : ""}
    </div>
  </div>`;
}

async function drHandleSubmit() {
  const btn = document.getElementById("drSubmitBtn");
  if (!btn) return;

  const formData = new FormData();
  formData.append("mission_id", drSelectedTaskId);

  let i = 0;
  document.querySelectorAll(".dr-row.editable").forEach(row => {
    const requestId = row.dataset.requestId;
    const checked = row.querySelector(`input[name="dr-exists-${requestId}"]:checked`);
    formData.append(`responses[${i}][document_request_id]`, requestId);
    formData.append(`responses[${i}][exists_flag]`, checked ? checked.value : "");
    formData.append(`responses[${i}][note]`, row.querySelector(".dr-note-input").value.trim());

    const fileInput = document.getElementById("dr-file-" + requestId);
    if (fileInput && fileInput.files && fileInput.files[0]) {
      formData.append("file_" + requestId, fileInput.files[0]);
    }
    i++;
  });

  drSubmitting = true;
  rerenderDRContent();
  try {
    const res = await apiPostFile(base + "/dashboard/document-requests/api/submit", formData);
    if (!res || !res.success) throw new Error(res && res.message || "تعذّر إرسال المستندات");
    showToast("تم إرسال المستندات بنجاح", "success");
    await loadDocumentRequests(drSelectedTaskId);
  } catch (e) {
    showToast(e.message || "تعذّر إرسال المستندات", "error");
  }
  drSubmitting = false;
  rerenderDRContent();
}

function bindDocumentRequestsEvents() {
  const taskSelect = document.getElementById("drTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    drSelectedTaskId = e.target.value;
    await loadDocumentRequests(drSelectedTaskId);
    rerenderDRContent();
  });

  const submitBtn = document.getElementById("drSubmitBtn");
  if (submitBtn) submitBtn.addEventListener("click", drHandleSubmit);

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
