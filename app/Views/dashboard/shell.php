<?php
/* نسخة كل ملف أصل (asset) مبنية على وقت آخر تعديل فعلي على القرص (filemtime) --
   بدون هذا، المتصفح يخزّن نسخة قديمة من JS/CSS مؤقتًا (Cache) ويستمر بعرضها
   حتى بعد نشر تحديث فعلي بالخادم، فيبدو للمستخدم إن الميزة الجديدة "ما اشتغلت"
   رغم وصول الكود الصحيح فعليًا -- تغيّر وقت التعديل تلقائيًا يغيّر الرابط
   فيجبر المتصفح يجيب النسخة الجديدة بدون أي تحديث يدوي إضافي (hard refresh) */
if (!function_exists('av')) {
    function av(string $path): string
    {
        $full = FCPATH . $path;
        $v = is_file($full) ? filemtime($full) : time();
        return base_url($path) . '?v=' . $v;
    }
}
?>
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
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/riskmatrix.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingsummary.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/taskdetail.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/senttasks.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingschedule.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/documentrequests.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
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

<script>
  window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };
  // مُعرَّفة هنا مرة وحدة فقط - كل ملفات الصفحات (wizard.js, riskmatrix.js, ...) تستخدمها مباشرة
  // بدون إعادة تعريفها (const بنفس الاسم بأكثر من <script> بنفس الصفحة تطلع SyntaxError)
  const base = window.APP.baseUrl;
</script>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/dashboard-data.js') ?>"></script>
<script src="<?= av('assets/js/wizard.js') ?>"></script>
<script src="<?= av('assets/js/riskmatrix.js') ?>"></script>
<script src="<?= av('assets/js/meetingsummary.js') ?>"></script>
<script src="<?= av('assets/js/observations.js') ?>"></script>
<script src="<?= av('assets/js/senttasks.js') ?>"></script>
<script src="<?= av('assets/js/finalreports.js') ?>"></script>
<script src="<?= av('assets/js/meetingschedule.js') ?>"></script>
<script src="<?= av('assets/js/documentrequests.js') ?>"></script>
<script src="<?= av('assets/js/missionreview.js') ?>"></script>
<script src="<?= av('assets/js/dashboard.js') ?>"></script>
</body>
</html>
