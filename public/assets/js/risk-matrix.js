document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const readOnly = window.APP.readOnly;

    const missionSelect   = document.getElementById('missionSelect');
    const taskCard        = document.getElementById('taskSelectorCard');
    const tsBadge         = document.getElementById('tsRequiredBadge');
    const rmCard          = document.getElementById('rmCard');
    const rmTableBody     = document.getElementById('rmTableBody');
    const rmEmptyRow      = document.getElementById('rmEmptyRow');
    const rmFooter        = document.getElementById('rmFooter');
    const addRiskBtn      = document.getElementById('addRiskBtn');
    const saveRiskBtn     = document.getElementById('saveRiskBtn');
    const rmSavedToast    = document.getElementById('rmSavedToast');

    let currentMissionId = null;
    let rows = [];
    let isDirty = false;

    function updateLockState() {
        const locked = !currentMissionId;
        rmCard.classList.toggle('is-locked', locked);
        taskCard.classList.toggle('needs-selection', locked);
        tsBadge.textContent = locked ? 'مطلوب' : '';
        tsBadge.classList.toggle('hidden-badge', !locked);
    }

    missionSelect.addEventListener('change', function () {
        currentMissionId = this.value || null;
        updateLockState();
        if (currentMissionId) loadItems(currentMissionId);
        else { rows = []; renderRows(); }
    });

    function loadItems(missionId) {
        fetch(base + '/dashboard/risk-matrix/api/items?mission_id=' + missionId)
            .then(r => r.json())
            .then(data => {
                rows = (data.items || []).map(it => ({
                    id: it.id, risk: it.risk || '', risk_rating: it.risk_rating || '',
                    controls: it.controls || '', activity_type: it.activity_type || '',
                }));
                isDirty = false;
                renderRows();
            });
    }

    function addRow() {
        rows.push({ id: 'new-' + Date.now() + Math.random(), risk: '', risk_rating: '', controls: '', activity_type: '' });
        isDirty = true;
        renderRows();
    }

    function removeRow(id) {
        rows = rows.filter(r => r.id !== id);
        isDirty = true;
        renderRows();
    }

    function ratingClass(rating) {
        if (rating === 'عالي') return 'rating-high';
        if (rating === 'متوسط') return 'rating-medium';
        if (rating === 'منخفض') return 'rating-low';
        return '';
    }

    function renderRows() {
        rmTableBody.querySelectorAll('tr[data-row-id]').forEach(r => r.remove());
        rmEmptyRow.hidden = rows.length > 0;
        rmFooter.hidden = rows.length === 0;

        rows.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.className = ratingClass(row.risk_rating);

            const roAttr = readOnly ? 'readonly' : '';
            const roDisabled = readOnly ? 'disabled' : '';

            tr.innerHTML = `
                <td><span class="rm-num-badge">${i + 1}</span></td>
                <td><textarea class="rm-textarea" rows="2" placeholder="أدخل وصف الخطر..." ${roAttr}>${escapeHtml(row.risk)}</textarea></td>
                <td>
                    <select class="rm-select" ${roDisabled}>
                        <option value="">— اختر —</option>
                        <option value="عالي" ${row.risk_rating === 'عالي' ? 'selected' : ''}>عالي</option>
                        <option value="متوسط" ${row.risk_rating === 'متوسط' ? 'selected' : ''}>متوسط</option>
                        <option value="منخفض" ${row.risk_rating === 'منخفض' ? 'selected' : ''}>منخفض</option>
                    </select>
                </td>
                <td><textarea class="rm-textarea" rows="2" placeholder="وصف الضوابط الرقابية..." ${roAttr}>${escapeHtml(row.controls)}</textarea></td>
                <td><input type="text" class="rm-input" placeholder="نوع النشاط..." value="${escapeAttr(row.activity_type)}" ${roAttr}></td>
                <td>${readOnly ? '' : `<button type="button" class="rm-del-btn" title="حذف">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>`}</td>
            `;

            if (!readOnly) {
                tr.querySelector('.rm-textarea:nth-of-type(1)')?.addEventListener('input', function () { row.risk = this.value; isDirty = true; updateSaveBtn(); });
                const textareas = tr.querySelectorAll('.rm-textarea');
                textareas[0].addEventListener('input', function () { row.risk = this.value; isDirty = true; updateSaveBtn(); });
                textareas[1].addEventListener('input', function () { row.controls = this.value; isDirty = true; updateSaveBtn(); });
                tr.querySelector('.rm-select').addEventListener('change', function () {
                    row.risk_rating = this.value; isDirty = true; updateSaveBtn();
                    tr.className = ratingClass(row.risk_rating);
                });
                tr.querySelector('.rm-input').addEventListener('input', function () { row.activity_type = this.value; isDirty = true; updateSaveBtn(); });
                tr.querySelector('.rm-del-btn').addEventListener('click', function () { removeRow(row.id); });
            }

            rmTableBody.appendChild(tr);
        });

        updateStats();
        updateSaveBtn();
    }

    function updateStats() {
        document.getElementById('rmTotal').textContent = rows.length;
        const counts = { 'عالي': 0, 'متوسط': 0, 'منخفض': 0 };
        rows.forEach(r => { if (counts[r.risk_rating] !== undefined) counts[r.risk_rating]++; });

        const highEl = document.getElementById('rmHighStat');
        const medEl  = document.getElementById('rmMedStat');
        const lowEl  = document.getElementById('rmLowStat');
        highEl.hidden = counts['عالي'] === 0;   highEl.textContent = 'عالي: ' + counts['عالي'];
        medEl.hidden  = counts['متوسط'] === 0;  medEl.textContent  = 'متوسط: ' + counts['متوسط'];
        lowEl.hidden  = counts['منخفض'] === 0;  lowEl.textContent  = 'منخفض: ' + counts['منخفض'];
    }

    function updateSaveBtn() {
        if (!saveRiskBtn) return;
        saveRiskBtn.disabled = !isDirty;
    }

    if (addRiskBtn) addRiskBtn.addEventListener('click', addRow);

    if (saveRiskBtn) {
        saveRiskBtn.addEventListener('click', async function () {
            if (!currentMissionId) return;
            saveRiskBtn.disabled = true;

            const csrfName  = document.querySelector('meta[name="csrf-token-name"]').content;
            const csrfValue = document.querySelector('meta[name="csrf-token-value"]').content;

            try {
                const res = await fetch(base + '/dashboard/risk-matrix/api/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        mission_id: currentMissionId,
                        rows: rows.map(r => ({ risk: r.risk, risk_rating: r.risk_rating, controls: r.controls, activity_type: r.activity_type })),
                        [csrfName]: csrfValue,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    isDirty = false;
                    rmSavedToast.hidden = false;
                    setTimeout(() => { rmSavedToast.hidden = true; }, 3000);
                    loadItems(currentMissionId);
                } else {
                    alert(data.message || 'تعذّر الحفظ');
                }
            } catch (e) {
                alert('تعذّر الاتصال بالخادم');
            } finally {
                updateSaveBtn();
            }
        });
    }

    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }
    function escapeAttr(str) { return String(str ?? '').replace(/"/g, '&quot;'); }

    updateLockState();
    renderRows();

    // اختيار المهمة تلقائيًا لو فيها مهمة واحدة بس
    if (missionSelect.options.length === 2) {
        missionSelect.selectedIndex = 1;
        missionSelect.dispatchEvent(new Event('change'));
    }
});
