/* ============================================================
   منطق لوحة المراجع الداخلي - متصل بالـ API الحقيقي
   (base معرّفة بملف shell.php مباشرة، متاحة لكل ملفات الصفحات)
   ============================================================ */

/* ---------- بيانات المستخدم والقائمة الجانبية (تُجلب من السيرفر) ---------- */
let currentUser = null;
let navItemsData = [];

let isAuditHead     = false;
let isAuditMember   = false;
let isHrCoordinator = false;
let isHrDept        = false;
let isPresident      = false;

/* ---------- الحالة العامة ---------- */
let navOpen        = true;
let mobileOpen      = false;
let profileOpen     = false;
let activeContent   = "home";
let activeStatCard  = null;

/* بيانات الرئيسية الحقيقية - تُملأ بعد fetch */
let homeStats    = { active_count: 0, review_count: 0, meetings_count: 0 };
let activeMissions = [];
let scheduledMeetings = [];
let confirmedMeetingAlert = null;

/* ============================================================
   تهيئة الصفحة
   ============================================================ */
document.addEventListener("DOMContentLoaded", async () => {
  try {
    const res = await fetch(base + "/api/session");
    if (res.status === 401) { window.location.href = base + "/"; return; }
    currentUser = await res.json();
  } catch (e) {
    window.location.href = base + "/";
    return;
  }

  const roleCode = currentUser.role_code;
  isPresident     = roleCode === "top_management";
  isHrDept        = ["dept_coordinator", "dept_manager", "specialized_manager"].includes(roleCode);
  isAuditHead     = roleCode === "audit_head";
  isAuditMember   = roleCode === "audit_member";
  isHrCoordinator = roleCode === "dept_coordinator";

  try {
    const navData = await apiGet(base + "/api/nav-items");
    navItemsData = navData.items || [];
  } catch (e) {
    navItemsData = [];
  }

  renderSidebar();
  renderProfile();
  await renderContent();
  bindGlobalEvents();
  lucide.createIcons();
});

/* ============================================================
   الشريط الجانبي
   ============================================================ */
function renderSidebar() {
  const sidebar     = document.getElementById("sidebar");
  const logoRow     = document.getElementById("sidebarLogoRow");
  const nav         = document.getElementById("sidebarNav");
  const bottom      = document.getElementById("sidebarBottom");

  sidebar.classList.toggle("collapsed", !navOpen);
  sidebar.classList.toggle("mobile-open", mobileOpen);

  const logoUrl = base + "/assets/images/kamc.png";

  /* Logo row */
  if (navOpen) {
    logoRow.innerHTML = `
      <div class="logo-info">
        <div class="logo-box"><img src="${logoUrl}" alt="KAMC"></div>
        <div class="logo-title">
          <p class="t1">ارتقاء</p>
          <p class="t2">مدينة الملك عبدالله الطبية</p>
        </div>
      </div>
      <button class="sidebar-toggle-btn" id="toggleNavBtn" title="طي القائمة">
        <i data-lucide="panel-left-close"></i>
      </button>
    `;
  } else {
    logoRow.innerHTML = `
      <button class="sidebar-toggle-collapsed" id="toggleNavBtn" title="فتح القائمة">
        <img src="${logoUrl}" alt="KAMC">
      </button>
    `;
  }

  /* Nav items - القائمة جاهزة ومفلترة حسب الدور فعليًا من السيرفر (GET /api/nav-items) */
  nav.innerHTML = navItemsData.map(item => `
    <button class="nav-item ${activeContent === item.key ? "active" : ""}" data-nav="${item.key}">
      <div class="nav-icon-box"><i data-lucide="${item.icon}"></i></div>
      <div class="nav-text">
        <span class="nav-label">${item.label}</span>
        <span class="nav-desc">${item.desc}</span>
      </div>
      ${!navOpen ? `<span class="nav-tooltip">${item.label}</span>` : ""}
    </button>
  `).join("");

  bottom.innerHTML = `
    <button class="sidebar-logout-btn" id="sidebarLogoutBtn" title="تسجيل الخروج">
      <i data-lucide="log-out"></i>
      <span>تسجيل الخروج</span>
    </button>
  `;

  document.getElementById("toggleNavBtn").addEventListener("click", () => {
    navOpen = !navOpen;
    renderSidebar();
    lucide.createIcons();
  });

  nav.querySelectorAll(".nav-item").forEach(btn => {
    btn.addEventListener("click", async () => {
      activeContent = btn.dataset.nav;
      activeStatCard = null;
      renderSidebar();
      await renderContent();
      lucide.createIcons();
    });
  });

  document.getElementById("sidebarLogoutBtn").addEventListener("click", logout);

  lucide.createIcons();
}

