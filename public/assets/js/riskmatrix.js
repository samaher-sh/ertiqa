/* ============================================================
   مصفوفة المخاطر (Risk Matrix) — متصل بالـ API الحقيقي
   نفس نمط قائمة/نموذج/عرض المستخدم بصفحة الملاحظات (observations.js) تمامًا،
   بإعادة استخدام أصنافها (obs-*) لضمان تطابق بصري كامل
   ============================================================ */

let rmSelectedTaskId = "";
let rmRows = [];
let rmView = "list"; // list | new | edit | view
let rmDraft = null;
let rmViewTarget = null;
let rmLoading = false;
let rmForceReadOnly = false; // تجبر القراءة فقط بغض النظر عن الدور (تستخدمها جولة "عرض" بالمراسلات المشتركة)
let rmOpenMenuId = null;

const rmIsReadOnly = () => rmForceReadOnly || isHrDept || isHrCoordinator || isAuditHead;

function autoGrowTextareaRM(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = (el.scrollHeight) + "px";
}

function rerenderRMContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;
  // منطقة تمرير الجدول الداخلية (rm-table-scroll) عنصر جديد كليًا بعد كل
  // innerHTML، فسكرول التمرير فيها يرجع صفر تلقائيًا -- لازم نحفظه ونرجّعه يدويًا
  // وإلا أي ضغطة (فتح قائمة ⋮ مثلًا) ترجّع الجدول لأعلى فجأة
  const tableScrollEl = document.querySelector(".rm-table-scroll");
  const tableScrollTop = tableScrollEl ? tableScrollEl.scrollTop : 0;

  ca.innerHTML = renderRiskMatrixPage();
  bindRiskMatrixEvents();
  lucide.createIcons();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) { el.focus(); if (selStart !== null && el.setSelectionRange) { try { el.setSelectionRange(selStart, selEnd); } catch (e) {} } }
  }
  if (ca) ca.scrollTop = scrollTop;
  const newTableScrollEl = document.querySelector(".rm-table-scroll");
  if (newTableScrollEl) newTableScrollEl.scrollTop = tableScrollTop;
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
  rmLoading = false;
}

/* الباك-إند يستبدل كل صفوف المهمة دفعة وحدة بكل حفظة (replaceForMission) --
   فإضافة/تعديل/حذف صف واحد يعني نبعث المصفوفة الكاملة الحالية بعد التعديل،
   بدل احتياج endpoint منفصل لكل عملية */
async function rmPersist() {
  return apiPost(base + "/dashboard/risk-matrix/api/save", {
    mission_id: rmSelectedTaskId,
    rows: rmRows.map(r => ({ risk: r.risk, risk_rating: r.riskRating, controls: r.controls, activity_type: r.activity })),
  });
}

/* ============================================================
   جدول قراءة فقط بدون قائمة إجراءات -- تستخدمها جولة "عرض" بالمراسلات
   المشتركة لعرض نفس شكل مصفوفة المخاطر بالضبط (نفس نمط renderObsReadOnlyTable)
   ============================================================ */
function renderRmReadOnlyTable() {
  if (rmLoading) return `<div class="obs-empty"><p class="main">جارِ التحميل...</p></div>`;
  if (rmRows.length === 0) {
    return `<div class="obs-empty"><i data-lucide="shield-alert"></i><p class="main">لا توجد مخاطر مسجلة لهذه المهمة</p></div>`;
  }
  return `
  <div class="obs-table-wrap">
    <table class="obs-table">
      <thead><tr>
        <th style="width:50px;">الرقم</th>
        <th>المخاطر</th>
        <th style="width:130px;">تقييم المخاطر</th>
        <th style="width:160px;">نوع النشاط</th>
      </tr></thead>
      <tbody>
        ${rmRows.map((row, i) => {
          const rc = row.riskRating ? CLASS_COLORS[row.riskRating] : null;
          return `
          <tr style="background:${i % 2 === 0 ? "#fff" : "#f6fcfe"};">
            <td style="text-align:center;">${i + 1}</td>
            <td><span class="obs-title-cell">${escapeHtml(row.risk || "—")}</span></td>
            <td>${rc ? `<span class="obs-pill" style="background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="dot" style="background:${rc.dot};"></span>${escapeHtml(row.riskRating)}</span>` : "—"}</td>
            <td><span class="obs-date-cell">${escapeHtml(row.activity || "—")}</span></td>
          </tr>`;
        }).join("")}
      </tbody>
    </table>
  </div>`;
}

