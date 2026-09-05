/* ============================================================
   meetingsummary-page.js — تحسين تدريجي لصفحة ملخص الاجتماع الحقيقية
   (MeetingSummaryController::index/save). الصفحة تشتغل بالكامل بدون هذا
   الملف: كل الحقول + إضافة/حذف صفوف الحضور والنقاط (round-trip عادي
   form_action=add_attendee/remove_attendee/add_point/remove_point) + الحفظ
   النهائي -- نماذج HTML عادية بالكامل.

   هذا الملف يضيف 3 تحسينات لا تُغيّر أي سلوك أساسي:
     1) إضافة/حذف صفوف الحضور والنقاط تصير فورية بالمتصفح بدل round-trip
        (بنفس نمط riskmatrix-page.js)
     2) لوحة توقيع تفاعلية بـ canvas لخانة "إعداد واعتماد" -- ميزة تحتاج
        جافاسكربت أساسًا (رسم بالماوس/اللمس)، فبدون هذا الملف تبقى الخانة
        تعرض التوقيع المحفوظ سابقًا (أو رسالة "لا يوجد توقيع") فقط، بدون
        إمكانية رسم توقيع جديد -- هذا الاستثناء الوحيد المعقول لأن التوقيع
        اليدوي أصلًا تفاعل بصري بحت
     3) رفع مرفقات فوري (fetch بدل تنقل صفحة كامل) -- endpoint الرفع
        (DocumentController::uploadMeetingAttachment) يرجّع JSON فقط، فبدون
        جافاسكربت ما فيه طريقة نظيفة تعرض الاستجابة، فزر "إرفاق ملفات" نفسه
        يُضاف من هذا الملف فقط (غير موجود بالـ HTML الأساسي)
   ============================================================ */

/* نفس autoGrowTextarea() بـ meetingsummary.js القديم -- كل حقول النص الطويل
   (مكان الاجتماع، عنوان المهمة، الهدف، النقطة/الإفادة) تكبر تلقائيًا
   حسب المحتوى بدل ما يبقى ارتفاعها ثابت ويختفي الكلام اللي فوق */
function autoGrowMSumTextarea(el) {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
}
function bindMSumAutoGrow(scope) {
  scope.querySelectorAll(".wiz-textarea, .msum-growfield").forEach(el => {
    autoGrowMSumTextarea(el);
    el.addEventListener("input", () => autoGrowMSumTextarea(el));
  });
}

document.addEventListener("DOMContentLoaded", () => {
  bindMSumAutoGrow(document);

  bindRowGroup({
    tbodyId: "msumAttendanceBody", rowSelector: "[data-msum-attendee-row]",
    addBtnId: "msumAddAttendanceBtn", delSelector: "[data-msum-del-attendee]",
    fieldPrefix: "attendees", rowNumSelector: null,
    template: index => `
      <tr data-msum-attendee-row>
        <td class="msum-row-num">${index + 1}</td>
        <td><input type="text" name="attendees[${index}][name]" class="msum-plain-input" placeholder="أدخل الاسم"></td>
        <td><input type="text" name="attendees[${index}][dept]" class="msum-plain-input" placeholder="أدخل الإدارة"></td>
        <td><input type="text" name="attendees[${index}][position]" class="msum-plain-input" placeholder="أدخل الوظيفة"></td>
        <td style="text-align:center;"><button type="button" class="msum-del-btn" data-msum-del-attendee><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button></td>
      </tr>`,
  });

  bindRowGroup({
    tbodyId: "msumPointsBody", rowSelector: "[data-msum-point-row]",
    addBtnId: "msumAddPointBtn", delSelector: "[data-msum-del-point]",
    fieldPrefix: "points", rowNumSelector: ".msum-point-num",
    template: index => `
      <tr data-msum-point-row>
        <td><textarea rows="2" name="points[${index}][text]" class="wiz-textarea plain" placeholder="النقطة ${index + 1}..."></textarea></td>
        <td><textarea rows="2" class="wiz-textarea" style="border:1.5px solid var(--pb);background:#f0f8fd;" name="points[${index}][statement]" placeholder="اكتب الإفادة..."></textarea></td>
        <td style="text-align:center;"><button type="button" class="msum-del-btn" data-msum-del-point><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button></td>
      </tr>`,
  });

  bindApproveCheckbox();
  initAttachUpload();
  bindAttachListActions();
});

/* ---------- عرض/حذف مرفق من قائمة "المرفقات" ---------- */
function bindAttachListActions() {
  const wrap = document.getElementById("msumAttachListWrap");
  if (!wrap) return;

  wrap.addEventListener("click", async e => {
    const viewBtn = e.target.closest(".msum-attach-view-btn");
    if (viewBtn) {
      const row = viewBtn.closest(".msum-attach-row");
      openAttachPreviewModal(row.dataset.attachName, row.dataset.attachUrl);
      return;
    }

    const delBtn = e.target.closest(".msum-attach-del-btn");
    if (delBtn) {
      if (!confirm("هل أنت متأكد من حذف هذا المرفق؟")) return;
      try {
        const data = await apiPost(base + "/dashboard/documents/delete/" + delBtn.dataset.attachId, {});
        if (data.success) {
          const row = delBtn.closest(".msum-attach-row");
          const list = row.parentElement;
          row.remove();
          if (!list.querySelector(".msum-attach-row")) {
            list.outerHTML = '<div style="padding:16px 24px;"><span class="msum-attach-empty" style="margin-right:0;">لا توجد مرفقات</span></div>';
          }
        } else {
          alert(data.message || "تعذّر حذف المرفق");
        }
      } catch (err) {
        alert(err.message || "تعذّر حذف المرفق");
      }
    }
  });
}