/* ============================================================
   بطاقة الملف الشخصي (الهيدر) - بيانات حقيقية من /api/session
   ============================================================ */
function renderProfile() {
  const initial = (currentUser.full_name || currentUser.role_name || "م").charAt(0);
  document.getElementById("avatarInitial").textContent   = initial;
  document.getElementById("avatarInitialLg").textContent = initial;
  document.getElementById("profileName").textContent      = currentUser.full_name || "المستخدم";
  document.getElementById("profileRoleLabel").textContent = currentUser.role_name || "—";
  document.getElementById("ddName").textContent            = currentUser.full_name || "المستخدم";
  document.getElementById("ddRole").textContent            = currentUser.role_name || "—";
  document.getElementById("ddEmail").textContent           = currentUser.email || "—";
  // لا يوجد "رقم وظيفي" منفصل بجدول users فعليًا - نعرض رقم الهوية (المعرّف الحقيقي المستخدم بالنظام) بدلاً منه
  document.getElementById("ddEmpId").textContent           = currentUser.national_id || "—";
  document.getElementById("ddFullName").textContent        = currentUser.full_name || "—";
  document.getElementById("ddEmpId2").textContent          = currentUser.national_id || "—";
  document.getElementById("ddPhone").textContent           = currentUser.phone || "—";
  document.getElementById("ddDept").textContent             = currentUser.department_parent_name || currentUser.department_name || "—";
  document.getElementById("ddSubDept").textContent          = currentUser.department_parent_name ? (currentUser.department_name || "—") : "";

  const profileBtn  = document.getElementById("profileBtn");
  const profileWrap = document.getElementById("profileWrap");

  profileBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    profileOpen = !profileOpen;
    profileWrap.classList.toggle("open", profileOpen);
  });

  document.addEventListener("click", (e) => {
    if (!profileWrap.contains(e.target)) {
      profileOpen = false;
      profileWrap.classList.remove("open");
    }
  });

  document.getElementById("logoutBtn").addEventListener("click", logout);
}

function logout() {
  window.location.href = base + "/auth/logout";
}

/* ============================================================
   منطقة المحتوى الرئيسية
   ============================================================ */
