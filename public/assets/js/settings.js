/* ============================================================
   الإعدادات (Settings) — منقول عن SettingsPage في AdminDashboard.tsx
   (صفحة "قيد التطوير" في المصدر الأصلي نفسه، بدون منطق إضافي)
   ============================================================ */

function renderSettingsPage() {
  return `
  <div class="flex flex-col gap-5">
    <div class="wiz-card">
      <div class="wiz-card-head"><i data-lucide="settings"></i><div><h2>الإعدادات</h2></div></div>
      <div class="settings-body">
        <div class="settings-icon"><i data-lucide="settings"></i></div>
        <p class="msg">صفحة الإعدادات قيد التطوير</p>
        <p class="hint">ستتضمن إعدادات الصلاحيات، الإشعارات، وتخصيص النظام</p>
      </div>
    </div>
  </div>`;
}
function bindSettingsEvents() {}
