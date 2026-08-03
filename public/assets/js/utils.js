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
