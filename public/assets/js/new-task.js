document.addEventListener('DOMContentLoaded', function () {
    const base = window.APP.baseUrl;
    const subsByParent = window.SUB_DEPTS_BY_PARENT || {};

    const mainDept   = document.getElementById('mainDept');
    const targetDept = document.getElementById('targetDept');
    const targetHint = document.getElementById('targetHint');
    const yearSel    = document.getElementById('year');
    const procedure  = document.getElementById('procedure');
    const reviewerName  = document.getElementById('reviewerName');
    const reviewerEmail = document.getElementById('reviewerEmail');
    const reviewerPhone = document.getElementById('reviewerPhone');
    const directorName  = document.getElementById('directorName');

    /* ── تعبئة الإدارة المستهدفة تلقائيًا حسب الإدارة المختارة ── */
    mainDept.addEventListener('change', function () {
        const subs = subsByParent[this.value] || [];
        targetDept.innerHTML = '<option value="">— اختر —</option>' +
            subs.map(s => `<option value="${s.id}">${escapeHtml(s.name_ar)}</option>`).join('');
        targetDept.disabled = subs.length === 0;
        targetHint.hidden = subs.length > 0;
        updateLetter();
    });

    /* ── تحديث الخطاب مباشرة مع كل تغيير ── */
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
        if (procedure.value.trim()) {
            procBox.hidden = false;
            setText('procedureText', procedure.value);
        } else {
            procBox.hidden = true;
        }

        const dirEl = document.getElementById('mDirector');
        if (directorName.value.trim()) {
            dirEl.hidden = false;
            dirEl.textContent = directorName.value;
        } else {
            dirEl.hidden = true;
        }
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    [mainDept, targetDept, yearSel, procedure, reviewerName, reviewerEmail, reviewerPhone, directorName]
        .forEach(el => el.addEventListener('input', updateLetter));
    [mainDept, targetDept, yearSel].forEach(el => el.addEventListener('change', updateLetter));

    updateLetter();

    /* ── التحقق والإرسال ── */
    const form = document.getElementById('newTaskForm');
    const submitBtn = document.getElementById('submitBtn');
    const formError  = document.getElementById('formError');

    const requiredFields = [
        { el: mainDept,      group: mainDept.closest('.field-group') },
        { el: targetDept,    group: targetDept.closest('.field-group') },
        { el: procedure,     group: procedure.closest('.nt-section') },
        { el: reviewerName,  group: reviewerName.closest('.field-group') },
        { el: reviewerEmail, group: reviewerEmail.closest('.field-group') },
        { el: reviewerPhone, group: reviewerPhone.closest('.field-group') },
        { el: directorName,  group: directorName.closest('.field-group') },
    ];

    function validate() {
        let ok = true;
        requiredFields.forEach(f => {
            const empty = !f.el.value || !f.el.value.trim();
            f.group.classList.toggle('has-error', empty);
            const errEl = f.group.querySelector('.field-error');
            if (errEl) errEl.hidden = !empty;
            if (empty) ok = false;
        });
        return ok;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        formError.hidden = true;

        if (!validate()) {
            formError.textContent = 'يرجى تعبئة جميع الحقول المطلوبة (المميّزة باللون الأحمر).';
            formError.hidden = false;
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'جارٍ الإرسال...';

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
                    [csrfName]: csrfValue,
                }),
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
                return;
            }

            formError.textContent = data.message || 'تعذّر إرسال الطلب. تأكد من صحة البيانات.';
            formError.hidden = false;
        } catch (err) {
            formError.textContent = 'تعذّر الاتصال بالخادم. حاول مرة أخرى.';
            formError.hidden = false;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'إرسال الطلب';
        }
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }
});
