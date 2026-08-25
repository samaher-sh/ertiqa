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
<?= $this->renderSection('styles') ?>
</head>
<body>
<div class="app-shell">
  <div class="mobile-overlay" id="mobileOverlay"></div>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo-row" id="sidebarLogoRow"><?php if (isset($navItems)): ?><div class="logo-info"><div class="logo-box"><img src="<?= base_url('assets/images/kamc.png') ?>" alt="KAMC"></div><div class="logo-title"><p class="t1">ارتقاء</p><p class="t2">مدينة الملك عبدالله الطبية</p></div></div><button class="sidebar-toggle-btn" id="toggleNavBtn" title="طي القائمة"><i data-lucide="panel-left-close"></i></button><button class="sidebar-toggle-collapsed" id="toggleNavBtnCollapsed" title="فتح القائمة"><img src="<?= base_url('assets/images/kamc.png') ?>" alt="KAMC"></button><?php endif; ?></div>
    <nav class="sidebar-nav" id="sidebarNav"><?php if (isset($navItems)): ?><?php foreach ($navItems as $item): ?><?php $isMigrated = in_array($item['key'], $migratedKeys ?? [], true); ?><a class="nav-item<?= (($activeNavKey ?? '') === $item['key']) ? ' active' : '' ?>" href="<?= esc($isMigrated ? $item['url'] : base_url('dashboard')) ?>"><div class="nav-icon-box"><i data-lucide="<?= esc($item['icon']) ?>"></i></div><div class="nav-text"><span class="nav-label"><?= esc($item['label']) ?></span></div><span class="nav-tooltip"><?= esc($item['label']) ?></span></a><?php endforeach; ?><?php endif; ?></nav>
    <div class="sidebar-bottom" id="sidebarBottom"><?php if (isset($navItems)): ?><a class="sidebar-logout-btn" id="sidebarLogoutBtn" href="<?= base_url('auth/logout') ?>" title="تسجيل الخروج"><i data-lucide="log-out"></i><span>تسجيل الخروج</span></a><?php endif; ?></div>
  </aside>
  <script>
    try { if (localStorage.getItem('sidebarCollapsed') === '1') document.getElementById('sidebar').classList.add('collapsed'); } catch (e) {}
  </script>
  <div class="main-col">
    <header class="topbar">
      <button class="mobile-menu-btn" id="mobileMenuBtn"><i data-lucide="menu"></i></button>
      <div class="topbar-spacer"></div>
      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn">
          <div class="avatar-orange" id="avatarInitial"><?= isset($currentUser) ? esc(mb_substr($currentUser['full_name'] ?? 'م', 0, 1)) : 'م' ?></div>
          <div class="profile-text">
            <span class="profile-name" id="profileName"><?= isset($currentUser) ? esc($currentUser['full_name']) : 'المستخدم' ?></span>
            <span class="profile-role" id="profileRoleLabel"><?= isset($currentUser) ? esc($currentUser['role_name']) : '—' ?></span>
          </div>
          <i data-lucide="chevron-down" class="chevron" id="profileChevron"></i>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <div class="dd-head">
            <div class="avatar-orange avatar-lg" id="avatarInitialLg"><?= isset($currentUser) ? esc(mb_substr($currentUser['full_name'] ?? 'م', 0, 1)) : 'م' ?></div>
            <p class="dd-name" id="ddName"><?= isset($currentUser) ? esc($currentUser['full_name']) : 'المستخدم' ?></p>
            <p class="dd-role" id="ddRole"><?= isset($currentUser) ? esc($currentUser['role_name']) : '—' ?></p>
            <p class="dd-email" id="ddEmail"><?= isset($currentUser) ? esc($currentUser['email']) : '—' ?></p>
            <div class="dd-empid-pill"><i data-lucide="user"></i><span id="ddEmpId"><?= isset($currentUser) ? esc($currentUser['national_id']) : 'EMP-20431' ?></span></div>
          </div>
          <div class="dd-divider"></div>
          <div class="dd-section">
            <p class="dd-section-title">البيانات الشخصية</p>
            <div class="dd-row"><div class="dd-icon"><i data-lucide="user"></i></div><div class="dd-row-text"><span class="dd-label">الاسم الكامل</span><span class="dd-value" id="ddFullName"><?= isset($currentUser) ? esc($currentUser['full_name']) : 'أحمد محمد العتيبي' ?></span></div></div>
            <div class="dd-row"><div class="dd-icon"><i data-lucide="user"></i></div><div class="dd-row-text"><span class="dd-label">الرقم الوظيفي</span><span class="dd-value" id="ddEmpId2"><?= isset($currentUser) ? esc($currentUser['national_id']) : 'EMP-20431' ?></span></div></div>
            <div class="dd-row"><div class="dd-icon"><i data-lucide="phone"></i></div><div class="dd-row-text"><span class="dd-label">رقم الجوال</span><span class="dd-value" id="ddPhone"><?= isset($currentUser) ? esc($currentUser['phone']) : '0501234567' ?></span></div></div>
          </div>
          <div class="dd-divider"></div>
          <div class="dd-section">
            <p class="dd-section-title">الانتماء الوظيفي</p>
            <span class="dd-dept" id="ddDept"><?= isset($currentUser) ? esc($currentUser['department_parent_name'] ?? $currentUser['department_name'] ?? '') : 'الإدارة التنفيذية' ?></span>
            <div class="dd-subdept-row"><span class="dd-subdept-bar"></span><span class="dd-subdept" id="ddSubDept"><?= isset($currentUser) ? (!empty($currentUser['department_parent_name']) ? esc($currentUser['department_name']) : '') : 'المراجعة الداخلية' ?></span></div>
          </div>
          <div class="dd-divider"></div>
          <div class="dd-footer"><a class="logout-btn" id="logoutBtn" href="<?= base_url('auth/logout') ?>"><i data-lucide="log-out"></i>تسجيل خروج</a></div>
        </div>
      </div>
    </header>
    <main class="content-area" id="contentArea"><?= $this->renderSection('content') ?></main>
  </div>
</div>
<div id="toastContainer" class="toast-container"></div>
<script>
  window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };
  const base = window.APP.baseUrl;
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
