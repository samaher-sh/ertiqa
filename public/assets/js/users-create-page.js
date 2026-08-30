/* ============================================================
   users-create-page.js — تحسين تدريجي لصفحة "إضافة مستخدم"
   (UsersController::create). الصفحة تشتغل بالكامل بدون هذا الملف (النموذج
   HTML عادي، إرسال حقيقي). هذا الملف يضيف بس زر "بحث" اللي يتحقق من وجود
   الموظف بالدليل الموحّد (LDAP) عبر dashboard/ldap/search قبل الإرسال،
   ويعرض اسمه وبريده وإدارته كمعاينة.
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("searchLdapBtn");
  const input = document.getElementById("employeeNumberInput");
  const box = document.getElementById("ldapPreviewBox");
  const empty = document.getElementById("ldapPreviewEmpty");
  if (!btn || !input || !box || !empty) return;

  async function runSearch() {
    const q = input.value.trim();
    box.style.display = "none";
    empty.style.display = "none";
    if (!q) return;

    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "جارِ البحث...";

    try {
      const res = await fetch(base + "/dashboard/ldap/search?q=" + encodeURIComponent(q), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const data = await res.json();

      if (Array.isArray(data) && data.length > 0) {
        const emp = data[0];
        document.getElementById("ldapPreviewName").textContent = emp.full_name || "—";
        document.getElementById("ldapPreviewEmail").textContent = emp.email || "—";
        document.getElementById("ldapPreviewDept").textContent = emp.dept || "—";
        box.style.display = "";
      } else {
        empty.style.display = "";
      }
    } catch (e) {
      empty.style.display = "";
    } finally {
      btn.disabled = false;
      btn.textContent = originalText;
    }
  }

  btn.addEventListener("click", runSearch);
  input.addEventListener("keydown", e => {
    if (e.key === "Enter") { e.preventDefault(); runSearch(); }
  });
});
