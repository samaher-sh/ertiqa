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
 * كل حقول النص الطويل (.wiz-textarea, .msum-growfield) تكبر تلقائيًا حسب
 * المحتوى بدل ما يبقى ارتفاعها ثابت -- بدون هذا، أي نص يفيض عن الارتفاع
 * الثابت يختفي فعليًا (الحقول أصلًا resize:none + overflow-y:hidden بالتصميم)
 * بدل ما يظهر أو حتى يقدر المستخدم يمرّر لرؤيته. يُستدعى تلقائيًا لكل صفحة
 * حقيقية (utils.js يُحمَّل أولًا بكل صفحة)، وأي كود يضيف حقول جديدة ديناميكيًا
 * بعد التحميل (صفوف مضافة بجافاسكربت) يستدعي bindAutoGrowTextareas(newRow) يدويًا.
 */
function autoGrowTextarea(el) {
  if (!el) return;
  // نحفظ الارتفاع الطبيعي (المبني على rows= الأصلي) أول مرة، قبل أي تعديل --
  // بدونه، حقل فاضٍ (بدون قيمة، بس فيه placeholder) يصير scrollHeight بارتفاع
  // سطر وحد تقريبًا، فيتقلّص الحقل لشكل ضيق مشوَّه بدل ارتفاعه الأصلي المقصود.
  // نحسبه من rows/line-height/padding/border بدل offsetHeight لأن حقول خطوة 3
  // بمعالج "بدء مهمة" تكون داخل قسم display:none وقت التحميل (offsetHeight
  // يرجّع صفر وقتها)، بينما القيم المحسوبة هذي متوفرة بغض النظر عن الظهور.
  if (el.dataset.autoGrowMinHeight === undefined) {
    const cs = getComputedStyle(el);
    const rows = parseInt(el.getAttribute("rows"), 10) || 1;
    const lineHeight = parseFloat(cs.lineHeight) || parseFloat(cs.fontSize) * 1.2 || 20;
    const paddingV = parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom);
    const borderV = parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
    el.dataset.autoGrowMinHeight = String(rows * lineHeight + paddingV + borderV);
  }
  const minHeight = parseFloat(el.dataset.autoGrowMinHeight) || 0;
  el.style.height = "auto";
  el.style.height = Math.max(el.scrollHeight, minHeight) + "px";
}
function bindAutoGrowTextareas(scope) {
  scope.querySelectorAll(".wiz-textarea, .msum-growfield").forEach(el => {
    if (el.dataset.autoGrowBound === "1") return;
    el.dataset.autoGrowBound = "1";
    autoGrowTextarea(el);
    el.addEventListener("input", () => autoGrowTextarea(el));
  });
}
document.addEventListener("DOMContentLoaded", () => bindAutoGrowTextareas(document));

/**
 * يخلي حقل <input type="file" multiple> يتراكم فيه الاختيار عبر كذا فتحة
 * لنافذة اختيار الملفات، بدل السلوك الافتراضي بالمتصفح (كل فتحة تستبدل
 * الاختيار السابق كليًا فيضيع أي ملف مختار قبلها لو المستخدم رجع فتح
 * النافذة مرة ثانية لإضافة ملف إضافي قبل الإرسال/الحفظ النهائي).
 * ملف بنفس الاسم والحجم يستبدل النسخة السابقة (تحديث)، غير كذا يُضاف للقائمة.
 * القائمة المتراكمة تُخزَّن على العنصر نفسه (input._accumulatedFiles) عشان
 * أي كود خارجي (مثل زر "إزالة" بمعاينة الملفات) يقدر يتعامل معها مباشرة عبر
 * removeFromAccumulatingFileInput() بدل ما يعيد بناء input.files من نفسه ويكسر
 * التزامن. لازم تُستدعى قبل أي addEventListener("change", ...) ثانية على نفس
 * الحقل، عشان تلك المستمعات تقرأ القائمة المجمَّعة الصحيحة من input.files.
 */
function bindAccumulatingFileInput(input) {
  if (!input || input.dataset.accumulating === "1") return;
  input.dataset.accumulating = "1";
  input._accumulatedFiles = [];

  input.addEventListener("change", () => {
    Array.from(input.files || []).forEach(f => {
      const idx = input._accumulatedFiles.findIndex(existing => existing.name === f.name && existing.size === f.size);
      if (idx !== -1) input._accumulatedFiles[idx] = f; else input._accumulatedFiles.push(f);
    });
    rebuildAccumulatingFileInput(input);
  });
}

/** يزيل ملفًا واحدًا (بالفهرس) من حقل مُهيَّأ بـ bindAccumulatingFileInput() */
function removeFromAccumulatingFileInput(input, index) {
  if (!input || !input._accumulatedFiles) return;
  input._accumulatedFiles.splice(index, 1);
  rebuildAccumulatingFileInput(input);
}

function rebuildAccumulatingFileInput(input) {
  const dt = new DataTransfer();
  input._accumulatedFiles.forEach(f => dt.items.add(f));
  input.files = dt.files;
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
