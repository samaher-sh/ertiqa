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

  const root = document.getElementById("tpl-notifications").content.cloneNode(true);
  const card = root.querySelector(".notif-card");

  const badge = card.querySelector('[data-slot="badge"]');
  if (unreadCount > 0) {
    badge.hidden = false;
    badge.textContent = unreadCount + " غير مقروء";
  }

  const list = card.querySelector('[data-slot="list"]');
  if (notifLoading) {
    list.innerHTML = `<div class="notif-empty">جارِ التحميل...</div>`;
  } else if (notifItems.length === 0) {
    list.innerHTML = `<div class="notif-empty">لا توجد إخطارات حاليًا</div>`;
  } else {
    const itemTpl = document.getElementById("tpl-notif-item");
    notifItems.forEach(n => {
      const item = itemTpl.content.cloneNode(true);
      const row = item.querySelector(".notif-item");
      row.classList.toggle("unread", !Number(n.is_read));
      row.dataset.notifId = n.id;
      row.querySelector('[data-slot="title"]').textContent = n.title == null ? "" : String(n.title);
      row.querySelector('[data-slot="type"]').textContent = n.type == null ? "" : String(n.type);
      row.querySelector('[data-slot="body"]').textContent = n.body == null ? "" : String(n.body);
      row.querySelector('[data-slot="time"]').textContent = n.created_at == null ? "" : String(n.created_at);
      list.appendChild(item);
    });
  }

  return card.outerHTML;
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
