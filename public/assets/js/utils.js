/* ============================================================
   utils.js — دوال مشتركة تُستخدم بكل صفحات المشروع
   لازم يُحمَّل هذا الملف أول شي بـ shell.php (قبل باقي السكربتات)
   ============================================================ */

/**
 * تعقيم نص قبل حقنه بـ innerHTML — نسخة واحدة موحّدة تحل محل
 * escHtml / escHtml2 / escHtmlRM / escHtmlMSum / escHtmlFR / escHtmlST /
 * escHtmlTD / escHtmlMS / escHtmlNotif / escapeHtml (كانت 10 نسخ مكررة،
 * وفيها فرق فعلي كان يسبب خطر أمني بسيط بنسخة dashboard.js القديمة:
 * escapeHtml القديمة كانت تعتمد على div.textContent/innerHTML، وهذي الطريقة
 * لا تُعقّم علامة الاقتباس المزدوجة (") فعليًا، فكانت تسمح بكسر خاصية HTML
 * لو النص محقون داخل attribute بدل عنصر عادي).
 */
function escapeHtml(str) {
  return String(str == null ? "" : str)
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

/**
 * قراءة اسم/قيمة CSRF Token من meta tags الموجودة بكل صفحة
 */
function getCsrfMeta() {
  const nameEl  = document.querySelector('meta[name="csrf-token-name"]');
  const valueEl = document.querySelector('meta[name="csrf-token-value"]');
  return {
    name:  nameEl  ? nameEl.content  : null,
    value: valueEl ? valueEl.content : null,
  };
}

/**
 * طلب GET موحّد — يرجّع JSON مباشرة، ويرمي خطأ واضح لو فشل الطلب
 */
async function apiGet(path) {
  const res = await fetch(path, { headers: { "Accept": "application/json" } });
  if (!res.ok) {
    let msg = "تعذّر تحميل البيانات (خطأ " + res.status + ")";
    try { const data = await res.json(); if (data && data.message) msg = data.message; } catch (e) {}
    throw new Error(msg);
  }
  return res.json();
}

/**
 * طلب POST موحّد — يضيف CSRF تلقائيًا، ويرجّع JSON مباشرة
 */
async function apiPost(path, body) {
  const csrf = getCsrfMeta();
  const payload = { ...(body || {}) };
  if (csrf.name && csrf.value) payload[csrf.name] = csrf.value;

  const res = await fetch(path, {
    method: "POST",
    headers: { "Content-Type": "application/json", "Accept": "application/json" },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok && !("success" in data)) {
    throw new Error(data.message || "تعذّر إتمام العملية (خطأ " + res.status + ")");
  }
  return data;
}

/**
 * طلب POST لرفع ملفات (multipart) — بدون Content-Type يدوي (المتصفح يحدده تلقائيًا)
 */
async function apiPostFile(path, formData) {
  const csrf = getCsrfMeta();
  if (csrf.name && csrf.value) formData.append(csrf.name, csrf.value);
  const res = await fetch(path, { method: "POST", body: formData });
  return res.json();
}

/**
 * طلب POST يرجّع ملف PDF حقيقي (مولَّد من السيرفر بـ mPDF) ويبدأ تحميله مباشرة
 * -- نفس تجربة تصدير مصفوفة المخاطر/ملخص الاجتماع بالضبط (ملف واحد ينزّل
 * فورًا بلا نافذة طباعة/حوار "حفظ كـ PDF" وسيط)، تستخدمها المستندات اللي ما
 * عندها mission_id محفوظ بعد (خطاب/اتفاقية المعالج، ملاحظة قيد التعبئة) فما
 * تقدر تعتمد رابط GET بمعرّف زي باقي مستندات PdfController
 */
async function postForPdfDownload(path, body, filename) {
  const csrf = getCsrfMeta();
  const payload = { ...(body || {}) };
  if (csrf.name && csrf.value) payload[csrf.name] = csrf.value;

  const res = await fetch(path, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error("تعذّر إنشاء ملف PDF (خطأ " + res.status + ")");

  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename || "مستند.pdf";
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/**
 * تحديد نوع القيمة أثناء الكتابة لحقول name/phone/email (نفس فلاتر p1Reviewer/
 * p1Phone/p1Email بـ wizard.js ومرادفاتها بـ missionreview.js الأصليتين بالضبط)
 * -- يشتغل تلقائيًا على أي حقل عليه data-mask بأي صفحة حقيقية (mvc-layout.js
 * يحمّل هذا الملف أول شي بكل صفحة)، بدون حاجة لتكرار المنطق بكل *-page.js
 *   data-mask="letters" -> يمنع الأرقام (عربي/إنجليزي) — أسماء الأشخاص
 *   data-mask="phone"   -> أرقام فقط، بحد أقصى 10 خانات
 *   data-mask="email"   -> يمنع أي حرف غير إنجليزي/رقم/رموز البريد القياسية
 */
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-mask]").forEach(el => {
    const kind = el.dataset.mask;
    el.addEventListener("input", () => {
      const pos = el.selectionEnd;
      let v = el.value;
      if (kind === "letters") v = v.replace(/[0-9٠-٩]/g, "");
      else if (kind === "phone") v = v.replace(/[^0-9]/g, "").slice(0, 10);
      else if (kind === "email") v = v.replace(/[^A-Za-z0-9@._+-]/g, "");
      if (v !== el.value) {
        el.value = v;
        try { el.setSelectionRange(pos, pos); } catch (e) {}
      }
    });
  });
});
