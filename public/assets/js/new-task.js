document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const subsByParent = window.SUB_DEPTS_BY_PARENT || {};

    /* ═══════ عناصر الخطوة 1 ═══════ */
    const mainDept   = document.getElementById('mainDept');
    const targetDept = document.getElementById('targetDept');
    const targetHint = document.getElementById('targetHint');
    const yearSel    = document.getElementById('year');
    const procedure  = document.getElementById('procedure');
    const reviewerName  = document.getElementById('reviewerName');
    const reviewerEmail = document.getElementById('reviewerEmail');
    const reviewerPhone = document.getElementById('reviewerPhone');
    const directorName  = document.getElementById('directorName');

    mainDept.addEventListener('change', function () {
        const subs = subsByParent[this.value] || [];
        targetDept.innerHTML = '<option value="">— اختر —</option>' +
            subs.map(s => `<option value="${s.id}">${escapeHtml(s.name_ar)}</option>`).join('');
        targetDept.disabled = subs.length === 0;
        targetHint.hidden = subs.length > 0;
        updateLetter();
        updateSubjectDeptDisplay();
    });

    targetDept.addEventListener('change', updateSubjectDeptDisplay);

    function updateSubjectDeptDisplay() {
        const el = document.getElementById('subjectDeptDisplay');
        if (el) el.value = targetDept.selectedOptions[0]?.text || '';
    }

    /* ── تحديث الخطاب مباشرة ── */
    const letterDate = document.getElementById('letterDate');
    const letterRef   = document.getElementById('letterRef');
    letterDate.textContent = new Date().toLocaleDateString('en-GB');
    const today = new Date();
    letterRef.textContent = String((today.getMonth() + 1) * 100 + today.getDate()).padStart(4, '0');

    function updateLetter() {
        setText('mDept', mainDept.selectedOptions[0]?.text || '');
        setText('mTarget', targetDept.selectedOptions[0]?.text || 'الإدارة المستهدفة');
        setText('mYear', yearSel.value);
        setText('letterYear', yearSel.value);
        setText('mReviewer', reviewerName.value || '...............');
        setText('mEmail', reviewerEmail.value || '........................');
        setText('mPhone', reviewerPhone.value || '........................');

        const procBox = document.getElementById('procedureBox');
        if (procedure.value.trim()) { procBox.hidden = false; setText('procedureText', procedure.value); }
        else { procBox.hidden = true; }

        const dirEl = document.getElementById('mDirector');
        if (directorName.value.trim()) { dirEl.hidden = false; dirEl.textContent = directorName.value; }
        else { dirEl.hidden = true; }
    }

    function setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }

    [mainDept, targetDept, yearSel, procedure, reviewerName, reviewerEmail, reviewerPhone, directorName]
        .forEach(el => el.addEventListener('input', updateLetter));
    updateLetter();

    /* ═══════ عناصر الخطوة 2 — قنوات الاتصال ═══════ */
    const CHANNELS = [
        { key: 'email', label: 'البريد الإلكتروني', type: 'email', placeholder: 'أدخل عنوان البريد الإلكتروني' },
        { key: 'memo',  label: 'المذكرات الداخلية',  type: 'textarea', placeholder: 'أدخل تفاصيل المذكرات الداخلية' },
        { key: 'phone', label: 'الهاتف الداخلي',     type: 'tel', placeholder: 'أدخل رقم الهاتف الداخلي' },
    ];
    const channelState = { email: true, memo: true, phone: true };
    const channelsWrap = document.getElementById('channelsWrap');

    function renderChannels() {
        channelsWrap.innerHTML = CHANNELS.map(c => `
            <div class="channel-row ${channelState[c.key] ? 'active' : ''}" data-channel="${c.key}">
                <div class="channel-head" data-toggle="${c.key}">
                    <span class="channel-check">${channelState[c.key] ? '✓' : ''}</span>
                    <span class="channel-label">${c.label}</span>
                </div>
                ${channelState[c.key] ? `
                <div class="channel-body">
                    ${c.type === 'textarea'
                        ? `<textarea rows="3" data-value="${c.key}" placeholder="${c.placeholder}"></textarea>`
                        : `<input type="${c.type}" data-value="${c.key}" placeholder="${c.placeholder}">`}
                </div>` : ''}
            </div>
        `).join('');

        channelsWrap.querySelectorAll('[data-toggle]').forEach(el => {
            el.addEventListener('click', function () {
                const key = this.dataset.toggle;
                const currentVal = channelsWrap.querySelector(`[data-value="${key}"]`)?.value || '';
                channelValues[key] = currentVal;
                channelState[key] = !channelState[key];
                renderChannels();
            });
        });
        channelsWrap.querySelectorAll('[data-value]').forEach(el => {
            el.value = channelValues[el.dataset.value] || '';
            el.addEventListener('input', function () { channelValues[this.dataset.value] = this.value; });
        });
    }
    const channelValues = { email: '', memo: '', phone: '' };
    renderChannels();

    /* ═══════ عناصر الخطوة 3 — المستندات ═══════ */
    let docRows = [];
    const docTableBody = document.getElementById('docTableBody');
    const docEmptyRow  = document.getElementById('docEmptyRow');
    const docCountLabel = document.getElementById('docCountLabel');
    let docTouched = false;

    function renderDocs() {
        docCountLabel.textContent = docRows.length + ' مستند مضاف';
        docTableBody.querySelectorAll('tr[data-doc-id]').forEach(r => r.remove());

        if (docRows.length === 0) {
            docEmptyRow.hidden = false;
            return;
        }
        docEmptyRow.hidden = true;

        docRows.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.dataset.docId = row.id;
            const isEmpty = docTouched && !row.name.trim();
            tr.innerHTML = `
                <td><span class="doc-num-badge">${i + 1}</span></td>
                <td>
                    <input type="text" class="doc-name-input ${isEmpty ? 'has-error' : ''}" value="${escapeAttr(row.name)}" placeholder="أدخل اسم المستند...">
                </td>
                <td class="doc-locked-cell">🔒 توجد / لا توجد</td>
                <td class="doc-locked-cell">🔒 رفع</td>
                <td class="doc-locked-cell">🔒 ملاحظة</td>
                <td><button type="button" class="doc-del-btn" title="حذف">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                </button></td>
            `;
            tr.querySelector('.doc-name-input').addEventListener('input', function () {
                row.name = this.value;
                docCountLabel.textContent = docRows.length + ' مستند مضاف';
            });
            tr.querySelector('.doc-del-btn').addEventListener('click', function () {
                docRows = docRows.filter(r => r.id !== row.id);
                renderDocs();
            });
            docTableBody.appendChild(tr);
        });
    }

    document.getElementById('addDocBtn').addEventListener('click', function () {
        docRows.push({ id: Date.now() + Math.random(), name: '' });
        renderDocs();
    });

    renderDocs();

    /* ═══════ التنقل بين الخطوات ═══════ */
    let currentStep = 1;
    const totalSteps = 3;

    function updateStepsBar() {
        for (let n = 1; n <= totalSteps; n++) {
            const circle = document.querySelector(`[data-step-circle="${n}"]`);
            circle.classList.toggle('is-current', n === currentStep);
            circle.classList.toggle('is-done', n < currentStep);
            circle.textContent = n < currentStep ? '✓' : n;
        }
        for (let n = 1; n < totalSteps; n++) {
            document.querySelector(`[data-step-line="${n}"]`).classList.toggle('is-done', n < currentStep);
        }
        document.querySelectorAll('[data-step-panel]').forEach(panel => {
            panel.hidden = Number(panel.dataset.stepPanel) !== currentStep;
        });
        document.getElementById('btnPrev').hidden = currentStep === 1;
        document.getElementById('btnNext').hidden = currentStep === totalSteps;
        document.getElementById('btnSend').hidden = currentStep !== totalSteps;
    }

    function validateStep1() {
        const fields = [
            { el: mainDept,      group: mainDept.closest('.field-group') },
            { el: targetDept,    group: targetDept.closest('.field-group') },
            { el: procedure,     group: procedure.closest('.nt-section') },
            { el: reviewerName,  group: reviewerName.closest('.field-group') },
            { el: reviewerEmail, group: reviewerEmail.closest('.field-group') },
            { el: reviewerPhone, group: reviewerPhone.closest('.field-group') },
            { el: directorName,  group: directorName.closest('.field-group') },
        ];
        let ok = true;
        fields.forEach(f => {
            const empty = !f.el.value || !f.el.value.trim();
            f.group.classList.toggle('has-error', empty);
            const errEl = f.group.querySelector('.field-error');
            if (errEl) errEl.hidden = !empty;
            if (empty) ok = false;
        });
        return ok;
    }

    function validateStep3() {
        docTouched = true;
        renderDocs();
        const docFormError = document.getElementById('docFormError');
        if (docRows.length === 0 || docRows.some(r => !r.name.trim())) {
            docFormError.textContent = 'يرجى إضافة مستند واحد على الأقل، وتعبئة كل الأسماء.';
            docFormError.hidden = false;
            return false;
        }
        docFormError.hidden = true;
        return true;
    }

    document.getElementById('btnNext').addEventListener('click', function () {
        if (currentStep === 1 && !validateStep1()) return;
        if (currentStep === 2) updateSubjectDeptDisplay();
        currentStep = Math.min(currentStep + 1, totalSteps);
        updateStepsBar();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.getElementById('btnPrev').addEventListener('click', function () {
        currentStep = Math.max(currentStep - 1, 1);
        updateStepsBar();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.querySelectorAll('.step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = Number(this.dataset.step);
            if (target < currentStep) { currentStep = target; updateStepsBar(); }
            // ما نسمح نقفز لقدام بدون تحقق من الخطوة الحالية
        });
    });

    updateStepsBar();

    /* ═══════ الإرسال النهائي ═══════ */
    document.getElementById('btnSend').addEventListener('click', async function () {
        if (!validateStep1()) { currentStep = 1; updateStepsBar(); return; }
        if (!validateStep3()) return;

        const btn = this;
        btn.disabled = true;
        btn.textContent = 'جارٍ الإرسال...';

        const csrfName  = document.querySelector('meta[name="csrf-token-name"]').content;
        const csrfValue = document.querySelector('meta[name="csrf-token-value"]').content;

        try {
            const res = await fetch(base + '/dashboard/new-task', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    main_dept_id:   mainDept.value,
                    target_dept_id: targetDept.value,
                    year:           yearSel.value,
                    procedure:      procedure.value,
                    reviewer_name:  reviewerName.value,
                    reviewer_email: reviewerEmail.value,
                    reviewer_phone: reviewerPhone.value,
                    director_name:  directorName.value,
                    doc_names:      docRows.map(r => r.name),
                    [csrfName]: csrfValue,
                }),
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
                return;
            }

            const docFormError = document.getElementById('docFormError');
            docFormError.textContent = data.message || 'تعذّر إرسال الطلب. تأكد من صحة البيانات.';
            docFormError.hidden = false;
        } catch (err) {
            const docFormError = document.getElementById('docFormError');
            docFormError.textContent = 'تعذّر الاتصال بالخادم. حاول مرة أخرى.';
            docFormError.hidden = false;
        } finally {
            btn.disabled = false;
            btn.textContent = 'إرسال الطلب';
        }
    });

    function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }
    function escapeAttr(str) { return String(str ?? '').replace(/"/g, '&quot;'); }
});
