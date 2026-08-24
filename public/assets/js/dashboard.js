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
/* قائمة إخطارات موحَّدة (نوعين: "task" بانتظار إجراء بمهمة، و"meeting" موعد اجتماع
   مؤكد) -- ودجت "إخطارات" الثابت بالرئيسية يعرضها كقائمة منسدلة واحدة */
let homeNotifications = [];
let notificationsOpen = false;

/* مفاتيح الإخطارات المُخفاة يدويًا (زر X) أو بعد فتحها فعليًا (زر "فتح") -- محلي
   بالجلسة الحالية فقط. المفتاح = نوع+مهمة+عنوان، فلو تغيّر أي منها فعليًا (مثلاً
   المهمة تقدّمت لمرحلة/دور جديد، أو صار تأكيد موعد جديد) يرجع يظهر الإخطار من
   جديد رغم إخفاء السابق */
let dismissedNotificationKeys = new Set();
function notificationKey(n) {
  return n ? [n.type, n.mission_id, n.title].join("|") : "";
}

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
      // دخول عادي من القائمة الجانبية (لا عبر مؤشر أداء محدَّد) يشوف كل التقارير
      // بلا فلتر -- غير كذا يبقى فلتر آخر مؤشر ضغطه "متسربًا" هنا لاحقًا
      if (activeContent === "finalReports" && typeof frAuditHeadFilter !== "undefined") frAuditHeadFilter = "";
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
  } else if (activeContent === "sentTasks") {
    await loadMissionsForSelector();
    el.innerHTML = renderSentTasksPage();
    bindSentTasksEvents();
  } else if (activeContent === "finalReports") {
    await loadMissionsForSelector();
    await initWizardData();
    await initFinalReportsData();
    if (frView === "create" && frCreateSelectedTask) {
      await frLoadChecklist(frCreateSelectedTask);
      if (frCurrentItems.length) await frEnsureStepLoaded(frEffectiveExpandedStep(frCurrentItems));
    }
    el.innerHTML = renderFinalReportsPage();
    bindFinalReportsEvents();
  } else if (activeContent === "meetingSchedule") {
    await loadMissionsForSelector();
    el.innerHTML = renderMeetingSchedulePage();
    bindMeetingScheduleEvents();
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
    const promises = [
      apiGet(base + "/dashboard/api/home-stats"),
      apiGet(missionsUrl),
      apiGet(base + "/dashboard/api/scheduled-meetings"),
    ];
    // رئيس إدارة المراجعة الداخلية يحتاج frReportsList نفسها (finalreports.js) هنا
    // عشان القائمة المنسدلة لمؤشري "تحتاج اعتماد"/"معتمدة" تعرض التقارير الفعلية
    // مباشرة، بدل ما يُضطر ينتقل لصفحة ثانية بس عشان يشوفها
    if (isAuditHead) promises.push(initFinalReportsData());
    const [stats, missionsData, meetingsData] = await Promise.all(promises);
    homeStats = stats;
    activeMissions = missionsData.missions || [];
    scheduledMeetings = meetingsData.meetings || [];
    homeNotifications = stats.notifications || [];
  } catch (e) {
    homeStats = { active_count: 0, review_count: 0, meetings_count: 0 };
    activeMissions = [];
    scheduledMeetings = [];
    homeNotifications = [];
  }
}

/* ودجت "إخطارات" الثابت بالرئيسية -- عنصر واحد ثابت دائم الظهور (لا يختفي حسب
   المحتوى) لمنسّق الإدارة الخاضعة للمراجعة وفريق المراجعة، ترويسته قابلة للضغط
   لفتح/طي قائمة منسدلة تجمع كل الإخطارات الحقيقية (مواعيد مؤكدة + مهام بانتظار
   إجراء) بنفس شكل بطاقة الإخطار المستخدم سابقًا، مع زر "فتح" وزر إغلاق (X)
   مستقلين لكل إخطار */
function renderNotificationsWidget() {
  if (!(isHrDept || isHrCoordinator || isAuditMember || isAuditHead)) return "";

  const items = homeNotifications.filter(n => !dismissedNotificationKeys.has(notificationKey(n)));
  return `
  <div class="home-banner">
    <button type="button" class="home-banner-head notif-trigger" id="notifTriggerBtn">
      <i data-lucide="bell" class="home-banner-head-icon"></i>
      <div class="home-banner-head-text">
        <p class="t1">إخطارات</p>
        <p class="t2">${items.length === 0 ? "لا توجد إخطارات جديدة حاليًا" : `لديك ${items.length} ${items.length === 1 ? "إخطار جديد" : "إخطارات جديدة"}`}</p>
      </div>
      ${items.length > 0 ? `<span class="home-banner-badge">${items.length}</span>` : ""}
      <i data-lucide="chevron-down" class="notif-trigger-chevron ${notificationsOpen ? "notif-trigger-chevron-open" : ""}"></i>
    </button>
    ${notificationsOpen ? (
      items.length === 0
        ? `<p class="notif-empty">لا توجد إخطارات حاليًا</p>`
        : items.map(n => renderNotificationItem(n)).join("")
    ) : ""}
  </div>`;
}

