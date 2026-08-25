/* ============================================================
   meetingschedule-page.js — تحسين تدريجي لصفحة جدولة الاجتماع الحقيقية
   (MissionChatController::index + send/propose/confirm/cancel/cancel-confirmed).
   الصفحة تشتغل بالكامل بدون هذا الملف: كل الأزرار نماذج POST/Redirect/GET
   عادية، ومنتقي "اقترح موعدًا" عنصر <details>/<summary> أصلي.
   هذا الملف يضيف فقط: تمرير المحادثة لآخر رسالة تلقائيًا عند فتح الصفحة.
   (التحديث الدوري كل 5 ثواني بالـ SPA الأصلية ميزة "شات حي" اختيارية، غير
   ضرورية لصحة البيانات على صفحة حقيقية يعاد تحميلها بعد كل إجراء أصلًا)
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const body = document.getElementById("mcChatBody");
  if (body) body.scrollTop = body.scrollHeight;
});
