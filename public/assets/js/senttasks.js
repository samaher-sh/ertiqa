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
let stLogCollapsed = false;

/* جولة "عرض" (Tour): تُفتح مكان بطاقة "بانتظار الطرف الآخر" -- تصفّح بالتالي/
   السابق بين المراحل اللي فعليًا وصلتها المهمة (تدريجيًا)، كل مرحلة تحت اسمها
   نفس نموذجها الحقيقي بالضبط (نفس شكل الخطاب/الاتفاقية/المستندات كما تظهر
   بصفحة "مراجعة المهمة" الفعلية) -- قراءة فقط دائمًا بإجبار mrCanEdit=false */
let stShowTour = false;
let stTourIndex = 0;
let stTourMrLoaded = false;
let stTourRmLoaded = false;
let stTourMsumLoaded = false;
let stTourObsLoaded = false;
let stTourLoading = false;

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

/* مراحل جولة "عرض" -- كل مرحلة تظهر بالجولة فقط لو فيها حدث حقيقي واحد على
   الأقل بسجل audit_logs (action يطابق أحد actions المذكورة)، بنفس ترتيبها هنا
   دائمًا (تدريجيًا، مو كل السبع مرة وحدة) -- ما عدا الخطاب الرسمي (always:
   true): هذا موجود فعليًا فور إنشاء المهمة نفسها، مو نتيجة فعل منفصل يُسجَّل
   بالسجل الزمني، وبعض المهام القديمة أصلًا ما فيها صف mission_created بجدول
   audit_logs (أُضيف تسجيله بمرحلة لاحقة من المشروع) -- فربطه بحدث كان يخفيه
   نهائيًا رغم وجود المهمة فعليًا. renderer يحدد مصدر المحتوى -- كل مرحلة
   تستخدم نفس دالة العرض الحقيقية من صفحتها الأصلية بالضبط (مو ملخّص مختصر)،
   بإجبار القراءة فقط: "mr1/mr2/mr3" = renderMrPage1/2/3 (missionreview.js)،
   "rm" = renderRmReadOnlyTable (riskmatrix.js)، "msum" = renderMeetingSummaryCards
   (meetingsummary.js)، "obs" = renderObsReadOnlyTable (observations.js)؛
   "log" = لا يوجد قسم "التقرير النهائي" بحد ذاته فنكتفي بتفصيل حدث اعتماده
   من السجل الزمني */
const ST_TOUR_STAGES = [
  { label: "الخطاب الرسمي",           always: true,                                             renderer: "mr1" },
  { label: "اتفاقية مستوى الخدمة",     actions: ["sla_submitted"],                              renderer: "mr2" },
  { label: "قائمة المستندات المرسلة",  actions: ["documents_submitted"],                        renderer: "mr3" },
  { label: "مصفوفة المخاطر",          actions: ["risk_matrix_saved"],                           renderer: "rm" },
  { label: "الاجتماع",                actions: ["meeting_confirmed", "meeting_summary_saved"],  renderer: "msum" },
  { label: "الملاحظات",               actions: ["observation_added"],                           renderer: "obs" },
  { label: "التقرير النهائي",         actions: ["report_finalized"],                            renderer: "log" },
];

function renderSentTasksPage() {
  return sentTasksSelected ? renderSentTaskDetail() : renderSentTasksList();
}
function bindSentTasksEvents() {
  if (sentTasksSelected) { bindSentTaskDetailEvents(); return; }
  bindSentTasksListEvents();
}

/* ---------- List ---------- */
/* المرحلة الحقيقية (task.next_stage، من MissionModel::computeRealNextStage) بدل
   missions.current_stage الخام اللي يبقى 1 دائمًا -- هذا كان يخلي القائمة تعرض
   "مرحلة 1" حتى بعد ما ترد الإدارة الخاضعة للمراجعة فعليًا وتُرسل ردها */
