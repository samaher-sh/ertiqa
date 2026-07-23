<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مصفوفة المخاطر — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/risk-matrix.css') ?>">
</head>
<body>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-logo">
                <img src="<?= base_url('assets/images/logo-kamc.jpg') ?>" alt="KAMC">
                <div class="sidebar-logo-text"><p class="brand">ارتقاء</p><p class="sub">مدينة الملك عبدالله الطبية</p></div>
            </div>
            <button id="sidebarToggle" class="icon-btn" title="طي القائمة">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'riskMatrix' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-text"><span class="nav-label"><?= esc($item['label']) ?></span><span class="nav-desc"><?= esc($item['desc']) ?></span></span>
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

    <div class="main-col">
        <header class="topbar">
            <div class="profile-wrap">
                <button id="profileBtn" class="profile-btn">
                    <span class="avatar"><?= esc(mb_substr($full_name ?: 'م', 0, 1)) ?></span>
                    <span class="profile-text"><span class="p-name"><?= esc($full_name ?: 'المستخدم') ?></span><span class="p-role"><?= esc($role_name) ?></span></span>
                    <svg id="profileChevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div id="profileMenu" class="profile-menu" hidden>
                    <div class="profile-menu-foot" style="padding:16px;">
                        <a href="<?= base_url('auth/logout') ?>" class="logout-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>تسجيل خروج</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="rm-wrap">

                <!-- ══════ محدّد المهمة المرتبطة ══════ -->
                <div id="taskSelectorCard" class="task-selector-card">
                    <div class="ts-band" id="tsBand">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <p id="tsTitle">المهمة / الإدارة المرتبطة</p>
                        <span id="tsRequiredBadge" class="ts-badge">مطلوب</span>
                    </div>
                    <div class="ts-body">
                        <label for="missionSelect">اختر المهمة / الإدارة المرتبطة <span class="req">*</span></label>
                        <select id="missionSelect" class="nt-select">
                            <option value="">— اختر —</option>
                            <?php foreach ($missions as $m): ?>
                                <option value="<?= $m['id'] ?>" data-code="<?= esc($m['mission_code']) ?>"><?= esc($m['mission_code']) ?> — <?= esc($m['target_department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($missions)): ?>
                            <p class="field-hint">ما عندك أي مهمة نشطة حاليًا — ابدئي مهمة جديدة أولًا من "بدء مهمة".</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══════ بطاقة الجدول ══════ -->
                <div id="rmCard" class="rm-card is-locked">
                    <div class="rm-head">
                        <div class="rm-head-left">
                            <div class="rm-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                            </div>
                            <div>
                                <h2>مصفوفة المخاطر</h2>
                                <p>Risk Matrix Form</p>
                            </div>
                            <?php if ($readOnly): ?>
                                <span class="ro-badge">🔒 عرض فقط</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$readOnly): ?>
                        <button type="button" id="addRiskBtn" class="add-risk-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            إضافة مخاطر
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="rm-table-wrap">
                        <table class="rm-table">
                            <thead>
                                <tr>
                                    <th class="rm-th-num">م</th>
                                    <th class="rm-th-risk">المخاطر</th>
                                    <th class="rm-th-rating">تقييم المخاطر</th>
                                    <th class="rm-th-controls">وصف الضوابط</th>
                                    <th class="rm-th-activity">نوع النشاط</th>
                                    <th class="rm-th-del"></th>
                                </tr>
                            </thead>
                            <tbody id="rmTableBody">
                                <tr id="rmEmptyRow">
                                    <td colspan="6" class="rm-empty">لا توجد مخاطر</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="rmFooter" class="rm-footer" hidden>
                        <div class="rm-stats">
                            <span>الإجمالي: <strong id="rmTotal">0</strong></span>
                            <span id="rmHighStat" class="rm-stat-high" hidden></span>
                            <span id="rmMedStat" class="rm-stat-med" hidden></span>
                            <span id="rmLowStat" class="rm-stat-low" hidden></span>
                        </div>
                        <?php if (!$readOnly): ?>
                        <button type="button" id="saveRiskBtn" class="save-risk-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            حفظ
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <p id="rmSavedToast" class="rm-toast" hidden>تم الحفظ بنجاح</p>
            </div>
        </main>
    </div>
</div>

<script>
    window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>", readOnly: <?= $readOnly ? 'true' : 'false' ?> };
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<script src="<?= base_url('assets/js/risk-matrix.js') ?>"></script>
</body>
</html>
