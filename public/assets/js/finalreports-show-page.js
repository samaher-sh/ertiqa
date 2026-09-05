/* ============================================================
   finalreports-show-page.js — تحسين تدريجي لصفحة مراحل اعتماد تقرير حقيقية
   (ReportController::show). إخفاء عناصر التصفح المشتركة داخل iframe معاينة
   المرحلة الحالية فقط -- زر "اعتماد التقرير" نموذج عادي بضغطة واحدة، ما
   يحتاج أي جافاسكربت (كان فيه توقيع يدوي بـ canvas، اتحذف).
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
});
