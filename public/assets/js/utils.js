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
   نافذة طباعة "بنفس شكل النموذج الحقيقي بالضبط" -- تُستخدم لتصدير المستندات
   اللي عندها معاينة مصمَّمة جاهزة على الشاشة (نموذج الخطاب الرسمي بالمعالج،
   بطاقة عرض الملاحظة) بدل إعادة بناء تصميم مبسّط يختلف شكله عن الأصل: تربط
   ملفات CSS الحقيقية للتطبيق (بدل تكرارها بنسخة مصغّرة) وتحقن HTML العنصر
   المعروض فعليًا على الشاشة (outerHTML) كما هو -- فيطلع المستند المُصدَّر
   نسخة طبق الأصل من النموذج اللي يشوفه المستخدم، بنفس الخط (Cairo) ونفس
   الألوان والتنسيق تمامًا، بدل نسخة "شبيهة" بألوان مقاربة فقط
   ============================================================ */
function printDocumentHTML({ title, cssFiles, bodyHtml, missionCode }) {
  const cssLinks = (cssFiles || []).map(f => `<link rel="stylesheet" href="${base}/assets/css/${f}">`).join("\n");
  return `
    <html dir="rtl">
      <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(title || "مستند")}</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        ${cssLinks}
        <style>
          body{ background:#eef4f7; padding:32px 16px; display:flex; justify-content:center; }
          .print-doc-wrap{ width:100%; max-width:820px; }
          .wiz-paper{ min-height:auto !important; }
          .obs-form-back, #obsViewEditBtn{ display:none !important; }
          .print-doc-footer{ margin:16px auto 0; max-width:820px; padding-top:8px; border-top:1px solid #d8e6eb; font-size:9px; color:#9ca3af; text-align:center; }
          @media print {
            body{ background:#fff; padding:0; display:block; }
            .print-doc-wrap{ max-width:none; }
            .wiz-paper, .obs-form-card{ border:none !important; box-shadow:none !important; border-radius:0 !important; }
          }
        </style>
      </head>
      <body>
        <div class="print-doc-wrap">${bodyHtml}</div>
        <div class="print-doc-footer">مستند صادر من نظام ارتقاء — إدارة المراجعة الداخلية${missionCode ? "، سرّي وخاص بالمهمة " + escapeHtml(missionCode) : ""}</div>
        <script>
          window.onload = () => {
            // تحويل أي أيقونات data-lucide متبقية (مثلاً ببطاقات مُعاد بناؤها من
            // الحالة بدل استنساخ DOM جاهز أصلاً محوَّل) قبل الطباعة -- لا تأثير
            // لها لو ما فيه عناصر data-lucide أصلاً
            try { window.lucide && window.lucide.createIcons(); } catch (e) {}
            window.print();
            setTimeout(() => window.close(), 500);
          }
        </script>
      </body>
    </html>`;
}
