<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>المراسلات المشتركة — ارتقاء</title>
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/observations.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/sent-tasks.css') ?>?v=<?= time() ?>">
</head>
<body>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-logo">
                <img src="<?= base_url('assets/images/logo-kamc.jpg') ?>" alt="KAMC">
                <div class="sidebar-logo-text"><p class="brand">ارتقاء</p><p class="sub">مدينة الملك عبدالله الطبية</p></div>
            </div>
            <button id="sidebarToggle" class="icon-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg></button>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'sentTasks' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-text"><span class="nav-label"><?= esc($item['label']) ?></span><span class="nav-desc"><?= esc($item['desc']) ?></span></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <a href="<?= base_url('auth/logout') ?>" class="logout-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>تسجيل الخروج</span></a>
        </div>
    </aside>

    <div class="main-col">
        <header class="topbar">
            <div class="profile-wrap">
                <button id="profileBtn" class="profile-btn">
                    <span class="avatar"><?= esc(mb_substr($full_name ?: 'م', 0, 1)) ?></span>
                    <span class="profile-text"><span class="p-name"><?= esc($full_name ?: 'المستخدم') ?></span><span class="p-role"><?= esc($role_name) ?></span></span>
                    <svg id="profileChevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div id="profileMenu" class="profile-menu" hidden>
                    <div class="profile-menu-foot" style="padding:16px;"><a href="<?= base_url('auth/logout') ?>" class="logout-btn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>تسجيل خروج</span></a></div>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="obs-wrap">
                <div class="obs-card">
                    <div class="obs-head">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <span class="obs-head-title">المراسلات المشتركة</span>
                    </div>
                    <div class="ts-body" style="padding:20px 24px;">
                        <label for="taskSelect">اختر المهمة لعرض سجل نشاطها الزمني</label>
                        <select id="taskSelect" class="nt-select">
                            <option value="">— اختر —</option>
                            <?php foreach ($missions as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['mission_code']) ?> — <?= esc($m['target_department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="obs-card" id="timelineCard" style="margin-top:12px;">
                    <div class="obs-head" style="background:#f8fbfd;border-bottom:1px solid #d8e6eb;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span style="color:#152c33;font-weight:800;font-size:13px;">سجل النشاط والتدقيق</span>
                    </div>
                    <div id="timelineBody" class="timeline-body">
                        <p class="obs-empty">اختاري مهمة لعرض سجل نشاطها</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>?v=<?= time() ?>"></script>
<script src="<?= base_url('assets/js/sent-tasks.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
