/* ============================================================
   صفحة الإخطارات — متصلة بالـ API الحقيقي (جدول notifications)
   ============================================================ */
let notifItems = [];
let notifLoading = false;

async function loadNotifications() {
  notifLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/notifications/api/list");
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
                <p class="notif-title">${escapeHtml(n.title)}</p>
                <span class="notif-type-tag">${escapeHtml(n.type)}</span>
              </div>
              <p class="notif-text">${escapeHtml(n.body)}</p>
              <span class="notif-time" dir="ltr">${escapeHtml(n.created_at)}</span>
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
        await apiPost(base + "/dashboard/notifications/api/mark-read/" + id, {});
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
