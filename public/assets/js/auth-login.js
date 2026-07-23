document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('year').textContent = new Date().getFullYear();

    const form        = document.getElementById('loginForm');
    const usernameEl   = document.getElementById('username');
    const passwordEl   = document.getElementById('password');
    const errorEl       = document.getElementById('errorMsg');
    const submitBtn      = document.getElementById('submitBtn');
    const togglePassBtn = document.getElementById('togglePass');
    const eyeIcon        = document.getElementById('eyeIcon');

    const EYE_OPEN  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/>';
    const EYE_CLOSED = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M6.61 6.61A18.5 18.5 0 0 0 1 12s4 8 11 8a10.44 10.44 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>';

    let showPass = false;
    togglePassBtn.addEventListener('click', function () {
        showPass = !showPass;
        passwordEl.type = showPass ? 'text' : 'password';
        eyeIcon.innerHTML = showPass ? EYE_CLOSED : EYE_OPEN;
    });

    function clearError() {
        errorEl.hidden = true;
        errorEl.textContent = '';
        form.classList.remove('has-error');
    }

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
        form.classList.add('has-error');
    }

    usernameEl.addEventListener('input', clearError);
    passwordEl.addEventListener('input', clearError);

    document.getElementById('forgotLink').addEventListener('click', function (e) {
        e.preventDefault();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearError();

        const username = usernameEl.value.trim();
        const password = passwordEl.value;

        if (!username || !password) {
            showError('يرجى إدخال اسم المستخدم وكلمة المرور.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'جارٍ تسجيل الدخول...';

        const csrfName  = document.querySelector('meta[name="csrf-token-name"]').content;
        const csrfValue = document.querySelector('meta[name="csrf-token-value"]').content;

        try {
            const res = await fetch('/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    national_id: username,
                    password: password,
                    [csrfName]: csrfValue,
                }),
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
                return;
            }

            showError(data.message || 'اسم المستخدم أو كلمة المرور غير صحيحة.');
        } catch (err) {
            showError('تعذّر الاتصال بالخادم. حاول مرة أخرى.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'تسجيل الدخول';
        }
    });
});
