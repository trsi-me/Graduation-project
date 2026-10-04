/**
 * تسجيل الدخول والتسجيل — ربط PHP
 */
(function () {
    const params = new URLSearchParams(window.location.search);
    const typeParam = params.get('type');
    const regUserType = document.getElementById('regUserType');
    if (regUserType) {
        if (typeParam === 'volunteer') {
            regUserType.value = 'volunteer';
        } else {
            regUserType.value = 'senior';
        }
    }

    document.querySelectorAll('.tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const target = btn.dataset.tab;
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            if (loginForm) loginForm.classList.toggle('active', target === 'login');
            if (registerForm) registerForm.classList.toggle('active', target === 'register');
        });
    });

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const err = document.getElementById('loginError');
            if (err) { err.style.display = 'none'; err.textContent = ''; }
            const fd = new FormData();
            fd.append('email', document.getElementById('loginEmail').value.trim());
            fd.append('password', document.getElementById('loginPassword').value);
            try {
                const res = await fetch('auth_login612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    if (err) {
                        err.textContent = data.error || 'فشل تسجيل الدخول';
                        err.style.display = 'block';
                    }
                }
            } catch (ex) {
                if (err) {
                    err.textContent = 'تعذر الاتصال بالخادم';
                    err.style.display = 'block';
                }
            }
        });
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const err = document.getElementById('registerError');
            if (err) { err.style.display = 'none'; err.textContent = ''; }
            const pass = document.getElementById('regPassword').value;
            const confirm = document.getElementById('regConfirmPassword').value;
            if (pass !== confirm) {
                if (err) {
                    err.textContent = 'كلمة المرور غير متطابقة';
                    err.style.display = 'block';
                }
                return;
            }
            const fd = new FormData();
            fd.append('full_name', document.getElementById('regName').value.trim());
            fd.append('phone', document.getElementById('regPhone').value.trim());
            fd.append('email', document.getElementById('regEmail').value.trim());
            fd.append('password', pass);
            fd.append('confirm_password', confirm);
            fd.append('user_type', document.getElementById('regUserType').value);
            try {
                const res = await fetch('auth_register612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    if (err) {
                        err.textContent = data.error || 'تعذر إنشاء الحساب';
                        err.style.display = 'block';
                    }
                }
            } catch (ex) {
                if (err) {
                    err.textContent = 'تعذر الاتصال بالخادم';
                    err.style.display = 'block';
                }
            }
        });
    }

    const tr = document.getElementById('termsLinkReg');
    const pr = document.getElementById('privacyLinkReg');
    if (tr) tr.addEventListener('click', (e) => { e.preventDefault(); document.getElementById('termsModal').style.display = 'flex'; });
    if (pr) pr.addEventListener('click', (e) => { e.preventDefault(); document.getElementById('privacyModal').style.display = 'flex'; });
})();
