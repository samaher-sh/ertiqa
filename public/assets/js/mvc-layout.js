/* ============================================================
   mvc-layout.js — تحسين تدريجي مشترك لكل صفحات layouts/app.php الحقيقية
   (Server-Rendered). الصفحة تشتغل بالكامل بدون هذا الملف؛ هذا يضيف فقط:
   تبديل السايدبار بالموبايل، وفتح/إغلاق قائمة الحساب بالهيدر — نفس سلوك
   renderSidebar()/renderProfile() بـ dashboard.js لكن بدون تحميل dashboard.js
   كاملًا (اللي يعيد بناء السايدبار/الهيدر بالكامل من الصفر ويفترض بنية الـ
   SPA shell.php، وده مو مطلوب هنا لأن الهيدر/السايدبار مُرندرين من السيرفر
   أصلًا بهالصفحات). يُحمَّل قبل أي ملف *-page.js خاص بصفحة معيّنة.
   ============================================================ */

document.addEventListener("DOMContentLoaded", bindMvcChrome);

function bindMvcChrome() {
  const menuBtn = document.getElementById("mobileMenuBtn");
  const overlay = document.getElementById("mobileOverlay");
  const sidebar = document.getElementById("sidebar");
  if (menuBtn && overlay && sidebar) {
    menuBtn.addEventListener("click", () => {
      sidebar.classList.add("mobile-open");
      overlay.classList.add("show");
    });
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("mobile-open");
      overlay.classList.remove("show");
    });
  }

  const profileBtn = document.getElementById("profileBtn");
  const profileWrap = document.getElementById("profileWrap");
  if (profileBtn && profileWrap) {
    profileBtn.addEventListener("click", e => {
      e.stopPropagation();
      profileWrap.classList.toggle("open");
    });
    document.addEventListener("click", e => {
      if (!profileWrap.contains(e.target)) profileWrap.classList.remove("open");
    });
  }
}
