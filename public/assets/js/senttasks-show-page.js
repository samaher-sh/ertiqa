/* ============================================================
   senttasks-show-page.js — تحسين تدريجي لصفحة تفاصيل المهمة الحقيقية
   (SentTasksController::show). الصفحة تشتغل بالكامل بدون هذا الملف: كل
   رابط "عرض" بكارت "المراحل المنجزة" رابط <a href target="_blank"> حقيقي
   يفتح بتبويب جديد.
   هذا الملف يضيف تحسين واحد فقط: بدل فتح تبويب جديد (أو تنزيل مباشر لو
   كان الرابط PDF)، يفتح نافذة معاينة منبثقة (iframe) بنفس الصفحة، مع زر
   إغلاق -- مطابقةً لطلب "أبغى أشوفه بس، مو يحمّل لي الملف".
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".st-stage-preview-btn").forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      openStagePreviewModal(btn.getAttribute("href"), btn.dataset.previewTitle || "معاينة");
    });
  });
});

function openStagePreviewModal(url, title) {
  const overlay = document.createElement("div");
  overlay.className = "st-preview-modal-overlay";
  overlay.innerHTML = `
    <div class="st-preview-modal">
      <div class="st-preview-modal-head">
        <span>${escapeHtml(title)}</span>
        <button type="button" class="st-preview-modal-close" title="إغلاق"><i data-lucide="x"></i></button>
      </div>
      <iframe src="${url}"></iframe>
    </div>`;
  document.body.appendChild(overlay);
  if (window.lucide) lucide.createIcons();

  /* الصفحة المعروضة بالـ iframe صفحة كاملة (layouts/app.php بسايدبار وهيدر
     خاصين فيها)، والمستخدم أصلًا داخل سياق هذي المهمة وشغّال بنفس السايدبار/
     الهيدر الحقيقيين خارج المعاينة -- فتكرارهما جوّا iframe زائد بدون فايدة.
     بما إنه نفس الأصل (same-origin)، بعد تحميل iframe نخفي: السايدبار، الهيدر
     العلوي، وبطاقة "المهمة / الإدارة المرتبطة" (نفس .obs-linked-card
     المشتركة)، عشان يبين محتوى الصفحة المطلوب فقط */
  const iframe = overlay.querySelector("iframe");
  iframe.addEventListener("load", () => {
    try {
      const doc = iframe.contentDocument;
      ["#sidebar", ".topbar", ".mobile-overlay", ".obs-linked-card"].forEach(sel => {
        const el = doc.querySelector(sel);
        if (el) el.style.display = "none";
      });
    } catch (e) {}
  });

  const close = () => overlay.remove();
  overlay.addEventListener("click", e => { if (e.target === overlay) close(); });
  overlay.querySelector(".st-preview-modal-close").addEventListener("click", close);
}
