<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ملخص الاجتماع — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/risk-matrix.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/meeting-summary.css') ?>">
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
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'meetingSummary' ? 'active' : '' ?>">
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

                <div id="taskSelectorCard" class="task-selector-card">
                    <div class="ts-band" id="tsBand">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <p>المهمة / الإدارة المرتبطة</p>
                        <span id="tsRequiredBadge" class="ts-badge">مطلوب</span>
                    </div>
                    <div class="ts-body">
                        <label for="missionSelect">اختر المهمة / الإدارة المرتبطة <span class="req">*</span></label>
                        <select id="missionSelect" class="nt-select">
                            <option value="">— اختر —</option>
                            <?php foreach ($missions as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['mission_code']) ?> — <?= esc($m['target_department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="msContent" class="ms-locked-wrap">

                    <!-- ══ 1. بيانات الاجتماع ══ -->
                    <div class="nt-card">
                        <div class="nt-card-head">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            <div><h2>ملخص الاجتماع</h2><p>Meeting Summary</p></div>
                            <?php if ($allReadOnly): ?><span class="ro-badge" style="margin-right:auto;">🔒 عرض فقط</span><?php endif; ?>
                        </div>
                        <?php if ($isHrUser): ?>
                            <p class="auto-note">⚡ الإدارة محل المراجعة والعنوان والهدف تُملأ تلقائياً</p>
                        <?php endif; ?>
                        <div class="ms-info-grid">
                            <div class="field-group"><label>تاريخ الاجتماع</label><input type="date" id="mDate" class="nt-select"></div>
                            <div class="field-group"><label>الوقت</label><input type="time" id="mTime" class="nt-select"></div>
                            <div class="field-group"><label>مكان الاجتماع</label><input type="text" id="mLocation" class="nt-select" placeholder="أدخل مكان الاجتماع"></div>
                            <div class="field-group"><label>الإدارة محل المراجعة</label><input type="text" id="mDeptDisplay" class="nt-select" disabled></div>
                            <div class="field-group ms-full-row"><label>عنوان المهمة</label><input type="text" id="mTitle" class="nt-select" placeholder="عنوان مهمة المراجعة"></div>
                            <div class="field-group ms-full-row"><label>الهدف من الاجتماع</label><textarea id="mObjective" rows="2" class="nt-select"></textarea></div>
                        </div>
                    </div>

                    <!-- ══ 2. الحضور ══ -->
                    <div class="nt-card">
                        <div class="nt-card-head" style="justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <span style="color:#fff;font-weight:700;font-size:14px;">قائمة الحضور</span>
                            </div>
                            <button type="button" id="addAttendeeBtn" class="add-doc-btn" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.3);">+ إضافة حضور</button>
                        </div>
                        <div class="doc-table-wrap">
                            <table class="doc-table">
                                <thead><tr><th class="doc-th-num">الرقم</th><th class="doc-th-name">الاسم</th><th>الإدارة</th><th>الوظيفة</th><th class="doc-th-del"></th></tr></thead>
                                <tbody id="attendeesBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ══ 3. ملخص المناقشة ══ -->
                    <div class="nt-card">
                        <div class="nt-card-head" style="justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span style="color:#fff;font-weight:700;font-size:14px;">ملخص ما تم مناقشته خلال الاجتماع</span>
                            </div>
                            <button type="button" id="addPointBtn" class="add-doc-btn" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.3);">+ إضافة نقطة</button>
                        </div>
                        <div class="doc-table-wrap">
                            <table class="doc-table ms-points-table">
                                <thead><tr><th class="col-point">النقطة</th><th>الرأي</th><th class="col-reason">السبب / التوضيح</th><th class="doc-th-del"></th></tr></thead>
                                <tbody id="pointsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ══ 3.5 المرفقات ══ -->
                    <div class="nt-card">
                        <div class="nt-card-head" style="justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                <span style="color:#fff;font-weight:700;font-size:14px;">المرفقات</span>
                            </div>
                            <label class="add-doc-btn" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.3);cursor:pointer;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                إرفاق ملف
                                <input type="file" id="attachmentInput" hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            </label>
                        </div>
                        <div id="attachmentsList" class="attachments-list">
                            <p class="dp-empty" style="padding:20px;text-align:center;color:#9ca3af;">لا يوجد مرفقات</p>
                        </div>
                    </div>

                    <!-- ══ 4. الاعتماد ══ -->
                    <div id="approvalCard" class="nt-card">
                        <div class="nt-card-head">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <div><h2 style="font-size:14px;">الاعتماد</h2></div>
                        </div>
                        <div class="doc-table-wrap">
                            <table class="doc-table">
                                <thead><tr><th>البيان</th><th>الاسم</th><th>الوظيفة</th><th style="width:150px;text-align:center;">التوقيع</th><th style="width:140px;">التاريخ</th></tr></thead>
                                <tbody id="approvalsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="wizard-nav" style="justify-content:space-between;">
                    <a href="#" id="pdfExportBtn" class="pdf-export-btn disabled" target="_blank">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        تصدير PDF
                    </a>
                    <button type="button" id="saveMeetingBtn" class="save-risk-btn" disabled>حفظ</button>
                </div>
                <p id="msSavedToast" class="rm-toast" hidden>تم حفظ التغييرات بنجاح</p>
            </div>
        </main>
    </div>
</div>

<script>
    window.APP = {
        baseUrl: "<?= rtrim(base_url(), '/') ?>",
        isHrUser: <?= $isHrUser ? 'true' : 'false' ?>,
        allReadOnly: <?= $allReadOnly ? 'true' : 'false' ?>
    };
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<script src="<?= base_url('assets/js/meeting-summary.js') ?>"></script>
</body>
</html>
