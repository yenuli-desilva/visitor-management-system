/**
 * Authentication Controller (Login & Registration)
 * Visitor Management System (VMS) Frontend
 */

document.addEventListener('DOMContentLoaded', () => {
  // Password visibility toggle
  document.querySelectorAll('.password-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;

      if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>`;
      } else {
        input.type = 'password';
        btn.innerHTML = `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
      }
    });
  });

  // Demo accounts helper clicks
  document.querySelectorAll('.demo-account-item').forEach(item => {
    item.addEventListener('click', () => {
      const u = item.getAttribute('data-user');
      const p = item.getAttribute('data-pass');
      const uInput = document.getElementById('login-username');
      const pInput = document.getElementById('login-password');
      if (uInput && pInput) {
        uInput.value = u;
        pInput.value = p;
        uInput.focus();
        App.showToast('info', 'Credentials Loaded', `Loaded credentials for ${u}. Click Sign In!`, 2000);
      }
    });
  });

  // ----------------------------------------------------
  // LOGIN FORM
  // ----------------------------------------------------
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = loginForm.querySelector('button[type="submit"]');
      const alertBox = document.getElementById('auth-alert');
      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value;

      if (!username || !password) {
        showError(alertBox, 'Username and password are required.');
        return;
      }

      setLoading(submitBtn, true, 'Signing in...');
      hideAlert(alertBox);

      try {
        const response = await App.request('auth/login.php', {
          method: 'POST',
          body: { username, password }
        });

        App.showToast('success', 'Access Granted', response.message);
        const role = response.data?.role;
        setTimeout(() => {
          if (role === 'admin') {
            window.location.href = 'admin-dashboard.html';
          } else {
            window.location.href = 'user-dashboard.html';
          }
        }, 500);
      } catch (err) {
        showError(alertBox, err.message);
        App.showToast('error', 'Login Failed', err.message);
        setLoading(submitBtn, false, 'Sign In');
      }
    });
  }

  // ----------------------------------------------------
  // REGISTER FORM
  // ----------------------------------------------------
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    const passInput = document.getElementById('reg-password');
    const confirmInput = document.getElementById('reg-confirm-password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    if (passInput && strengthBar && strengthText) {
      passInput.addEventListener('input', () => {
        const val = passInput.value;
        const score = getPasswordStrength(val);
        updateStrengthDisplay(score, strengthBar, strengthText);
      });
    }

    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = registerForm.querySelector('button[type="submit"]');
      const alertBox = document.getElementById('auth-alert');
      const fullName = document.getElementById('reg-fullname').value.trim();
      const username = document.getElementById('reg-username').value.trim();
      const password = passInput.value;
      const confirmPassword = confirmInput.value;

      if (!fullName || !username || !password || !confirmPassword) {
        showError(alertBox, 'Please complete all required fields.');
        return;
      }

      if (password !== confirmPassword) {
        showError(alertBox, 'Passwords do not match.');
        return;
      }

      if (password.length < 6) {
        showError(alertBox, 'Password must be at least 6 characters long.');
        return;
      }

      setLoading(submitBtn, true, 'Creating Account...');
      hideAlert(alertBox);

      try {
        const response = await App.request('auth/register.php', {
          method: 'POST',
          body: {
            full_name: fullName,
            username: username,
            password: password,
            confirm_password: confirmPassword
          }
        });

        App.showToast('success', 'Registered Successfully', response.message);
        showSuccess(alertBox, 'Account created! Redirecting to login...');
        setTimeout(() => {
          window.location.href = 'login.html';
        }, 1200);
      } catch (err) {
        showError(alertBox, err.message);
        App.showToast('error', 'Registration Failed', err.message);
        setLoading(submitBtn, false, 'Create Account');
      }
    });
  }

  function showError(box, msg) {
    if (!box) return;
    box.textContent = msg;
    box.className = 'auth-alert error';
    box.style.display = 'block';
  }

  function showSuccess(box, msg) {
    if (!box) return;
    box.textContent = msg;
    box.className = 'auth-alert success';
    box.style.display = 'block';
  }

  function hideAlert(box) {
    if (box) box.style.display = 'none';
  }

  function setLoading(btn, loading, text) {
    if (!btn) return;
    btn.disabled = loading;
    btn.innerHTML = loading ? `<span class="spinner" style="border-top-color:#fff;"></span> ${text}` : text;
  }

  function getPasswordStrength(pass) {
    if (!pass) return 0;
    let s = 0;
    if (pass.length >= 6) s++;
    if (pass.length >= 10) s++;
    if (/[A-Z]/.test(pass)) s++;
    if (/[0-9]/.test(pass)) s++;
    if (/[^A-Za-z0-9]/.test(pass)) s++;
    return s;
  }

  function updateStrengthDisplay(score, bar, label) {
    const levels = [
      { pct: '0%', color: '#e2e8f0', text: '' },
      { pct: '20%', color: '#ef4444', text: 'Very Weak' },
      { pct: '40%', color: '#f59e0b', text: 'Weak' },
      { pct: '60%', color: '#0284c7', text: 'Medium' },
      { pct: '80%', color: '#0d9488', text: 'Strong' },
      { pct: '100%', color: '#10b981', text: 'Very Strong' }
    ];
    const lvl = levels[score] || levels[0];
    bar.style.width = lvl.pct;
    bar.style.backgroundColor = lvl.color;
    label.textContent = lvl.text;
    label.style.color = lvl.color;
  }
});
