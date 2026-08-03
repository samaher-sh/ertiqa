/* ============================================================
   جدولة الاجتماع (Meeting Schedule Form)
   منقول عن MeetingScheduleForm في AdminDashboard.tsx
   ============================================================ */

let msState = {
  title: "", loc: "", date: "", time: "",
  objective: "التعريف بأهداف المراجعة، فهم نشاط الإدارة، السياسات والإجراءات المتبعة",
  dept: "", sent: false,
};

function rerenderMSContent() {
  const active = document.activeElement;
  const activeId = active && active.id;
  const selStart = active && typeof active.selectionStart === "number" ? active.selectionStart : null;
  const selEnd = active && typeof active.selectionEnd === "number" ? active.selectionEnd : null;
  const ca = document.getElementById("contentArea");
  const scrollTop = ca ? ca.scrollTop : 0;

  renderContent();

  if (activeId) {
    const el = document.getElementById(activeId);
    if (el) { el.focus(); if (selStart !== null && el.setSelectionRange) { try { el.setSelectionRange(selStart, selEnd); } catch (e) {} } }
  }
  if (ca) ca.scrollTop = scrollTop;
}

function renderMeetingSchedulePage() {
  const s = msState;
  return `
  <div class="flex flex-col gap-5">
    <div class="wiz-card">
      <div class="wiz-card-head"><i data-lucide="calendar"></i><div><h2>جدولة الاجتماع</h2><p>Meeting Schedule</p></div></div>
      <div class="ms-grid">
        <div class="wiz-field span2">
          <label class="wiz-label">عنوان المهمة / الاجتماع</label>
          <input id="msTitle" type="text" class="wiz-input plain" placeholder="عنوان الاجتماع" value="${escapeHtml(s.title)}">
        </div>
        <div class="wiz-field">
          <label class="wiz-label">الإدارة المستهدفة</label>
          <div class="wiz-input-icon-wrap"><i data-lucide="building-2"></i><input id="msDept" type="text" class="wiz-input plain" placeholder="اسم الإدارة" value="${escapeHtml(s.dept)}"></div>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">مكان الاجتماع</label>
          <div class="wiz-input-icon-wrap"><i data-lucide="building-2"></i><input id="msLoc" type="text" class="wiz-input plain" placeholder="قاعة / موقع الاجتماع" value="${escapeHtml(s.loc)}"></div>
        </div>
        <div class="wiz-field">
          <label class="wiz-label">تاريخ الاجتماع</label>
          <input id="msDate" type="date" class="wiz-input plain" value="${s.date}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
        <div class="wiz-field">
          <label class="wiz-label">وقت الاجتماع</label>
          <input id="msTime" type="time" class="wiz-input plain" value="${s.time}" onclick="try{this.showPicker&&this.showPicker()}catch(e){}">
        </div>
        <div class="wiz-field span2">
          <label class="wiz-label">الهدف من الاجتماع</label>
          <textarea id="msObjective" rows="3" class="wiz-textarea plain">${escapeHtml(s.objective)}</textarea>
        </div>
      </div>
      <div class="ms-footer">
        <span class="ms-footer-hint">يُرسل إشعار الاجتماع للإدارة المستهدفة تلقائياً عند الإرسال</span>
        <button class="ms-send-btn ${s.sent ? "sent" : ""}" id="msSendBtn">
          ${s.sent ? '<i data-lucide="check"></i> تم إرسال الإشعار' : '<i data-lucide="calendar"></i> إرسال إشعار الاجتماع'}
        </button>
      </div>
    </div>
  </div>`;
}

function bindMeetingScheduleEvents() {
  const $ = id => document.getElementById(id);
  const s = msState;
  $("msTitle").addEventListener("input", e => { s.title = e.target.value; rerenderMSContent(); });
  $("msDept").addEventListener("input", e => { s.dept = e.target.value; rerenderMSContent(); });
  $("msLoc").addEventListener("input", e => { s.loc = e.target.value; rerenderMSContent(); });
  $("msDate").addEventListener("change", e => { s.date = e.target.value; rerenderMSContent(); });
  $("msTime").addEventListener("change", e => { s.time = e.target.value; rerenderMSContent(); });
  $("msObjective").addEventListener("input", e => { s.objective = e.target.value; rerenderMSContent(); });
  $("msSendBtn").addEventListener("click", () => { s.sent = true; rerenderMSContent(); });
}
