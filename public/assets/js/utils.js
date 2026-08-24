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

/* ============================================================
   قالب هيدر/فوتر PDF موحّد -- تستخدمه كل مستندات "نافذة الطباعة" المولَّدة
   بالمتصفح (تصدير ملاحظة، الخطاب الرسمي بالمعالج، اتفاقية مستوى الخدمة)
   عشان تطلع بنفس الهوية البصرية بالضبط زي مستندات mPDF الرسمية
   (خطاب المهمة، مصفوفة المخاطر، ملخص الاجتماع، التقرير النهائي) --
   كانت كل صفحة قبل هذا تبني letterhead خاص فيها بشعار مختلف (kamc.png بدل
   النسخة المقصوصة kamc-pdf-logo.png) وسماكة حدّ مختلفة وفوتر مختلف تمامًا،
   فيبان للمستخدم إن كل تصدير "شكله" مستقل عن البقية
   ============================================================ */
const PDF_LETTERHEAD_STYLE = `
  .pdf-letterhead{ display:flex; justify-content:space-between; align-items:center; gap:14px; border-bottom:1.5px solid #3185b3; padding-bottom:10px; margin-bottom:24px; }
  .pdf-letterhead-brand{ display:flex; align-items:center; gap:10px; }
  .pdf-letterhead-brand img{ height:34px; width:auto; }
  .pdf-letterhead-titles h1{ font-size:15px; color:#196b7f; margin:0; }
  .pdf-letterhead-titles p{ font-size:11px; color:#6b8c95; margin:3px 0 0; }
  .pdf-letterhead-meta{ text-align:left; font-size:11px; color:#4b5563; white-space:nowrap; }
  .pdf-letterhead-meta p{ margin:2px 0; }
  .pdf-footer{ margin-top:40px; padding-top:8px; border-top:1px solid #d8e6eb; font-size:9px; color:#9ca3af; text-align:center; }
`;

function pdfLetterheadHTML(docTitle, metaLines) {
  const metaHTML = (metaLines || []).map(l => `<p>${l}</p>`).join("");
  return `
    <div class="pdf-letterhead">
      <div class="pdf-letterhead-brand">
        <img src="${base}/assets/images/kamc-pdf-logo.png" alt="مدينة الملك عبدالله الطبية">
        <div class="pdf-letterhead-titles">
          <h1>إدارة المراجعة الداخلية</h1>
          <p>${escapeHtml(docTitle)}</p>
        </div>
      </div>
      <div class="pdf-letterhead-meta">${metaHTML}</div>
    </div>`;
}

function pdfFooterHTML(missionCode) {
  return `<div class="pdf-footer">مستند صادر من نظام ارتقاء — إدارة المراجعة الداخلية${missionCode ? "، سرّي وخاص بالمهمة " + escapeHtml(missionCode) : ""}</div>`;
}
