/* ============================================================
   المراسلات المشتركة (Sent Tasks & Communications) — متصل بالـ API الحقيقي

   ملاحظة مهمة: القائمة الوهمية الأصلية كانت تعرض بيانات "لقطة" (snapshot)
   ثابتة لكل حقل أدخلته الإدارة الخاضعة بكل مرحلة (deptPhaseData)، ومافي
   endpoint حقيقي يجمّع هذا التفصيل. لذلك القائمة هنا مبنية على المهام
   النشطة الحقيقية (نفس مصدر بيانات "المهمة المرتبطة" بباقي الصفحات)،
   والتفاصيل تعرض السجل الزمني الحقيقي لمراحل المهمة عبر
   GET /dashboard/sent-tasks/api/timeline?mission_id=X
   بدل سجل الأنشطة الوهمي القديم.
   ============================================================ */

let sentTasksSelected = null;
let stTimelineEvents = [];
let stTimelineLoading = false;
let stNextStage = null;

/* forRole يحدد مين عليه الدور الحالي فعليًا بهذي المرحلة: "target" = الإدارة الخاضعة
   للمراجعة (تعبئة اتفاقية مستوى الخدمة + المستندات)، "audit" = عضو المراجعة (كل
   الباقي). الطرف الثاني يشوف نفس الصفحة لكن Read-only دائمًا (زر "عرض" بدل
   "إكمال الحقول") — الصلاحية الفعلية مفروضة من الباك-إند بكل صفحة على حدة */
const ST_STAGE_TO_PAGE = {
  2: { key: "missionReview",  label: "استكمال الاتفاقية والمستندات", forRole: "target" },
  3: { key: "riskMatrix",     label: "مصفوفة المخاطر",              forRole: "audit" },
  4: { key: "meetingSummary", label: "ملخص الاجتماع",                forRole: "audit" },
  5: { key: "observations",   label: "الملاحظات",                   forRole: "audit" },
  7: { key: "finalReports",   label: "التقرير النهائي",              forRole: "audit" },
};

function renderSentTasksPage() {
  return sentTasksSelected ? renderSentTaskDetail() : renderSentTasksList();
}
function bindSentTasksEvents() {
  if (sentTasksSelected) { bindSentTaskDetailEvents(); return; }
  bindSentTasksListEvents();
}

/* ---------- List ---------- */
function renderSentTasksList() {
  return `
  <div class="flex flex-col gap-5">
    <div class="st-card">
      <div class="st-card-head">
        <div class="st-card-head-left">
          <i data-lucide="send"></i>
          <div><h2>المراسلات المشتركة</h2><p>Sent Tasks &amp; Communications</p></div>
        </div>
        <span class="st-count-badge">${missionsForSelector.length} مهمة</span>
      </div>
      <table class="st-table">
        <thead><tr>
          <th>رقم المهمة</th><th>العنوان</th><th>الإدارة الخاضعة</th><th>المراجع المسؤول</th><th>تاريخ الإنشاء</th><th>المرحلة الحالية</th><th class="center">إجراء</th>
        </tr></thead>
        <tbody>
          ${missionsForSelector.map(task => `
            <tr>
              <td class="st-id-cell">${escapeHtml(task.mission_code)}</td>
              <td class="st-title-cell">${escapeHtml(task.title)}</td>
              <td class="st-dept-cell">${escapeHtml(task.target_department_name)}</td>
              <td class="st-sentby-cell">${escapeHtml(task.reviewer_name || "—")}</td>
              <td class="st-sentat-cell" dir="ltr">${escapeHtml(task.created_at || "—")}</td>
              <td><span class="st-status-pill">المرحلة ${task.current_stage}</span></td>
              <td style="text-align:center;"><button class="st-view-btn" data-view-sent="${task.id}" title="عرض التفاصيل والسجل">عرض</button></td>
            </tr>
          `).join("")}
          ${missionsForSelector.length === 0 ? `<tr><td colspan="7" class="st-empty-row">لا توجد مهام مرسلة حالياً</td></tr>` : ""}
        </tbody>
      </table>
    </div>
  </div>`;
}
function bindSentTasksListEvents() {
  document.querySelectorAll("[data-view-sent]").forEach(btn => {
    btn.addEventListener("click", async () => {
      const task = missionsForSelector.find(m => String(m.id) === String(btn.dataset.viewSent));
      if (!task) return;
      sentTasksSelected = task;
      await stLoadTimeline(task.id);
      renderSidebar(); renderContent(); lucide.createIcons();
    });
  });
}

/* ---------- Timeline (real) ---------- */
async function stLoadTimeline(missionId) {
  stTimelineLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/sent-tasks/api/timeline?mission_id=" + missionId);
    stTimelineEvents = data.events || [];
    stNextStage = data.next_stage || null;
  } catch (e) {
    stTimelineEvents = [];
    stNextStage = null;
  }
  stTimelineLoading = false;
}

