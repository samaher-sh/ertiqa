<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — ارتقاء</title>
<meta name="csrf-token-name" content="<?= csrf_token() ?>">
<meta name="csrf-token-value" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/auth-login.css') ?>">
</head>
<body>

<div class="login-page">
    <div class="login-card">

        <!-- ══════ يمين — لوحة الهوية ══════ -->
        <div class="login-brand">
            <div class="login-brand-logo">
                <img src="<?= base_url('assets/images/logo-kamc.jpg') ?>" alt="KAMC">
            </div>

            <div class="login-brand-text">
                <h1>ارتقاء</h1>
                <p class="login-brand-sub1">مدينة الملك عبدالله الطبية</p>
                <p class="login-brand-sub2">نظام الرقابة والمراجعة الداخلية</p>
            </div>

            <div class="login-brand-divider"></div>

            <p class="login-brand-tagline">منصة متكاملة لإدارة<br>مهام المراجعة الداخلية</p>
        </div>

        <!-- ══════ يسار — نموذج الدخول ══════ -->
        <div class="login-form-panel">
            <div class="login-form-header">
                <h2>تسجيل الدخول</h2>
                <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
            </div>

            <form id="loginForm" class="login-form" novalidate>

                <div class="field-group">
                    <label for="username">اسم المستخدم</label>
                    <div class="input-wrap">
                        <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" autocomplete="username">
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">كلمة المرور</label>
                    <div class="input-wrap">
                        <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" autocomplete="current-password">
                        <button type="button" id="togglePass" class="toggle-pass" tabindex="-1">
                            <svg id="eyeIcon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <p id="errorMsg" class="error-msg" hidden></p>

                <div class="forgot-row">
                    <a href="#" id="forgotLink">نسيت كلمة المرور؟</a>
                </div>

                <button type="submit" id="submitBtn" class="submit-btn">تسجيل الدخول</button>

                <p class="support-note">للدعم الفني تواصل مع إدارة تقنية المعلومات</p>
            </form>
        </div>
    </div>

    <p class="footer-note">© <span id="year"></span> مدينة الملك عبدالله الطبية — جميع الحقوق محفوظة</p>
</div>

<script src="<?= base_url('assets/js/auth-login.js') ?>"></script>
</body>
</html>