function openAttachPreviewModal(name, url) {
  const overlay = document.createElement("div");
  overlay.className = "msum-attach-modal-overlay";
  overlay.innerHTML = `
    <div class="msum-attach-modal">
      <i data-lucide="file-text" class="file-ic"></i>
      <p>${escapeHtml(name)}</p>
      <div class="msum-attach-modal-actions">
        <a class="msum-attach-modal-download" href="${url}" target="_blank"><i data-lucide="download"></i> تحميل</a>
        <button type="button" class="msum-attach-modal-close">إغلاق</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  if (window.lucide) lucide.createIcons();

  const close = () => overlay.remove();
  overlay.addEventListener("click", e => { if (e.target === overlay) close(); });
  overlay.querySelector(".msum-attach-modal-close").addEventListener("click", close);
}

function bindRowGroup({ tbodyId, rowSelector, addBtnId, delSelector, fieldPrefix, template }) {
  const tbody = document.getElementById(tbodyId);
  const addBtn = document.getElementById(addBtnId);
  if (!tbody) return;

  if (addBtn) {
    addBtn.setAttribute("type", "button");
    addBtn.removeAttribute("name");
    addBtn.removeAttribute("value");
    addBtn.addEventListener("click", () => {
      const emptyRow = tbody.querySelector("tr:not([" + rowSelector.slice(1, -1) + "])");
      if (emptyRow) emptyRow.remove();
      const index = tbody.querySelectorAll(rowSelector).length;
      const div = document.createElement("tbody");
      div.innerHTML = template(index).trim();
      const newRow = div.firstElementChild;
      tbody.appendChild(newRow);
      bindMSumAutoGrow(newRow);
      if (window.lucide) lucide.createIcons();
    });
  }

  tbody.querySelectorAll(delSelector).forEach(convertDeleteButton);

  tbody.addEventListener("click", e => {
    const btn = e.target.closest(delSelector);
    if (!btn) return;
    btn.closest(rowSelector).remove();
    renumber();
  });

  function convertDeleteButton(btn) {
    btn.removeAttribute("name");
    btn.removeAttribute("value");
    btn.removeAttribute("onclick");
    btn.setAttribute("type", "button");
  }

  function renumber() {
    const rows = tbody.querySelectorAll(rowSelector);
    rows.forEach((row, i) => {
      const numCell = row.querySelector(".msum-row-num, .msum-point-num");
      if (numCell) numCell.textContent = String(i + 1);
      row.querySelectorAll("[name]").forEach(field => {
        field.name = field.name.replace(new RegExp("^" + fieldPrefix + "\\[\\d+\\]"), fieldPrefix + "[" + i + "]");
      });
    });
  }
}

/* اعتماد "إعداد واعتماد" بضغطة واحدة (بدون توقيع يدوي) -- checkbox واحد
   يضبط قيمة الحقل المخفي المستخدَم أصلًا لتخزين التوقيع ('1' بدل صورة
   base64)، ويسجّل تاريخ اليوم تلقائيًا وقت التفعيل */
function bindApproveCheckbox() {
  const checkbox = document.getElementById("msumApproveCheckbox");
  const hiddenInput = document.getElementById("msumSignatureInput");
  const dateInput = document.getElementById("msumApprovalDate");
  if (!checkbox || !hiddenInput) return;

  checkbox.addEventListener("change", () => {
    hiddenInput.value = checkbox.checked ? "1" : "";
    if (checkbox.checked && dateInput && !dateInput.value) {
      dateInput.value = new Date().toISOString().slice(0, 10);
    }
  });
}

function initAttachUpload() {
  const mount = document.getElementById("msumAttachMount");
  const form = document.getElementById("msumForm");
  if (!mount || !form) return;
  const missionId = form.querySelector('input[name="mission_id"]').value;
  if (!missionId || missionId === "0") return;

  mount.innerHTML = `
    <label class="msum-attach-btn" style="cursor:pointer;box-shadow:none;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);">
      <i data-lucide="upload"></i> إرفاق ملفات
      <input type="file" id="msumAttachInput" hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
    </label>`;
  if (window.lucide) lucide.createIcons();

  document.getElementById("msumAttachInput").addEventListener("change", async e => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append("mission_id", missionId);
    formData.append("file", file);
    const csrf = getCsrfMeta();
    if (csrf.name && csrf.value) formData.append(csrf.name, csrf.value);
    try {
      const res = await fetch(base + "/dashboard/meetings/api/upload", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      } else {
        alert(data.message || "تعذّر رفع الملف");
      }
    } catch (err) {
      alert("تعذّر الاتصال بالخادم");
    }
  });
}
