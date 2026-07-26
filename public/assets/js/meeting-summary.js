document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const isHrUser = window.APP.isHrUser;
    const allReadOnly = window.APP.allReadOnly;

    const missionSelect = document.getElementById('missionSelect');
    const taskCard = document.getElementById('taskSelectorCard');
    const tsBadge = document.getElementById('tsRequiredBadge');
    const msContent = document.getElementById('msContent');
    const approvalCard = document.getElementById('approvalCard');
    const saveBtn = document.getElementById('saveMeetingBtn');
    const savedToast = document.getElementById('msSavedToast');

    if (isHrUser) approvalCard.hidden = true; // الاعتماد يظهر فقط لغير HR

    let currentMissionId = null;
    let missionLabel = '';
    let attendees = [];
    let points = [];
    let approvals = [];
    let isDirty = false;

    function markDirty() { isDirty = true; saveBtn.disabled = allReadOnly; }

    function updateLock() {
        const locked = !currentMissionId;
        msContent.classList.toggle('is-locked', locked);
        taskCard.classList.toggle('needs-selection', locked);
        tsBadge.classList.toggle('hidden-badge', !locked);
    }

    const pdfExportBtn = document.getElementById('pdfExportBtn');

    missionSelect.addEventListener('change', function () {
        currentMissionId = this.value || null;
        if (pdfExportBtn) {
            pdfExportBtn.href = currentMissionId ? (base + '/dashboard/pdf/meeting-summary/' + currentMissionId) : '#';
            pdfExportBtn.classList.toggle('disabled', !currentMissionId);
        }
        missionLabel = this.selectedOptions[0]?.text.split('—')[1]?.trim() || '';
        updateLock();
        if (currentMissionId) { loadData(); loadAttachments(); }
    });

    /* ═══════ المرفقات ═══════ */
    const attachmentInput = document.getElementById('attachmentInput');
    const attachmentsList = document.getElementById('attachmentsList');

    function loadAttachments() {
        fetch(base + '/dashboard/meetings/api/attachments?mission_id=' + currentMissionId)
            .then(r => r.json())
            .then(data => renderAttachments(data.documents || []));
    }

    function renderAttachments(docs) {
        if (docs.length === 0) {
            attachmentsList.innerHTML = '<p class="dp-empty" style="padding:20px;text-align:center;color:#9ca3af;">لا يوجد مرفقات</p>';
            return;
        }
        attachmentsList.innerHTML = docs.map(d => `
            <div class="attachment-row">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3185b3" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <a href="${base}/dashboard/documents/download/${d.id}" class="attachment-name">${escapeHtml(d.file_name)}</a>
                <span class="attachment-size">${formatSize(d.file_size)}</span>
            </div>
        `).join('');
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    attachmentInput.addEventListener('change', async function () {
        if (!currentMissionId || !this.files.length) return;

        const formData = new FormData();
        formData.append('mission_id', currentMissionId);
        formData.append('file', this.files[0]);

        const csrfName  = document.querySelector('meta[name="csrf-token-name"]').content;
        const csrfValue = document.querySelector('meta[name="csrf-token-value"]').content;
        formData.append(csrfName, csrfValue);

        try {
            const res = await fetch(base + '/dashboard/meetings/api/upload', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                loadAttachments();
            } else {
                alert(data.message || 'تعذّر رفع الملف');
            }
        } catch (e) {
            alert('تعذّر الاتصال بالخادم');
        } finally {
            attachmentInput.value = '';
        }
    });

    function loadData() {
        fetch(base + '/dashboard/meetings/api/data?mission_id=' + currentMissionId)
            .then(r => r.json())
            .then(data => {
                const m = data.meeting;
                document.getElementById('mDate').value = m.meeting_date || '';
                document.getElementById('mTime').value = m.meeting_time || '';
                document.getElementById('mLocation').value = m.location || '';
                document.getElementById('mDeptDisplay').value = missionLabel;
                document.getElementById('mTitle').value = m.title || '';
                document.getElementById('mObjective').value = m.objective || (isHrUser ? '' : 'التعريف بأهداف المراجعة، فهم نشاط الإدارة، السياسات والإجراءات المتبعة');

                attendees = data.attendees.map(a => ({ name: a.external_name || '', dept: a.attendee_dept || '', position: a.attendee_position || '' }));
                points    = data.points.map(p => ({ text: p.point_text || '', opinion: p.opinion || '', reason: p.reason || '' }));
                approvals = data.approvals.map(a => ({ statement: a.statement || 'إعداد واعتماد', name: a.signer_name || '', position: a.position || 'رئيس المهمة', signature: a.signature_data || '', date: a.approval_date || '' }));

                isDirty = false;
                saveBtn.disabled = true;
                renderAttendees();
                renderPoints();
                renderApprovals();
            });
    }

    /* ═══════ الحضور ═══════ */
    const attendeesBody = document.getElementById('attendeesBody');
    function renderAttendees() {
        attendeesBody.innerHTML = '';
        attendees.forEach((row, i) => {
            const tr = document.createElement('tr');
            const ro = allReadOnly ? 'readonly' : '';
            tr.innerHTML = `
                <td><span class="doc-num-badge">${i + 1}</span></td>
                <td><input type="text" class="doc-name-input" placeholder="أدخل الاسم" value="${escapeAttr(row.name)}" ${ro}></td>
                <td><input type="text" class="doc-name-input" placeholder="أدخل الإدارة" value="${escapeAttr(row.dept)}" ${ro}></td>
                <td><input type="text" class="doc-name-input" placeholder="أدخل الوظيفة" value="${escapeAttr(row.position)}" ${ro}></td>
                <td>${allReadOnly ? '' : `<button type="button" class="doc-del-btn">🗑</button>`}</td>
            `;
            const inputs = tr.querySelectorAll('input');
            inputs[0].addEventListener('input', function () { row.name = this.value; markDirty(); });
            inputs[1].addEventListener('input', function () { row.dept = this.value; markDirty(); });
            inputs[2].addEventListener('input', function () { row.position = this.value; markDirty(); });
            tr.querySelector('.doc-del-btn')?.addEventListener('click', function () { attendees.splice(i, 1); renderAttendees(); markDirty(); });
            attendeesBody.appendChild(tr);
        });
    }
    document.getElementById('addAttendeeBtn').addEventListener('click', function () {
        attendees.push({ name: '', dept: '', position: '' }); renderAttendees(); markDirty();
    });

    /* ═══════ نقاط الملخص ═══════ */
    const pointsBody = document.getElementById('pointsBody');
    function renderPoints() {
        pointsBody.innerHTML = '';
        const canWriteOpinion = isHrUser && !allReadOnly;
        points.forEach((pt, i) => {
            const tr = document.createElement('tr');
            const textCell = isHrUser
                ? `<div style="padding:8px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:13px;">${escapeHtml(pt.text)}</div>`
                : `<textarea rows="2" placeholder="النقطة ${i + 1}..." ${allReadOnly ? 'readonly' : ''}>${escapeHtml(pt.text)}</textarea>`;

            const opinionCell = canWriteOpinion
                ? `<textarea rows="2" placeholder="اكتب الرأي...">${escapeHtml(pt.opinion)}</textarea>`
                : `<div style="padding:8px;font-size:13px;color:${pt.opinion ? '#166534' : '#9ca3af'};">${escapeHtml(pt.opinion) || '—'}</div>`;

            const reasonCell = canWriteOpinion
                ? `<textarea rows="2" placeholder="اكتب السبب أو التوضيح...">${escapeHtml(pt.reason)}</textarea>`
                : `<div style="padding:8px;font-size:13px;color:#152c33;">${escapeHtml(pt.reason) || '—'}</div>`;

            tr.innerHTML = `<td>${textCell}</td><td>${opinionCell}</td><td>${reasonCell}</td><td>${(!isHrUser && !allReadOnly) ? '<button type="button" class="doc-del-btn">🗑</button>' : ''}</td>`;

            if (!isHrUser && !allReadOnly) {
                tr.querySelector('textarea')?.addEventListener('input', function () { pt.text = this.value; markDirty(); });
                tr.querySelector('.doc-del-btn').addEventListener('click', function () { points.splice(i, 1); renderPoints(); markDirty(); });
            }
            if (canWriteOpinion) {
                const tas = tr.querySelectorAll('textarea');
                const opinionTa = isHrUser ? tas[0] : tas[1];
                const reasonTa  = isHrUser ? tas[1] : tas[2];
                opinionTa?.addEventListener('input', function () { pt.opinion = this.value; markDirty(); });
                reasonTa?.addEventListener('input', function () { pt.reason = this.value; markDirty(); });
            }
            pointsBody.appendChild(tr);
        });
    }
    document.getElementById('addPointBtn').addEventListener('click', function () {
        if (isHrUser) return; // نفس الأصلية: زر الإضافة يظهر بس لغير HR
        points.push({ text: '', opinion: '', reason: '' }); renderPoints(); markDirty();
    });
    if (isHrUser) document.getElementById('addPointBtn').hidden = true;

    /* ═══════ الاعتماد + لوحة التوقيع ═══════ */
    const approvalsBody = document.getElementById('approvalsBody');
    function renderApprovals() {
        approvalsBody.innerHTML = '';
        approvals.forEach((row, i) => {
            const tr = document.createElement('tr');
            const ro = allReadOnly ? 'readonly' : '';
            tr.innerHTML = `
                <td><input type="text" class="doc-name-input" value="${escapeAttr(row.statement)}" ${ro}></td>
                <td><input type="text" class="doc-name-input" placeholder="الاسم" value="${escapeAttr(row.name)}" ${ro}></td>
                <td><input type="text" class="doc-name-input" value="${escapeAttr(row.position)}" ${ro}></td>
                <td class="sig-cell"></td>
                <td><input type="date" value="${escapeAttr(row.date)}" ${ro}></td>
            `;
            const inputs = tr.querySelectorAll('input[type=text]');
            inputs[0].addEventListener('input', function () { row.statement = this.value; markDirty(); });
            inputs[1].addEventListener('input', function () { row.name = this.value; markDirty(); });
            inputs[2].addEventListener('input', function () { row.position = this.value; markDirty(); });
            tr.querySelector('input[type=date]').addEventListener('input', function () { row.date = this.value; markDirty(); });

            const sigCell = tr.querySelector('.sig-cell');
            if (allReadOnly) {
                sigCell.innerHTML = row.signature ? `<div class="sig-readonly"><img src="${row.signature}"></div>` : `<div class="sig-readonly">—</div>`;
            } else if (row.signature) {
                sigCell.innerHTML = `<div class="sig-readonly" style="position:relative;">
                    <img src="${row.signature}">
                    <button type="button" class="sig-clear-btn" title="مسح">✕</button>
                </div>`;
                sigCell.querySelector('.sig-clear-btn').addEventListener('click', function () { row.signature = ''; renderApprovals(); markDirty(); });
            } else {
                sigCell.innerHTML = `<div class="sig-pad-wrap"><span class="sig-pad-hint">وقّع هنا</span><canvas></canvas></div>`;
                setupSignaturePad(sigCell.querySelector('canvas'), sigCell.querySelector('.sig-pad-hint'), function (dataUrl) {
                    row.signature = dataUrl; markDirty();
                    setTimeout(renderApprovals, 300);
                });
            }
            approvalsBody.appendChild(tr);
        });
    }

    function setupSignaturePad(canvas, hint, onSign) {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width || 200;
        canvas.height = rect.height || 40;
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#152c33';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        let drawing = false;

        function pos(e) {
            const r = canvas.getBoundingClientRect();
            const p = e.touches ? e.touches[0] : e;
            return { x: p.clientX - r.left, y: p.clientY - r.top };
        }
        function start(e) { drawing = true; hint.style.display = 'none'; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e) { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function end() { if (!drawing) return; drawing = false; onSign(canvas.toDataURL('image/png')); }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);
    }

    /* ═══════ الحفظ ═══════ */
    ['mDate', 'mTime', 'mLocation', 'mTitle', 'mObjective'].forEach(id => {
        document.getElementById(id).addEventListener('input', markDirty);
    });

    saveBtn.addEventListener('click', async function () {
        if (!currentMissionId) return;
        saveBtn.disabled = true;
        saveBtn.textContent = 'جارٍ الحفظ...';

        const csrfName  = document.querySelector('meta[name="csrf-token-name"]').content;
        const csrfValue = document.querySelector('meta[name="csrf-token-value"]').content;

        try {
            const res = await fetch(base + '/dashboard/meetings/api/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mission_id: currentMissionId,
                    date: document.getElementById('mDate').value,
                    time: document.getElementById('mTime').value,
                    location: document.getElementById('mLocation').value,
                    title: document.getElementById('mTitle').value,
                    objective: document.getElementById('mObjective').value,
                    attendees, points, approvals,
                    [csrfName]: csrfValue,
                }),
            });
            const data = await res.json();
            if (data.success) {
                isDirty = false;
                savedToast.hidden = false;
                setTimeout(() => { savedToast.hidden = true; }, 3000);
            } else {
                alert(data.message || 'تعذّر الحفظ');
            }
        } catch (e) {
            alert('تعذّر الاتصال بالخادم');
        } finally {
            saveBtn.textContent = 'حفظ';
            saveBtn.disabled = !isDirty;
        }
    });

    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }
    function escapeAttr(str) { return String(str ?? '').replace(/"/g, '&quot;'); }

    updateLock();

    if (missionSelect.options.length === 2) {
        missionSelect.selectedIndex = 1;
        missionSelect.dispatchEvent(new Event('change'));
    }
});
