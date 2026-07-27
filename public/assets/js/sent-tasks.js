document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const taskSelect = document.getElementById('taskSelect');
    const timelineBody = document.getElementById('timelineBody');

    taskSelect.addEventListener('change', function () {
        if (!this.value) {
            timelineBody.innerHTML = '<p class="obs-empty">اختاري مهمة لعرض سجل نشاطها</p>';
            return;
        }
        fetch(base + '/dashboard/sent-tasks/api/timeline?mission_id=' + this.value)
            .then(r => r.json())
            .then(data => renderTimeline(data.events || []));
    });

    function renderTimeline(events) {
        if (events.length === 0) {
            timelineBody.innerHTML = '<p class="obs-empty">لا يوجد سجل نشاط مسجّل لهذه المهمة بعد</p>';
            return;
        }
        timelineBody.innerHTML = events.map(e => `
            <div class="tl-item">
                <div class="tl-dot"></div>
                <div class="tl-content">
                    <p class="tl-stage">${escapeHtml(e.stage_name)}</p>
                    <p class="tl-meta">${escapeHtml(e.user_name)} — ${escapeHtml(formatDate(e.entered_at))}</p>
                </div>
            </div>
        `).join('');
    }

    function formatDate(dt) {
        if (!dt) return '';
        return dt.replace('T', ' ').slice(0, 16);
    }

    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }

    if (taskSelect.options.length === 2) {
        taskSelect.selectedIndex = 1;
        taskSelect.dispatchEvent(new Event('change'));
    }
});
