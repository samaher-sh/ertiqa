/* ============================================================
   reports-index-page.js — تحسين تدريجي لصفحة قائمة التقارير النهائية
   (ReportController::index) بيوزر audit_head فقط. يضيف نافذتَي اعتماد/رفض
   منبثقتَين (نموذجا PRG عاديان -- بدون هذا الملف تختفي أزرار الاعتماد/الرفض
   ولا يوصل المستخدم لهما، بس النماذج نفسها HTML عادي لو فُتحت).
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add("open");
  }
  function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove("open");
  }

  document.querySelectorAll("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn.dataset.modalClose));
  });
  document.querySelectorAll(".fr-modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  /* ===== زر "اعتماد" بكل صف: يفتح نافذة الاعتماد ويعبّي رقم التقرير ===== */
  const approveModal = document.getElementById("frApproveModal");
  const approveReportId = document.getElementById("frApproveReportId");
  const approveMissionLabel = document.getElementById("frApproveMissionLabel");
  const approveHeadName = document.getElementById("frApproveHeadName");
  const approveDate = document.getElementById("frApproveDate");

  document.querySelectorAll(".fr-action-approve-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      approveReportId.value = btn.dataset.reportId;
      approveMissionLabel.textContent = "المهمة: " + btn.dataset.missionCode;
      approveHeadName.value = "";
      approveDate.value = new Date().toISOString().slice(0, 10);
      clearSignaturePad();
      openModal("frApproveModal");
    });
  });

  /* ===== لوحة التوقيع (نفس آلية finalreports-show-page.js، هنا نسخة واحدة تُعاد تصفيرها لكل تقرير) ===== */
  const canvas = document.getElementById("frApproveSigPad");
  const approveForm = document.getElementById("frApproveForm");
  let hasSignature = false;

  function clearSignaturePad() {
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSignature = false;
    const hint = document.getElementById("frApproveSigPadHint");
    if (hint) { hint.style.display = ""; hint.textContent = "وقّع هنا"; hint.style.color = ""; }
  }

  if (canvas && approveForm) {
    const ctx = canvas.getContext("2d");
    ctx.strokeStyle = "#152c33";
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";

    const hint = document.getElementById("frApproveSigPadHint");
    let drawing = false;
    let last = null;

    function pointFromEvent(e) {
      const rect = canvas.getBoundingClientRect();
      const src = e.touches && e.touches.length ? e.touches[0] : e;
      return {
        x: (src.clientX - rect.left) * (canvas.width / rect.width),
        y: (src.clientY - rect.top) * (canvas.height / rect.height),
      };
    }
    function start(e) {
      e.preventDefault();
      drawing = true;
      last = pointFromEvent(e);
    }
    function move(e) {
      if (!drawing) return;
      e.preventDefault();
      const p = pointFromEvent(e);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      last = p;
      hasSignature = true;
      if (hint) hint.style.display = "none";
    }
    function end() { drawing = false; }

    canvas.addEventListener("mousedown", start);
    canvas.addEventListener("mousemove", move);
    window.addEventListener("mouseup", end);
    canvas.addEventListener("touchstart", start, { passive: false });
    canvas.addEventListener("touchmove", move, { passive: false });
    canvas.addEventListener("touchend", end);

    const clearBtn = document.getElementById("frApproveSigPadClear");
    if (clearBtn) clearBtn.addEventListener("click", clearSignaturePad);

    approveForm.addEventListener("submit", (e) => {
      const name = (approveHeadName.value || "").trim();
      if (!name) {
        e.preventDefault();
        approveHeadName.focus();
        return;
      }
      if (!hasSignature) {
        e.preventDefault();
        if (hint) { hint.textContent = "يجب التوقيع قبل الاعتماد"; hint.style.display = ""; hint.style.color = "#dc2626"; }
        return;
      }
      document.getElementById("frApproveHeadNameHidden").value = name;
      document.getElementById("frApproveSigHidden").value = canvas.toDataURL("image/png");
    });
  }

  /* ===== زر "رفض" بكل صف: يفتح نافذة الرفض ويعبّي رقم التقرير ===== */
  const rejectReportId = document.getElementById("frRejectReportId");
  const rejectMissionLabel = document.getElementById("frRejectMissionLabel");
  const rejectNote = document.getElementById("frRejectNote");

  document.querySelectorAll(".fr-action-reject-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      rejectReportId.value = btn.dataset.reportId;
      rejectMissionLabel.textContent = "المهمة: " + btn.dataset.missionCode;
      rejectNote.value = "";
      openModal("frRejectModal");
    });
  });
});
