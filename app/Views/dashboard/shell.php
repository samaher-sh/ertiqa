<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ارتقاء — لوحة المراجع الداخلي</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/riskmatrix.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/meetingsummary.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/taskdetail.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/senttasks.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/finalreports.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/meetingschedule.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/notifications.css') ?>">
</head>
<body>

<div class="app-shell">

  <!-- Mobile overlay -->
  <div class="mobile-overlay" id="mobileOverlay"></div>

  <!-- ═══ Sidebar ═══ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo-row" id="sidebarLogoRow"></div>
    <nav class="sidebar-nav" id="sidebarNav"></nav>
    <div class="sidebar-bottom" id="sidebarBottom"></div>
  </aside>

  <!-- ═══ Main column ═══ -->
  <div class="main-col">

    <!-- Header -->
    <header class="topbar">
      <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i data-lucide="menu"></i>
      </button>
      <div class="topbar-spacer"></div>

      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn">
          <div class="avatar-orange" id="avatarInitial">م</div>
          <div class="profile-text">
            <span class="profile-name" id="profileName">المستخدم</span>
            <span class="profile-role" id="profileRoleLabel">—</span>
          </div>
          <i data-lucide="chevron-down" class="chevron" id="profileChevron"></i>
        </button>

        <div class="profile-dropdown" id="profileDropdown">
          <div class="dd-head">
            <div class="avatar-orange avatar-lg" id="avatarInitialLg">م</div>
            <p class="dd-name" id="ddName">المستخدم</p>
            <p class="dd-role" id="ddRole">—</p>
            <p class="dd-email" id="ddEmail">—</p>
            <div class="dd-empid-pill">
              <i data-lucide="user"></i>
              <span id="ddEmpId">EMP-20431</span>
            </div>
          </div>

          <div class="dd-divider"></div>
          <div class="dd-section">
            <p class="dd-section-title">البيانات الشخصية</p>
            <div class="dd-row">
              <div class="dd-icon"><i data-lucide="user"></i></div>
              <div class="dd-row-text"><span class="dd-label">الاسم الكامل</span><span class="dd-value" id="ddFullName">أحمد محمد العتيبي</span></div>
            </div>
            <div class="dd-row">
              <div class="dd-icon"><i data-lucide="user"></i></div>
              <div class="dd-row-text"><span class="dd-label">الرقم الوظيفي</span><span class="dd-value" id="ddEmpId2">EMP-20431</span></div>
            </div>
            <div class="dd-row">
              <div class="dd-icon"><i data-lucide="phone"></i></div>
              <div class="dd-row-text"><span class="dd-label">رقم الجوال</span><span class="dd-value" id="ddPhone">0501234567</span></div>
            </div>
          </div>

          <div class="dd-divider"></div>
          <div class="dd-section">
            <p class="dd-section-title">الانتماء الوظيفي</p>
            <span class="dd-dept" id="ddDept">الإدارة التنفيذية</span>
            <div class="dd-subdept-row">
              <span class="dd-subdept-bar"></span>
              <span class="dd-subdept" id="ddSubDept">المراجعة الداخلية</span>
            </div>
          </div>

          <div class="dd-divider"></div>
          <div class="dd-footer">
            <button class="logout-btn" id="logoutBtn">
              <i data-lucide="log-out"></i>
              تسجيل خروج
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Content area -->
    <main class="content-area" id="contentArea"></main>
  </div>
</div>

<!-- Toast container -->
<div id="toastContainer" class="toast-container"></div>

<!-- قوالب HTML الثابتة للصفحات (تُستنسخ وتُملأ ديناميكيًا من ملفات JS بدل بناء HTML كامل
     داخل template strings JS مباشرة) -->
<template id="tpl-notifications">
  <div class="notif-card">
    <div class="notif-head">
      <i data-lucide="bell"></i>
      <div><h2>الإخطارات</h2><p>Notifications</p></div>
      <span class="notif-unread-badge" data-slot="badge" hidden></span>
    </div>
    <div class="notif-list" data-slot="list"></div>
  </div>
</template>

<template id="tpl-notif-item">
  <div class="notif-item" data-notif-id="">
    <div class="notif-dot-wrap"><span class="notif-dot"></span></div>
    <div class="notif-icon"><i data-lucide="bell"></i></div>
    <div class="notif-body">
      <div class="notif-title-row">
        <p class="notif-title" data-slot="title"></p>
        <span class="notif-type-tag" data-slot="type"></span>
      </div>
      <p class="notif-text" data-slot="body"></p>
      <span class="notif-time" dir="ltr" data-slot="time"></span>
    </div>
  </div>
</template>

<script>
  window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };
  // مُعرَّفة هنا مرة وحدة فقط - كل ملفات الصفحات (wizard.js, riskmatrix.js, ...) تستخدمها مباشرة
  // بدون إعادة تعريفها (const بنفس الاسم بأكثر من <script> بنفس الصفحة تطلع SyntaxError)
  const base = window.APP.baseUrl;
</script>
<script src="<?= base_url('assets/js/utils.js') ?>"></script>
<script src="<?= base_url('assets/js/dashboard-data.js') ?>"></script>
<script src="<?= base_url('assets/js/wizard.js') ?>"></script>
<script src="<?= base_url('assets/js/riskmatrix.js') ?>"></script>
<script src="<?= base_url('assets/js/meetingsummary.js') ?>"></script>
<script src="<?= base_url('assets/js/observations.js') ?>"></script>
<script src="<?= base_url('assets/js/taskdetail.js') ?>"></script>
<script src="<?= base_url('assets/js/senttasks.js') ?>"></script>
<script src="<?= base_url('assets/js/scheduledmeetings.js') ?>"></script>
<script src="<?= base_url('assets/js/finalreports.js') ?>"></script>
<script src="<?= base_url('assets/js/meetingschedule.js') ?>"></script>
<script src="<?= base_url('assets/js/notifications.js') ?>"></script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
</body>
</html>