async function renderContent() {
  const el = document.getElementById("contentArea");

  if (activeContent === "home") {
    await loadHomeData();
    el.innerHTML = renderHomeTab();
    bindHomeEvents();
  } else if (activeContent === "riskMatrix") {
    await loadMissionsForSelector();
    if (rmSelectedTaskId) await rmLoadItems(rmSelectedTaskId);
    el.innerHTML = renderRiskMatrixPage();
    bindRiskMatrixEvents();
  } else if (activeContent === "meetingSummary") {
    await loadMissionsForSelector();
    if (msumSelectedTaskId) await msumLoadData(msumSelectedTaskId);
    el.innerHTML = renderMeetingSummaryPage();
    bindMeetingSummaryEvents();
  } else if (activeContent === "newTask") {
    await initWizardData();
    el.innerHTML = renderWizardPage();
    bindWizardEvents();
  } else if (activeContent === "observations") {
    await loadMissionsForSelector();
    if (obsSelectedTaskId) await obsLoadList(obsSelectedTaskId);
    el.innerHTML = renderObservationsPage();
    bindObservationsEvents();
  } else if (activeContent === "taskDetail" && typeof selectedTaskDetail !== "undefined" && selectedTaskDetail) {
    el.innerHTML = renderTaskDetailPage();
    bindTaskDetailEvents();
  } else if (activeContent === "sentTasks") {
    await loadMissionsForSelector();
    el.innerHTML = renderSentTasksPage();
    bindSentTasksEvents();
  } else if (activeContent === "finalReports") {
    await loadMissionsForSelector();
    await initFinalReportsData();
    if (frView === "create" && frCreateSelectedTask) await frLoadChecklist(frCreateSelectedTask);
    el.innerHTML = renderFinalReportsPage();
    bindFinalReportsEvents();
  } else if (activeContent === "meetingSchedule") {
    await loadMissionsForSelector();
    el.innerHTML = renderMeetingSchedulePage();
    bindMeetingScheduleEvents();
  } else if (activeContent === "notifications") {
    await loadNotifications();
    el.innerHTML = renderNotificationsPage();
    bindNotificationsEvents();
  } else if (activeContent === "documentRequests") {
    await loadMissionsForSelector();
    if (drSelectedTaskId) await loadDocumentRequests(drSelectedTaskId);
    el.innerHTML = renderDocumentRequestsPage();
    bindDocumentRequestsEvents();
  } else if (activeContent === "missionReview") {
    await loadMissionsForSelector();
    if (mrSelectedTaskId) await loadMissionReviewData(mrSelectedTaskId);
    el.innerHTML = renderMissionReviewPage();
    bindMissionReviewEvents();
  } else {
    el.innerHTML = renderPlaceholder(activeContent);
  }
  lucide.createIcons();
}

/* ---------- قائمة المهام النشطة المشتركة (تستخدمها كل صفحة فيها "اختر المهمة المرتبطة") ---------- */
let missionsForSelector = [];

async function loadMissionsForSelector() {
  try {
    const url = isHrDept ? base + "/dashboard/api/target-missions" : base + "/dashboard/api/active-missions";
    const data = await apiGet(url);
    missionsForSelector = data.missions || [];
  } catch (e) {
    missionsForSelector = [];
  }
}

/* ---------- تبويب الرئيسية ---------- */
async function loadHomeData() {
  try {
    // منسّق/مدير الإدارة الخاضعة للمراجعة يرى المهام الموجّهة فعليًا لإدارته
    // (target_department_id)، لا المهام التي يقودها أو ضمن فريقها (وهو مفهوم خاص بالمراجعين)
    const missionsUrl = isHrDept ? base + "/dashboard/api/target-missions" : base + "/dashboard/api/active-missions";
    const [stats, missionsData, meetingsData] = await Promise.all([
      apiGet(base + "/dashboard/api/home-stats"),
      apiGet(missionsUrl),
      apiGet(base + "/dashboard/api/scheduled-meetings"),
    ]);
    homeStats = stats;
    activeMissions = missionsData.missions || [];
    scheduledMeetings = meetingsData.meetings || [];
    confirmedMeetingAlert = stats.confirmed_meeting_alert || null;
  } catch (e) {
    homeStats = { active_count: 0, review_count: 0, meetings_count: 0 };
    activeMissions = [];
    scheduledMeetings = [];
    confirmedMeetingAlert = null;
  }
}

/* تنبيه اجتماع مؤكد بالصفحة الرئيسية — يظهر لطرفي المهمة فور تأكيد أحدهما لموعد
   عبر شات "جدولة اجتماع" (بيانات حقيقية ضمن /dashboard/api/home-stats) */