/* ============================================================
   وضع القائمة (List)
   ============================================================ */
function renderRmListMode() {
  const readOnly = rmIsReadOnly();
  const locked = !rmSelectedTaskId;

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(rmSelectedTaskId, "rmTaskSelect")}

    <div class="obs-disabled-wrap ${locked ? "locked" : ""}">
      <div class="obs-list-card">
        <div class="obs-list-header">
          <div class="obs-list-header-left">
            <i data-lucide="bar-chart-2"></i>
            <span class="obs-list-title">مصفوفة المخاطر</span>
          </div>
          <div class="obs-header-actions">
            ${!readOnly ? `<button class="obs-btn-add" id="rmAddBtn"><i data-lucide="plus"></i> إضافة مخاطر</button>` : `
              <span class="obs-readonly-badge"><i data-lucide="lock"></i> عرض فقط</span>`}
            <button class="obs-btn-pdf" id="rmExportBtn" ${locked ? "disabled" : ""}><i data-lucide="file-text"></i> تصدير PDF</button>
          </div>
        </div>

        ${rmLoading ? `<div class="obs-empty"><p class="main">جارِ التحميل...</p></div>` :
          rmRows.length === 0 ? `
          <div class="obs-empty">
            <i data-lucide="shield-alert"></i>
            <p class="main">لا توجد مخاطر</p>
            <p class="hint">ابدأ بإضافة خطر جديد</p>
          </div>
        ` : `
          <div class="obs-table-wrap rm-table-scroll">
            <table class="obs-table">
              <thead><tr>
                <th style="width:50px;">الرقم</th>
                <th>المخاطر</th>
                <th style="width:130px;">تقييم المخاطر</th>
                <th style="width:160px;">نوع النشاط</th>
                ${!isAuditHead ? '<th style="width:60px;">الإجراءات</th>' : ""}
              </tr></thead>
              <tbody>
                ${rmRows.map((row, i) => {
                  const rc = row.riskRating ? CLASS_COLORS[row.riskRating] : null;
                  const menuOpen = String(rmOpenMenuId) === String(row.id);
                  return `
                  <tr style="background:${i % 2 === 0 ? "#fff" : "#f6fcfe"};">
                    <td style="text-align:center;">${i + 1}</td>
                    <td><span class="obs-title-cell">${escapeHtml(row.risk || "—")}</span></td>
                    <td>${rc ? `<span class="obs-pill" style="background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="dot" style="background:${rc.dot};"></span>${escapeHtml(row.riskRating)}</span>` : "—"}</td>
                    <td><span class="obs-date-cell">${escapeHtml(row.activity || "—")}</span></td>
                    ${!isAuditHead ? `
                    <td class="obs-menu-cell">
                      <button class="obs-menu-btn" data-menu-toggle="${row.id}"><i data-lucide="more-vertical"></i></button>
                      ${menuOpen ? `
                        <div class="obs-menu-dropdown">
                          <button class="obs-menu-item" data-view-rm="${row.id}"><i data-lucide="eye"></i> عرض</button>
                          ${!readOnly ? `
                            <button class="obs-menu-item" data-edit-rm="${row.id}"><i data-lucide="pencil"></i> تعديل</button>
                            <div class="obs-menu-sep"></div>
                            <button class="obs-menu-item danger" data-delete-rm="${row.id}"><i data-lucide="trash-2"></i> حذف</button>
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
  </div>`;
}

function bindRmListEvents() {
  const taskSelect = document.getElementById("rmTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    rmSelectedTaskId = e.target.value;
    if (rmSelectedTaskId) await rmLoadItems(rmSelectedTaskId);
    else rmRows = [];
    rerenderRMContent();
  });

  const addBtn = document.getElementById("rmAddBtn");
  if (addBtn) addBtn.addEventListener("click", rmOpenNew);

  const exportBtn = document.getElementById("rmExportBtn");
  if (exportBtn) exportBtn.addEventListener("click", () => {
    if (!rmSelectedTaskId) return;
    // لو المتصفح حظر النافذة المنبثقة، window.open() ترجّع null بصمت بدون أي خطأ --
    // بدون هذا التحقق كان الزر "ما يشتغل" فعليًا بلا أي تنبيه للمستخدم
    const w = window.open(base + "/dashboard/pdf/risk-matrix/" + rmSelectedTaskId, "_blank");
    if (!w) showToast("يرجى السماح بالنوافذ المنبثقة لهذا الموقع لتصدير PDF", "error");
  });

  document.querySelectorAll("[data-menu-toggle]").forEach(btn => {
    btn.addEventListener("click", e => {
      e.stopPropagation();
      const id = btn.dataset.menuToggle;
      rmOpenMenuId = String(rmOpenMenuId) === id ? null : id;
      rerenderRMContent();
    });
  });
  document.querySelectorAll("[data-view-rm]").forEach(btn => {
    btn.addEventListener("click", () => rmOpenView(btn.dataset.viewRm));
  });
  document.querySelectorAll("[data-edit-rm]").forEach(btn => {
    btn.addEventListener("click", () => rmOpenEdit(btn.dataset.editRm));
  });
  document.querySelectorAll("[data-delete-rm]").forEach(btn => {
    btn.addEventListener("click", () => rmDelete(btn.dataset.deleteRm));
  });

  /* إغلاق القائمة المنسدلة عند الضغط خارجها أو عند تمرير الجدول (منطقة التمرير
     الداخلية الجديدة) -- وإلا تفضل القائمة عالقة بموضعها القديم بعد التمرير.
     capture:true عشان الإغلاق يشتغل حتى لو ضغط المستخدم على عنصر عنده
     stopPropagation خاص فيه (زر الملف الشخصي بالأعلى مثلًا) */
  if (rmOpenMenuId !== null) {
    rmPositionOpenMenu();
    setTimeout(() => {
      document.addEventListener("click", function closeRmMenu() {
        rmOpenMenuId = null;
        rerenderRMContent();
        document.removeEventListener("click", closeRmMenu, true);
      }, { once: true, capture: true });
      const scrollWrap = document.querySelector(".rm-table-scroll");
      if (scrollWrap) scrollWrap.addEventListener("scroll", function closeRmMenuOnScroll() {
        rmOpenMenuId = null;
        rerenderRMContent();
        scrollWrap.removeEventListener("scroll", closeRmMenuOnScroll);
      }, { once: true });
    }, 0);
  }
}

/* القائمة المنسدلة (⋮) لازم تُموضَع بـ position:fixed محسوبة من الزر فعليًا (بدل
   position:absolute الثابتة بالـ CSS) -- لأن الجدول صار له منطقة تمرير داخلية
   (.rm-table-scroll) تقص أي محتوى يفيض عن حدودها، فلو صف قريب من أسفل منطقة
   التمرير كانت القائمة تنقص/تختفي جزئيًا. برضو تنقلب لأعلى تلقائيًا لو ما فيه
   مساحة كافية أسفل الشاشة */
function rmPositionOpenMenu() {
  requestAnimationFrame(() => {
    const dropdown = document.querySelector(".obs-menu-dropdown");
    const btn = document.querySelector(`[data-menu-toggle="${rmOpenMenuId}"]`);
    if (!dropdown || !btn) return;
    const btnRect = btn.getBoundingClientRect();
    const ddRect = dropdown.getBoundingClientRect();
    let top = btnRect.bottom + 4;
    if (top + ddRect.height > window.innerHeight - 12) {
      top = btnRect.top - ddRect.height - 4;
    }
    let left = btnRect.left;
    if (left + ddRect.width > window.innerWidth - 12) left = btnRect.right - ddRect.width;
    dropdown.style.position = "fixed";
    dropdown.style.top = top + "px";
    dropdown.style.left = left + "px";
  });
}

/* ---------- إجراءات القائمة ---------- */
function rmOpenNew() {
  rmDraft = { id: 0, risk: "", riskRating: "", controls: "", activity: "" };
  rmView = "new";
  rerenderRMContent();
}
function rmOpenEdit(id) {
  const row = rmRows.find(r => String(r.id) === String(id));
  if (!row) return;
  rmDraft = { ...row };
  rmOpenMenuId = null;
  rmView = "edit";
  rerenderRMContent();
}
function rmOpenView(id) {
  const row = rmRows.find(r => String(r.id) === String(id));
  if (!row) return;
  rmViewTarget = row;
  rmOpenMenuId = null;
  rmView = "view";
  rerenderRMContent();
}
async function rmDelete(id) {
  rmOpenMenuId = null;
  const snapshot = rmRows;
  rmRows = rmRows.filter(r => String(r.id) !== String(id));
  try {
    const data = await rmPersist();
    if (!data.success) {
      rmRows = snapshot;
      showToast(data.message || "تعذّر حذف الخطر", "error");
    } else {
      showToast("تم حذف الخطر", "success");
    }
  } catch (e) {
    rmRows = snapshot;
    showToast("تعذّر الاتصال بالخادم", "error");
  }
  rerenderRMContent();
}
function rmCancel() {
  rmView = "list"; rmDraft = null; rmViewTarget = null;
  rerenderRMContent();
}

async function rmSave() {
  if (!rmDraft) return;
  if (!rmDraft.risk.trim()) {
    showToast("يرجى إدخال وصف الخطر على الأقل.", "error");
    return;
  }

  const saveBtn = document.getElementById("rmFormSave");
  if (saveBtn) saveBtn.disabled = true;

  const isNew = rmView === "new";
  const snapshot = rmRows;
  rmRows = isNew ? [...rmRows, rmDraft] : rmRows.map(r => String(r.id) === String(rmDraft.id) ? { ...rmDraft } : r);

  try {
    const data = await rmPersist();
    if (!data.success) {
      rmRows = snapshot;
      showToast(data.message || "تعذّر الحفظ", "error");
      return;
    }
    showToast(isNew ? "تمت إضافة الخطر" : "تم حفظ التعديلات", "success");
    await rmLoadItems(rmSelectedTaskId);
    rmCancel();
  } catch (e) {
    rmRows = snapshot;
    showToast("تعذّر الاتصال بالخادم", "error");
  } finally {
    if (saveBtn) saveBtn.disabled = false;
  }
}

/* ============================================================
   وضع النموذج (إضافة/تعديل)
   ============================================================ */
function renderRmFormMode() {
  const locked = !rmSelectedTaskId;
  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(rmSelectedTaskId, "rmTaskSelect")}
    <div class="obs-disabled-wrap ${locked ? "locked" : ""}">
      ${renderRmForm()}
    </div>
  </div>`;
}

function renderRmForm() {
  const d = rmDraft;
  const rc = d.riskRating ? CLASS_COLORS[d.riskRating] : null;
  return `
  <div class="obs-form-card">
    <div class="obs-form-head">
      <div class="obs-form-head-left">
        <button class="obs-form-back" id="rmFormBack"><i data-lucide="chevron-right"></i></button>
        <h3 class="obs-form-title">${rmView === "new" ? "إضافة خطر جديد" : "تعديل الخطر"}</h3>
      </div>
      <button class="obs-form-save" id="rmFormSave"><i data-lucide="check"></i> حفظ</button>
    </div>

    <div class="obs-form-body">
      <div class="wiz-field">
        <label class="wiz-label">المخاطر <span class="wiz-req">*</span></label>
        <textarea id="rmFormRisk" rows="3" class="wiz-textarea plain" placeholder="أدخل وصف الخطر...">${escapeHtml(d.risk)}</textarea>
      </div>

      <div class="obs-divider"></div>

      <div class="obs-grid-2">
        <div class="wiz-field">
          <label class="wiz-label">تقييم المخاطر</label>
          <select id="rmFormRating" class="wiz-select ${d.riskRating ? "filled" : ""}" style="${rc ? `border-color:${rc.border};background:${rc.bg};color:${rc.text};` : ""}">
            <option value="">— اختر —</option>
            <option value="عالي" ${d.riskRating === "عالي" ? "selected" : ""}>عالي</option>
            <option value="متوسط" ${d.riskRating === "متوسط" ? "selected" : ""}>متوسط</option>
            <option value="منخفض" ${d.riskRating === "منخفض" ? "selected" : ""}>منخفض</option>
          </select>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">نوع النشاط</label>
          <input id="rmFormActivity" type="text" class="wiz-input plain" placeholder="نوع النشاط..." value="${escapeHtml(d.activity)}">
        </div>
        <div class="wiz-field" style="grid-column:1/-1;">
          <label class="wiz-label">وصف الضوابط</label>
          <textarea id="rmFormControls" rows="3" class="wiz-textarea plain" placeholder="وصف الضوابط الرقابية...">${escapeHtml(d.controls)}</textarea>
        </div>
      </div>
    </div>
  </div>`;
}

function bindRmFormEvents() {
  const $ = id => document.getElementById(id);
  const d = rmDraft;

  $("rmFormBack").addEventListener("click", rmCancel);
  $("rmFormSave").addEventListener("click", rmSave);

  const riskEl = $("rmFormRisk");
  autoGrowTextareaRM(riskEl);
  riskEl.addEventListener("input", e => { d.risk = e.target.value; autoGrowTextareaRM(e.target); });

  const controlsEl = $("rmFormControls");
  autoGrowTextareaRM(controlsEl);
  controlsEl.addEventListener("input", e => { d.controls = e.target.value; autoGrowTextareaRM(e.target); });

  $("rmFormActivity").addEventListener("input", e => { d.activity = e.target.value; });
  $("rmFormRating").addEventListener("change", e => { d.riskRating = e.target.value; rerenderRMContent(); });
}

/* ============================================================
   وضع العرض (read-only)
   ============================================================ */
function renderRmViewMode() {
  const v = rmViewTarget;
  const rc = v.riskRating ? CLASS_COLORS[v.riskRating] : null;
  const readOnly = rmIsReadOnly();

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(rmSelectedTaskId, "rmTaskSelect")}

    <div class="obs-form-card">
      <div class="obs-form-head">
        <div class="obs-form-head-left">
          <button class="obs-form-back" id="rmViewBack"><i data-lucide="chevron-right"></i></button>
          <h3 class="obs-form-title">عرض الخطر</h3>
        </div>
        ${!readOnly ? `<button class="obs-form-save" id="rmViewEditBtn"><i data-lucide="pencil"></i></button>` : ""}
      </div>

      <div class="obs-form-body">
        <div class="obs-view-box"><span class="lbl">المخاطر</span><p>${escapeHtml(v.risk || "—")}</p></div>

        <div class="obs-divider"></div>

        <div class="obs-view-grid">
          <div class="obs-view-field">
            <span class="lbl">تقييم المخاطر</span>
            ${rc ? `<span class="obs-pill" style="width:fit-content;background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="dot" style="background:${rc.dot};"></span>${escapeHtml(v.riskRating)}</span>` : `<span class="val">—</span>`}
          </div>
          <div class="obs-view-field"><span class="lbl">نوع النشاط</span><span class="val">${escapeHtml(v.activity || "—")}</span></div>
        </div>

        <div class="obs-view-box"><span class="lbl">وصف الضوابط</span><p>${escapeHtml(v.controls || "—")}</p></div>
      </div>
    </div>
  </div>`;
}

function bindRmViewEvents() {
  document.getElementById("rmViewBack").addEventListener("click", rmCancel);
  const editBtn = document.getElementById("rmViewEditBtn");
  if (editBtn) editBtn.addEventListener("click", () => {
    rmDraft = { ...rmViewTarget };
    rmView = "edit";
    rerenderRMContent();
  });

  const taskSelect = document.getElementById("rmTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", async e => {
    rmSelectedTaskId = e.target.value;
    rmCancel();
  });
}

/* ============================================================
   الحاوية العامة
   ============================================================ */
function renderRiskMatrixPage() {
  if (rmView === "view" && rmViewTarget) return renderRmViewMode();
  if ((rmView === "new" || rmView === "edit") && rmDraft) return renderRmFormMode();
  return renderRmListMode();
}

function bindRiskMatrixEvents() {
  if (rmView === "view" && rmViewTarget) { bindRmViewEvents(); return; }
  if ((rmView === "new" || rmView === "edit") && rmDraft) { bindRmFormEvents(); return; }
  bindRmListEvents();
}
