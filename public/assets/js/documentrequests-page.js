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
     4) تراكم اختيار الملفات (bindAccumulatingFileInput بـ utils.js) -- بدون
        هذا الملف كل فتحة لنافذة اختيار الملفات تستبدل الاختيار السابق كليًا
        (سلوك المتصفح الافتراضي)، فيضيع أي ملف اختير قبل فتحة لاحقة إذا كان
        المستخدم يرفق الملفات على دفعات قبل "إرسال المستندات"
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

    const closeAndClear = () => {
      const input = details.querySelector('input[name="doc_name"]');
      if (input) input.value = "";
      details.open = false;
    };

    const cancelBtn = document.getElementById("drAddCancelBtn");
    if (cancelBtn) cancelBtn.addEventListener("click", closeAndClear);

    // الضغط بأي مكان خارج نموذج "إضافة مستند" أثناء فتحه يلغيه ويفرّغ الحقل،
    // بدل ما يضل مفتوح ومعبّى بنص لم يُحفظ
    document.addEventListener("click", (e) => {
      if (details.open && !details.contains(e.target)) closeAndClear();
    });
  }

  bindDrDocNameEdit();
  bindDrRequestDelete();

  document.querySelectorAll('input[type="file"]').forEach((input) => {
    bindAccumulatingFileInput(input);
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

/* ---------- تعديل اسم مستند مطلوب مباشرة من الجدول (ضغط + كتابة) ---------- */
function bindDrDocNameEdit() {
  document.querySelectorAll(".wiz-doc-name-input:not([readonly])[data-request-id]").forEach((input) => {
    const original = input.value;

    const save = async () => {
      const value = input.value.trim();
      if (value === "" || value === input.dataset.savedValue || value === original) {
        input.value = input.dataset.savedValue || original;
        return;
      }
      try {
        const data = await apiPost(base + "/dashboard/document-requests/api/rename/" + input.dataset.requestId, { doc_name: value });
        if (data.success) {
          input.dataset.savedValue = data.doc_name;
        } else {
          alert(data.message || "تعذّر تعديل اسم المستند");
          input.value = input.dataset.savedValue || original;
        }
      } catch (err) {
        alert(err.message || "تعذّر تعديل اسم المستند");
        input.value = input.dataset.savedValue || original;
      }
    };

    input.addEventListener("blur", save);
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        input.blur();
      } else if (e.key === "Escape") {
        input.value = input.dataset.savedValue || original;
        input.blur();
      }
    });
  });
}

/* ---------- حذف طلب مستند بالكامل من القائمة (AJAX) ---------- */
function bindDrRequestDelete() {
  document.querySelectorAll(".wiz-doc-table").forEach((table) => {
    table.addEventListener("click", async (e) => {
      const btn = e.target.closest(".wiz-doc-row-del-btn");
      if (!btn) return;
      if (!confirm("هل أنت متأكد من حذف هذا المستند من القائمة؟")) return;
      try {
        const data = await apiPost(base + "/dashboard/document-requests/api/delete/" + btn.dataset.requestId, {});
        if (data.success) {
          const tbody = table.querySelector("tbody");
          btn.closest("tr").remove();

          const remaining = tbody.querySelectorAll("tr").length;
          const countEl = document.querySelector(".wiz-doc-footer-count strong");
          if (countEl) countEl.textContent = String(remaining);
          if (remaining === 0) {
            tbody.innerHTML = '<tr><td colspan="5"><div class="wiz-doc-empty"><i data-lucide="file-text"></i><br>لا توجد مستندات مطلوبة لهذه المهمة</div></td></tr>';
            if (window.lucide) lucide.createIcons();
          }
        } else {
          alert(data.message || "تعذّر حذف المستند");
        }
      } catch (err) {
        alert(err.message || "تعذّر حذف المستند");
      }
    });
  });
}

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