function renderNotificationItem(n) {
  const key = notificationKey(n);
  const isMeeting = n.type === "meeting";
  const icon = isMeeting ? "calendar-check" : n.type === "report_approval" ? "file-check" : "bell";
  return `
  <div class="home-banner-body notif-item">
    <div class="home-banner-icon-box"><i data-lucide="${icon}"></i></div>
    <div class="home-banner-content">
      <div class="home-banner-title-row">
        <span class="home-banner-item-title">${escapeHtml(n.title)}</span>
      </div>
      <p class="home-banner-desc">${escapeHtml(n.body || "")}</p>
    </div>
    <button type="button" class="home-banner-open-btn" data-notif-open="${key}">فتح</button>
    <button type="button" class="home-banner-dismiss-btn notif-item-dismiss" data-notif-dismiss="${key}" title="إخفاء"><i data-lucide="x"></i></button>
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

/* بانر رئيس إدارة المراجعة الداخلية فقط (تقارير تحتاج اعتماد) -- منفصل عن ودجت
   الإخطارات الموحَّد (مفهوم مختلف: اعتماد تقارير، مو مواعيد/مهام بانتظار إجراء) */
function renderHomeBanner() {
  if (!isAuditHead) return "";

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

function renderHomeTab() {
  const STATS = homeStatsCards();

  // "بدء مهمة" ومؤشرات الأداء (المهام النشطة/اجتماعات مجدولة) أول شي بأعلى
  // الصفحة، والإخطارات (بانر التنبيه العام + تأكيد الموعد) تحتها
  let html = `<div class="stats-grid ${isAuditMember ? "" : "two-col"}">`;
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

  html += renderNotificationsWidget() + renderHomeBanner();

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
    // كل مؤشر يعرض التقارير الفعلية اللي يعدّها بالضبط مباشرة بالقائمة المنسدلة
    // (تحتاج اعتماد = pending_signatures، معتمدة = sent) -- الضغط على أي تقرير
    // يوديك لتفاصيله مباشرة (نفس renderApprovalStepper)، بدل زر عام يوديك
    // لصفحة قائمة ثانية تحتاج بعدها تدور على التقرير مرة ثانية
    const isApprovedCard = card && card.key === "reportsApproved";
    const targetStatus = isApprovedCard ? "sent" : "pending_signatures";
    const reports = (typeof frReportsList !== "undefined" ? frReportsList : []).filter(r => r.status === targetStatus);
    const bodyHtml = reports.length === 0
      ? `<p class="empty-hint">لا توجد بيانات لعرضها حالياً</p>`
      : reports.map(r => `
        <button class="task-row" data-report-mission="${r.mission_id}">
          <div class="task-row-icon"><i data-lucide="file-text"></i></div>
          <div class="task-row-body">
            <p class="task-row-title">${escapeHtml(r.mission_code)} — ${escapeHtml(r.target_dept_name || "")}</p>
            <p class="task-row-sub">${escapeHtml((r.created_at || "").slice(0, 10))}</p>
          </div>
          <div class="task-row-badges">
            <span class="task-phase-badge">${isApprovedCard ? "معتمد" : "بانتظار الاعتماد"}</span>
          </div>
        </button>
      `).join("");
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
      : `<div class="ms-meeting-row">` + scheduledMeetings.map(m => `
        <div class="ms-meeting-card">
          <p class="ms-meeting-title">${escapeHtml(m.mission_title || m.title || m.meeting_code)}</p>
          <p class="ms-meeting-code">${escapeHtml(m.mission_code || "")}</p>
          <div class="ms-meeting-meta">
            <span><i data-lucide="map-pin"></i> ${escapeHtml(m.location || "لم يُحدَّد المكان بعد")}</span>
            ${m.meeting_date ? `
              <span><i data-lucide="calendar"></i> ${escapeHtml(m.meeting_date)}</span>
              <span><i data-lucide="clock"></i> ${escapeHtml(m.meeting_time || "")}</span>
            ` : `<span>بانتظار تحديد الموعد</span>`}
          </div>
          ${m.meeting_date ? `
            <button type="button" class="ms-meeting-postpone-btn" data-postpone-mission="${m.mission_id}">
              <i data-lucide="calendar-clock"></i> تأجيل الموعد
            </button>
          ` : ""}
        </div>
      `).join("") + `</div>`;
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

/* يفتح صفحة جدولة اجتماع مباشرة على مهمة معيّنة (بدل ما يحتاج يختارها يدويًا من
   القائمة) -- نفس منطق تبديل المهمة الفعلي بصفحة meetingschedule.js (تحديد فوري من
   النسخة المخزّنة مؤقتًا إن وُجدت، وتحديث حقيقي بالخلفية) */
function openMeetingScheduleForMission(missionId) {
  if (!missionId) return;
  mcSelectedTaskId = String(missionId);
  mcShowProposeForm = false;
  const cached = mcMessagesCache[mcSelectedTaskId];
  mcMessages = cached ? cached.messages : [];
  mcMeeting = cached ? cached.meeting : null;
  mcLoadMessages(true, false);
}

function bindHomeEvents() {
  const notifTriggerBtn = document.getElementById("notifTriggerBtn");
  if (notifTriggerBtn) notifTriggerBtn.addEventListener("click", () => {
    notificationsOpen = !notificationsOpen;
    const el = document.getElementById("contentArea");
    el.innerHTML = renderHomeTab();
    bindHomeEvents();
    lucide.createIcons();
  });

  document.querySelectorAll("[data-notif-open]").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.stopPropagation();
      const key = btn.dataset.notifOpen;
      const n = homeNotifications.find(x => notificationKey(x) === key);
      if (!n) return;
      // فتحه يخفيه ضمنيًا كمان (نفس فكرة زر الإغلاق) -- ما يرجع يظهر لو رجع للرئيسية
      dismissedNotificationKeys.add(key);
      if (n.type === "meeting") {
        openMeetingScheduleForMission(n.mission_id);
        activeContent = "meetingSchedule";
      } else if (n.type === "report_approval") {
        await frOpenReportForMission(n.mission_id);
        activeContent = "finalReports";
      } else {
        await loadMissionsForSelector();
        const task = missionsForSelector.find(m => Number(m.id) === Number(n.mission_id));
        if (task) await stOpenTaskDetail(task);
        activeContent = "sentTasks";
      }
      activeStatCard = null;
      renderSidebar(); await renderContent(); lucide.createIcons();
    });
  });

  document.querySelectorAll("[data-notif-dismiss]").forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      dismissedNotificationKeys.add(btn.dataset.notifDismiss);
      const el = document.getElementById("contentArea");
      el.innerHTML = renderHomeTab();
      bindHomeEvents();
      lucide.createIcons();
    });
  });

  const newTaskCard = document.getElementById("homeNewTaskCard");
  if (newTaskCard) newTaskCard.addEventListener("click", async () => {
    activeContent = "newTask";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  const bannerOpenBtn = document.getElementById("homeBannerOpenBtn");
  if (bannerOpenBtn) bannerOpenBtn.addEventListener("click", async () => {
    // البانر ما يظهر أصلًا إلا لمّا فيه تقارير بانتظار الاعتماد (reports_pending_count > 0)
    frAuditHeadFilter = "pending_signatures";
    frView = "list";
    activeContent = "finalReports";
    renderSidebar(); await renderContent(); lucide.createIcons();
  });

  document.querySelectorAll("[data-report-mission]").forEach(btn => {
    btn.addEventListener("click", async () => {
      await frOpenReportForMission(btn.dataset.reportMission);
      activeContent = "finalReports";
      activeStatCard = null;
      renderSidebar(); await renderContent(); lucide.createIcons();
    });
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
    btn.addEventListener("click", async () => {
      // بطاقة "المهام النشطة" بالصفحة الرئيسية -- الضغط على مهمة يوديها مباشرة
      // لتفاصيلها بـ"المراسلات المشتركة" بدل رسالة "سيتوفر لاحقًا" المؤقتة
      const missionId = btn.dataset.mission;
      await loadMissionsForSelector();
      const task = missionsForSelector.find(m => String(m.id) === String(missionId));
      if (task) await stOpenTaskDetail(task);
      activeContent = "sentTasks";
      renderSidebar(); await renderContent(); lucide.createIcons();
    });
  });

  document.querySelectorAll("[data-postpone-mission]").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.stopPropagation();
      // "تأجيل الموعد" ببطاقة اجتماع مجدولة بالرئيسية -- يوديه مباشرة لنفس المهمة
      // بصفحة جدولة اجتماع، وين يقدر يلغي الموعد المؤكد الحالي (زر × بجانب شارة
      // الموعد المؤكد) ويكتب السبب بالمحادثة ويقترح موعد بديل، بنفس الأدوات
      // الموجودة أصلًا بتلك الصفحة
      openMeetingScheduleForMission(btn.dataset.postponeMission);
      activeContent = "meetingSchedule";
      activeStatCard = null;
      renderSidebar(); await renderContent(); lucide.createIcons();
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
