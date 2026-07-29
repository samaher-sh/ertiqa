<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول - ارتقاء</title>
  <meta name="csrf-token-name" content="<?= csrf_token() ?>">
  <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
</head>
<body>
  <div class="page-wrapper">
    <div class="login-card">

      <!-- RIGHT PANEL (Branding) -->
      <div class="panel-right">
        <div class="logo-container">
          <!-- Fallback icon if kamc.png is not found in same dir -->
          <img src="<?= base_url('assets/images/kamc.png') ?>" alt="KAMC Logo" class="kamc-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
          <svg class="fallback-logo" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="brand-text">
          <h1>ارتقاء</h1>
          <p class="subtitle">مدينة الملك عبدالله الطبية</p>
          <p class="sub-subtitle">نظام الرقابة والمراجعة الداخلية</p>
        </div>
        <div class="divider"></div>
        <p class="description">منصة متكاملة لإدارة<br />مهام المراجعة الداخلية</p>
      </div>

      <!-- LEFT PANEL (Form) -->
      <div class="panel-left">
        <div class="form-header">
          <h2>تسجيل الدخول</h2>
          <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
        </div>

        <form id="loginForm" class="login-form">
          <div class="input-group">
            <label>اسم المستخدم</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="username" placeholder="أدخل اسم المستخدم" autocomplete="username" />
            </div>
          </div>

          <div class="input-group">
            <label>كلمة المرور</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="password" placeholder="أدخل كلمة المرور" autocomplete="current-password" />
              <button type="button" id="togglePassword" class="toggle-password" tabindex="-1">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="eyeOffIcon" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <p id="errorMessage" class="error-message"></p>

          <div class="forgot-password">
            <a href="#">نسيت كلمة المرور؟</a>
          </div>

          <button type="submit" id="submitBtn" class="submit-btn">تسجيل الدخول</button>

          <p class="support-text">للدعم الفني تواصل مع إدارة تقنية المعلومات</p>
        </form>
      </div>
    </div>

    <p class="footer-text">© <span id="currentYear"></span> مدينة الملك عبدالله الطبية — جميع الحقوق محفوظة</p>
  </div>

  <script src="<?= base_url('assets/js/shared.js') ?>"></script>
</body>
</html>
