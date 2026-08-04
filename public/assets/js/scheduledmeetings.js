/* ============================================================
   اجتماعات مجدولة — قائمة اجتماعات المستخدم الحقيقية المجدولة
   (نفس مصدر بيانات بطاقة "اجتماعات مجدولة" بالرئيسية —
   GET /dashboard/api/scheduled-meetings)
   ============================================================ */
let smList = [];
let smLoading = false;

async function loadScheduledMeetingsPage() {
  smLoading = true;
  try {
    const data = await apiGet(base + "/dashboard/api/scheduled-meetings");
    smList = data.meetings || [];
  } catch (e) {
    smList = [];
  }
  smLoading = false;
}

function renderScheduledMeetingsPage() {
  return `
  <div class="flex flex-col gap-5">
    <div class="st-card">
      <div class="st-card-head">
        <div class="st-card-head-left">
          <i data-lucide="calendar"></i>
          <div><h2>اجتماعات مجدولة</h2><p>Scheduled Meetings</p></div>
        </div>
        <span class="st-count-badge">${smList.length} اجتماع</span>
      </div>
      <table class="st-table">
        <thead><tr>
          <th>العنوان</th><th>المهمة / الإدارة</th><th>التاريخ</th><th>الوقت</th><th>المكان</th>
        </tr></thead>
        <tbody>
          ${smLoading ? `<tr><td colspan="5" class="st-empty-row">جارِ التحميل...</td></tr>` :
            smList.length === 0 ? `<tr><td colspan="5" class="st-empty-row">لا توجد اجتماعات مجدولة حالياً</td></tr>` :
            smList.map(m => {
              const mission = missionsForSelector.find(x => String(x.id) === String(m.mission_id));
              return `
              <tr>
                <td class="st-title-cell">${escapeHtml(m.title || m.meeting_code)}</td>
                <td class="st-dept-cell">${escapeHtml(mission ? (mission.target_department_name || mission.mission_code) : "—")}</td>
                <td class="st-sentat-cell" dir="ltr">${escapeHtml(m.meeting_date || "—")}</td>
                <td class="st-sentat-cell" dir="ltr">${escapeHtml(m.meeting_time || "—")}</td>
                <td class="st-dept-cell">${escapeHtml(m.location || "—")}</td>
              </tr>`;
            }).join("")}
        </tbody>
      </table>
    </div>
  </div>`;
}

function bindScheduledMeetingsEvents() {}
