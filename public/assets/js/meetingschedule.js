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
/* تخزين مؤقت لآخر رسائل/اجتماع محمّلين لكل مهمة (بمفتاح mission_id) -- يمنع اختفاء
   الرسائل لحظيًا ("لا توجد رسائل بعد") عند التنقل بين مهمة وأخرى ثم الرجوع لنفس
   المهمة السابقة؛ تُحدَّث تلقائيًا بكل استجابة ناجحة من mcLoadMessages() */
let mcMessagesCache = {};

/* آخر رسالة confirmed تطابق الاجتماع المؤكَّد الحالي (status='scheduled') -- هذي هي
   الوحيدة اللي تحمل زر "إلغاء الموعد المؤكد" داخل فقاعتها، عشان ما يتكرر الزر لو
   فيه رسالتين confirmed لنفس التأكيد (الاقتراح المتحوّل + الرسالة النظامية) */
function mcLastConfirmedIndex() {
  if (!mcMeeting || mcMeeting.status !== "scheduled") return -1;
  let idx = -1;
  mcMessages.forEach((m, i) => {
    if (m.type === "confirmed" && m.proposed_date === mcMeeting.meeting_date && m.proposed_time === mcMeeting.meeting_time) idx = i;
  });
  return idx;
}

function renderChatBodyContent() {
  if (mcMessages.length === 0) return `<p class="mc-empty">لا توجد رسائل بعد — ابدأ المحادثة لتحديد موعد الاجتماع</p>`;
  const lastConfirmedIdx = mcLastConfirmedIndex();
  return mcMessages.map((m, i) => renderChatBubble(m, i === lastConfirmedIdx)).join("");
}

