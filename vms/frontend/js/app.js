/**
 * Core Application Manager & Global Utilities
 * Visitor Management System (VMS) Frontend
 */

const App = {
  // Base path to backend APIs
  apiBase: '../backend',

  /**
   * Centralized HTTP client using fetch()
   *
   * @param {string} endpoint
   * @param {object} options
   * @returns {Promise<any>}
   */
  async request(endpoint, options = {}) {
    const url = endpoint.startsWith('http') || endpoint.startsWith('../')
      ? endpoint
      : `${this.apiBase}/${endpoint}`;

    const config = {
      method: options.method || 'GET',
      headers: {
        'Accept': 'application/json',
        ...(options.headers || {})
      },
      credentials: 'include' // Send PHP session cookies across requests
    };

    if (options.body) {
      if (options.body instanceof FormData) {
        config.body = options.body;
      } else {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(options.body);
      }
    }

    try {
      const response = await fetch(url, config);
      let json;

      try {
        json = await response.json();
      } catch (parseErr) {
        throw new Error(`Server returned status ${response.status}`);
      }

      if (!response.ok || json.success === false) {
        const errorMsg = json.message || `Request failed (${response.status})`;
        const err = new Error(errorMsg);
        err.status = response.status;
        err.data = json;
        throw err;
      }

      return json;
    } catch (err) {
      console.error('API Error:', err);
      throw err;
    }
  },

  /**
   * Toast notification display
   *
   * @param {'success'|'error'|'info'} type
   * @param {string} title
   * @param {string} message
   */
  showToast(type, title, message, duration = 3500) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    let iconSvg = '';
    if (type === 'success') {
      iconSvg = `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`;
    } else if (type === 'error') {
      iconSvg = `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
    } else {
      iconSvg = `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4m0-4h.01"/></svg>`;
    }

    toast.innerHTML = `
      <div style="flex-shrink:0;">${iconSvg}</div>
      <div style="flex:1;">
        <div style="font-weight:700; font-size:0.88rem;">${this.escapeHtml(title)}</div>
        <div style="font-size:0.82rem; color:var(--text-muted);">${this.escapeHtml(message)}</div>
      </div>
      <button type="button" class="toast-close-btn" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">&times;</button>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    const remove = () => {
      toast.classList.remove('show');
      setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 300);
    };

    toast.querySelector('.toast-close-btn').addEventListener('click', remove);
    setTimeout(remove, duration);
  },

  /**
   * Modal dialog manager
   */
  openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  },

  closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  },

  /**
   * Global confirmation modal
   */
  confirm(title, message, onConfirm, confirmText = 'Confirm', confirmClass = 'btn-danger') {
    let modal = document.getElementById('global-confirm-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'global-confirm-modal';
      modal.className = 'modal-overlay';
      modal.innerHTML = `
        <div class="modal-dialog">
          <div class="modal-header">
            <h3 class="modal-title" id="confirm-title">Confirm Action</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('global-confirm-modal')">&times;</button>
          </div>
          <div class="modal-body" id="confirm-body">Are you sure?</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('global-confirm-modal')">Cancel</button>
            <button type="button" class="btn" id="confirm-ok-btn">Confirm</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-body').textContent = message;

    const okBtn = document.getElementById('confirm-ok-btn');
    okBtn.textContent = confirmText;
    okBtn.className = `btn ${confirmClass}`;

    const newBtn = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newBtn, okBtn);

    newBtn.addEventListener('click', () => {
      this.closeModal('global-confirm-modal');
      if (typeof onConfirm === 'function') onConfirm();
    });

    this.openModal('global-confirm-modal');
  },

  /**
   * Initializes mobile sidebar slide-out drawer
   */
  initSidebar() {
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (mobileBtn && sidebar && overlay) {
      mobileBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
      });
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
      });
    }

    // Set active link in sidebar
    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
      const href = link.getAttribute('href');
      if (href === currentPath || (currentPath === 'index.html' && href === 'dashboard.html')) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });

    // Topbar live clock
    const clock = document.getElementById('topbar-live-clock');
    if (clock) {
      const update = () => {
        const d = new Date();
        clock.textContent = d.toLocaleDateString(undefined, {
          weekday: 'short', month: 'short', day: 'numeric', year: 'numeric',
          hour: '2-digit', minute: '2-digit'
        });
      };
      update();
      setInterval(update, 30000);
    }
  },

  /**
   * Verifies authentication status, sets user profile, dynamically updates
   * navigation links based on role, and enforces strict portal isolation.
   *
   * @param {string|boolean} roleRestriction 'admin' | 'user' | true (admin) | false (any)
   * @returns {Promise<object>} Authenticated user profile
   */
  async checkAuth(roleRestriction = false) {
    try {
      const res = await this.request('auth/me.php');
      const user = res.data?.user;

      if (!user) {
        throw new Error('Not authenticated');
      }

      // Populate user info in UI
      const nameEls = document.querySelectorAll('.user-name-display');
      nameEls.forEach(el => el.textContent = user.full_name || user.username);

      const roleEls = document.querySelectorAll('.user-role-display');
      roleEls.forEach(el => el.textContent = user.role === 'admin' ? 'Administrator' : 'Registered User');

      const roleBadges = document.querySelectorAll('.user-role-badge');
      roleBadges.forEach(el => {
        el.textContent = user.role === 'admin' ? 'Admin' : 'User';
        el.className = `badge ${user.role === 'admin' ? 'badge-admin' : 'badge-user'} user-role-badge`;
      });

      const initialsEls = document.querySelectorAll('.user-initials-display');
      if (initialsEls.length > 0) {
        const parts = (user.full_name || user.username).split(' ');
        const initials = (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
        initialsEls.forEach(el => el.textContent = initials);
      }

      // Dynamically point all generic "Dashboard" links to the user's specific portal
      const targetDashboard = user.role === 'admin' ? 'admin-dashboard.html' : 'user-dashboard.html';
      document.querySelectorAll('a[href="dashboard.html"]').forEach(a => {
        a.href = targetDashboard;
      });

      // Role isolation checks: Admin cannot see user portal, and User cannot see admin portal
      if (user.role === 'admin') {
        // Administrator
        document.querySelectorAll('.admin-only').forEach(el => {
          el.style.display = '';
        });
        document.querySelectorAll('.user-only').forEach(el => {
          el.style.display = 'none';
        });

        // Admin cannot see user portal!
        if (roleRestriction === 'user') {
          console.warn('Admin attempted to access user portal. Redirecting to admin command portal.');
          window.location.replace('admin-dashboard.html');
          return null;
        }
      } else {
        // Ordinary User / Staff
        // User cannot see admin portal or admin menus!
        document.querySelectorAll('.admin-only').forEach(el => {
          el.style.setProperty('display', 'none', 'important');
        });
        document.querySelectorAll('.user-only').forEach(el => {
          el.style.display = '';
        });

        if (roleRestriction === true || roleRestriction === 'admin') {
          console.warn('Ordinary user attempted to access admin portal. Redirecting to user portal.');
          window.location.replace('user-dashboard.html?error=unauthorized');
          return null;
        }
      }

      return user;
    } catch (err) {
      // Not logged in -> redirect to login.html
      const current = window.location.pathname.split('/').pop();
      if (current && current !== 'login.html' && current !== 'index.html' && current !== 'register.html') {
        window.location.replace('login.html');
      }
      return null;
    }
  },

  /**
   * Logs out user session
   */
  async logout() {
    try {
      await this.request('auth/logout.php', { method: 'POST' });
    } catch (e) {
      console.warn('Logout API error:', e);
    }
    window.location.href = 'index.html';
  },

  /**
   * HTML escape
   */
  escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
};
