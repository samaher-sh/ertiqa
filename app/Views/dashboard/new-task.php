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
            <div class="wizard-wrap">

                <!-- ══════ شريط المراحل (Timeline) ══════ -->
                <div class="steps-bar">
                    <?php $stepLabels = ['طلب المراجعة الداخلية', 'اتفاقية مستوى الخدمة', 'قائمة المستندات']; ?>
                    <?php foreach ($stepLabels as $i => $label): $n = $i + 1; ?>
                        <div class="step-item">
                            <button type="button" class="step-btn" data-step="<?= $n ?>">
                                <span class="step-circle" data-step-circle="<?= $n ?>"><?= $n ?></span>
                                <span class="step-label"><?= esc($label) ?></span>
                            </button>
                            <?php if ($n < 3): ?><div class="step-line" data-step-line="<?= $n ?>"></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ══════ الخطوة 1 ══════ -->
                <div class="wizard-step" data-step-panel="1">
                    <div class="nt-grid">
                        <div class="nt-card">
                            <div class="nt-card-head">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <div><h2>طلب المراجعة الداخلية</h2><p>Internal Audit Request</p></div>
                            </div>

                            <div class="nt-form">
                                <div class="nt-section">
                                    <p class="nt-section-title">بيانات الإدارة</p>
                                    <div class="field-group">
                                        <label for="mainDept">الإدارة <span class="req">*</span></label>
                                        <select id="mainDept" class="nt-select">
                                            <option value="">— اختر —</option>
                                            <?php foreach ($mainDepts as $d): ?>
                                                <option value="<?= $d['id'] ?>"><?= esc($d['name_ar']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                    </div>
                                    <div class="field-group">
                                        <label for="targetDept">الإدارة المستهدفة <span class="req">*</span></label>
                                        <select id="targetDept" class="nt-select" disabled>
                                            <option value="">— اختر الإدارة أولاً —</option>
                                        </select>
                                        <p class="field-hint" id="targetHint">يُرجى اختيار الإدارة أولاً لتفعيل هذا الحقل</p>
                                        <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                    </div>
                                    <div class="field-group">
                                        <label for="year">السنة</label>
                                        <select id="year" class="nt-select">
                                            <?php foreach ($years as $y): ?>
                                                <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="nt-section">
                                    <p class="nt-section-title">المراد مناقشته في الاجتماع <span class="req">*</span></p>
                                    <textarea id="procedure" rows="4" placeholder="أدخل المراد مناقشته في الاجتماع هنا..."></textarea>
                                    <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                </div>

                                <div class="nt-section">
                                    <p class="nt-section-title">بيانات المراجع</p>
                                    <div class="field-group">
                                        <label for="reviewerName">اسم المراجع الرئيسي <span class="req">*</span></label>
                                        <div class="input-wrap">
                                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                            <input type="text" id="reviewerName" placeholder="الاسم كاملاً">
                                        </div>
                                        <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                    </div>
                                    <div class="field-group">
                                        <label for="reviewerEmail">البريد الإلكتروني <span class="req">*</span></label>
                                        <div class="input-wrap">
                                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
                                            <input type="email" id="reviewerEmail" placeholder="example@kamc.med.sa">
                                        </div>
                                        <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                    </div>
                                    <div class="field-group">
                                        <label for="reviewerPhone">رقم الجوال <span class="req">*</span></label>
                                        <div class="input-wrap">
                                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            <input type="tel" id="reviewerPhone" placeholder="05xxxxxxxx">
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
                                            <input type="text" id="directorName" placeholder="الاسم كاملاً">
                                        </div>
                                        <p class="field-error" hidden>هذا الحقل مطلوب</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="nt-card">
                            <div class="nt-card-head">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <div><h2>نموذج الخطاب الرسمي</h2><p>يتم ملؤه تلقائياً من النموذج</p></div>
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
                </div>

                <!-- ══════ الخطوة 2 — اتفاقية مستوى الخدمة ══════ -->
                <div class="wizard-step" data-step-panel="2" hidden>
                    <div class="nt-card">
                        <div class="nt-card-head">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            <div><h2>اتفاقية مستوى الخدمة</h2><p>Service Level Agreement</p></div>
                        </div>
                        <div class="nt-form">
                            <div class="two-col">
                                <div class="field-group">
                                    <label>الإدارة الخاضعة للمراجعة</label>
                                    <input type="text" id="subjectDeptDisplay" disabled class="nt-select">
                                </div>
                                <div class="field-group">
                                    <label for="slaDate">تاريخ الاتفاقية</label>
                                    <input type="date" id="slaDate" class="nt-select">
                                </div>
                            </div>
                            <div class="field-group">
                                <label>وصف الخدمة</label>
                                <textarea id="slaDesc" rows="3">تهدف هذه الخدمة إلى عقد اجتماعات المراجعة الداخلية مع الإدارات الخاضعة للمراجعة وتنفيذ العمليات المتعلقة بأعمال المراجعة حسب خطة المراجعة.</textarea>
                            </div>

                            <div class="nt-section">
                                <p class="nt-section-title">قنوات الاتصال المعتمدة</p>
                                <div id="channelsWrap"></div>
                            </div>
                        </div>
                    </div>

                    <div class="nt-card" style="margin-top:16px;">
                        <div class="nt-card-head">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg>
                            <div><h2>بنود الاتفاقية</h2><p>تُرسل للإدارة المستهدفة للموافقة عليها</p></div>
                        </div>
                        <div class="sla-table-wrap">
                            <table class="sla-table">
                                <thead>
                                    <tr>
                                        <th class="sla-th-subject">الموضوع</th>
                                        <th>موافق</th>
                                        <th>غير موافق</th>
                                        <th>ملاحظات إن وجد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $ri = 0; foreach ($slaSections as $si => $sec): ?>
                                        <tr>
                                            <td colspan="4" class="sla-section-row">
                                                <span class="sla-section-num"><?= $si + 1 ?></span><?= esc($sec['title']) ?>
                                            </td>
                                        </tr>
                                        <?php foreach ($sec['rows'] as $row): $ri++; ?>
                                        <tr>
                                            <td class="sla-subject-cell"><span class="sla-dot"></span><?= esc($row) ?></td>
                                            <td class="sla-check-cell"><div class="sla-box sla-box-agree"></div></td>
                                            <td class="sla-check-cell"><div class="sla-box sla-box-disagree"></div></td>
                                            <td class="sla-note-cell"><div class="sla-note-line"></div></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="sla-footnote">تُملأ خانتا "موافق / غير موافق" من قِبل ممثل الإدارة المستهدفة عند الاستلام</div>
                    </div>
                </div>

                <!-- ══════ الخطوة 3 — قائمة المستندات ══════ -->
                <div class="wizard-step" data-step-panel="3" hidden>
                    <div class="nt-card">
                        <div class="nt-card-head">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            <div><h2>قائمة المستندات المطلوبة</h2><p>Required Documents Checklist</p></div>
                        </div>

                        <div class="doc-toolbar">
                            <span id="docCountLabel" class="doc-count">0 مستند مضاف</span>
                            <button type="button" id="addDocBtn" class="add-doc-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                إضافة مستند
                            </button>
                        </div>

                        <div class="doc-table-wrap">
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <th class="doc-th-num">الرقم</th>
                                        <th class="doc-th-name">المستند</th>
                                        <th class="doc-th-locked">🔒 توجد / لا توجد</th>
                                        <th class="doc-th-locked">🔒 رفع الملف</th>
                                        <th class="doc-th-locked">🔒 الملاحظات</th>
                                        <th class="doc-th-del"></th>
                                    </tr>
                                </thead>
                                <tbody id="docTableBody">
                                    <tr id="docEmptyRow">
                                        <td colspan="6" class="doc-empty">لا توجد مستندات — اضغط «إضافة مستند» لإضافة صف جديد</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p id="docFormError" class="form-error" hidden style="margin:16px;"></p>
                    </div>
                </div>

                <!-- ══════ أزرار التنقل ══════ -->
                <div class="wizard-nav">
                    <button type="button" id="btnPrev" class="wizard-btn wizard-btn-ghost" hidden>السابق</button>
                    <button type="button" id="btnNext" class="wizard-btn wizard-btn-primary">التالي</button>
                    <button type="button" id="btnSend" class="wizard-btn wizard-btn-primary" hidden>إرسال الطلب</button>
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
