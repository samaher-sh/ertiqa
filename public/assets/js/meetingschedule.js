/* ============================================================
   جدولة اجتماع (Meeting Schedule Chat) — متصل بالـ API الحقيقي
   شات حقيقي بين عضو المراجعة ومنسّق الإدارة للاتفاق على موعد الاجتماع
   ============================================================ */

let mcSelectedTaskId = "";
let mcMessages = [];
let mcMeeting = null;
let mcMyUserId = null;
let mcPollTimer = null;
let mcShowProposeForm = false;

function renderMeetingSchedulePage() {
  const locked = !mcSelectedTaskId;

  return `
  <div class="flex flex-col gap-4">
    ${renderLinkedTaskSelector(mcSelectedTaskId, "mcTaskSelect")}

    <div class="mc-locked-wrap ${locked ? "locked" : ""}">
      <div class="wiz-card mc-card">
        <div class="wiz-card-head">
          <i data-lucide="calendar"></i>
          <div><h2>جدولة اجتماع</h2><p>Meeting Schedule</p></div>
          ${mcMeeting && mcMeeting.meeting_date ? `
            <span class="mc-confirmed-badge" style="margin-right:auto;">
              <i data-lucide="check-circle" style="width:12px;height:12px;"></i>
              الموعد المؤكد: ${mcMeeting.meeting_date} ${mcMeeting.meeting_time || ""}
            </span>` : ""}
        </div>

        <div class="mc-chat-body" id="mcChatBody">
          ${mcMessages.length === 0 ? `<p class="mc-empty">لا توجد رسائل بعد — ابدأ المحادثة لتحديد موعد الاجتماع</p>` : mcMessages.map(m => renderChatBubble(m)).join("")}
        </div>

        <div class="mc-compose">
          ${mcShowProposeForm ? `
            <div class="mc-propose-form">
              <div class="mc-propose-row">
                <input id="mcProposeDate" type="date" class="wiz-input plain" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
                <input id="mcProposeTime" type="time" class="wiz-input plain" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
              </div>
              <input id="mcProposeLocation" type="text" class="wiz-input plain" placeholder="مكان الاجتماع (اختياري)">
              <div class="mc-propose-actions">
                <button type="button" class="mc-propose-cancel" id="mcProposeCancel">إلغاء</button>
                <button type="button" class="mc-propose-submit" id="mcProposeSubmit"><i data-lucide="send"></i> اقترح هذا الموعد</button>
              </div>
            </div>
          ` : `
            <div class="mc-compose-row">
              <button type="button" class="mc-propose-btn" id="mcOpenProposeBtn" title="اقترح موعد">
                <i data-lucide="calendar-plus"></i>
              </button>
              <input id="mcMessageInput" type="text" class="mc-text-input" placeholder="اكتب رسالة...">
              <button type="button" class="mc-send-btn" id="mcSendBtn"><i data-lucide="send"></i></button>
            </div>
          `}
        </div>
      </div>
    </div>
  </div>`;
}