function stStageBadgeText(task) {
  const stage = task.next_stage;
  const info = ST_STAGE_TO_PAGE[stage];
  if (!info) return stage === 7 ? "التقرير النهائي" : "المرحلة " + stage;
  return stIsMyTurn(info) ? "بانتظارك — " + info.label : "بانتظار الطرف الآخر — " + info.label;
}

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
              <td><span class="st-status-pill">${escapeHtml(stStageBadgeText(task))}</span></td>
              <td style="text-align:center;"><button class="st-view-btn" data-view-sent="${task.id}" title="عرض التفاصيل والسجل">عرض</button></td>
            </tr>
          `).join("")}
          ${missionsForSelector.length === 0 ? `<tr><td colspan="7" class="st-empty-row">لا توجد مهام مرسلة حالياً</td></tr>` : ""}
        </tbody>
      </table>
    </div>
  </div>`;
}
/* يفتح تفاصيل مهمة معيّنة بـ"المراسلات المشتركة" -- مستخدمة من قائمة هذي الصفحة
   نفسها، وكمان من صفحات ثانية (الإخطارات، بانر الصفحة الرئيسية) لما تحتاج توديك
   مباشرة لمهمة محددة بدل قائمة عامة */
async function stOpenTaskDetail(task) {
  sentTasksSelected = task;
  stShowTour = false;
  stTourIndex = 0;
  stTourMrLoaded = false;
  stTourRmLoaded = false;
  stTourMsumLoaded = false;
  stTourObsLoaded = false;
  await stLoadTimeline(task.id);
}

