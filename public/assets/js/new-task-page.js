/* ============================================================
   new-task-page.js — تحسين تدريجي لصفحة "بدء مهمة" الحقيقية
   (MissionController::create/store). الصفحة تشتغل بالكامل بدون هذا الملف:
   نموذج POST/Redirect/GET واحد يشمل كل الحقول (الخطوتين معًا)، فيُرسل
   مرة وحدة صحيحة حتى بدون جافاسكربت.

   هذا الملف يضيف تحسينات لا تُغيّر أي سلوك أساسي (نفس renderWizardPage()
   بـ wizard.js بالضبط):
     1) عرض خطوة وحدة بالمرة (تنقّل بين "طلب المراجعة"/"اتفاقية مستوى
        الخدمة") بدل عرض الاثنتين دفعة وحدة (fallback بدون JS)
     2) تحديث حي لمعاينة الخطاب أثناء الكتابة
     3) طيّ/فرد كل قناة اتصال بالضغط على ترويستها
     4) اعتماد "المراجع الرئيسي" بخطوة اتفاقية مستوى الخدمة بضغطة واحدة
        (checkbox بدل توقيع يدوي بـ canvas)
     5) تصدير PDF لمسودة الخطاب (خطوة 1) واتفاقية مستوى الخدمة (خطوة 2)
        قبل الحفظ
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  bindLivePreview();
  bindDraftLetterExport();
  bindDraftAgreementExport();
  bindChannelToggles();
  bindSignatureApproveCheckbox();
  bindStepNav();
});

/* ---------- 1) تنقّل الخطوات الثلاث (طلب المراجعة، اتفاقية مستوى الخدمة، تخطيط المهمة) ---------- */
function bindStepNav() {
  const steps = [document.getElementById("wizStep1"), document.getElementById("wizStep2"), document.getElementById("wizStep3")];
  const stepsHeader = document.getElementById("wizSteps");
  if (!steps[0] || !steps[1] || !steps[2] || !stepsHeader) return;

  const prevBtn = document.getElementById("wizPrevBtn");
  const nextBtn = document.getElementById("wizNextBtn");
  const sendBtn = document.getElementById("wizSendBtn");
  const circles = [document.getElementById("wizStepCircle1"), document.getElementById("wizStepCircle2"), document.getElementById("wizStepCircle3")];
  const stepLabels = stepsHeader.querySelectorAll(".wiz-step-label");
  const LAST_PAGE = steps.length;

  let page = 1;

  function render() {
    steps.forEach((el, i) => { el.style.display = page === i + 1 ? "flex" : "none"; });
    steps[0].style.display = page === 1 ? "" : "none";

    circles.forEach((c, i) => {
      const n = i + 1;
      c.classList.toggle("current", page === n);
      c.classList.toggle("done", page > n);
      c.innerHTML = page > n ? '<i data-lucide="check"></i>' : String(n);
      stepLabels[i].classList.toggle("current", page === n);
      stepLabels[i].classList.toggle("done", page > n);
    });

    document.querySelectorAll(".wiz-dots [data-goto-step]").forEach(d => {
      d.classList.toggle("current", Number(d.dataset.gotoStep) === page);
    });

    prevBtn.style.display = page === 1 ? "none" : "";
    nextBtn.style.display = page === LAST_PAGE ? "none" : "";
    sendBtn.style.display = page === LAST_PAGE ? "" : "none";
    /* sendBtn هو زر type="submit" الوحيد بالنموذج، فلو بقي مفعّلاً وهو مخفي
       بخطوة سابقة يصير "الزر الافتراضي" لأي إرسال ضمني (ضغط Enter بأي حقل
       نصي)، فيرسل النموذج فارغًا بدون المرور على isPage1Valid() -- تعطيله
       هنا يمنع هذا الإرسال العرَضي، وما يأثّر على النسخة بدون جافاسكربت
       (الزر هناك يبقى مفعّلاً افتراضيًا لأنه ما فيه JS يشغّل هذا السطر أصلاً) */
    sendBtn.disabled = page !== LAST_PAGE;

    if (window.lucide) lucide.createIcons();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function isPage1Valid() {
    const val = id => (document.getElementById(id) ? document.getElementById(id).value.trim() : "");
    return !!(val("mainDeptSelect") && val("p1Target") && val("p1Procedure") && val("p1Reviewer") && val("p1Director") && val("p1Email") && val("p1Phone"));
  }

  function canLeavePage1() {
    if (!isPage1Valid()) {
      alert("يرجى تعبئة كل الحقول المطلوبة بالخطوة الأولى أولاً.");
      return false;
    }
    return true;
  }

  document.querySelectorAll("[data-goto-step]").forEach(btn => {
    btn.addEventListener("click", () => {
      const target = Number(btn.dataset.gotoStep);
      if (target > 1 && page === 1 && !canLeavePage1()) return;
      page = target;
      render();
    });
  });

  nextBtn.addEventListener("click", () => {
    if (page === 1 && !canLeavePage1()) return;
    page = Math.min(page + 1, LAST_PAGE);
    render();
  });
  prevBtn.addEventListener("click", () => { page = Math.max(page - 1, 1); render(); });

  render();
}

/* ---------- 2) معاينة حية للخطاب ---------- */
function bindLivePreview() {
  const $ = id => document.getElementById(id);
  const bind = (fieldId, mirrorId, fallback) => {
    const field = $(fieldId);
    const mirror = $(mirrorId);
    if (!field || !mirror) return;
    field.addEventListener("input", () => {
      mirror.textContent = field.value.trim() || fallback;
    });
  };

  bind("p1Reviewer", "mReviewer", "...............");
  bind("p1Reviewer", "p3ReviewerName", "—");
  bind("p1Email", "mEmail", "........................");
  bind("p1Phone", "mPhone", "........................");

  const targetSelect = $("p1Target");
  const mTarget = $("mTarget");
  const p2TargetName = $("p2TargetName");
  const p3TargetName = $("p3TargetName");
  if (targetSelect) {
    targetSelect.addEventListener("change", () => {
      const opt = targetSelect.options[targetSelect.selectedIndex];
      const name = (opt && opt.value) ? opt.textContent : "";
      if (mTarget) mTarget.textContent = name || "الإدارة المستهدفة";
      if (p2TargetName) p2TargetName.textContent = name || "—";
      if (p3TargetName) p3TargetName.textContent = name || "—";
    });
  }

  const yearSelect = $("p1Year");
  const mYear = $("mYear");
  if (yearSelect && mYear) yearSelect.addEventListener("change", () => { mYear.textContent = yearSelect.value; });

  const director = $("p1Director");
  const mDirector = $("mDirector");
  if (director && mDirector) {
    director.addEventListener("input", () => {
      const val = director.value.trim();
      mDirector.textContent = val;
      mDirector.hidden = !val;
    });
  }

  const procedure = $("p1Procedure");
  const procedureBox = $("procedureBox");
  const procedureText = $("procedureText");
  if (procedure && procedureBox && procedureText) {
    procedure.addEventListener("input", () => {
      const val = procedure.value.trim();
      procedureText.textContent = val;
      procedureBox.hidden = !val;
    });
  }
}

/* ---------- 3) طيّ/فرد قنوات الاتصال ---------- */
function bindChannelToggles() {
  document.querySelectorAll("[data-ch-toggle]").forEach(head => {
    head.addEventListener("click", () => {
      const wrap = head.closest("[data-wiz-channel]");
      const checkbox = head.querySelector('input[type="checkbox"]');
      const body = wrap.querySelector(".wiz-channel-body");
      const check = head.querySelector(".wiz-channel-check");
      checkbox.checked = !checkbox.checked;
      wrap.classList.toggle("active", checkbox.checked);
      body.hidden = !checkbox.checked;
      check.innerHTML = checkbox.checked ? '<i data-lucide="check"></i>' : "";
      if (window.lucide) lucide.createIcons();
    });
  });
}

/* ---------- 4) اعتماد "المراجع الرئيسي" بضغطة زر واحدة (بدون توقيع يدوي) ---------- */
function bindSignatureApproveCheckbox() {
  const btn = document.getElementById("p2SigApproveBtn");
  const hiddenInput = document.getElementById("p2SigSignature");
  if (!btn || !hiddenInput) return;

  btn.addEventListener("click", () => {
    const approved = !btn.classList.contains("approved");
    btn.classList.toggle("approved", approved);
    hiddenInput.value = approved ? "1" : "";
    const span = btn.querySelector("span");
    if (span) span.textContent = approved ? "تم الاعتماد" : "أعتمد اتفاقية مستوى الخدمة";
  });
}

/* ---------- 5) تصدير PDF للمسودات قبل الحفظ ---------- */
function bindDraftLetterExport() {
  const btn = document.getElementById("wizP1ExportBtn");
  if (!btn) return;
  btn.addEventListener("click", async () => {
    const val = id => (document.getElementById(id) ? document.getElementById(id).value : "");
    const targetSelect = document.getElementById("p1Target");
    const targetName = targetSelect && targetSelect.selectedIndex > 0 ? targetSelect.options[targetSelect.selectedIndex].textContent : "";
    const mainSelect = document.getElementById("mainDeptSelect");
    const mainName = mainSelect && mainSelect.selectedIndex > 0 ? mainSelect.options[mainSelect.selectedIndex].textContent : "";

    try {
      await postForPdfDownload(base + "/dashboard/pdf/wizard-letter-preview", {
        year: val("p1Year"),
        mission_code: "",
        procedure_note: val("p1Procedure"),
        reviewer_name: val("p1Reviewer"),
        reviewer_email: val("p1Email"),
        reviewer_phone: val("p1Phone"),
        director_name: val("p1Director"),
        main_dept_name: mainName,
        target_dept_name: targetName,
      }, "خطاب-معاينة-مسودة.pdf");
    } catch (e) {
      alert(e.message || "تعذّر تصدير المستند");
    }
  });
}

function bindDraftAgreementExport() {
  const btn = document.getElementById("wizP2ExportBtn");
  if (!btn) return;
  btn.addEventListener("click", async () => {
    const val = id => (document.getElementById(id) ? document.getElementById(id).value : "");
    const channels = {};
    const channelValues = {};
    document.querySelectorAll("[data-wiz-channel]").forEach(wrap => {
      const key = wrap.dataset.wizChannel;
      const checkbox = wrap.querySelector('input[type="checkbox"]');
      const field = wrap.querySelector("input.wiz-input, textarea.wiz-textarea");
      channels[key] = !!(checkbox && checkbox.checked);
      channelValues[key] = field ? field.value : "";
    });
    const sectionsCard = document.getElementById("wizSlaSectionsCard");
    let sections = [];
    try { sections = JSON.parse(sectionsCard.dataset.slaSections); } catch (e) {}

    try {
      await postForPdfDownload(base + "/dashboard/pdf/service-agreement-preview", {
        subject_dept: document.getElementById("p2TargetName") ? document.getElementById("p2TargetName").textContent : "",
        date: val("p2Date"),
        desc: val("p2Desc"),
        channels,
        channel_values: channelValues,
        sections,
        sig_name: val("p2SigName"),
        sig_date: val("p2SigDate"),
        sig_signature: val("p2SigSignature"),
      }, "اتفاقية-مستوى-الخدمة.pdf");
    } catch (e) {
      alert(e.message || "تعذّر تصدير المستند");
    }
  });
}
