/* ============================================================
   مصفوفة المخاطر (Risk Matrix) — متصل بالـ API الحقيقي
   ============================================================ */

let rmSelectedTaskId = "";
let rmRows = [];
let rmDirty = false;
let rmToastVisible = false;
let rmToastTimer = null;
let rmLoading = false;

const rmIsReadOnly = () => isHrCoordinator || isAuditHead;

function autoGrowTextareaRM(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = (el.scrollHeight) + "px";
}

function updateSaveBtnStateRM() {
  const submitBtn = document.getElementById("rmSubmitBtn");
  if (submitBtn) {
    submitBtn.disabled = !rmDirty || !rmSelectedTaskId;
    submitBtn.classList.toggle("dirty", rmDirty && !!rmSelectedTaskId);
  }
  const hint = document.querySelector(".rm-submit-hint");
  if (hint) hint.hidden = rmDirty;
}
function rerenderRMContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  ca.innerHTML = renderRiskMatrixPage();
  bindRiskMatrixEvents();
  lucide.createIcons();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) { el.focus(); if (selStart !== null && el.setSelectionRange) { try { el.setSelectionRange(selStart, selEnd); } catch (e) {} } }
  }
  if (ca) ca.scrollTop = scrollTop;
}

async function rmLoadItems(missionId) {
  rmLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/risk-matrix/api/items?mission_id=" + missionId);
    rmRows = (data.items || []).map(it => ({
      id: it.id, risk: it.risk || "", riskRating: it.risk_rating || "",
      controls: it.controls || "", activity: it.activity_type || "",
    }));
  } catch (e) {
    rmRows = [];
  }
  rmDirty = false;
  rmLoading = false;
}

