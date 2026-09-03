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
      </tr>
      <tr class="msum-point-response-row">
        <td colspan="3">
          <div class="msum-point-response">
            <label>الرأي</label>
            <div class="msum-opinion-readonly empty">—</div>
            <input type="hidden" name="points[${index}][hr_opinion]" value="">
            <label>السبب</label>
            <div class="msum-opinion-readonly empty">—</div>
            <input type="hidden" name="points[${index}][hr_reason]" value="">
          </div>
        </td>
      </tr>`,
  });

  initSignaturePad();
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

  /* بعض المجموعات (النقاط) تُرندَر كصفَّين لكل عنصر: الصف الرئيسي (مطابق
     rowSelector) وصف "ردّ الإدارة" أسفله مباشرة (بدون data-attribute مطابق
     لـ rowSelector) -- هذي الدالة تجمع الصف الرئيسي مع كل الصفوف التابعة له
     (اللي تليه ولا تطابق rowSelector) كوحدة واحدة تُضاف/تُحذف/تُرقَّم معًا */
  function rowGroup(row) {
    const group = [row];
    let sib = row.nextElementSibling;
    while (sib && !sib.matches(rowSelector)) {
      group.push(sib);
      sib = sib.nextElementSibling;
    }
    return group;
  }

  if (addBtn) {
    addBtn.setAttribute("type", "button");
    addBtn.removeAttribute("name");
    addBtn.removeAttribute("value");
    addBtn.addEventListener("click", () => {
      const emptyRow = tbody.querySelector(".msum-empty-points")?.closest("tr");
      if (emptyRow) emptyRow.remove();
      const index = tbody.querySelectorAll(rowSelector).length;
      const div = document.createElement("tbody");
      div.innerHTML = template(index).trim();
      Array.from(div.children).forEach(row => {
        tbody.appendChild(row);
        bindMSumAutoGrow(row);
      });
      if (window.lucide) lucide.createIcons();
    });
  }

  tbody.querySelectorAll(delSelector).forEach(convertDeleteButton);

  tbody.addEventListener("click", e => {
    const btn = e.target.closest(delSelector);
    if (!btn) return;
    rowGroup(btn.closest(rowSelector)).forEach(row => row.remove());
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
      rowGroup(row).flatMap(r => Array.from(r.querySelectorAll("[name]"))).forEach(field => {
        field.name = field.name.replace(new RegExp("^" + fieldPrefix + "\\[\\d+\\]"), fieldPrefix + "[" + i + "]");
      });
    });
  }
}

function initSignaturePad() {
  const cell = document.getElementById("msumSigCell");
  const hiddenInput = document.getElementById("msumSignatureInput");
  const preview = document.getElementById("msumSigPreview");
  if (!cell || !hiddenInput) return;

  if (preview) preview.remove();

  const wrap = document.createElement("div");
  wrap.className = "msum-sig-pad-wrap";
  wrap.innerHTML = '<canvas class="msum-sig-canvas" width="220" height="80"></canvas><button type="button" class="msum-sig-clear" title="مسح التوقيع">✕</button>';
  cell.appendChild(wrap);
  const canvas = wrap.querySelector("canvas");
  const clearBtn = wrap.querySelector(".msum-sig-clear");

  const ctx = canvas.getContext("2d");
  ctx.strokeStyle = "#152c33";
  ctx.lineWidth = 2;
  ctx.lineCap = "round";
  ctx.lineJoin = "round";

  if (hiddenInput.value) {
    const img = new Image();
    img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    img.src = hiddenInput.value;
  }

  let drawing = false, last = null;
  const pointFromEvent = e => {
    const rect = canvas.getBoundingClientRect();
    const src = e.touches && e.touches.length ? e.touches[0] : e;
    return { x: (src.clientX - rect.left) * (canvas.width / rect.width), y: (src.clientY - rect.top) * (canvas.height / rect.height) };
  };
  const start = e => { e.preventDefault(); drawing = true; last = pointFromEvent(e); };
  const move = e => {
    if (!drawing) return;
    e.preventDefault();
    const p = pointFromEvent(e);
    ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
    last = p;
  };
  const end = () => { if (!drawing) return; drawing = false; hiddenInput.value = canvas.toDataURL("image/png"); };

  canvas.addEventListener("mousedown", start);
  canvas.addEventListener("mousemove", move);
  window.addEventListener("mouseup", end);
  canvas.addEventListener("touchstart", start, { passive: false });
  canvas.addEventListener("touchmove", move, { passive: false });
  canvas.addEventListener("touchend", end);

  clearBtn.addEventListener("click", () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hiddenInput.value = "";
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
