<?php
/**
 * قالب بريد إخطار بسيط -- يعكس نفس صيغة إخطارات المنصة (عنوان + وصف + رابط
 * اختياري للصفحة المرتبطة). يُستخدَم من NotificationService::sendEmail()
 * فقط، ما يُعرض بأي طريقة ثانية.
 */
?>
<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;background:#f0f7fa;padding:24px;">
  <div style="max-width:480px;margin:0 auto;">
    <div style="background:#3185b3;color:#fff;padding:16px 22px;border-radius:14px 14px 0 0;font-weight:700;font-size:15px;">
      ارتقاء — نظام الرقابة والمراجعة الداخلية
    </div>
    <div style="background:#fff;padding:22px;border:1px solid #d8e6eb;border-top:none;border-radius:0 0 14px 14px;">
      <h2 style="margin:0 0 10px;font-size:16px;color:#152c33;"><?= esc($title) ?></h2>
      <p style="margin:0;font-size:13px;color:#4a6b74;line-height:1.8;"><?= esc($body) ?></p>
      <?php if (!empty($link)): ?>
        <p style="margin:20px 0 0;">
          <a href="<?= esc($link, 'attr') ?>" style="display:inline-block;background:#3185b3;color:#fff;text-decoration:none;font-size:12px;font-weight:700;padding:10px 20px;border-radius:10px;">فتح بالمنصة</a>
        </p>
      <?php endif; ?>
      <p style="margin:20px 0 0;font-size:11px;color:#9ca3af;">هذا إخطار تلقائي من منصة ارتقاء، الرجاء عدم الرد على هذا البريد.</p>
    </div>
  </div>
</div>
