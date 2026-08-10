/* ============================================================
   صفحة الإخطارات — لمستخدمي الإدارة الخاضعة للمراجعة (dept_coordinator وما شابه)
   كل مهمة موجّهة فعليًا لإدارة المستخدم (نفس /dashboard/api/target-missions
   المستخدم أصلًا لعرض المهام بصفحات أخرى) تظهر كإخطار "طلب مراجعة داخلية جديد".
   حالة القراءة محلية بالجلسة فقط (ما فيه عمود "مقروء" لكل مهمة بقاعدة البيانات).
   ============================================================ */
let notifLoading = false;
let notifReadIds = [];

async function loadNotifications() {
  notifLoading = true;
  try {
    await loadMissionsForSelector();
  } catch (e) {}
  notifLoading = false;
}

function renderNotificationsPage() {
  const items = missionsForSelector.map(m => ({
    id: m.id,
    unread: !notifReadIds.includes(m.id),
    type: m.status === "active" ? "مهمة نشطة" : "مكتملة",
    title: "طلب مراجعة داخلية — " + (m.target_department_name || ""),
    body: `تم إرسال طلب مراجعة داخلية من إدارة المراجعة الداخلية بخصوص المهمة ${m.mission_code || ""}. يرجى الاطلاع على الخطاب الرسمي ومتابعة مراحل المهمة.`,
    time: m.mission_code || "",
  }));

  const unreadCount = items.filter(n => n.unread).length;

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
  } else if (items.length === 0) {
    list.innerHTML = `<div class="notif-empty">لا توجد إخطارات حاليًا</div>`;
  } else {
    const itemTpl = document.getElementById("tpl-notif-item");
    items.forEach(n => {
      const item = itemTpl.content.cloneNode(true);
      const row = item.querySelector(".notif-item");
      row.classList.toggle("unread", n.unread);
      row.dataset.notifId = n.id;
      row.querySelector('[data-slot="title"]').textContent = n.title;
      row.querySelector('[data-slot="type"]').textContent = n.type;
      row.querySelector('[data-slot="body"]').textContent = n.body;
      row.querySelector('[data-slot="time"]').textContent = n.time;
      list.appendChild(item);
    });
  }

  return card.outerHTML;
}

function bindNotificationsEvents() {
  document.querySelectorAll("[data-notif-id]").forEach(el => {
    el.addEventListener("click", async () => {
      const id = Number(el.dataset.notifId);
      if (!notifReadIds.includes(id)) notifReadIds.push(id);
      // كل إخطار مبني أصلًا من مهمة حقيقية بـ missionsForSelector -- نفتح
      // "المراسلات المشتركة" الخاصة بنفس المهمة مباشرة بدل صفحة مراجعة عامة
      const task = missionsForSelector.find(m => Number(m.id) === id);
      if (task) await stOpenTaskDetail(task);
      activeContent = "sentTasks";
      renderSidebar();
      await renderContent();
      lucide.createIcons();
    });
  });
}

function rerenderNotifContent() {
  const el = document.getElementById("contentArea");
  el.innerHTML = renderNotificationsPage();
  bindNotificationsEvents();
  lucide.createIcons();
}
