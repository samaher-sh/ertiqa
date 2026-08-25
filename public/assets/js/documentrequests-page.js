/* ============================================================
   documentrequests-page.js — تحسين تدريجي بسيط لصفحة قائمة المستندات
   الحقيقية (DocumentRequestController::index/add). الصفحة تشتغل بالكامل
   بدون هذا الملف: <details>/<summary> أصلي لإظهار/إخفاء نموذج "إضافة
   مستند"، ونموذج POST/Redirect/GET عادي للحفظ.
   هذا الملف يضيف فقط: تركيز تلقائي على حقل اسم المستند عند فتح النموذج.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const details = document.getElementById("drAddDetails");
  if (!details) return;
  details.addEventListener("toggle", () => {
    if (details.open) {
      const input = details.querySelector('input[name="doc_name"]');
      if (input) input.focus();
    }
  });
});
