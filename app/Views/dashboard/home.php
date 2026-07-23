<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الرئيسية — ارتقاء</title>
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
</head>
<body>

<div class="app-shell">

    <!-- ══════ الشريط الجانبي ══════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-logo">
                <img src="<?= base_url('assets/images/logo-kamc.jpg') ?>" alt="KAMC">
                <div class="sidebar-logo-text">
                    <p class="brand">ارتقاء</p>
                    <p class="sub">مدينة الملك عبدالله الطبية</p>
                </div>
            </div>
            <button id="sidebarToggle" class="icon-btn" title="طي القائمة">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'home' ? 'active' : '' ?>" data-key="<?= $item['key'] ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-text">
                    <span class="nav-label"><?= esc($item['label']) ?></span>
                    <span class="nav-desc"><?= esc($item['desc']) ?></span>
                </span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <a href="<?= base_url('auth/logout') ?>" class="logout-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <!-- ══════ الجانب الأيمن: هيدر + محتوى ══════ -->
    <div class="main-col">

        <header class="topbar">
            <div class="profile-wrap">
                <button id="profileBtn" class="profile-btn">
                    <span class="avatar"><?= esc(mb_substr($full_name ?: 'م', 0, 1)) ?></span>
                    <span class="profile-text">
                        <span class="p-name"><?= esc($full_name ?: 'المستخدم') ?></span>
                        <span class="p-role"><?= esc($role_name) ?></span>
                    </span>
                    <svg id="profileChevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div id="profileMenu" class="profile-menu" hidden>
                    <div class="profile-menu-head">
                        <span class="avatar avatar-lg"><?= esc(mb_substr($full_name ?: 'م', 0, 1)) ?></span>
                        <p class="p-name"><?= esc($full_name ?: 'المستخدم') ?></p>
                        <p class="p-role"><?= esc($role_name) ?></p>
                    </div>
                    <div class="profile-menu-divider"></div>
                    <div class="profile-menu-body">
                        <p class="section-label">البيانات الشخصية</p>
                        <div class="info-row">
                            <span class="info-label">رقم الهوية</span>
                            <span class="info-value"><?= esc($national_id) ?></span>
                        </div>
                    </div>
                    <div class="profile-menu-divider"></div>
                    <div class="profile-menu-body">
                        <p class="section-label">الانتماء الوظيفي</p>
                        <p class="dept-name"><?= esc($department_name ?: '—') ?></p>
                    </div>
                    <div class="profile-menu-divider"></div>
                    <div class="profile-menu-foot">
                        <a href="<?= base_url('auth/logout') ?>" class="logout-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>تسجيل خروج</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="content-inner">

                <?php if ($isAuditMember): ?>

                    <!-- ── 3 بطاقات إجراء سريع ── -->
                    <div class="action-grid">
                        <a href="#" class="action-card action-primary">
                            <p class="ac-label">بدء مهمة</p>
                            <p class="ac-sub">New Audit Task</p>
                            <div class="ac-value">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span>ابدأ</span>
                            </div>
                        </a>

                        <button type="button" class="action-card" id="btnActiveTasks">
                            <p class="ac-label">المهام النشطة</p>
                            <p class="ac-sub">Active Tasks</p>
                            <div class="ac-value">
                                <span id="activeCountVal">—</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                        </button>

                        <button type="button" class="action-card" id="btnMeetings">
                            <p class="ac-label">اجتماعات مجدولة</p>
                            <p class="ac-sub">Scheduled Meetings</p>
                            <div class="ac-value">
                                <span id="meetingsCountVal">—</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                        </button>
                    </div>

                    <!-- ── قائمة منسدلة: المهام النشطة ── -->
                    <div id="panelActiveTasks" class="dropdown-panel" hidden>
                        <div class="dp-head">قائمة المهام النشطة</div>
                        <div id="activeTasksList" class="dp-body">
                            <p class="dp-empty">جارِ التحميل...</p>
                        </div>
                    </div>

                    <!-- ── قائمة منسدلة: الاجتماعات ── -->
                    <div id="panelMeetings" class="dropdown-panel" hidden>
                        <div class="dp-head">قائمة الاجتماعات المجدولة</div>
                        <div id="meetingsList" class="dp-body">
                            <p class="dp-empty">جارِ التحميل...</p>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="coming-soon">
                        <p>الصفحة الرئيسية لهذا الدور (<?= esc($role_name) ?>) لسا قيد التحويل.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<script>
    window.APP = {
        baseUrl: "<?= rtrim(base_url(), '/') ?>",
        isAuditMember: <?= $isAuditMember ? 'true' : 'false' ?>
    };
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
</body>
</html>