function renderMeetingAlertBanner() {
  if (!confirmedMeetingAlert) return "";
  const a = confirmedMeetingAlert;
  return `
  <div class="home-meeting-alert">
    <i data-lucide="calendar-check"></i>
    <div class="home-meeting-alert-text">
      <p class="t1">تم تأكيد موعد اجتماع${a.mission_code ? " — " + escapeHtml(a.mission_code) : ""}</p>
      <p class="t2">${escapeHtml(a.meeting_date || "")}${a.meeting_time ? " — " + escapeHtml(a.meeting_time) : ""}${a.location ? " · " + escapeHtml(a.location) : ""}</p>
    </div>
    <button type="button" class="home-meeting-alert-btn" id="homeMeetingAlertBtn">عرض</button>
  </div>`;
}

/* مصفوفة بطاقات الإحصائيات تختلف حسب الدور: رئيس المراجعة يشرف على التقارير
   (لا مهام شخصية له)، بينما بقية الأدوار ترى اجتماعاتها/مهامها النشطة */
function homeStatsCards() {
  if (isAuditHead) {
    return [
      { key: "reportsPending",  label: "تقارير تحتاج اعتماد", sub: "Reports Requiring Approval", value: homeStats.reports_pending_count || 0 },
      { key: "reportsApproved", label: "التقارير المعتمدة",   sub: "Approved Reports",           value: homeStats.reports_approved_count || 0 },
    ];
  }
  return [
    { key: "activeMissions",    label: "المهام النشطة",   sub: "Active Tasks",       value: activeMissions.length },
    { key: "scheduledMeetings", label: "اجتماعات مجدولة", sub: "Scheduled Meetings", value: scheduledMeetings.length },
  ];
}

/* بطاقة/شريط الإخطار العلوي بالرئيسية — يختلف حسب الدور، ويعتمد فقط على بيانات حقيقية
   (لا يظهر شيء إن ما كان فيه محتوى حقيقي ليعرضه) */
function renderHomeBanner() {
  if (isAuditHead) {
    const pending = homeStats.reports_pending_count || 0;
    if (pending === 0) return "";
    return `
    <div class="home-banner">
      <div class="home-banner-head">
        <i data-lucide="clipboard-check" class="home-banner-head-icon"></i>
        <div class="home-banner-head-text">
          <p class="t1">التقارير التي تحتاج اعتماد</p>
          <p class="t2">يوجد تقارير نهائية قيد الانتظار لاعتمادها</p>
        </div>
        <span class="home-banner-badge">تقارير جديدة</span>
      </div>
      <div class="home-banner-body">
        <div class="home-banner-icon-box"><i data-lucide="file-text"></i></div>
        <div class="home-banner-content">
          <div class="home-banner-title-row">
            <span class="home-banner-item-title">تقارير تحتاج اعتماد — المراجعة الداخلية</span>
            <span class="home-banner-dot"></span>
            <span class="home-banner-tag">بانتظار الاعتماد</span>
          </div>
          <p class="home-banner-desc">يوجد ${pending} ${pending === 1 ? "تقرير" : "تقارير"} جاهزة وتنتظر اعتمادك للبدء بتعميمها بشكل نهائي.</p>
        </div>
        <button class="home-banner-open-btn" id="homeBannerOpenBtn">عرض التقارير</button>
      </div>
    </div>`;
  }

  if (isHrDept || isHrCoordinator) {
    const n = homeStats.latest_notification;
    if (!n) return "";
    return `
    <div class="home-banner">
      <div class="home-banner-head">
        <i data-lucide="bell" class="home-banner-head-icon"></i>
        <div class="home-banner-head-text">
          <p class="t1">لديك إخطارات جديدة</p>
          <p class="t2">${escapeHtml(n.title)}</p>
        </div>
        <span class="home-banner-badge">${homeStats.unread_notifications_count || 1} غير مقروء</span>
      </div>
      <div class="home-banner-body">
        <div class="home-banner-icon-box"><i data-lucide="bell"></i></div>
        <div class="home-banner-content">
          <div class="home-banner-title-row">
            <span class="home-banner-item-title">${escapeHtml(n.title)}</span>
          </div>
          <p class="home-banner-desc">${escapeHtml(n.body || "")}</p>
        </div>
        <button class="home-banner-open-btn" id="homeBannerOpenBtn">فتح</button>
      </div>
    </div>`;
  }

  return "";
}

