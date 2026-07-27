document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const obsReadOnly = window.APP.obsReadOnly;

    const RISK_COLORS = {
        'عالي':  { bg: '#fee2e2', text: '#b91c1c', border: '#fca5a5', dot: '#ef4444' },
        'متوسط': { bg: '#fef9c3', text: '#a16207', border: '#fde047', dot: '#eab308' },
        'منخفض': { bg: '#dcfce7', text: '#15803d', border: '#86efac', dot: '#22c55e' },
    };

    const missionSelect = document.getElementById('missionSelect');
    const taskCard = document.getElementById('taskSelectorCard');
    const tsBadge = document.getElementById('tsRequiredBadge');
    const listPanel = document.getElementById('listPanel');
    const formPanel = document.getElementById('formPanel');
    const obsTableBody = document.getElementById('obsTableBody');
    const pdfExportBtn = document.getElementById('pdfExportBtn');

    let currentMissionId = null;
    let items = [];
    let draft = null;

    function updateLock() {
        const locked = !currentMissionId;
        listPanel.classList.toggle('is-locked', locked);
        taskCard.classList.toggle('needs-selection', locked);
        tsBadge.classList.toggle('hidden-badge', !locked);
    }

    missionSelect.addEventListener('change', function () {
        currentMissionId = this.value || null;
        updateLock();
        pdfExportBtn.classList.toggle('disabled', !currentMissionId);
        pdfExportBtn.href = currentMissionId ? (base + '/dashboard/pdf/observations/' + currentMissionId) : '#';
        if (currentMissionId) loadList();
    });

    function loadList() {
        fetch(base + '/dashboard/observations/api/list?mission_id=' + currentMissionId)
            .then(r => r.json())
            .then(data => { items = data.items || []; renderList(); });
    }

    function renderList() {
        if (items.length === 0) {
            obsTableBody.innerHTML = '<tr><td colspan="5" class="obs-empty">لا توجد ملاحظات مسجلة لهذه المهمة</td></tr>';
            return;
        }
        obsTableBody.innerHTML = items.map(o => {
            const rc = RISK_COLORS[o.risk_severity] || RISK_COLORS['متوسط'];
            return `
            <tr>
                <td><span class="obs-ref-badge">${escapeHtml(o.ref_code)}</span><span class="obs-title-cell">${escapeHtml(o.title || 'بدون عنوان')}</span></td>
                <td><span class="obs-dept-cell">${escapeHtml(o.department_name || '—')}</span></td>
                <td>${escapeHtml(o.observation_date || '')}</td>
                <td><span class="risk-badge" style="background:${rc.bg};color:${rc.text};border:1px solid ${rc.border};"><span class="risk-dot" style="background:${rc.dot};"></span>${escapeHtml(o.risk_severity)}</span></td>
                ${obsReadOnly ? '' : `<td style="text-align:center;">
                    <button type="button" class="obs-row-menu-btn" data-edit="${o.id}" title="تعديل">✎</button>
                    <button type="button" class="obs-row-menu-btn" data-del="${o.id}" title="حذف" style="color:#ef4444;">🗑</button>
                </td>`}
            </tr>`;
        }).join('');

        obsTableBody.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEdit(Number(b.dataset.edit))));
        obsTableBody.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => deleteObs(Number(b.dataset.del))));
    }

    function deleteObs(id) {
        if (!confirm('هل تريدين حذف هذه الملاحظة؟')) return;
        fetch(base + '/dashboard/observations/api/delete/' + id, { method: 'POST', headers: csrfHeaders() })
            .then(r => r.json()).then(() => loadList());
    }

    /* ═══════ النموذج ═══════ */
    const fDate = document.getElementById('fDate');
    const fDept = document.getElementById('fDept');
    const fTitle = document.getElementById('fTitle');
    const fObservation = document.getElementById('fObservation');
    const fStandard = document.getElementById('fStandard');
    const fReason = document.getElementById('fReason');
    const fImpact = document.getElementById('fImpact');
    const fRecommendations = document.getElementById('fRecommendations');
    const riskBtns = document.querySelectorAll('.risk-btn');
    let selectedRisk = 'متوسط';

    riskBtns.forEach(b => b.addEventListener('click', function () {
        selectedRisk = this.dataset.risk;
        riskBtns.forEach(x => x.classList.toggle('active', x === this));
    }));

    function openNew() {
        draft = null;
        fDate.value = new Date().toISOString().slice(0, 10);
        fDept.value = ''; fTitle.value = ''; fObservation.value = ''; fStandard.value = '';
        fReason.value = ''; fImpact.value = ''; fRecommendations.value = '';
        selectedRisk = 'متوسط';
        riskBtns.forEach(x => x.classList.toggle('active', x.dataset.risk === 'متوسط'));
        document.querySelectorAll('input[name=addToReport]').forEach(r => r.checked = false);
        document.getElementById('formTitle').textContent = 'رصد ملاحظة جديدة';
        showForm();
    }

    function openEdit(id) {
        const o = items.find(x => x.id === id);
        if (!o) return;
        draft = o;
        fDate.value = o.observation_date || '';
        fDept.value = o.department_id || '';
        fTitle.value = o.title || '';
        fObservation.value = o.observation_text || '';
        fStandard.value = o.standard_text || '';
        fReason.value = o.reason_text || '';
        fImpact.value = o.impact_text || '';
        fRecommendations.value = o.recommendations_text || '';
        selectedRisk = o.risk_severity || 'متوسط';
        riskBtns.forEach(x => x.classList.toggle('active', x.dataset.risk === selectedRisk));
        document.querySelectorAll('input[name=addToReport]').forEach(r => r.checked = (o.add_to_report == r.value));
        document.getElementById('formTitle').textContent = 'تعديل الملاحظة';
        showForm();
    }

    function showForm() { listPanel.hidden = true; formPanel.hidden = false; }
    function showList() { formPanel.hidden = true; listPanel.hidden = false; }

    document.getElementById('newObsBtn')?.addEventListener('click', openNew);
    document.getElementById('formBackBtn').addEventListener('click', showList);

    document.getElementById('formSaveBtn').addEventListener('click', async function () {
        if (!fDept.value || !fObservation.value.trim()) {
            alert('يرجى تعبئة الإدارة محل المراجعة ونص الملاحظة على الأقل.');
            return;
        }
        const addToReportEl = document.querySelector('input[name=addToReport]:checked');

        const payload = {
            id: draft ? draft.id : null,
            mission_id: currentMissionId,
            department_id: fDept.value,
            title: fTitle.value,
            observation_date: fDate.value,
            risk_severity: selectedRisk,
            observation_text: fObservation.value,
            standard_text: fStandard.value,
            reason_text: fReason.value,
            impact_text: fImpact.value,
            recommendations_text: fRecommendations.value,
            add_to_report: addToReportEl ? addToReportEl.value === '1' : null,
        };
        payload[csrfName()] = csrfValue();

        const res = await fetch(base + '/dashboard/observations/api/save', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) { showList(); loadList(); }
        else alert(data.message || 'تعذّر الحفظ');
    });

    function csrfName() { return document.querySelector('meta[name="csrf-token-name"]').content; }
    function csrfValue() { return document.querySelector('meta[name="csrf-token-value"]').content; }
    function csrfHeaders() { return { 'X-CSRF-TOKEN': csrfValue() }; }
    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }

    updateLock();
    if (missionSelect.options.length === 2) { missionSelect.selectedIndex = 1; missionSelect.dispatchEvent(new Event('change')); }
});