function renderMeetingSchedulePage() {
  const locked = !mcSelectedTaskId;

  return `
  <div class="flex flex-col gap-4 mc-page-wrap">
    ${renderLinkedTaskSelector(mcSelectedTaskId, "mcTaskSelect")}

    <div class="mc-locked-wrap mc-schedule-wrap ${locked ? "locked" : ""}">
      <div class="wiz-card mc-card">
        <div class="wiz-card-head">
          <i data-lucide="calendar"></i>
          <div><h2>جدولة اجتماع</h2><p>Meeting Schedule</p></div>
        </div>

        <div class="mc-chat-body" id="mcChatBody">
          ${renderChatBodyContent()}
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

function renderChatBubble(m, showCancelConfirmed) {
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

/**
 * fullRender=true يعيد رسم الصفحة كاملة (لازم أول ما تُفتح المحادثة أو تتغيّر المهمة).
 * fullRender=false يحدّث بس منطقة الفقاعات + شارة الموعد المؤكد بأعلى البطاقة، بدون
 * لمس صندوق الكتابة إطلاقًا — هذا يمنع اختفاء أي نص لسا المستخدم يكتبه كل ما يوصل
 * تحديث دوري (كل 5 ثواني) أو رسالة جديدة من الطرف الثاني.
 */
async function mcLoadMessages(scrollToBottom, fullRender) {
  if (!mcSelectedTaskId) return;
  try {
    const data = await apiGet(base + "/dashboard/meeting-schedule/api/messages?mission_id=" + encodeURIComponent(mcSelectedTaskId));
    if (data && data.success) {
      mcMessages = data.messages || [];
      mcMeeting = data.meeting || null;
      mcMyUserId = data.my_user_id || null;
      mcMessagesCache[mcSelectedTaskId] = { messages: mcMessages, meeting: mcMeeting };

      if (fullRender) {
        rerenderMSContent();
      } else {
        mcUpdateChatBodyOnly();
      }
      if (scrollToBottom) mcScrollToBottom();
    }
  } catch (e) {
    console.error("تعذّر تحميل المحادثة:", e);
  }
}

function mcUpdateChatBodyOnly() {
  const body = document.getElementById("mcChatBody");
  if (body) {
    body.innerHTML = renderChatBodyContent();
    body.querySelectorAll("[data-confirm-msg]").forEach(btn => {
      btn.addEventListener("click", () => mcHandleConfirm(btn));
    });
    body.querySelectorAll("[data-cancel-msg]").forEach(btn => {
      btn.addEventListener("click", () => mcHandleCancel(btn));
    });
    body.querySelectorAll("[data-cancel-confirmed]").forEach(btn => {
      btn.addEventListener("click", () => mcHandleCancelConfirmed(btn));
    });
  }
  lucide.createIcons();
}

async function mcHandleConfirm(btn) {
  btn.disabled = true;
  try {
    await apiPost(base + "/dashboard/meeting-schedule/api/confirm", { mission_id: mcSelectedTaskId, message_id: btn.dataset.confirmMsg });
    await mcLoadMessages(true, false);
  } catch (e) {
    showToast(e.message || "تعذّر تأكيد الموعد", "error");
    btn.disabled = false;
  }
}

async function mcHandleCancel(btn) {
  btn.disabled = true;
  try {
    await apiPost(base + "/dashboard/meeting-schedule/api/cancel", { mission_id: mcSelectedTaskId, message_id: btn.dataset.cancelMsg });
    await mcLoadMessages(true, false);
  } catch (e) {
    showToast(e.message || "تعذّر إلغاء الموعد", "error");
    btn.disabled = false;
  }
}

/* إلغاء موعد مؤكَّد فعليًا (بعد الاتفاق عليه) -- يختلف عن mcHandleCancel اللي يلغي
   اقتراحًا لسا ما تأكّد؛ نجاحها يخلي mcMeeting.status يصير "cancelled"، وبالتبعية
   المؤشر بالصفحة الرئيسية (يعتمد على نفس status='scheduled') يختفي تلقائيًا */
async function mcHandleCancelConfirmed(btn) {
  btn.disabled = true;
  try {
    await apiPost(base + "/dashboard/meeting-schedule/api/cancel-confirmed", { mission_id: mcSelectedTaskId });
    await mcLoadMessages(true, false);
  } catch (e) {
    showToast(e.message || "تعذّر إلغاء الموعد", "error");
    btn.disabled = false;
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
    if (mcSelectedTaskId) mcLoadMessages(false, false);
  }, 5000);
}

function bindMeetingScheduleEvents() {
  const taskSelect = document.getElementById("mcTaskSelect");
  if (taskSelect) taskSelect.addEventListener("change", e => {
    mcSelectedTaskId = e.target.value;
    mcShowProposeForm = false;
    // لو المهمة الجديدة سبق تحميلها بنفس الجلسة، نعرض نسختها المخزّنة فورًا (بدون
    // ما تختفي رسائلها للحظة) بينما التحديث الفعلي يجري بالخلفية بهدوء
    const cached = mcSelectedTaskId ? mcMessagesCache[mcSelectedTaskId] : null;
    mcMessages = cached ? cached.messages : [];
    mcMeeting = cached ? cached.meeting : null;
    rerenderMSContent();
    if (mcSelectedTaskId) { mcLoadMessages(true, !cached); mcStartPolling(); }
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
        await mcLoadMessages(true, false);
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
      await mcLoadMessages(true, true);
    } catch (e) {
      showToast(e.message || "تعذّر إرسال الاقتراح", "error");
    } finally {
      submitBtn.disabled = false;
    }
  });

  document.querySelectorAll("[data-confirm-msg]").forEach(btn => {
    btn.addEventListener("click", () => mcHandleConfirm(btn));
  });
  document.querySelectorAll("[data-cancel-msg]").forEach(btn => {
    btn.addEventListener("click", () => mcHandleCancel(btn));
  });
  document.querySelectorAll("[data-cancel-confirmed]").forEach(btn => {
    btn.addEventListener("click", () => mcHandleCancelConfirmed(btn));
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