function renderHomeTab() {
  const STATS = homeStatsCards();

  let html = renderMeetingAlertBanner() + renderHomeBanner();

  html += `<div class="stats-grid ${isAuditMember ? "" : "two-col"}">`;
  if (isAuditMember) {
    html += `
      <button class="stat-action-card" id="homeNewTaskCard">
        <div class="stat-action-top"><span class="stat-dot light"></span></div>
        <div>
          <p class="stat-action-label">بدء مهمة</p>
          <p class="stat-action-sub">New Audit Task</p>
        </div>
        <p class="stat-action-cta"><i data-lucide="plus"></i> ابدأ</p>
      </button>
    `;
  }
  STATS.forEach((s, i) => {
    html += `
      <button class="stat-card ${activeStatCard === i ? "active" : ""}" data-stat="${i}">
        <div class="stat-card-top">
          <span class="stat-dot"></span>
          ${activeStatCard === i ? `<i data-lucide="chevron-down" class="stat-card-chevron"></i>` : ""}
        </div>
        <div>
          <p class="stat-label">${s.label}</p>
          <p class="stat-sub">${s.sub}</p>
        </div>
        <p class="stat-value">${s.value}</p>
      </button>
    `;
  });
  html += `</div>`;

  if (activeStatCard !== null) {
    html += renderStatDetailPanel(activeStatCard);
  }

  return html;
}

function renderStatDetailPanel(idx) {
  const cards = homeStatsCards();
  const card = cards[idx] || null;
  const label = card ? card.label : "";

  if (isAuditHead) {
    return `
      <div class="detail-panel" style="border-color:var(--pb);">
        <div class="detail-head" style="background:var(--pl); border-color:var(--pb);">
          <span class="detail-dot" style="background:var(--p);"></span>
          <p class="detail-title" style="color:var(--p);">${label}</p>
          <button class="detail-close" id="closeDetailBtn" style="color:var(--p);"><i data-lucide="x"></i></button>
        </div>
        <div class="detail-body">
          <button class="task-row" id="statGoReportsBtn">
            <div class="task-row-icon"><i data-lucide="file-text"></i></div>
            <div class="task-row-body">
              <p class="task-row-title">عرض تفاصيل التقارير</p>
              <p class="task-row-sub">الانتقال إلى صفحة التقارير النهائية</p>
            </div>
          </button>
        </div>
      </div>
    `;
  }

  let bodyHtml;
  if (card && card.key === "activeMissions") {
    bodyHtml = activeMissions.length === 0
      ? `<p class="empty-hint">لا توجد بيانات لعرضها حالياً</p>`
      : activeMissions.map(m => `
        <button class="task-row" data-mission="${m.id}">
          <div class="task-row-icon"><i data-lucide="eye"></i></div>
          <div class="task-row-body">
            <p class="task-row-title">${escapeHtml(m.target_department_name || "")}</p>
            <p class="task-row-sub">${escapeHtml(m.mission_code)} · ${escapeHtml(String(m.year))}</p>
          </div>
          <div class="task-row-badges">
            <span class="task-phase-badge">المرحلة ${m.current_stage}</span>
          </div>
        </button>
      `).join("");
  } else {
    bodyHtml = scheduledMeetings.length === 0
      ? `<p class="empty-hint">لا توجد بيانات لعرضها حالياً</p>`
      : scheduledMeetings.map(m => `
        <div class="task-row">
          <div class="task-row-icon"><i data-lucide="calendar"></i></div>
          <div class="task-row-body">
            <p class="task-row-title">${escapeHtml(m.title || m.meeting_code)}</p>
            <p class="task-row-sub">${escapeHtml(m.meeting_date || "")} ${escapeHtml(m.meeting_time || "")}</p>
          </div>
        </div>
      `).join("");
  }

  return `
    <div class="detail-panel" style="border-color:var(--pb);">
      <div class="detail-head" style="background:var(--pl); border-color:var(--pb);">
        <span class="detail-dot" style="background:var(--p);"></span>
        <p class="detail-title" style="color:var(--p);">${label}</p>
        <button class="detail-close" id="closeDetailBtn" style="color:var(--p);"><i data-lucide="x"></i></button>
      </div>
      <div class="detail-body">${bodyHtml}</div>
    </div>
  `;
}

