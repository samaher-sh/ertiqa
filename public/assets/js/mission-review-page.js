/* ============================================================
   mission-review-page.js — تحسين تدريجي لصفحة "استكمال الاتفاقية"
   (MissionReviewController::index). النموذج يشتغل بالكامل بدون هذا
   الملف (radio input حقيقي، إرسال HTML عادي). هذا الملف يزامن فقط
   الشكل المرئي لخانات "موافق/غير موافق" (الكلاس checked + أيقونة
   الصح/الإكس) مع حالة الـ radio الفعلية وقت الضغط، لأنها تُحسَب
   حاليًا سيرفر-سايد وقت التحميل فقط ولا تتحرك تلقائيًا بمجرد النقر.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("mrAgreementForm");
  if (!form) return;

  /* غلاف <span> ثابت حول أيقونة lucide -- lucide.createIcons() يستبدل
     <i data-lucide> بـ <svg> فيفقد أي علامة نقدر نلقاها بها بالمرة الجاية،
     فنبقي الغلاف نفسه ثابت ونستبدل بس محتواه بكل تبديل */
  function setToggleVisual(label, checked, iconName, color) {
    label.classList.toggle("checked", checked);
    let wrap = label.querySelector(".mr-toggle-icon");
    if (checked) {
      if (!wrap) {
        wrap = document.createElement("span");
        wrap.className = "mr-toggle-icon";
        label.appendChild(wrap);
      }
      wrap.innerHTML = `<i data-lucide="${iconName}" style="width:14px;height:14px;color:${color};"></i>`;
    } else if (wrap) {
      wrap.remove();
    }
  }

  form.addEventListener("change", (e) => {
    const input = e.target;
    if (input.tagName !== "INPUT" || input.type !== "radio") return;

    const match = /^rows\[(\d+)\]\[answer\]$/.exec(input.name || "");
    if (!match) return;
    const rowId = match[1];

    const agreeLabel = form.querySelector(`[data-mr-row="${rowId}"][data-mr-answer="agree"]`);
    const disagreeLabel = form.querySelector(`[data-mr-row="${rowId}"][data-mr-answer="disagree"]`);
    if (!agreeLabel || !disagreeLabel) return;

    const isAgree = input.value === "agree";
    setToggleVisual(agreeLabel, isAgree, "check", "var(--p)");
    setToggleVisual(disagreeLabel, !isAgree, "x", "#dc2626");

    const row = input.closest("tr");
    if (row) row.classList.remove("mr-row-unanswered");

    if (window.lucide) lucide.createIcons();
  });
});