function renderSentTaskTimeline() {
  if (stTimelineLoading) return `<div class="td-timeline"><p style="padding:16px;color:var(--muted);">جارِ التحميل...</p></div>`;
  if (stTimelineEvents.length === 0) return `<div class="td-timeline"><p style="padding:16px;color:var(--muted);">لا يوجد سجل بعد لهذه المهمة</p></div>`;
  return `
  <div class="td-timeline">
    <div class="td-timeline-rail"></div>
    ${stTimelineEvents.map(ev => `
      <div class="td-activity-row">
        <div class="td-activity-avatar">${escapeHtml(ev.user_name).charAt(0)}</div>
        <div class="td-activity-body">
          <div class="td-activity-user">${escapeHtml(ev.user_name)}</div>
          <div class="td-activity-meta">
            <span class="td-activity-btn-tag">${escapeHtml(ev.stage_name)}</span>
            <span class="td-activity-sep">—</span>
            <span class="td-activity-time">${escapeHtml(ev.entered_at)}</span>
          </div>
          ${ev.detail ? `<p class="td-activity-detail">${escapeHtml(ev.detail)}</p>` : ""}
        </div>
      </div>
    `).join("")}
  </div>`;
}

/* ---------- Detail ---------- */
function stIsMyTurn(nextStage) {
  if (!nextStage) return false;
  return (nextStage.forRole === "target" && isHrDept) || (nextStage.forRole === "audit" && !isHrDept);
}

function renderSentTaskDetail() {
  const t = sentTasksSelected;
  const nextStage = ST_STAGE_TO_PAGE[stNextStage];
  const myTurn = stIsMyTurn(nextStage);
  return `
  <div class="flex flex-col gap-5" dir="rtl">
    <div class="st-detail-header">
      <button class="st-detail-back" id="stBackBtn"><i data-lucide="chevron-right"></i></button>
      <div class="st-detail-icon"><i data-lucide="history"></i></div>
      <div>
        <h2 class="st-detail-title">${escapeHtml(t.title)}</h2>
        <p class="st-detail-sub">${escapeHtml(t.mission_code)} · ${escapeHtml(t.target_department_name)}</p>
      </div>
      <span class="st-detail-status">المرحلة ${stNextStage || t.current_stage}</span>
    </div>

    <div class="st-detail-grid">
      <div class="st-detail-left">
        <div class="st-phase-card">
          <div class="st-phase-head">
            <div class="st-phase-icon"><i data-lucide="file-text"></i></div>
            <span>بيانات المهمة</span>
          </div>
          <div class="st-phase-fields">
            <div class="st-phase-field"><span class="lbl">الإدارة الخاضعة</span><span class="val">${escapeHtml(t.target_department_name)}</span></div>
            <div class="st-phase-field"><span class="lbl">المراجع المسؤول</span><span class="val">${escapeHtml(t.reviewer_name || "—")}</span></div>
            <div class="st-phase-field"><span class="lbl">مدير الإدارة</span><span class="val">${escapeHtml(t.director_name || "—")}</span></div>
            <div class="st-phase-field"><span class="lbl">تاريخ الإنشاء</span><span class="val" dir="ltr">${escapeHtml(t.created_at || "—")}</span></div>
          </div>
        </div>

        <div class="st-complete-card">
          ${nextStage ? (myTurn ? `
          <div class="st-complete-hint"><i data-lucide="pencil"></i><span>أكمل الحقول المتبقية الخاصة بك في نموذج "${nextStage.label}"</span></div>
          <button class="st-complete-btn" id="stCompleteBtn" data-next-page="${nextStage.key}"><i data-lucide="pencil"></i> إكمال الحقول</button>
          ` : `
          <div class="st-complete-hint"><i data-lucide="eye"></i><span>بانتظار الطرف الآخر لإكمال "${nextStage.label}" — تقدر تطّلع على آخر تحديث</span></div>
          <button class="st-complete-btn" id="stCompleteBtn" data-next-page="${nextStage.key}"><i data-lucide="eye"></i> عرض</button>
          `) : `
          <div class="st-complete-hint"><i data-lucide="info"></i><span>لا يوجد نموذج مرتبط مباشرة بالمرحلة الحالية لهذه المهمة</span></div>
          `}
        </div>
      </div>

      <div class="st-log-card">
        <div class="st-log-head"><i data-lucide="clock"></i><div><p class="t">سجل النشاط والتدقيق</p><p class="s">سجل زمني لجميع الإجراءات</p></div></div>
        ${renderSentTaskTimeline()}
      </div>
    </div>
  </div>`;
}
function bindSentTaskDetailEvents() {
  document.getElementById("stBackBtn").addEventListener("click", () => { sentTasksSelected = null; renderSidebar(); renderContent(); lucide.createIcons(); });
  const completeBtn = document.getElementById("stCompleteBtn");
  if (completeBtn) completeBtn.addEventListener("click", () => {
    const missionId = String(sentTasksSelected.id);
    const nextPage = completeBtn.dataset.nextPage;

    // نحمّل المهمة تلقائيًا بالصفحة الهدف بدل ما يُطلب من المستخدم يعيد اختيارها يدويًا
    if (nextPage === "missionReview") mrSelectedTaskId = missionId;
    else if (nextPage === "riskMatrix") rmSelectedTaskId = missionId;
    else if (nextPage === "meetingSummary") msumSelectedTaskId = missionId;
    else if (nextPage === "observations") obsSelectedTaskId = missionId;
    else if (nextPage === "finalReports") { frView = "create"; frCreateSelectedTask = missionId; }

    activeContent = nextPage;
    sentTasksSelected = null;
    renderSidebar(); renderContent(); lucide.createIcons();
  });
}