function bindHomeEvents() {
  const meetingAlertBtn = document.getElementById("homeMeetingAlertBtn");
  if (meetingAlertBtn) meetingAlertBtn.addEventListener("click", async () => {
    activeContent = "meetingSchedule";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  const newTaskCard = document.getElementById("homeNewTaskCard");
  if (newTaskCard) newTaskCard.addEventListener("click", async () => {
    activeContent = "newTask";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  const bannerOpenBtn = document.getElementById("homeBannerOpenBtn");
  if (bannerOpenBtn) bannerOpenBtn.addEventListener("click", async () => {
    activeContent = isAuditHead ? "finalReports" : "notifications";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  const statGoReportsBtn = document.getElementById("statGoReportsBtn");
  if (statGoReportsBtn) statGoReportsBtn.addEventListener("click", async () => {
    activeContent = "finalReports";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  document.querySelectorAll(".stat-card").forEach(btn => {
    btn.addEventListener("click", async () => {
      const i = parseInt(btn.dataset.stat, 10);
      activeStatCard = activeStatCard === i ? null : i;
      const el = document.getElementById("contentArea");
      el.innerHTML = renderHomeTab();
      bindHomeEvents();
      lucide.createIcons();
    });
  });

  const closeBtn = document.getElementById("closeDetailBtn");
  if (closeBtn) closeBtn.addEventListener("click", async () => {
    activeStatCard = null;
    const el = document.getElementById("contentArea");
    el.innerHTML = renderHomeTab();
    bindHomeEvents();
    lucide.createIcons();
  });

  document.querySelectorAll("[data-mission]").forEach(btn => {
    btn.addEventListener("click", () => {
      showToast("عرض تفاصيل المهمة " + btn.dataset.mission + " — سيتوفر في المرحلة القادمة", "default");
    });
  });
}

/* ---------- صفحة "قيد الإنشاء" لبقية الأقسام ---------- */
function renderPlaceholder(key) {
  const item = navItemsData.find(n => n.key === key) || { label: "الصفحة", desc: "" };
  return `
    <div class="placeholder-card">
      <div class="placeholder-head">
        <i data-lucide="${item.icon || "settings"}"></i>
        <div>
          <h2>${item.label}</h2>
          <p>${item.desc}</p>
        </div>
      </div>
      <div class="placeholder-body">
        <div class="placeholder-icon"><i data-lucide="${item.icon || "settings"}"></i></div>
        <p class="msg">هذا القسم قيد التحويل حالياً</p>
        <p class="hint">سيتم إضافته في المرحلة القادمة من التحويل</p>
      </div>
    </div>
  `;
}

/* ============================================================
   إشعارات Toast بسيطة (بديل sonner)
   ============================================================ */
function showToast(message, type) {
  const container = document.getElementById("toastContainer");
  const el = document.createElement("div");
  el.className = "toast" + (type === "success" ? " success" : type === "error" ? " error" : "");
  el.textContent = message;
  container.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ============================================================
   أحداث عامة (موبايل)
   ============================================================ */
function bindGlobalEvents() {
  document.getElementById("mobileMenuBtn").addEventListener("click", () => {
    mobileOpen = true;
    document.getElementById("mobileOverlay").classList.add("show");
    renderSidebar();
  });
  document.getElementById("mobileOverlay").addEventListener("click", () => {
    mobileOpen = false;
    document.getElementById("mobileOverlay").classList.remove("show");
    renderSidebar();
  });
}
