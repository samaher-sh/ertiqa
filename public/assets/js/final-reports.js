document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;

    const listView = document.getElementById('listView');
    const createView = document.getElementById('createView');
    const missionSelect = document.getElementById('reportMissionSelect');
    const checklistCard = document.getElementById('checklistCard');
    const checklistBody = document.getElementById('checklistBody');
    const checklistProgress = document.getElementById('checklistProgress');
    const finalizeBtn = document.getElementById('finalizeBtn');

    let currentReport = null;
    let currentItems = [];

    document.getElementById('createReportBtn')?.addEventListener('click', () => {
        listView.hidden = true; createView.hidden = false;
    });
    document.getElementById('backToListBtn').addEventListener('click', () => {
        createView.hidden = true; listView.hidden = false; location.reload();
    });

    document.querySelectorAll('.view-report-btn').forEach(b => {
        b.addEventListener('click', function () {
            listView.hidden = true; createView.hidden = false;
            missionSelect.value = this.dataset.mission;
            missionSelect.dispatchEvent(new Event('change'));
        });
    });

    missionSelect.addEventListener('change', function () {
        if (!this.value) { checklistCard.hidden = true; return; }
        loadChecklist(this.value);
    });

    function loadChecklist(missionId) {
        fetch(base + '/dashboard/reports/api/checklist?mission_id=' + missionId)
            .then(r => r.json())
            .then(data => {
                currentReport = data.report;
                currentItems = data.items;
                window.__lastCompletion = data.completion;
                renderChecklist(data.completion);
                checklistCard.hidden = false;
            });
    }

    function renderChecklist(completion) {
        const checkedCount = currentItems.filter(i => Number(i.is_checked) === 1).length;
        checklistProgress.textContent = checkedCount + ' / ' + currentItems.length;
        finalizeBtn.disabled = checkedCount < currentItems.length;

        checklistBody.innerHTML = currentItems.map(item => {
            const isDone = completion[item.section_number];
            const isChecked = Number(item.is_checked) === 1;
            return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;">
                        <span class="check-badge" style="background:${isChecked ? '#dcfce7' : 'var(--pl)'};color:${isChecked ? '#166534' : 'var(--pd)'};">${item.section_number}</span>
                        <span style="font-weight:700;font-size:13px;color:#152c33;">${escapeHtml(item.section_title)}</span>
                    </div>
                </td>
                <td style="text-align:center;">
                    <span class="done-tag ${isDone ? 'ok' : 'pending'}">${isDone ? 'مكتملة' : 'غير مكتملة'}</span>
                </td>
                <td style="text-align:center;">
                    <button type="button" class="chk-toggle ${isChecked ? 'checked' : ''}" data-section="${item.section_number}" ${!isDone ? 'disabled title="أكملي بيانات هذي المرحلة أولاً"' : ''}>
                        ${isChecked ? '✓' : ''}
                    </button>
                </td>
            </tr>`;
        }).join('');

        checklistBody.querySelectorAll('.chk-toggle:not(:disabled)').forEach(btn => {
            btn.addEventListener('click', function () {
                const section = Number(this.dataset.section);
                const item = currentItems.find(i => i.section_number === section);
                const newVal = Number(item.is_checked) === 1 ? 0 : 1;
                item.is_checked = newVal;

                fetch(base + '/dashboard/reports/api/toggle-check', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: currentReport.id, section_number: section, checked: !!newVal }),
                }).then(() => renderChecklist(window.__lastCompletion || {}));
            });
        });
    }

    finalizeBtn.addEventListener('click', function () {
        fetch(base + '/dashboard/reports/api/finalize', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: currentReport.id }),
        }).then(r => r.json()).then(data => {
            if (data.success) { alert('تم اعتماد التقرير وإرساله للمراجعة.'); location.reload(); }
            else alert(data.message || 'تعذّر الاعتماد');
        });
    });

    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }
});
