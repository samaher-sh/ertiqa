<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>التقارير النهائية — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/observations.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/final-reports.css') ?>?v=<?= time() ?>">
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
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'finalReports' ? 'active' : '' ?>">
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

                <!-- ══ قائمة التقارير ══ -->
                <div id="listView">
                    <div class="obs-card" style="margin-bottom:12px;">
                        <div class="obs-head">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            <div>
                                <div class="obs-head-title"><?= $isPresident ? 'التقارير التي تتطلب المراجعة' : 'التقارير النهائية' ?></div>
                            </div>
                            <span class="fr-count-badge"><?= count($reports) ?> تقرير</span>
                            <?php if (!$isAuditHead && !$isHrUser): ?>
                            <button type="button" id="createReportBtn" class="fr-create-btn" style="margin-right:auto;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                إنشاء تقرير
                            </button>
                            <?php else: ?>
                            <span class="obs-ro-badge" style="margin-right:auto;">🔒 عرض فقط</span>
                            <?php endif; ?>
                        </div>

                        <div class="obs-table-wrap">
                            <table class="obs-table">
                                <thead><tr>
                                    <th>ID المهمة</th><th>الإدارة</th><th>الإدارة المستهدفة</th><th>السنة</th><th>التاريخ</th><th>الحالة</th><th></th>
                                </tr></thead>
                                <tbody>
                                    <?php if (empty($reports)): ?>
                                        <tr><td colspan="7" class="obs-empty">لا توجد تقارير حاليًا</td></tr>
                                    <?php else: foreach ($reports as $r): ?>
                                        <tr>
                                            <td><?= esc($r['mission_code']) ?></td>
                                            <td><?= esc($r['audit_dept_name']) ?></td>
                                            <td><?= esc($r['target_dept_name']) ?></td>
                                            <td><?= esc($r['year']) ?></td>
                                            <td><?= esc(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= esc($r['status']) ?>">
                                                    <?= $r['status'] === 'draft' ? 'تحت الإعداد' : ($r['status'] === 'pending_signatures' ? 'تحت المراجعة' : 'معتمد') ?>
                                                </span>
                                            </td>
                                            <td><button type="button" class="obs-row-menu-btn view-report-btn" data-mission="<?= $r['mission_id'] ?>">عرض</button></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ══ إنشاء تقرير (Checklist) ══ -->
                <div id="createView" hidden>
                    <div class="obs-card" style="margin-bottom:12px;">
                        <div class="obs-head" style="background:#fff;border-bottom:1px solid #eee;">
                            <button type="button" id="backToListBtn" class="fr-back-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                التقارير النهائية
                            </button>
                        </div>
                    </div>

                    <div class="fr-task-card">
                        <div class="fr-task-head">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            <div><h3>المهمة / الإدارة المرتبطة</h3><p>اختر المهمة التي سيُبنى عليها التقرير</p></div>
                        </div>
                        <label>اختر المهمة / الإدارة المرتبطة <span class="req">*</span></label>
                        <select id="reportMissionSelect" class="nt-select">
                            <option value="">--- اختر المهمة ---</option>
                            <?php foreach ($missions as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['mission_code']) ?> — <?= esc($m['target_department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="obs-card" id="checklistCard" hidden style="margin-top:12px;">
                        <div class="obs-head">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg>
                            <span class="obs-head-title">مراحل الاعتماد</span>
                            <span class="fr-count-badge" id="checklistProgress" style="margin-right:auto;">0 / 6</span>
                        </div>
                        <div class="obs-table-wrap">
                            <table class="obs-table">
                                <thead><tr><th>تفاصيل المهمة</th><th style="width:100px;text-align:center;">الحالة الفعلية</th><th style="width:100px;text-align:center;">اعتماد</th></tr></thead>
                                <tbody id="checklistBody"></tbody>
                            </table>
                        </div>
                        <div class="fr-finalize-row">
                            <button type="button" id="finalizeBtn" class="fr-create-btn" disabled>اعتماد التقرير وإرساله</button>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>?v=<?= time() ?>"></script>
<script src="<?= base_url('assets/js/final-reports.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
