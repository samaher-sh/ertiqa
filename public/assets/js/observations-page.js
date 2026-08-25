/* ============================================================
   observations-page.js — تحسين تدريجي (progressive enhancement) لصفحة
   الملاحظات الحقيقية (Server-Rendered + نماذج PRG عادية بـ ObservationController).

   الصفحة تشتغل بالكامل بدون هذا الملف: تصفّح، اختيار مهمة، إضافة/تعديل/حذف/عرض،
   وتصدير PDF للملاحظات المحفوظة -- كلها روابط/نماذج HTML عادية. هذا الملف يضيف
   فقط تحسينات لا تُغيّر أي سلوك أساسي:
     - فلاتر فورية بدون إعادة تحميل الصفحة (بحث/إدارة/خطورة/حالة/تاريخ) على
       صفوف الجدول المُرندرة من السيرفر أصلًا (data-* attributes)، بدل قوالب JS
     - تصدير PDF لمسودة نموذج الإضافة/التعديل قبل الحفظ (يقرأ القيم الحالية من
       حقول النموذج مباشرة، بنفس آلية exportObservationToPDF() بـ observations.js
       لكن بدون الاعتماد على obsDraft -- الصفحة هذي لا تحمّل observations.js)
   تبديل السايدبار بالموبايل وقائمة الحساب مشتركان بكل الصفحات الحقيقية —
   انتقلا لملف mvc-layout.js (يُحمَّل قبل هذا الملف بكل صفحة).
   ملاحظة: observations.js نفسه غير مُحمَّل هنا إطلاقًا ولا يُستخدم من هذا الملف --
   مستخدَم فقط من صفحات الـ SPA القديمة (senttasks.js/finalreports.js) اللي لسا
   تضمّن جدول/بطاقة الملاحظات بداخلها، بدون أي تغيير عليه.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  bindObsFilters();
  bindDraftExport();
});

/* ---------- فلاتر قائمة الملاحظات ---------- */
function bindObsFilters() {
  const mount = document.getElementById("obsFiltersMount");
  const table = document.getElementById("obsTable");
  if (!mount || !table) return;

  const rows = Array.from(table.querySelectorAll("[data-obs-row]"));
  if (rows.length === 0) return;

  const depts = [...new Set(rows.map(r => r.dataset.dept).filter(Boolean))].sort();
  const state = { q: "", dept: "", risk: "", status: "", from: "", to: "" };

  mount.innerHTML = `
    <div class="obs-filters-bar">
      <div class="obs-filter-field grow-2">
        <span class="obs-filter-label">بحث</span>
        <div class="obs-search-wrap">
          <i data-lucide="search"></i>
          <input id="pageObsSearch" type="text" placeholder="بحث بعنوان الملاحظة، الإدارة، أو المرجع...">
        </div>
      </div>
      <div class="obs-filter-field">
        <span class="obs-filter-label">الإدارة</span>
        <select id="pageObsDept" class="wiz-select">
          <option value="">كل الإدارات</option>
          ${depts.map(d => `<option value="${escapeAttr(d)}">${escapeAttr(d)}</option>`).join("")}
        </select>
      </div>
      <div class="obs-filter-field">
        <span class="obs-filter-label">مستوى الخطر</span>
        <div class="obs-risk-toggle">
          ${["عالي", "متوسط", "منخفض"].map(r => `<button type="button" class="obs-risk-btn" data-risk-filter="${r}">${r}</button>`).join("")}
        </div>
      </div>
      <div class="obs-filter-field">
        <span class="obs-filter-label">الحالة</span>
        <select id="pageObsStatus" class="wiz-select">
          <option value="">كل الحالات</option>
          ${["بانتظار الرد", "قيد المعالجة", "مغلقة"].map(s => `<option value="${s}">${s}</option>`).join("")}
        </select>
      </div>
      <button type="button" class="obs-adv-toggle-btn" id="pageObsAdvToggle"><i data-lucide="sliders-horizontal"></i> متقدم</button>
      <button type="button" class="obs-clear-btn" id="pageObsClear" style="display:none;"><i data-lucide="x"></i> مسح الفلاتر</button>
      <div class="obs-adv-row" id="pageObsAdvRow" style="display:none;">
        <div class="obs-filter-field">
          <span class="obs-filter-label">من تاريخ</span>
          <input id="pageObsFrom" type="date" class="wiz-input">
        </div>
        <div class="obs-filter-field">
          <span class="obs-filter-label">إلى تاريخ</span>
          <input id="pageObsTo" type="date" class="wiz-input">
        </div>
      </div>
    </div>`;
  if (window.lucide) lucide.createIcons();

  const apply = () => {
    let visibleCount = 0;
    rows.forEach(row => {
      const matches =
        (!state.q || row.dataset.title.includes(state.q) || row.dataset.dept.includes(state.q) || row.dataset.ref.includes(state.q)) &&
        (!state.dept || row.dataset.dept === state.dept) &&
        (!state.risk || row.dataset.risk === state.risk) &&
        (!state.status || row.dataset.status === state.status) &&
        (!state.from || row.dataset.date >= state.from) &&
        (!state.to || row.dataset.date <= state.to);
      row.style.display = matches ? "" : "none";
      if (matches) visibleCount++;
    });

    const hasFilters = !!(state.q || state.dept || state.risk || state.status || state.from || state.to);
    document.getElementById("pageObsClear").style.display = hasFilters ? "" : "none";

    let noMatchRow = table.querySelector("#obsNoMatchRow");
    if (visibleCount === 0 && hasFilters) {
      if (!noMatchRow) {
        noMatchRow = document.createElement("tr");
        noMatchRow.id = "obsNoMatchRow";
        const colCount = table.querySelectorAll("thead th").length;
        noMatchRow.innerHTML = `<td colspan="${colCount}" style="text-align:center;padding:32px 0;color:#9ca3af;font-size:13px;">لا توجد ملاحظات مطابقة للتصفية</td>`;
        table.querySelector("tbody").appendChild(noMatchRow);
      }
    } else if (noMatchRow) {
      noMatchRow.remove();
    }

    updateFilteredExportLink();
  };

  document.getElementById("pageObsSearch").addEventListener("input", e => { state.q = e.target.value; apply(); });
  document.getElementById("pageObsDept").addEventListener("change", e => { state.dept = e.target.value; apply(); });
  document.getElementById("pageObsStatus").addEventListener("change", e => { state.status = e.target.value; apply(); });
  document.getElementById("pageObsFrom").addEventListener("change", e => { state.from = e.target.value; apply(); });
  document.getElementById("pageObsTo").addEventListener("change", e => { state.to = e.target.value; apply(); });

  document.querySelectorAll("[data-risk-filter]").forEach(btn => {
    btn.addEventListener("click", () => {
      state.risk = state.risk === btn.dataset.riskFilter ? "" : btn.dataset.riskFilter;
      document.querySelectorAll("[data-risk-filter]").forEach(b => b.classList.toggle("sel-" + b.dataset.riskFilter, b === btn && !!state.risk));
      apply();
    });
  });

  document.getElementById("pageObsAdvToggle").addEventListener("click", () => {
    const row = document.getElementById("pageObsAdvRow");
    row.style.display = row.style.display === "none" ? "" : "none";
  });

  document.getElementById("pageObsClear").addEventListener("click", () => {
    state.q = state.dept = state.risk = state.status = state.from = state.to = "";
    mount.querySelectorAll("input").forEach(i => (i.value = ""));
    mount.querySelectorAll("select").forEach(s => (s.value = ""));
    document.querySelectorAll("[data-risk-filter]").forEach(b => b.classList.remove("sel-" + b.dataset.riskFilter));
    apply();
  });

  /* زر تصدير القائمة الحقيقي (رابط GET عادي) يبقى يصدّر القائمة كاملة بدون
     فلترة -- لو فيه فلاتر نشطة نبدّله لتصدير POST يطابق بالضبط الصفوف الظاهرة
     حاليًا فقط (نفس ما يشوفه المستخدم بالجدول)، بنفس آلية exportObservationsListToPDF() */
  function updateFilteredExportLink() {
    const btn = document.getElementById("obsExportBtn");
    if (!btn) return;
    const hasFilters = !!(state.q || state.dept || state.risk || state.status || state.from || state.to);
    btn.onclick = hasFilters ? (e => { e.preventDefault(); exportVisibleRowsToPDF(rows); }) : null;
  }
}