function bindSentTasksListEvents() {
  document.querySelectorAll("[data-view-sent]").forEach(btn => {
    btn.addEventListener("click", async () => {
      const task = missionsForSelector.find(m => String(m.id) === String(btn.dataset.viewSent));
      if (!task) return;
      await stOpenTaskDetail(task);
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

/* rmForceReadOnly/msumForceReadOnly أعلام عامة بلا مالك ثاني غير الجولة --
   لازم ترجع false فور الخروج من الجولة، وإلا تبقى القراءة فقط "متسربة"
   للأبد على صفحتي مصفوفة المخاطر/ملخص الاجتماع الحقيقيتين حتى لصاحب الصلاحية
   الفعلي، بما إنه ما فيه أي كود ثاني يعيدها false غير هذا */
function stTourResetForceFlags() {
  if (typeof rmForceReadOnly !== "undefined") rmForceReadOnly = false;
  if (typeof msumForceReadOnly !== "undefined") msumForceReadOnly = false;
}

function stReachedTourStages() {
  return ST_TOUR_STAGES.filter(g => g.always || stTimelineEvents.some(ev => g.actions.includes(ev.action)));
}

const ST_TOUR_MR_RENDERERS = ["mr1", "mr2", "mr3"];

/* يجيب بيانات المرحلة المطلوبة (لو ما كانت محمّلة فعليًا مسبقًا) -- كل مجموعة
   مراحل تستخدم نفس تحميلة صفحتها الحقيقية بالضبط (مرة وحدة لكل فتح جولة)،
   وتُجبر القراءة فقط دائمًا بغض النظر عن صلاحية التعديل الفعلية للمشاهِد */
async function stGotoTourStage(i) {
  const stages = stReachedTourStages();
  if (i < 0 || i >= stages.length) return;
  stTourIndex = i;
  const stage = stages[i];
  const missionId = sentTasksSelected.id;

  if (ST_TOUR_MR_RENDERERS.includes(stage.renderer) && !stTourMrLoaded) {
    stTourLoading = true;
    renderSidebar(); renderContent(); lucide.createIcons();
    await loadMissionReviewData(missionId);
    mrCanEdit = false;
    stTourMrLoaded = true;
    stTourLoading = false;
  } else if (stage.renderer === "rm" && !stTourRmLoaded) {
    stTourLoading = true;
    renderSidebar(); renderContent(); lucide.createIcons();
    rmSelectedTaskId = String(missionId);
    await rmLoadItems(missionId);
    rmForceReadOnly = true;
    stTourRmLoaded = true;
    stTourLoading = false;
  } else if (stage.renderer === "msum" && !stTourMsumLoaded) {
    stTourLoading = true;
    renderSidebar(); renderContent(); lucide.createIcons();
    msumSelectedTaskId = String(missionId);
    await msumLoadData(missionId);
    msumForceReadOnly = true;
    stTourMsumLoaded = true;
    stTourLoading = false;
  } else if (stage.renderer === "obs" && !stTourObsLoaded) {
    stTourLoading = true;
    renderSidebar(); renderContent(); lucide.createIcons();
    obsSelectedTaskId = String(missionId);
    await obsLoadList(missionId);
    stTourObsLoaded = true;
    stTourLoading = false;
  }
  renderSidebar(); renderContent(); lucide.createIcons();
}

function stTourStageBody(stage) {
  if (stage.renderer === "log") {
    const ev = [...stTimelineEvents].reverse().find(e => stage.actions.includes(e.action));
    return `<div class="fr-preview-grid"><div class="fr-preview-field span2"><span class="lbl">${escapeHtml(stage.label)}</span><span class="val">${escapeHtml((ev && ev.detail) || "تم الاعتماد")}${ev ? " — " + escapeHtml(ev.entered_at) : ""}</span></div></div>`;
  }

  // نفس دوال العرض الحقيقية من كل صفحة تُعاد استخدامها مباشرة -- نفس الشكل
  // بالضبط، بدل تكرار العرض أو الاكتفاء بملخّص مختصر
  if (ST_TOUR_MR_RENDERERS.includes(stage.renderer)) {
    if (stTourLoading && !stTourMrLoaded) return `<p class="fr-preview-empty">جارِ التحميل...</p>`;
    if (stage.renderer === "mr1") return renderMrPage1();
    if (stage.renderer === "mr2") return renderMrPage2();
    return renderMrPage3();
  }
  if (stage.renderer === "rm") {
    if (stTourLoading && !stTourRmLoaded) return `<p class="fr-preview-empty">جارِ التحميل...</p>`;
    return renderRmReadOnlyTable();
  }
  if (stage.renderer === "msum") {
    if (stTourLoading && !stTourMsumLoaded) return `<p class="fr-preview-empty">جارِ التحميل...</p>`;
    return renderMeetingSummaryCards();
  }
  if (stage.renderer === "obs") {
    if (stTourLoading && !stTourObsLoaded) return `<p class="fr-preview-empty">جارِ التحميل...</p>`;
    return renderObsReadOnlyTable();
  }
  return "";
}

/* جولة "عرض": المراحل اللي فعليًا وصلتها المهمة بس (تدريجيًا)، بالتالي/السابق
   بينها، وتحت كل مرحلة محتواها الحقيقي -- تظهر مكان بطاقة "بانتظار الطرف
   الآخر" بدل ما تنتقل مباشرة لصفحة نموذج خام لمرحلة الطرف الآخر */
function renderSentTaskTour() {
  const stages = stReachedTourStages();
  if (stages.length === 0) {
    return `
    <div class="st-progress-view">
      <button type="button" class="st-progress-back" id="stProgressBack"><i data-lucide="chevron-right"></i> رجوع</button>
      <p class="dr-empty">لا يوجد سجل بعد لهذه المهمة</p>
    </div>`;
  }

  const idx = Math.min(stTourIndex, stages.length - 1);
  const stage = stages[idx];

  return `
  <div class="st-progress-view">
    <button type="button" class="st-progress-back" id="stProgressBack"><i data-lucide="chevron-right"></i> رجوع</button>
    <div class="wiz-steps st-progress-steps">
      ${stages.map((s, i) => `
        <div class="wiz-step">
          <button type="button" class="wiz-step-btn" data-tour-goto="${i}">
            <span class="wiz-step-circle ${idx === i ? "current" : "done"}">
              ${idx === i ? (i + 1) : '<i data-lucide="check"></i>'}
            </span>
            <span class="wiz-step-label ${idx === i ? "current" : "done"}">${escapeHtml(s.label)}</span>
          </button>
          ${i < stages.length - 1 ? `<span class="wiz-step-line done"></span>` : ""}
        </div>
      `).join("")}
    </div>
    <div class="wiz-card st-tour-card">${stTourStageBody(stage)}</div>
    <div class="st-tour-nav">
      <button type="button" class="st-tour-nav-btn" id="stTourPrev" ${idx === 0 ? "disabled" : ""}><i data-lucide="chevron-right"></i> السابق</button>
      <button type="button" class="st-tour-nav-btn primary" id="stTourNext" ${idx === stages.length - 1 ? "disabled" : ""}>التالي <i data-lucide="chevron-left"></i></button>
    </div>
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
      <span class="st-detail-status">${escapeHtml(stStageBadgeText({ next_stage: stNextStage || t.current_stage }))}</span>
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
          ${stShowTour ? renderSentTaskTour() : (nextStage ? (myTurn ? `
          <div class="st-complete-hint"><i data-lucide="pencil"></i><span>أكمل الحقول المتبقية الخاصة بك في نموذج "${nextStage.label}"</span></div>
          <button class="st-complete-btn" id="stCompleteBtn" data-next-page="${nextStage.key}"><i data-lucide="pencil"></i> إكمال الحقول</button>
          ` : `
          <div class="st-complete-hint"><i data-lucide="eye"></i><span>بانتظار الطرف الآخر لإكمال "${nextStage.label}" — تقدر تطّلع على المراحل المنجزة</span></div>
          <button class="st-complete-btn" id="stCompleteBtn" data-mode="progress"><i data-lucide="eye"></i> عرض</button>
          `) : `
          <div class="st-complete-hint"><i data-lucide="info"></i><span>لا يوجد نموذج مرتبط مباشرة بالمرحلة الحالية لهذه المهمة</span></div>
          `)}
        </div>
      </div>

      <div class="st-log-card">
        <button type="button" class="st-log-head st-log-toggle" id="stLogToggle">
          <i data-lucide="clock"></i><div><p class="t">سجل النشاط والتدقيق</p><p class="s">سجل زمني لجميع الإجراءات</p></div>
          <i data-lucide="${stLogCollapsed ? "chevron-down" : "chevron-up"}" class="st-log-chevron"></i>
        </button>
        ${stLogCollapsed ? "" : renderSentTaskTimeline()}
      </div>
    </div>
  </div>`;
}
function bindSentTaskDetailEvents() {
  document.getElementById("stBackBtn").addEventListener("click", () => {
    sentTasksSelected = null;
    stShowTour = false;
    stTourResetForceFlags();
    renderSidebar(); renderContent(); lucide.createIcons();
  });

  const logToggle = document.getElementById("stLogToggle");
  if (logToggle) logToggle.addEventListener("click", () => {
    stLogCollapsed = !stLogCollapsed;
    renderSidebar(); renderContent(); lucide.createIcons();
  });

  const progressBack = document.getElementById("stProgressBack");
  if (progressBack) progressBack.addEventListener("click", () => {
    stShowTour = false;
    stTourResetForceFlags();
    renderSidebar(); renderContent(); lucide.createIcons();
  });

  document.querySelectorAll("[data-tour-goto]").forEach(btn => {
    btn.addEventListener("click", () => stGotoTourStage(parseInt(btn.dataset.tourGoto, 10)));
  });
  const tourPrev = document.getElementById("stTourPrev");
  if (tourPrev) tourPrev.addEventListener("click", () => stGotoTourStage(stTourIndex - 1));
  const tourNext = document.getElementById("stTourNext");
  if (tourNext) tourNext.addEventListener("click", () => stGotoTourStage(stTourIndex + 1));

  const completeBtn = document.getElementById("stCompleteBtn");
  if (completeBtn) completeBtn.addEventListener("click", () => {
    // زر "عرض" ببطاقة "بانتظار الطرف الآخر" يفتح جولة "عرض" مكانه بدل ما ينتقل
    // مباشرة لصفحة النموذج الخام لمرحلة عضو المراجعة
    if (completeBtn.dataset.mode === "progress") {
      stShowTour = true;
      stTourIndex = 0;
      stGotoTourStage(0);
      return;
    }

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
