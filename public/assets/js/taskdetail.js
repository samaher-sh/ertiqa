/* ============================================================
   عرض تفاصيل المهمة (Task Detail View)
   منقول عن TaskDetailView في AdminDashboard.tsx
   ويوفّر أيضاً الحالة العامة selectedTaskDetail / taskPageMap
   التي تستخدمها صفحات أخرى (الملاحظات، مصفوفة المخاطر...)
   لمعرفة "آخر صفحة" مرتبطة بكل مهمة (onCompleteForm)
   ============================================================ */

let selectedTaskDetail = null;
let taskPageMap = {};

function updateTaskPage(taskId, pageKey) {
  taskPageMap[taskId] = pageKey;
}

/* يُستدعى من أي مكان (البطاقات، سجل الملاحظات...) لفتح تفاصيل مهمة */
function openTaskDetail(task) {
  selectedTaskDetail = task;
  activeContent = "taskDetail";
  renderSidebar();
  renderContent();
  lucide.createIcons();
}

/* سجل النشاط الثابت (نفس بيانات المصدر React) */
const TASK_DETAIL_ACTIVITIES = [
  { user: "أحمد الشهري", role: "رئيس إدارة الموارد البشرية", button: "بدء مهمة مراجعة جديدة", phase: "بدء مهمة", time: "10:24 ص" },
  { user: "فاطمة العتيبي", role: "عضو إدارة المراجعة الداخلية", button: "اضافة مستند", phase: "بدء مهمة", time: "11:00 ص" },
  { user: "سعد الدوسري", role: "مدير العمليات", button: "اضافة مخاطر", phase: "مصفوفة المخاطر", time: "01:00 م" },
  { user: "سعد الدوسري", role: "مدير العمليات", button: "إرسال", phase: "مصفوفة المخاطر", time: "01:15 م" },
  { user: "نورة السالم", role: "منسق الموارد البشرية", button: "اضافة حضور", phase: "ملخص الاجتماع", time: "09:30 ص" },
  { user: "نورة السالم", role: "منسق الموارد البشرية", button: "اضافة نقطة", phase: "ملخص الاجتماع", time: "09:45 ص" },
  { user: "نورة السالم", role: "منسق الموارد البشرية", button: "إرسال", phase: "ملخص الاجتماع", time: "10:05 ص" },
  { user: "خالد المطيري", role: "عضو إدارة المراجعة الداخلية", button: "رصد ملاحظة", phase: "الملاحظات", time: "02:45 م" },
  { user: "خالد المطيري", role: "عضو إدارة المراجعة الداخلية", button: "إرسال", phase: "الملاحظات", time: "03:10 م" },
  { user: "محمد الغامدي", role: "رئيس إدارة المراجعة الداخلية", button: "اعتماد التقرير", phase: "التقرير النهائي", time: "04:30 م" },
];

function escHtmlTD(str) {
  return String(str == null ? "" : str)
    .replace(/&/g, "&amp;").replace(/"/g, "&quot;")
    .replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function renderActivityTimeline(activities) {
  return `
  <div class="td-timeline">
    <div class="td-timeline-rail"></div>
    ${activities.map(act => `
      <div class="td-activity-row">
        <div class="td-activity-avatar">${escHtmlTD(act.user).charAt(0)}</div>
        <div class="td-activity-body">
          <div class="td-activity-user">${escHtmlTD(act.user)} <span class="role">(${escHtmlTD(act.role)})</span></div>
          <div class="td-activity-meta">
            <span class="td-activity-btn-tag">${escHtmlTD(act.button)}</span>
            <span class="td-activity-sep">—</span>
            <span class="td-activity-phase">${escHtmlTD(act.phase)}</span>
            <span class="td-activity-sep">—</span>
            <span class="td-activity-time">التاريخ${escHtmlTD(act.time)}</span>
          </div>
        </div>
      </div>
    `).join("")}
  </div>`;
}

function renderTaskDetailPage() {
  const task = selectedTaskDetail;
  if (!task) return "";

  return `
  <div class="flex flex-col gap-5" dir="rtl">
    <div class="td-header">
      <div class="td-header-left">
        <button class="td-back-btn" id="tdBackBtn"><i data-lucide="chevron-right"></i></button>
        <h2 class="td-title">تفاصيل المهمة: ${escHtmlTD(task.dept)}</h2>
        <span class="td-year">(${task.year})</span>
      </div>
      <button class="obs-btn-pdf" id="tdExportBtn"><i data-lucide="file-text"></i> تصدير PDF</button>
    </div>

    <div class="td-card">
      <div class="td-card-head">
        <div class="td-card-head-left">
          <div class="td-card-icon"><i data-lucide="file-text"></i></div>
          <div>
            <h3>البيانات المدخلة من الإدارة الخاضعة للمراجعة</h3>
            <p>الحقول المعبأة مسبقاً</p>
          </div>
        </div>
      </div>
      <div class="td-fields-grid">
        <div class="td-field-box"><span class="lbl">اسم المنسق</span><span class="val">محمد عبدالله</span></div>
        <div class="td-field-box"><span class="lbl">ملاحظات الإدارة</span><span class="val">تم إرفاق جميع المستندات المطلوبة للمراجعة الأولية.</span></div>
        <div class="td-field-box"><span class="lbl">تاريخ تسليم المستندات</span><span class="val">15 يوليو 2026</span></div>
        <div class="td-field-box"><span class="lbl">المستندات المرفقة</span><span class="val link">سياسات_الموارد_البشرية.pdf</span></div>
      </div>
      <div class="td-complete-footer">
        <button class="td-complete-btn" id="tdCompleteBtn"><i data-lucide="pencil"></i> إكمال النموذج</button>
      </div>
    </div>

    <div class="td-card">
      <div class="td-card-head">
        <div class="td-card-head-left">
          <div class="td-card-icon"><i data-lucide="clock"></i></div>
          <div>
            <h3>سجل النشاط والتدقيق</h3>
            <p>سجل زمني لجميع الإجراءات المتخذة على المهمة</p>
          </div>
        </div>
      </div>
      ${renderActivityTimeline(TASK_DETAIL_ACTIVITIES)}
    </div>
  </div>`;
}

function bindTaskDetailEvents() {
  const task = selectedTaskDetail;
  if (!task) return;

  document.getElementById("tdBackBtn").addEventListener("click", () => {
    activeContent = "home";
    selectedTaskDetail = null;
    renderSidebar(); renderContent(); lucide.createIcons();
  });

  document.getElementById("tdExportBtn").addEventListener("click", () => {
    exportTaskToPDF(task.id, "تفاصيل المهمة الرقابية: " + task.subDept, task.dept, [
      { label: "حالة المهمة", value: task.status },
      { label: "المرحلة الحالية", value: "المرحلة " + task.currentPhase },
      { label: "آخر إجراء", value: task.lastAction },
      { label: "السنة", value: task.year },
    ]);
  });

  document.getElementById("tdCompleteBtn").addEventListener("click", () => {
    const dest = taskPageMap[task.id];
    activeContent = dest || "home";
    renderSidebar(); renderContent(); lucide.createIcons();
  });
}