function renderRiskMatrixPage() {
  const readOnly = rmIsReadOnly();
  const locked = !rmSelectedTaskId;
  const COLS = [
    { label: "م", w: "52px", c: true },
    { label: "المخاطر" },
    { label: "تقييم المخاطر", w: "180px" },
    { label: "وصف الضوابط", w: "180px" },
    { label: "نوع النشاط", w: "180px" },
    { label: "", w: "52px", c: true },
  ];

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(rmSelectedTaskId, "rmTaskSelect")}

    <div class="rm-card ${locked ? "locked" : ""}">
      <div class="rm-head">
        <div class="rm-head-left">
          <div class="rm-head-icon"><i data-lucide="bar-chart-2"></i></div>
          <div><h2>مصفوفة المخاطر</h2><p>Risk Matrix Form</p></div>
          ${readOnly ? `<span class="rm-readonly-badge"><i data-lucide="lock" style="width:10px;height:10px;"></i> عرض فقط</span>` : ""}
        </div>
        <div style="display:flex;gap:8px;">
          ${!readOnly ? `<button class="obs-btn-add" id="rmAddBtn"><i data-lucide="plus"></i> إضافة مخاطر</button>` : ""}
          <button class="obs-btn-pdf" id="rmExportBtn" ${locked ? "disabled" : ""}><i data-lucide="file-text"></i> تصدير PDF</button>
        </div>
      </div>

      <div class="rm-table-wrap">
        <table class="rm-table">
          <thead><tr>${COLS.map(c => `<th class="${c.c ? "c" : ""}" style="${c.w ? `width:${c.w};` : ""}">${c.label}</th>`).join("")}</tr></thead>
          <tbody>
            ${rmLoading ? `<tr><td colspan="6"><div class="rm-empty"><p>جارِ التحميل...</p></div></td></tr>` :
              rmRows.length === 0 ? `
              <tr><td colspan="6"><div class="rm-empty"><div class="rm-empty-icon"><i data-lucide="shield-alert"></i></div><p>لا توجد مخاطر</p></div></td></tr>
            ` : rmRows.map((row, i) => {
              const rc = row.riskRating ? CLASS_COLORS[row.riskRating] : null;
              const rowBg = i % 2 === 0 ? "#fff" : "#f6fcfe";
              return `
              <tr style="background:${rowBg};">
                <td style="text-align:center;"><span class="rm-row-num">${i + 1}</span></td>
                <td><textarea rows="2" id="rm-${row.id}-risk" class="rm-cell-textarea ${row.risk ? "filled" : ""}" placeholder="أدخل وصف الخطر..." data-rm-field="risk" data-rm-id="${row.id}" ${readOnly ? "readonly" : ""}>${escapeHtml(row.risk)}</textarea></td>
                <td>
                  <div class="rm-rating-wrap">
                    <select class="rm-rating-select ${row.riskRating ? "set" : ""}" data-rm-rating="${row.id}" ${readOnly ? "disabled" : ""}
                      style="text-align:center;text-align-last:center;${rc ? `border-color:${rc.border};background:${rc.bg};color:${rc.text};` : ""}">
                      <option value="">— اختر —</option>
                      <option value="عالي" ${row.riskRating === "عالي" ? "selected" : ""}>عالي</option>
                      <option value="متوسط" ${row.riskRating === "متوسط" ? "selected" : ""}>متوسط</option>
                      <option value="منخفض" ${row.riskRating === "منخفض" ? "selected" : ""}>منخفض</option>
                    </select>
                  </div>
                </td>
                <td><textarea rows="2" id="rm-${row.id}-controls" class="rm-cell-textarea ${row.controls ? "filled" : ""}" placeholder="وصف الضوابط الرقابية..." data-rm-field="controls" data-rm-id="${row.id}" ${readOnly ? "readonly" : ""}>${escapeHtml(row.controls)}</textarea></td>
                <td><input type="text" id="rm-${row.id}-activity" class="rm-cell-input ${row.activity ? "filled" : ""}" placeholder="نوع النشاط..." data-rm-field="activity" data-rm-id="${row.id}" value="${escapeHtml(row.activity)}" ${readOnly ? "readonly" : ""}></td>
                <td style="text-align:center;">${!readOnly ? `<button class="rm-del-btn" data-rm-del="${row.id}"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button>` : ""}</td>
              </tr>`;
            }).join("")}
          </tbody>
        </table>
      </div>
    </div>

    <div class="rm-bottom-row">
      ${!readOnly ? `
      <div class="rm-submit-wrap">
        <button class="rm-submit-btn ${rmDirty && !locked ? "dirty" : ""}" id="rmSubmitBtn" ${(!rmDirty || locked) ? "disabled" : ""}>
          <i data-lucide="send"></i> إرسال
        </button>
        ${!rmDirty ? `<span class="rm-submit-hint">لا توجد تغييرات للإرسال</span>` : ""}
      </div>` : ""}
    </div>
  </div>
  ${rmToastVisible ? `<div class="rm-toast"><i data-lucide="check"></i> تم الحفظ بنجاح</div>` : ""}
  `;
}

function bindRiskMatrixEvents() {
  const taskSelect = document.getElementById("rmTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    rmSelectedTaskId = e.target.value;
    if (rmSelectedTaskId) {
      await rmLoadItems(rmSelectedTaskId);
    } else {
      rmRows = [];
    }
    rerenderRMContent();
  });

  const addBtn = document.getElementById("rmAddBtn");
  if (addBtn) addBtn.addEventListener("click", () => {
    rmRows.push({ id: "new-" + Date.now() + Math.random(), risk: "", riskRating: "", controls: "", activity: "" });
    rmDirty = true;
    rerenderRMContent();
  });

  document.querySelectorAll("[data-rm-del]").forEach(btn => {
    btn.addEventListener("click", () => {
      rmRows = rmRows.filter(r => String(r.id) !== String(btn.dataset.rmDel));
      rmDirty = true;
      rerenderRMContent();
    });
  });

  document.querySelectorAll("[data-rm-field]").forEach(el => {
    if (el.tagName === "TEXTAREA") { autoGrowTextareaRM(el); }
    el.addEventListener("input", () => {
      const row = rmRows.find(r => String(r.id) === String(el.dataset.rmId));
      if (row) { row[el.dataset.rmField] = el.value; rmDirty = true; }
      if (el.tagName === "TEXTAREA") { autoGrowTextareaRM(el); updateSaveBtnStateRM(); }
      else { rerenderRMContent(); }
    });
  });

  document.querySelectorAll("[data-rm-rating]").forEach(sel => {
    sel.addEventListener("change", () => {
      const row = rmRows.find(r => String(r.id) === String(sel.dataset.rmRating));
      if (row) { row.riskRating = sel.value; rmDirty = true; rerenderRMContent(); }
    });
  });

  const submitBtn = document.getElementById("rmSubmitBtn");
  if (submitBtn) submitBtn.addEventListener("click", rmHandleSave);

  const exportBtn = document.getElementById("rmExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", () => {
    if (!rmSelectedTaskId) return;
    window.open(base + "/dashboard/pdf/risk-matrix/" + rmSelectedTaskId, "_blank");
  });

  updateSaveBtnStateRM();
}

async function rmHandleSave() {
  if (!rmSelectedTaskId) return;
  try {
    const data = await apiPost(base + "/dashboard/risk-matrix/api/save", {
      mission_id: rmSelectedTaskId,
      rows: rmRows.map(r => ({ risk: r.risk, risk_rating: r.riskRating, controls: r.controls, activity_type: r.activity })),
    });
    if (!data.success) {
      showToast(data.message || "تعذّر الحفظ", "error");
      return;
    }
    rmDirty = false;
    rmToastVisible = true;
    rerenderRMContent();
    if (rmToastTimer) clearTimeout(rmToastTimer);
    rmToastTimer = setTimeout(() => { rmToastVisible = false; rerenderRMContent(); }, 3000);
  } catch (e) {
    showToast("تعذّر الاتصال بالخادم", "error");
  }
}
