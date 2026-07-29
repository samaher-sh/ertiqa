/* ============================================================
   صفحة الإخطارات — متصلة بالـ API الحقيقي (جدول notifications)
   ============================================================ */
let notifItems = [];
let notifLoading = false;

async function loadNotifications() {
  notifLoading = true;
  try {
    const res = await fetch(base + "/dashboard/notifications/api/list");
    const data = await res.json();
    notifItems = data.notifications || [];
  } catch (e) {
    notifItems = [];
  }
  notifLoading = false;
}

function renderNotificationsPage() {
  const unreadCount = notifItems.filter(n => !Number(n.is_read)).length;

  return `
  <div class="notif-card">
    <div class="notif-head">
      <i data-lucide="bell"></i>
      <div><h2>الإخطارات</h2><p>Notifications</p></div>
      ${unreadCount > 0 ? `<span class="notif-unread-badge">${unreadCount} غير مقروء</span>` : ""}
    </div>
    <div class="notif-list">
      ${notifLoading ? `<div class="notif-empty">جارِ التحميل...</div>` :
        notifItems.length === 0
        ? `<div class="notif-empty">لا توجد إخطارات حاليًا</div>`
        : notifItems.map(n => `
          <div class="notif-item ${!Number(n.is_read) ? "unread" : ""}" data-notif-id="${n.id}">
            <div class="notif-dot-wrap"><span class="notif-dot"></span></div>
            <div class="notif-icon"><i data-lucide="bell"></i></div>
            <div class="notif-body">
              <div class="notif-title-row">
                <p class="notif-title">${escHtmlNotif(n.title)}</p>
                <span class="notif-type-tag">${escHtmlNotif(n.type)}</span>
              </div>
              <p class="notif-text">${escHtmlNotif(n.body)}</p>
              <span class="notif-time" dir="ltr">${escHtmlNotif(n.created_at)}</span>
            </div>
          </div>
        `).join("")}
    </div>
  </div>`;
}

function bindNotificationsEvents() {
  document.querySelectorAll("[data-notif-id]").forEach(el => {
    el.addEventListener("click", async () => {
      const id = el.dataset.notifId;
      const n = notifItems.find(x => String(x.id) === String(id));
      if (!n || Number(n.is_read)) return;
      n.is_read = 1;
      rerenderNotifContent();
      try {
        await fetch(base + "/dashboard/notifications/api/mark-read/" + id, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ [csrfName()]: csrfValue() }),
        });
      } catch (e) {}
    });
  });
}

function rerenderNotifContent() {
  const el = document.getElementById("contentArea");
  el.innerHTML = renderNotificationsPage();
  bindNotificationsEvents();
  lucide.createIcons();
}

function escHtmlNotif(str) {
  return String(str == null ? "" : str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
