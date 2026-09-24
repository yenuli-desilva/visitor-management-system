/**
 * Global UI & Interaction Utilities
 * Visitor Management System (VMS)
 */

const UI = {
  /**
   * Displays a toast notification.
   *
   * @param {'success'|'error'|'info'|'warning'} type
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   */
  showToast(type, title, message, duration = 4000) {
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
      <div class="toast-icon">${iconSvg}</div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" type="button" aria-label="Close">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    `;

    container.appendChild(toast);

    // Trigger animation in next frame
    requestAnimationFrame(() => {
      toast.classList.add('show');
    });

    const closeBtn = toast.querySelector('.toast-close');
    const removeToast = () => {
      toast.classList.remove('show');
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    };

    closeBtn.addEventListener('click', removeToast);
    setTimeout(removeToast, duration);
  },

  /**
   * Opens a modal dialog by its element ID.
   *
   * @param {string} modalId
   */
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  },

  /**
   * Closes a modal dialog by its element ID.
   *
   * @param {string} modalId
   */
  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  },

  /**
   * Attaches modal backdrop and close button dismiss handlers.
   */
  initModalListeners() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          overlay.classList.remove('active');
          document.body.style.overflow = '';
        }
      });

      const closeButtons = overlay.querySelectorAll('[data-dismiss="modal"], .modal-close');
      closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          overlay.classList.remove('active');
          document.body.style.overflow = '';
        });
      });
    });
  },

  /**
   * Shows a generic confirmation modal dialog.
   *
   * @param {string} title
   * @param {string} message
   * @param {Function} onConfirm
   * @param {string} confirmBtnText
   * @param {string} confirmBtnClass
   */
  confirm(title, message, onConfirm, confirmBtnText = 'Confirm', confirmBtnClass = 'btn-danger') {
    let confirmModal = document.getElementById('global-confirm-modal');
    if (!confirmModal) {
      confirmModal = document.createElement('div');
      confirmModal.id = 'global-confirm-modal';
      confirmModal.className = 'modal-overlay';
      confirmModal.innerHTML = `
        <div class="modal-dialog">
          <div class="modal-header">
            <h3 class="modal-title" id="confirm-modal-title">Confirm Action</h3>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body" id="confirm-modal-body">
            Are you sure you want to proceed?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn" id="confirm-modal-submit-btn">Confirm</button>
          </div>
        </div>
      `;
      document.body.appendChild(confirmModal);
      this.initModalListeners();
    }

    document.getElementById('confirm-modal-title').textContent = title;
    document.getElementById('confirm-modal-body').textContent = message;
    
    const submitBtn = document.getElementById('confirm-modal-submit-btn');
    submitBtn.textContent = confirmBtnText;
    submitBtn.className = `btn ${confirmBtnClass}`;

    // Clean old listeners
    const newSubmitBtn = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);

    newSubmitBtn.addEventListener('click', async () => {
      this.closeModal('global-confirm-modal');
      if (typeof onConfirm === 'function') {
        onConfirm();
      }
    });

    this.openModal('global-confirm-modal');
  },

  /**
   * Initializes password visibility toggle buttons.
   */
  initPasswordToggles() {
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const inputId = btn.getAttribute('data-target');
        const input = document.getElementById(inputId);
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
  },

  /**
   * Initializes mobile sidebar slide-out menu drawer and backdrop overlay.
   */
  initSidebar() {
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!mobileBtn || !sidebar || !overlay) return;

    const toggle = () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    };

    const close = () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    };

    mobileBtn.addEventListener('click', toggle);
    overlay.addEventListener('click', close);
  }
};

// Global DOM ready init
document.addEventListener('DOMContentLoaded', () => {
  UI.initModalListeners();
  UI.initPasswordToggles();
  UI.initSidebar();
});