async function exportVisibleRowsToPDF(rows) {
  const btn = document.getElementById("obsExportBtn");
  const missionSelect = document.getElementById("obsTaskSelect");
  const missionOption = missionSelect ? missionSelect.options[missionSelect.selectedIndex] : null;
  const missionCode = missionOption ? missionOption.textContent.split("—")[0].trim() : "";

  const observations = rows
    .filter(r => r.style.display !== "none")
    .map(r => ({
      ref: r.dataset.ref, title: r.dataset.title, dept: r.dataset.dept, date: r.dataset.date, risk: r.dataset.risk,
    }));

  try {
    await postForPdfDownload(base + "/dashboard/pdf/observations-list-preview", { mission_code: missionCode, observations }, "ملاحظات-" + (missionCode || "رقابية") + ".pdf");
  } catch (e) {
    alert(e.message || "تعذّر تصدير المستند");
  }
}

/* ---------- تصدير PDF لمسودة نموذج الإضافة/التعديل قبل الحفظ ---------- */
function bindDraftExport() {
  const form = document.querySelector(".obs-form-card form[action*='/observations/api/save']");
  if (!form) return;

  const head = document.querySelector(".obs-form-head");
  if (!head) return;
  const actionsWrap = head.querySelector("div[style*='display:flex']") || head;

  const btn = document.createElement("button");
  btn.type = "button";
  btn.className = "obs-btn-pdf";
  btn.innerHTML = '<i data-lucide="file-text"></i> تصدير PDF';
  actionsWrap.insertBefore(btn, actionsWrap.firstChild);
  if (window.lucide) lucide.createIcons();

  btn.addEventListener("click", async () => {
    const val = id => (document.getElementById(id) ? document.getElementById(id).value : "");
    const checked = document.querySelector('input[name="add_to_report"]:checked');
    const refInput = form.querySelector('input[name="id"]');
    const deptField = form.querySelector(".obs-auto-field .val");

    try {
      await postForPdfDownload(base + "/dashboard/pdf/observation-preview", {
        ref: "",
        mission_code: "",
        title: val("obsTitle"),
        dept: deptField ? deptField.textContent.trim() : "",
        date: val("obsDate"),
        risk: val("obsRisk"),
        observation: val("obsObservation"),
        standard: val("obsStandard"),
        reason: val("obsReason"),
        impact: val("obsImpact"),
        recommendations: val("obsRecommendations"),
      }, "ملاحظة-مسودة.pdf");
    } catch (e) {
      alert(e.message || "تعذّر تصدير المستند");
    }
  });
}

function escapeAttr(str) {
  return String(str == null ? "" : str).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
