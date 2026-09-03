/* ============================================================
   documentrequests-page.js — تحسين تدريجي بسيط لصفحة قائمة المستندات
   الحقيقية (DocumentRequestController::index/add). الصفحة تشتغل بالكامل
   بدون هذا الملف: <details>/<summary> أصلي لإظهار/إخفاء نموذج "إضافة
   مستند"، ونموذج POST/Redirect/GET عادي للحفظ ورفع الملفات (تُرسَل مع
   "إرسال المستندات")، وحذف ملف واحد كان سيحتاج تنقّل صفحة كامل بدون هذا
   الملف (زر الحذف يبقى بلا وظيفة بدون JS -- هذا الاستثناء الوحيد المعقول
   لأن DocumentController::delete يرجّع JSON فقط).
   هذا الملف يضيف:
     1) تركيز تلقائي على حقل اسم المستند عند فتح نموذج "إضافة مستند"
     2) تحديث نص زر "رفع ملف" بعدد/اسم الملفات المختارة (input[type=file]
        الأصلي مخفي وراء label منسَّق، فبدون هذا التحديث ما فيه أي مؤشر
        مرئي إن الاختيار نجح فعلًا -- تُرفع الملفات صح وقت "إرسال المستندات"
        حتى بدون هذا التحديث، هو مجرّد مؤشر بصري)
     3) حذف فوري لمرفق واحد من القائمة (AJAX) بدل ما يضطر يستبدل كل الملفات
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const details = document.getElementById("drAddDetails");
  if (details) {
    details.addEventListener("toggle", () => {
      if (details.open) {
        const input = details.querySelector('input[name="doc_name"]');
        if (input) input.focus();
      }
    });
  }

  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener("change", () => {
      const label = document.querySelector(`label[for="${input.id}"]`);
      const span = label ? label.querySelector("span") : null;
      if (!label || !span) return;
      const count = input.files ? input.files.length : 0;
      if (count === 1) {
        span.textContent = input.files[0].name;
        label.classList.add("has-file");
      } else if (count > 1) {
        span.textContent = count + " ملفات مختارة";
        label.classList.add("has-file");
      } else {
        span.textContent = "رفع ملف";
        label.classList.remove("has-file");
      }
    });
  });

  bindDrFileDelete();
});

/* ---------- حذف مرفق واحد من قائمة مستند معيّن ---------- */
function bindDrFileDelete() {
  document.querySelectorAll(".wiz-doc-table").forEach(table => {
    table.addEventListener("click", async e => {
      const btn = e.target.closest(".dr-file-del-btn");
      if (!btn) return;
      if (!confirm("هل أنت متأكد من حذف هذا الملف؟")) return;
      try {
        const data = await apiPost(base + "/dashboard/documents/delete/" + btn.dataset.docId, {});
        if (data.success) {
          btn.closest(".dr-file-row").remove();
        } else {
          alert(data.message || "تعذّر حذف الملف");
        }
      } catch (err) {
        alert(err.message || "تعذّر حذف الملف");
      }
    });
  });
}
