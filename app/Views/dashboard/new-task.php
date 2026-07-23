<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>بدء مهمة — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/new-task.css') ?>">
</head>
<body>

<div class="app-shell">
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
            <a href="<?= $item['url'] ?>" class="nav-item <?= $item['key'] === 'newTask' ? 'active' : '' ?>" data-key="<?= $item['key'] ?>">
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
            <div class="nt-grid">

                <!-- ══════ يمين: النموذج ══════ -->
                <div class="nt-card">
                    <div class="nt-card-head">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <div>
                            <h2>طلب المراجعة الداخلية</h2>
                            <p>Internal Audit Request</p>
                        </div>
                    </div>

                    <form id="newTaskForm" class="nt-form" novalidate>

                        <div class="nt-section">
                            <p class="nt-section-title">بيانات الإدارة</p>

                            <div class="field-group">
                                <label for="mainDept">الإدارة <span class="req">*</span></label>
                                <select id="mainDept" name="main_dept_id" class="nt-select">
                                    <option value="">— اختر —</option>
                                    <?php foreach ($mainDepts as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= esc($d['name_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>

                            <div class="field-group">
                                <label for="targetDept">الإدارة المستهدفة <span class="req">*</span></label>
                                <select id="targetDept" name="target_dept_id" class="nt-select" disabled>
                                    <option value="">— اختر الإدارة أولاً —</option>
                                </select>
                                <p class="field-hint" id="targetHint">يُرجى اختيار الإدارة أولاً لتفعيل هذا الحقل</p>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>

                            <div class="field-group">
                                <label for="year">السنة</label>
                                <select id="year" name="year" class="nt-select">
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="nt-section">
                            <p class="nt-section-title">المراد مناقشته في الاجتماع <span class="req">*</span></p>
                            <textarea id="procedure" name="procedure" rows="4" placeholder="أدخل المراد مناقشته في الاجتماع هنا..."></textarea>
                            <p class="field-error" hidden>هذا الحقل مطلوب</p>
                        </div>

                        <div class="nt-section">
                            <p class="nt-section-title">بيانات المراجع</p>

                            <div class="field-group">
                                <label for="reviewerName">اسم المراجع الرئيسي <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" id="reviewerName" name="reviewer_name" placeholder="الاسم كاملاً">
                                </div>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>

                            <div class="field-group">
                                <label for="reviewerEmail">البريد الإلكتروني <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z" opacity="0"/><path d="M22 6l-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
                                    <input type="email" id="reviewerEmail" name="reviewer_email" placeholder="example@kamc.med.sa">
                                </div>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>

                            <div class="field-group">
                                <label for="reviewerPhone">رقم الجوال <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <input type="tel" id="reviewerPhone" name="reviewer_phone" placeholder="05xxxxxxxx">
                                </div>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>
                        </div>

                        <div class="nt-section">
                            <p class="nt-section-title">بيانات المدير</p>
                            <div class="field-group">
                                <label for="directorName">اسم المدير <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" id="directorName" name="director_name" placeholder="الاسم كاملاً">
                                </div>
                                <p class="field-error" hidden>هذا الحقل مطلوب</p>
                            </div>
                        </div>

                        <p id="formError" class="form-error" hidden></p>

                        <button type="submit" id="submitBtn" class="submit-btn">إرسال الطلب</button>
                    </form>
                </div>

                <!-- ══════ يسار: الخطاب الرسمي (يتحدث مباشرة) ══════ -->
                <div class="nt-card">
                    <div class="nt-card-head">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <div>
                            <h2>نموذج الخطاب الرسمي</h2>
                            <p>يتم ملؤه تلقائياً من النموذج</p>
                        </div>
                    </div>

                    <div class="letter-wrap">
                        <div class="letter-paper">
                            <div class="letter-head">
                                <div class="letter-head-right">
                                    <img src="<?= base_url('assets/images/logo-kamc.jpg') ?>" alt="KAMC">
                                    <p>إدارة المراجعة الداخلية</p>
                                </div>
                                <div class="letter-head-left">
                                    <p>التاريخ: <span id="letterDate"></span></p>
                                    <p>الرقم: م.م / <span id="letterYear"><?= $currentYear ?></span> / <span id="letterRef"></span></p>
                                </div>
                            </div>
                            <div class="letter-divider"></div>

                            <p class="letter-addr">سعادة المدير التنفيذي لـ<mark id="mDept"></mark> المحترم</p>
                            <p class="letter-greet">السلام عليكم ورحمة الله وبركاته،</p>

                            <p class="letter-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة <mark id="mTarget">الإدارة المستهدفة</mark> للقيام بعملية المراجعة الداخلية الشاملة وفق الخطة السنوية المعتمدة لعام <mark id="mYear"><?= $currentYear ?></mark>.</p>

                            <p class="letter-p">عليه نأمل التكرم بتوجيه من يلزم للعمل على التنسيق خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخ استلام هذا الإشعار.</p>

                            <div id="procedureBox" class="letter-procedure-box" hidden>
                                <div class="lp-head">المراد مناقشته في الاجتماع</div>
                                <p id="procedureText" class="lp-body"></p>
                            </div>

                            <p class="letter-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة حتى يتسنى لنا البدء بعملية المراجعة.</p>
                            <p class="letter-p">إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة.</p>
                            <p class="letter-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
                            <p class="letter-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark id="mReviewer">...............</mark></p>
                            <p class="letter-p" style="margin-bottom:4px;">والذي يمكن التواصل معه عبر القنوات التالية:</p>

                            <div class="letter-contacts">
                                <div><span>البريد الإلكتروني:</span> <strong id="mEmail">........................</strong></div>
                                <div><span>رقم الجوال:</span> <strong id="mPhone">........................</strong></div>
                            </div>

                            <p class="letter-p" style="margin-top:6px;">مدير إدارة المراجعة الداخلية</p>
                            <p id="mDirector" class="letter-director" hidden></p>
                            <p class="letter-p">وتقبلوا وافر التحية والتقدير،،.</p>

                            <div class="letter-bar"></div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    window.APP = { baseUrl: "<?= rtrim(base_url(), '/') ?>" };
    window.SUB_DEPTS_BY_PARENT = <?= json_encode($subsByParent, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<script src="<?= base_url('assets/js/new-task.js') ?>"></script>
</body>
</html>
