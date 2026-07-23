document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;

    /* ── طي/فتح الشريط الجانبي ── */
    const sidebar = document.getElementById('sidebar');
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
    });

    /* ── قائمة البروفايل ── */
    const profileBtn   = document.getElementById('profileBtn');
    const profileMenu  = document.getElementById('profileMenu');
    const profileChevron = document.getElementById('profileChevron');

    profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isHidden = profileMenu.hidden;
        profileMenu.hidden = !isHidden;
        profileChevron.style.transform = isHidden ? 'rotate(180deg)' : 'none';
    });

    document.addEventListener('click', function () {
        profileMenu.hidden = true;
        profileChevron.style.transform = 'none';
    });

    profileMenu.addEventListener('click', function (e) { e.stopPropagation(); });

    /* ── محتوى عضو المراجعة فقط ── */
    if (!window.APP.isAuditMember) return;

    const activeCountVal   = document.getElementById('activeCountVal');
    const meetingsCountVal = document.getElementById('meetingsCountVal');
    const panelActiveTasks = document.getElementById('panelActiveTasks');
    const panelMeetings    = document.getElementById('panelMeetings');
    const activeTasksList  = document.getElementById('activeTasksList');
    const meetingsList     = document.getElementById('meetingsList');

    /* جلب الإحصائيات */
    fetch(base + '/dashboard/api/home-stats')
        .then(r => r.json())
        .then(data => {
            activeCountVal.textContent   = data.active_count;
            meetingsCountVal.textContent = data.meetings_count;
        })
        .catch(() => {
            activeCountVal.textContent   = '0';
            meetingsCountVal.textContent = '0';
        });

    function togglePanel(panel, otherPanel) {
        const willOpen = panel.hidden;
        otherPanel.hidden = true;
        panel.hidden = !willOpen;
        return willOpen;
    }

    document.getElementById('btnActiveTasks').addEventListener('click', function () {
        const opened = togglePanel(panelActiveTasks, panelMeetings);
        if (opened && activeTasksList.dataset.loaded !== '1') {
            loadActiveTasks();
        }
    });

    document.getElementById('btnMeetings').addEventListener('click', function () {
        const opened = togglePanel(panelMeetings, panelActiveTasks);
        if (opened && meetingsList.dataset.loaded !== '1') {
            loadMeetings();
        }
    });

    function loadActiveTasks() {
        fetch(base + '/dashboard/api/active-missions')
            .then(r => r.json())
            .then(data => {
                activeTasksList.dataset.loaded = '1';
                const missions = data.missions || [];
                if (missions.length === 0) {
                    activeTasksList.innerHTML = '<p class="dp-empty">لا توجد مهام نشطة حالياً</p>';
                    return;
                }
                activeTasksList.innerHTML = missions.map(m => `
                    <div class="dp-row">
                        <span class="dp-badge">${escapeHtml(m.mission_code)}</span>
                        <div class="dp-row-main">
                            <p class="dp-row-title">${escapeHtml(m.target_department_name || '')}</p>
                            <p class="dp-row-sub">مرحلة ${escapeHtml(String(m.current_stage))}/7</p>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                activeTasksList.innerHTML = '<p class="dp-empty">تعذّر تحميل البيانات</p>';
            });
    }

    function loadMeetings() {
        fetch(base + '/dashboard/api/scheduled-meetings')
            .then(r => r.json())
            .then(data => {
                meetingsList.dataset.loaded = '1';
                const meetings = data.meetings || [];
                if (meetings.length === 0) {
                    meetingsList.innerHTML = '<p class="dp-empty">لا توجد اجتماعات مجدولة حالياً</p>';
                    return;
                }
                meetingsList.innerHTML = meetings.map(m => `
                    <div class="dp-row">
                        <span class="dp-badge">${escapeHtml(m.meeting_code)}</span>
                        <div class="dp-row-main">
                            <p class="dp-row-title">${escapeHtml(m.title)}</p>
                            <p class="dp-row-sub">${escapeHtml(m.meeting_date)} — ${escapeHtml(m.meeting_time)}</p>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                meetingsList.innerHTML = '<p class="dp-empty">تعذّر تحميل البيانات</p>';
            });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }
});
