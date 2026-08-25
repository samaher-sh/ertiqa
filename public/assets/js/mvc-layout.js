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

  /* طي/فتح السايدبار (وضع الأيقونات فقط) -- الحالة تُحفظ بـ localStorage عشان
     تبقى ثابتة بين تنقّلات الصفحات الحقيقية (كل تنقّل هنا إعادة تحميل كاملة
     للصفحة، عكس السايدبار الافتراضي بالـ SPA القديمة اللي كانت تحتفظ بمتغيّر
     JS واحد طول الجلسة). القيمة الابتدائية تُطبَّق فورًا بسكربت inline
     بـ layouts/app.php نفسه (راجع تعليقه) عشان ما يصير "ومضة" سايدبار مفتوح
     قبل ما يتطبّق الطي. */
  const toggleBtn = document.getElementById("toggleNavBtn");
  const toggleBtnCollapsed = document.getElementById("toggleNavBtnCollapsed");
  if (sidebar && (toggleBtn || toggleBtnCollapsed)) {
    const setCollapsed = (collapsed) => {
      sidebar.classList.toggle("collapsed", collapsed);
      try { localStorage.setItem("sidebarCollapsed", collapsed ? "1" : "0"); } catch (e) {}
    };
    if (toggleBtn) toggleBtn.addEventListener("click", () => setCollapsed(true));
    if (toggleBtnCollapsed) toggleBtnCollapsed.addEventListener("click", () => setCollapsed(false));
  }
}
