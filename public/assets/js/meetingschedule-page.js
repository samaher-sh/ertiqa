/* ============================================================
   meetingschedule-page.js — تحسين تدريجي لصفحة جدولة الاجتماع الحقيقية
   (MissionChatController::index + send/propose/confirm/cancel/cancel-confirmed).
   الصفحة تشتغل بالكامل بدون هذا الملف: كل الأزرار نماذج POST/Redirect/GET
   عادية، ومنتقي "اقترح موعدًا" عنصر <details>/<summary> أصلي.

   هذا الملف يضيف: تمرير المحادثة لآخر رسالة تلقائيًا عند فتح الصفحة، وتحديث
   دوري كل 5 ثواني (نفس mcStartPolling() بالـ SPA الأصلية) يجيب رسائل جديدة
   من الطرف الثاني بدون ما يحتاج المستخدم يعيد تحميل الصفحة يدويًا -- يعيد
   رسم منطقة الفقاعات فقط (mcChatBody)، بدون لمس صندوق الكتابة، عشان ما
   يضيع أي نص لسا المستخدم يكتبه.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const body = document.getElementById("mcChatBody");
  if (!body) return;
  body.scrollTop = body.scrollHeight;

  const missionId = body.dataset.missionId;
  const myUserId = body.dataset.myUserId;
  if (!missionId || missionId === "0") return;

  setInterval(() => pollMessages(missionId, myUserId), 5000);
});

async function pollMessages(missionId, myUserId) {
  try {
    const data = await apiGet(base + "/dashboard/meeting-schedule/api/messages?mission_id=" + encodeURIComponent(missionId));
    if (data && data.success) {
      renderChatBodyInto(document.getElementById("mcChatBody"), data.messages || [], data.meeting || null, myUserId, missionId);
    }
  } catch (e) {
    // فشل صامت -- نفس سلوك mcLoadMessages() الأصلي بالتحديث الدوري (console.error فقط)
  }
}

function mcLastConfirmedIndex(messages, meeting) {
  if (!meeting || meeting.status !== "scheduled") return -1;
  let idx = -1;
  messages.forEach((m, i) => {
    if (m.type === "confirmed" && m.proposed_date === meeting.meeting_date && m.proposed_time === meeting.meeting_time) idx = i;
  });
  return idx;
}

function renderChatBubble(m, isMine, showCancelConfirmed, missionId) {
  const side = isMine ? "mine" : "theirs";
  const time = (m.created_at || "").slice(11, 16);

  if (m.type === "proposal") {
    return `
    <div class="mc-bubble-row ${side}">
      <div class="mc-bubble mc-bubble-proposal">
        <p class="mc-bubble-sender">${escapeHtml(m.sender_name || "")}</p>
        <div class="mc-proposal-card">
          <i data-lucide="calendar-clock"></i>
          <div>
            <p class="mc-proposal-title">اقترح موعدًا للاجتماع</p>
            <p class="mc-proposal-detail">${escapeHtml(m.proposed_date)} — ${escapeHtml(m.proposed_time)}${m.proposed_location ? " · " + escapeHtml(m.proposed_location) : ""}</p>
          </div>
        </div>
        ${!isMine ? `
          <div class="mc-proposal-actions">
            <button type="button" class="mc-confirm-btn" data-confirm-msg="${m.id}"><i data-lucide="check"></i> تأكيد الموعد</button>
            <button type="button" class="mc-cancel-btn" data-cancel-msg="${m.id}"><i data-lucide="x"></i> إلغاء الموعد</button>
          </div>
        ` : `<span class="mc-waiting-hint">بانتظار تأكيد الطرف الآخر</span>`}
        <span class="mc-bubble-time">${escapeHtml(time)}</span>
      </div>
    </div>`;
  }

  if (m.type === "confirmed") {
    return `
    <div class="mc-bubble-row center">
      <div class="mc-bubble-confirmed">
        <i data-lucide="check-circle"></i>
        <span>تم تأكيد الموعد: ${escapeHtml(m.proposed_date)} — ${escapeHtml(m.proposed_time)}${m.proposed_location ? " · " + escapeHtml(m.proposed_location) : ""}</span>
        ${showCancelConfirmed ? `
          <button type="button" class="mc-cancel-confirmed-btn" data-cancel-confirmed title="إلغاء الموعد المؤكد">
            <i data-lucide="x"></i>
          </button>
        ` : ""}
      </div>
    </div>`;
  }

  if (m.type === "cancelled") {
    return `
    <div class="mc-bubble-row center">
      <div class="mc-bubble-cancelled">
        <i data-lucide="x-circle"></i>
        ${escapeHtml(m.message)}
      </div>
    </div>`;
  }

  return `
  <div class="mc-bubble-row ${side}">
    <div class="mc-bubble mc-bubble-text">
      <p class="mc-bubble-sender">${escapeHtml(m.sender_name || "")}</p>
      <p class="mc-bubble-msg">${escapeHtml(m.message)}</p>
      <span class="mc-bubble-time">${escapeHtml(time)}</span>
    </div>
  </div>`;
}

function renderChatBodyInto(body, messages, meeting, myUserId, missionId) {
  if (!body) return;
  const wasAtBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 40;

  if (messages.length === 0) {
    body.innerHTML = `<p class="mc-empty">لا توجد رسائل بعد — ابدأ المحادثة لتحديد موعد الاجتماع</p>`;
  } else {
    const lastConfirmedIdx = mcLastConfirmedIndex(messages, meeting);
    body.innerHTML = messages.map((m, i) => renderChatBubble(m, Number(m.sender_id) === Number(myUserId), i === lastConfirmedIdx, missionId)).join("");
  }
  if (window.lucide) lucide.createIcons();

  body.querySelectorAll("[data-confirm-msg]").forEach(btn => {
    btn.addEventListener("click", () => mcPostAction("confirm", missionId, { message_id: btn.dataset.confirmMsg }, myUserId));
  });
  body.querySelectorAll("[data-cancel-msg]").forEach(btn => {
    btn.addEventListener("click", () => mcPostAction("cancel", missionId, { message_id: btn.dataset.cancelMsg }, myUserId));
  });
  body.querySelectorAll("[data-cancel-confirmed]").forEach(btn => {
    btn.addEventListener("click", () => mcPostAction("cancel-confirmed", missionId, {}, myUserId));
  });

  if (wasAtBottom) body.scrollTop = body.scrollHeight;
}

async function mcPostAction(action, missionId, extra, myUserId) {
  try {
    await apiPost(base + "/dashboard/meeting-schedule/api/" + action, Object.assign({ mission_id: missionId }, extra));
    await pollMessages(missionId, myUserId);
  } catch (e) {
    alert(e.message || "تعذّر إتمام العملية");
  }
}
