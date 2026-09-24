/**
 * Authentication Form Handlers (Login & Register)
 * Visitor Management System (VMS)
 */

document.addEventListener('DOMContentLoaded', () => {
  // --------------------------------------------------
  // Demo Account Quick-Fill Buttons
  // --------------------------------------------------
  document.querySelectorAll('.demo-account-item').forEach(item => {
    item.addEventListener('click', () => {
      const username = item.getAttribute('data-user');
      const password = item.getAttribute('data-pass');
      const userInput = document.getElementById('login-username');
      const passInput = document.getElementById('login-password');

      if (userInput && passInput) {
        userInput.value = username;
        passInput.value = password;
        userInput.focus();
        UI.showToast('info', 'Credentials Loaded', `Loaded credentials for ${username}. Click Sign In!`, 2500);
      }
    });
  });

  // --------------------------------------------------
  // Registration Strength Meter
  // --------------------------------------------------
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    const passInput = document.getElementById('reg-password');
    const confirmInput = document.getElementById('reg-confirm-password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    if (passInput && strengthBar && strengthText) {
      passInput.addEventListener('input', () => {
        const val = passInput.value;
        const strength = calculatePasswordStrength(val);
        updateStrengthUI(strength, strengthBar, strengthText);
      });
    }

  }

  // Helper: Display form alert
  function calculatePasswordStrength(pass) {
    if (!pass) return 0;
    let score = 0;
    if (pass.length >= 6) score += 1;
    if (pass.length >= 10) score += 1;
    if (/[A-Z]/.test(pass)) score += 1;
    if (/[0-9]/.test(pass)) score += 1;
    if (/[^A-Za-z0-9]/.test(pass)) score += 1;
    return score; // 0 to 5
  }

  function updateStrengthUI(score, bar, label) {
    let pct = (score / 5) * 100;
    let color = '#ef4444';
    let text = 'Too Weak';

    if (score >= 4) {
      color = '#10b981';
      text = 'Strong Password';
    } else if (score >= 3) {
      color = '#0284c7';
      text = 'Good Password';
    } else if (score >= 2) {
      color = '#f59e0b';
      text = 'Medium Strength';
    } else if (score === 1) {
      color = '#ef4444';
      text = 'Weak Password';
    } else {
      pct = 0;
      text = '';
    }

    bar.style.width = pct + '%';
    bar.style.backgroundColor = color;
    label.textContent = text;
    label.style.color = color;
  }
});
