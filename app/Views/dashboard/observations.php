<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الملاحظات — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/observations.css') ?>?v=<?= time() ?>">
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
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'observations' ? 'active' : '' ?>">
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
                        <a href="<?= base_url('auth/logout') ?>" class="logout-btn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>تسجيل خروج</span></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="obs-wrap">

                <!-- محدّد المهمة -->
                <div id="taskSelectorCard" class="task-selector-card">
                    <div class="ts-band" id="tsBand">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg>
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

                <!-- ══ قائمة الملاحظات ══ -->
                <div id="listPanel" class="obs-card is-locked">
                    <div class="obs-head">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span class="obs-head-title">ملاحظات</span>
                        <?php if (!$obsReadOnly): ?>
                        <button type="button" id="newObsBtn" class="obs-new-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            رصد ملاحظة
                        </button>
                        <?php else: ?>
                        <span class="obs-ro-badge">🔒 <?= $isHrUser ? 'ملاحظات إدارتك — عرض فقط' : 'عرض فقط' ?></span>
                        <?php endif; ?>
                        <a href="#" id="pdfExportBtn" class="pdf-export-btn disabled" target="_blank" style="margin-right:auto;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            تصدير PDF
                        </a>
                    </div>

                    <div id="obsTableWrap" class="obs-table-wrap">
                        <table class="obs-table">
                            <thead>
                                <tr>
                                    <th class="col-title">موضوع الملاحظة</th>
                                    <th class="col-dept">الإدارة المعنية</th>
                                    <th class="col-date">التاريخ</th>
                                    <th class="col-risk">التصنيف</th>
                                    <?php if (!($role_name === 'رئيس إدارة المراجعة الداخلية')): ?><th class="col-action"></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="obsTableBody">
                                <tr><td colspan="5" class="obs-empty">لا توجد ملاحظات مسجلة لهذه المهمة</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ══ نموذج رصد/تعديل ══ -->
                <div id="formPanel" class="obs-card" hidden>
                    <div class="obs-head">
                        <button type="button" id="formBackBtn" class="obs-back-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="9 18 15 12 9 6" transform="rotate(180 12 12)"/></svg>
                        </button>
                        <span class="obs-head-title" id="formTitle">رصد ملاحظة جديدة</span>
                        <button type="button" id="formSaveBtn" class="obs-save-btn" style="margin-right:auto;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            حفظ واعتماد
                        </button>
                    </div>

                    <div class="obs-form-body">
                        <div class="obs-row-4">
                            <div class="field-group">
                                <label>تاريخ المراجعة</label>
                                <input type="date" id="fDate" class="nt-select">
                            </div>
                            <div class="field-group">
                                <label>الإدارة محل المراجعة <span class="req">*</span></label>
                                <select id="fDept" class="nt-select">
                                    <option value="">--- اختر ---</option>
                                    <?php foreach ($mainDepts as $d): ?><option value="<?= $d['id'] ?>"><?= esc($d['name_ar']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>عنوان الملاحظة</label>
                                <input type="text" id="fTitle" class="nt-select" placeholder="عنوان مختصر...">
                            </div>
                            <div class="field-group">
                                <label>مستوى الخطر</label>
                                <div class="risk-btns" id="fRiskBtns">
                                    <button type="button" data-risk="عالي" class="risk-btn">عالي</button>
                                    <button type="button" data-risk="متوسط" class="risk-btn">متوسط</button>
                                    <button type="button" data-risk="منخفض" class="risk-btn">منخفض</button>
                                </div>
                            </div>
                        </div>

                        <div class="obs-divider"></div>

                        <div class="obs-row-2">
                            <div class="field-group"><label>الملاحظة <span class="req">*</span></label><textarea id="fObservation" rows="4" placeholder="أدخل نص الملاحظة المكتشفة بوضوح..."></textarea></div>
                            <div class="field-group"><label>المعيار أو النظام</label><textarea id="fStandard" rows="4" placeholder="المادة النظامية أو السياسة التي تمت مخالفتها..."></textarea></div>
                            <div class="field-group"><label>السبب</label><textarea id="fReason" rows="3" placeholder="الأسباب الجذرية لحدوث هذه الملاحظة..."></textarea></div>
                            <div class="field-group"><label>الأثر</label><textarea id="fImpact" rows="3" placeholder="الأثر المالي أو التشغيلي المترتب..."></textarea></div>
                            <div class="field-group obs-full-row"><label>التوصيات</label><textarea id="fRecommendations" rows="2" placeholder="الإجراءات التصحيحية المقترحة..."></textarea></div>
                        </div>

                        <div class="obs-divider"></div>

                        <div class="obs-attach-row">
                            <div class="field-group">
                                <label class="add-doc-btn" style="cursor:pointer;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                    إرفاق
                                    <input type="file" id="fAttachInput" hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                </label>
                                <div id="fAttachList" class="attach-list"></div>
                            </div>
                            <div class="field-group">
                                <label>تضاف للتقرير؟</label>
                                <div class="radio-row">
                                    <label><input type="radio" name="addToReport" value="1"> نعم</label>
                                    <label><input type="radio" name="addToReport" value="0"> لا</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>", obsReadOnly: <?= $obsReadOnly ? 'true' : 'false' ?> };
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>?v=<?= time() ?>"></script>
<script src="<?= base_url('assets/js/observations.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
