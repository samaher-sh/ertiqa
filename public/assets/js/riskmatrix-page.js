/* ============================================================
   riskmatrix-page.js — تحسين تدريجي لصفحة تعديل مصفوفة المخاطر الحقيقية
   (RiskMatrixController::edit/save). الصفحة تشتغل بالكامل بدون هذا الملف:
   "إضافة صف"/"حذف صف" أزرار submit عادية (form_action=add_row/remove_row)
   تروح للسيرفر، يعدّل المصفوفة المؤقتة (بدون حفظ فعلي بقاعدة البيانات)،
   ويعيد رسم نفس النموذج -- round-trip كلاسيكي شغّال 100% بدون جافاسكربت.

   هذا الملف يعترض نفس الزرين ليصيرا فوريين بدون أي اتصال بالسيرفر (إضافة/حذف
   صف بالمتصفح مباشرة)، وزر "حفظ مصفوفة المخاطر" يبقى submit عادي حقيقي زي ما هو.
   ============================================================ */

/* نفس autoGrowTextareaRM() بـ riskmatrix.js القديم -- الحقول تكبر تلقائيًا
   حسب المحتوى بدل شريط تمرير داخلي ثابت الارتفاع */
function autoGrowRmTextarea(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
}
function bindRmAutoGrow(scope) {
  scope.querySelectorAll(".wiz-textarea.plain").forEach(el => {
    autoGrowRmTextarea(el);
    el.addEventListener("input", () => autoGrowRmTextarea(el));
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("rmEditForm");
  if (!form) return;

  const wrap = document.getElementById("rmRowsWrap");
  const addBtn = document.getElementById("rmAddRowBtn");
  if (!wrap || !addBtn) return;

  bindRmAutoGrow(wrap);

  addBtn.addEventListener("click", e => {
    e.preventDefault();
    addRow();
  });

  wrap.addEventListener("click", e => {
    const delBtn = e.target.closest("[data-remove-row-btn]");
    if (!delBtn) return;
    e.preventDefault();
    delBtn.closest("[data-rm-row]").remove();
    renumberRows();
  });

  function rowTemplate(index) {
    return `
      <div class="rm-edit-row" data-rm-row>
        <div class="rm-edit-row-head">
          <span class="rm-edit-row-num">#${index + 1}</span>
          <button type="button" class="obs-menu-item danger" data-remove-row-btn style="width:auto;padding:4px 10px;"><i data-lucide="trash-2"></i> حذف الصف</button>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">المخاطر <span class="wiz-req">*</span></label>
          <textarea name="rows[${index}][risk]" rows="2" class="wiz-textarea plain" placeholder="أدخل وصف الخطر..."></textarea>
        </div>
        <div class="obs-grid-2">
          <div class="wiz-field">
            <label class="wiz-label">تقييم المخاطر</label>
            <select name="rows[${index}][risk_rating]" class="wiz-select">
              <option value="">— اختر —</option>
              <option value="عالي">عالي</option>
              <option value="متوسط">متوسط</option>
              <option value="منخفض">منخفض</option>
            </select>
          </div>
          <div class="wiz-field">
            <label class="wiz-label">نوع النشاط</label>
            <input type="text" name="rows[${index}][activity_type]" class="wiz-input plain">
          </div>
          <div class="wiz-field" style="grid-column:1/-1;">
            <label class="wiz-label">وصف الضوابط</label>
            <textarea name="rows[${index}][controls]" rows="2" class="wiz-textarea plain"></textarea>
          </div>
        </div>
      </div>`;
  }

  function addRow() {
    const emptyState = document.getElementById("rmEmptyState");
    if (emptyState) emptyState.remove();
    const index = wrap.querySelectorAll("[data-rm-row]").length;
    const div = document.createElement("div");
    div.innerHTML = rowTemplate(index).trim();
    const rowEl = div.firstChild;
    wrap.appendChild(rowEl);
    bindRmAutoGrow(rowEl);
    if (window.lucide) lucide.createIcons();
  }

  function renumberRows() {
    const rows = wrap.querySelectorAll("[data-rm-row]");
    if (rows.length === 0) {
      wrap.innerHTML = '<div class="obs-empty" id="rmEmptyState"><i data-lucide="shield-alert"></i><p class="main">لا توجد صفوف بعد</p><p class="hint">اضغطي "إضافة صف" لبدء تعبئة الجدول</p></div>';
      if (window.lucide) lucide.createIcons();
      return;
    }
    rows.forEach((row, i) => {
      row.querySelector(".rm-edit-row-num").textContent = "#" + (i + 1);
      row.querySelectorAll("[name]").forEach(field => {
        field.name = field.name.replace(/rows\[\d+\]/, "rows[" + i + "]");
      });
    });
  }

  /* أزرار "حذف الصف" المُرندرة من السيرفر (وضع بدون-JS) لها onclick inline
     يضبط حقل remove_index قبل الإرسال -- نستبدلها بزر عادي (data-remove-row-btn)
     يشتغل فوريًا بدون submit، بدل ما نترك الاثنين يتعارضان */
  wrap.querySelectorAll("[data-rm-row] button[value='remove_row']").forEach(btn => {
    btn.removeAttribute("name");
    btn.removeAttribute("value");
    btn.removeAttribute("onclick");
    btn.setAttribute("type", "button");
    btn.setAttribute("data-remove-row-btn", "");
  });
});
