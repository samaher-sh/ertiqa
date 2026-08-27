/* ============================================================
   finalreports-show-page.js — تحسين تدريجي لصفحة مراحل اعتماد تقرير حقيقية
   (ReportController::show). دالتان مستقلتان:
   1) إخفاء عناصر التصفح المشتركة داخل iframe معاينة المرحلة الحالية.
   2) لوحة توقيع تفاعلية بـ canvas لكومبوننت "اعتماد رئيس إدارة المراجعة
      الداخلية" (نفس آلية msumInitSignaturePad/bindSignaturePad المستخدمة
      بملخص الاجتماع وخطوة اتفاقية مستوى الخدمة)، تربط التوقيع + الاسم
      بحقلين مخفيين يُرسلان مع نموذج الاعتماد العادي.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  /* نافذة معاينة مرحلة الاعتماد الحالية (fr-step-iframe): نفس الصفحة
     الحقيقية المطابقة للمرحلة (خطاب/اتفاقية/مستندات/مخاطر/اجتماع/ملاحظات)،
     مضمَّنة same-origin -- نخفي عناصر التصفح المشتركة (سايدبار/هيدر) وبطاقة
     "المهمة المرتبطة" عشان يبين محتوى المرحلة فقط، نفس أسلوب
     senttasks-show-page.js بالضبط */
  document.querySelectorAll(".fr-step-iframe").forEach(iframe => {
    iframe.addEventListener("load", () => {
      try {
        const doc = iframe.contentDocument;
        ["#sidebar", ".topbar", ".mobile-overlay", ".obs-linked-card"].forEach(sel => {
          const el = doc.querySelector(sel);
          if (el) el.style.display = "none";
        });
      } catch (e) {}
    });
  });

  const canvas = document.getElementById("frHeadSigPad");
  const form = document.getElementById("frApproveForm");
  if (!canvas || !form) return;

  const ctx = canvas.getContext("2d");
  ctx.strokeStyle = "#152c33";
  ctx.lineWidth = 2;
  ctx.lineCap = "round";
  ctx.lineJoin = "round";

  const hint = document.getElementById("frHeadSigPadHint");
  let drawing = false;
  let last = null;
  let hasSignature = false;

  function pointFromEvent(e) {
    const rect = canvas.getBoundingClientRect();
    const src = e.touches && e.touches.length ? e.touches[0] : e;
    return {
      x: (src.clientX - rect.left) * (canvas.width / rect.width),
      y: (src.clientY - rect.top) * (canvas.height / rect.height),
    };
  }
  function start(e) {
    e.preventDefault();
    drawing = true;
    last = pointFromEvent(e);
  }
  function move(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = pointFromEvent(e);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
    hasSignature = true;
    if (hint) hint.style.display = "none";
  }
  function end() { drawing = false; }

  canvas.addEventListener("mousedown", start);
  canvas.addEventListener("mousemove", move);
  window.addEventListener("mouseup", end);
  canvas.addEventListener("touchstart", start, { passive: false });
  canvas.addEventListener("touchmove", move, { passive: false });
  canvas.addEventListener("touchend", end);

  const clearBtn = document.getElementById("frHeadSigPadClear");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSignature = false;
    if (hint) hint.style.display = "";
  });

  form.addEventListener("submit", e => {
    const nameInput = document.getElementById("frHeadName");
    const name = (nameInput.value || "").trim();
    if (!name) {
      e.preventDefault();
      nameInput.focus();
      return;
    }
    if (!hasSignature) {
      e.preventDefault();
      if (hint) { hint.textContent = "يجب التوقيع قبل الاعتماد"; hint.style.display = ""; hint.style.color = "#dc2626"; }
      return;
    }
    document.getElementById("frHeadNameHidden").value = name;
    document.getElementById("frHeadSigHidden").value = canvas.toDataURL("image/png");
  });
});
