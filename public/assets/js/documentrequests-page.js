/* ============================================================
   documentrequests-page.js — تحسين تدريجي بسيط لصفحة قائمة المستندات
   الحقيقية (DocumentRequestController::index/add). الصفحة تشتغل بالكامل
   بدون هذا الملف: <details>/<summary> أصلي لإظهار/إخفاء نموذج "إضافة
   مستند"، ونموذج POST/Redirect/GET عادي للحفظ.
   هذا الملف يضيف: تركيز تلقائي على حقل اسم المستند عند فتح النموذج، وتحديث
   نص زر "رفع الملف" باسم الملف المختار (input[type=file] الأصلي مخفي
   وراء label منسَّق، فبدون هذا التحديث ما فيه أي مؤشر مرئي إن الاختيار
   نجح فعلًا -- كان يبان للمستخدم وكأن "رفع الملف" ما يشتغل رغم إنه يشتغل
   فعليًا ويُرفع صح وقت الضغط على "إرسال المستندات").
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const details = document.getElementById("drAddDetails");
  if (details) {
    details.addEventListener("toggle", () => {
      if (details.open) {
        const input = details.querySelector('input[name="doc_name"]');
        if (input) input.focus();
      }
    });
  }

  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener("change", () => {
      const label = document.querySelector(`label[for="${input.id}"]`);
      const span = label ? label.querySelector("span") : null;
      if (!label || !span) return;
      if (input.files && input.files.length > 0) {
        span.textContent = input.files[0].name;
        label.classList.add("has-file");
      } else {
        span.textContent = "رفع الملف";
        label.classList.remove("has-file");
      }
    });
  });
});
