/* ============================================================
   home-page.js — تحسين تدريجي بسيط للصفحة الرئيسية الحقيقية
   (DashboardController::index). الصفحة تشتغل بالكامل بدون هذا الملف:
   <details>/<summary> أصلي لفتح/طي ودجت الإخطارات، وروابط <a> حقيقية
   لكل تنقّل (مؤشرات الأداء، فتح إخطار...).
   هذا الملف يضيف فقط: إخفاء إخطار واحد محليًا عند الضغط على زر "×"
   (بدون أي تأثير على السيرفر -- نفس سلوك dismissedNotificationKeys
   بالواجهة الأصلية بالضبط: حالة مؤقتة تُصفَّر تلقائيًا عند إعادة تحميل الصفحة).
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".notif-item-dismiss").forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const item = btn.closest(".notif-item");
      if (item) item.remove();
    });
  });
});