function renderChatBubble(m) {
  const isMine = Number(m.sender_id) === Number(mcMyUserId);
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
        ${!isMine ? `<button type="button" class="mc-confirm-btn" data-confirm-msg="${m.id}"><i data-lucide="check"></i> تأكيد هذا الموعد</button>` : `<span class="mc-waiting-hint">بانتظار تأكيد الطرف الآخر</span>`}
        <span class="mc-bubble-time">${escapeHtml(time)}</span>
      </div>
    </div>`;
  }

  if (m.type === "confirmed") {
    return `
    <div class="mc-bubble-row center">
      <div class="mc-bubble-confirmed">
        <i data-lucide="check-circle"></i>
        تم تأكيد الموعد: ${escapeHtml(m.proposed_date)} — ${escapeHtml(m.proposed_time)}${m.proposed_location ? " · " + escapeHtml(m.proposed_location) : ""}
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

async function mcLoadMessages(scrollToBottom) {
  if (!mcSelectedTaskId) return;
  try {
    const data = await apiGet(base + "/dashboard/meeting-schedule/api/messages?mission_id=" + encodeURIComponent(mcSelectedTaskId));
    if (data && data.success) {
      mcMessages = data.messages || [];
      mcMeeting = data.meeting || null;
      mcMyUserId = data.my_user_id || null;
      rerenderMSContent();
      if (scrollToBottom) mcScrollToBottom();
    }
  } catch (e) {
    console.error("تعذّر تحميل المحادثة:", e);
  }
}

function mcScrollToBottom() {
  const body = document.getElementById("mcChatBody");
  if (body) body.scrollTop = body.scrollHeight;
}

function mcStartPolling() {
  if (mcPollTimer) clearInterval(mcPollTimer);
  mcPollTimer = setInterval(() => {
    if (activeContent !== "meetingSchedule") {
      clearInterval(mcPollTimer);
      mcPollTimer = null;
      return;
    }
    if (mcSelectedTaskId) mcLoadMessages(false);
  }, 5000);
}

function bindMeetingScheduleEvents() {
  const taskSelect = document.getElementById("mcTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", e => {
    mcSelectedTaskId = e.target.value;
    mcMessages = [];
    mcShowProposeForm = false;
    rerenderMSContent();
    if (mcSelectedTaskId) { mcLoadMessages(true); mcStartPolling(); }
  });

  const sendBtn = document.getElementById("mcSendBtn");
  const msgInput = document.getElementById("mcMessageInput");
  if (sendBtn && msgInput) {
    const doSend = async () => {
      const text = msgInput.value.trim();
      if (!text || !mcSelectedTaskId) return;
      sendBtn.disabled = true;
      try {
        await apiPost(base + "/dashboard/meeting-schedule/api/send", { mission_id: mcSelectedTaskId, message: text });
        msgInput.value = "";
        await mcLoadMessages(true);
      } catch (e) {
        showToast(e.message || "تعذّر إرسال الرسالة", "error");
      } finally {
        sendBtn.disabled = false;
      }
    };
    sendBtn.addEventListener("click", doSend);
    msgInput.addEventListener("keydown", e => { if (e.key === "Enter") doSend(); });
  }

  const openProposeBtn = document.getElementById("mcOpenProposeBtn");
  if (openProposeBtn) openProposeBtn.addEventListener("click", () => { mcShowProposeForm = true; rerenderMSContent(); });

  const cancelBtn = document.getElementById("mcProposeCancel");
  if (cancelBtn) cancelBtn.addEventListener("click", () => { mcShowProposeForm = false; rerenderMSContent(); });

  const submitBtn = document.getElementById("mcProposeSubmit");
  if (submitBtn) submitBtn.addEventListener("click", async () => {
    const date = document.getElementById("mcProposeDate").value;
    const time = document.getElementById("mcProposeTime").value;
    const location = document.getElementById("mcProposeLocation").value.trim();
    if (!date || !time) { showToast("يرجى تحديد التاريخ والوقت.", "error"); return; }
    submitBtn.disabled = true;
    try {
      await apiPost(base + "/dashboard/meeting-schedule/api/propose", { mission_id: mcSelectedTaskId, date, time, location });
      mcShowProposeForm = false;
      await mcLoadMessages(true);
    } catch (e) {
      showToast(e.message || "تعذّر إرسال الاقتراح", "error");
    } finally {
      submitBtn.disabled = false;
    }
  });

  document.querySelectorAll("[data-confirm-msg]").forEach(btn => {
    btn.addEventListener("click", async () => {
      btn.disabled = true;
      try {
        await apiPost(base + "/dashboard/meeting-schedule/api/confirm", { mission_id: mcSelectedTaskId, message_id: btn.dataset.confirmMsg });
        await mcLoadMessages(true);
      } catch (e) {
        showToast(e.message || "تعذّر تأكيد الموعد", "error");
        btn.disabled = false;
      }
    });
  });

  mcScrollToBottom();

  if (mcSelectedTaskId && !mcPollTimer) mcStartPolling();
  if (taskSelect && taskSelect.options.length === 2 && !mcSelectedTaskId) {
    taskSelect.selectedIndex = 1;
    taskSelect.dispatchEvent(new Event("change"));
  }
}

function rerenderMSContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  ca.innerHTML = renderMeetingSchedulePage();
  bindMeetingScheduleEvents();
  lucide.createIcons();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) { el.focus(); if (selStart !== null && el.setSelectionRange) { try { el.setSelectionRange(selStart, selEnd); } catch (e) {} } }
  }
  if (ca) ca.scrollTop = scrollTop;
}
