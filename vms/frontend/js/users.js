/**
 * Users Management Controller (Admin Only)
 * Visitor Management System (VMS) Frontend
 */

let currentAdminUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  currentAdminUser = await App.checkAuth(true); // Enforce admin access
  if (!currentAdminUser) return;

  App.initSidebar();

  const path = window.location.pathname.split('/').pop();

  if (path === 'users.html') {
    initUsersPage();
  } else if (path === 'add-user.html') {
    initAddUserPage();
  } else if (path === 'edit-user.html') {
    initEditUserPage();
  }
});

// ----------------------------------------------------
// 1. USERS LIST PAGE
// ----------------------------------------------------
async function initUsersPage() {
  const tbody = document.getElementById('users-tbody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align:center; padding: 40px;">
        <span class="spinner"></span>
        <div style="margin-top: 8px; color: var(--text-muted); font-size: 0.85rem;">Loading user accounts...</div>
      </td>
    </tr>
  `;

  try {
    const res = await App.request('users/get_users.php');
    const users = res.data?.users || [];
    renderUsersTable(users);
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center; color:var(--danger); padding:32px;">
          Failed to load users: ${App.escapeHtml(err.message)}
        </td>
      </tr>
    `;
    App.showToast('error', 'Error', err.message);
  }
}

function renderUsersTable(users) {
  const tbody = document.getElementById('users-tbody');
  if (!tbody) return;

  // Compute live user stats
  const totalUsers = users.length;
  const adminCount = users.filter(u => u.Role === 'admin').length;
  const standardCount = users.filter(u => u.Role !== 'admin').length;
  const activeCount = users.filter(u => u.Status === 'active').length;

  const elTot = document.getElementById('usr-stat-total'); if (elTot) elTot.textContent = totalUsers;
  const elAdm = document.getElementById('usr-stat-admins'); if (elAdm) elAdm.textContent = adminCount;
  const elStd = document.getElementById('usr-stat-standard'); if (elStd) elStd.textContent = standardCount;
  const elAct = document.getElementById('usr-stat-active'); if (elAct) elAct.textContent = activeCount;

  const currentUserId = currentAdminUser ? currentAdminUser.user_id : 0;

  if (users.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty-state">No users registered yet.</td></tr>`;
    return;
  }

  tbody.innerHTML = users.map(u => {
    const isSelf = parseInt(u.UserID, 10) === currentUserId;
    const roleBadge = u.Role === 'admin'
      ? `<span class="badge badge-admin">Administrator</span>`
      : `<span class="badge badge-user">Standard User</span>`;

    const statusBadge = u.Status === 'active'
      ? `<span class="badge badge-success"><span class="badge-dot"></span> Active</span>`
      : `<span class="badge badge-danger"><span class="badge-dot"></span> Blocked</span>`;

    const blockBtn = !isSelf ? `
      <button type="button" class="btn btn-sm btn-secondary btn-user-toggle" data-id="${u.UserID}" data-status="${u.Status}" data-name="${App.escapeHtml(u.Username)}">
        ${u.Status === 'active' ? 'Block' : 'Unblock'}
      </button>
    ` : '';

    const editBtn = `<a href="edit-user.html?id=${u.UserID}" class="btn btn-sm btn-secondary">Edit</a>`;

    const deleteBtn = !isSelf ? `
      <button type="button" class="btn btn-sm btn-secondary text-danger btn-user-delete" data-id="${u.UserID}" data-name="${App.escapeHtml(u.FullName || u.Username)}">
        &times;
      </button>
    ` : '';

    return `
      <tr>
        <td>#${u.UserID}</td>
        <td>
          <div class="font-semibold">${App.escapeHtml(u.FullName)} ${isSelf ? '<span class="badge badge-navy" style="font-size:0.65rem; margin-left:4px;">You</span>' : ''}</div>
        </td>
        <td><code>${App.escapeHtml(u.Username)}</code></td>
        <td>${roleBadge}</td>
        <td>${statusBadge}</td>
        <td style="font-size:0.82rem; color:var(--text-muted);">${formatDate(u.CreatedAt)}</td>
        <td>
          <div class="table-actions">
            ${blockBtn}
            ${editBtn}
            ${deleteBtn}
          </div>
        </td>
      </tr>
    `;
  }).join('');

  attachUserActions();
}

function attachUserActions() {
  // Toggle Block/Unblock
  document.querySelectorAll('.btn-user-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const cur = btn.getAttribute('data-status');
      const name = btn.getAttribute('data-name');
      const action = cur === 'active' ? 'block' : 'unblock';

      App.confirm(`${capitalize(action)} User`, `Are you sure you want to ${action} user "${name}"?`, async () => {
        try {
          const res = await App.request('users/toggle_status.php', {
            method: 'POST',
            body: { user_id: id }
          });
          App.showToast('success', 'Status Updated', res.message);
          initUsersPage();
        } catch (err) {
          App.showToast('error', 'Update Failed', err.message);
        }
      }, capitalize(action), cur === 'active' ? 'btn-danger' : 'btn-teal');
    });
  });

  // Delete User
  document.querySelectorAll('.btn-user-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name');

      App.confirm('Delete User Account', `Permanently delete account for "${name}"?`, async () => {
        try {
          const res = await App.request('users/delete_user.php', {
            method: 'POST',
            body: { user_id: id }
          });
          App.showToast('success', 'User Deleted', res.message);
          initUsersPage();
        } catch (err) {
          App.showToast('error', 'Delete Failed', err.message);
        }
      }, 'Delete User', 'btn-danger');
    });
  });
}

// ----------------------------------------------------
// 2. ADD USER PAGE
// ----------------------------------------------------
function initAddUserPage() {
  const form = document.getElementById('add-user-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');

      const payload = {
        full_name: document.getElementById('user-fullname').value.trim(),
        username: document.getElementById('user-username').value.trim(),
        password: document.getElementById('user-password').value,
        role: document.getElementById('user-role').value,
        status: document.getElementById('user-status').value
      };

      if (!payload.full_name || !payload.username || !payload.password) {
        App.showToast('error', 'Validation Error', 'All required fields must be filled.');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner"></span> Creating...`;

      try {
        const res = await App.request('users/add_user.php', {
          method: 'POST',
          body: payload
        });
        App.showToast('success', 'User Created', res.message);
        setTimeout(() => window.location.href = 'users.html', 800);
      } catch (err) {
        App.showToast('error', 'Failed', err.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Create User';
      }
    });
  }
}

// ----------------------------------------------------
// 3. EDIT USER PAGE
// ----------------------------------------------------
async function initEditUserPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const userId = urlParams.get('id');

  if (!userId) {
    App.showToast('error', 'Error', 'No user ID provided.');
    window.location.href = 'users.html';
    return;
  }

  try {
    const res = await App.request(`users/get_user.php?id=${userId}`);
    const u = res.data?.user;
    if (!u) throw new Error('User not found');

    document.getElementById('edit-user-id').value = u.UserID;
    document.getElementById('edit-user-fullname').value = u.FullName || '';
    document.getElementById('edit-user-username').value = u.Username || '';
    document.getElementById('edit-user-role').value = u.Role || 'user';
    document.getElementById('edit-user-status').value = u.Status || 'active';
  } catch (err) {
    App.showToast('error', 'Error', err.message);
    setTimeout(() => window.location.href = 'users.html', 1500);
    return;
  }

  const form = document.getElementById('edit-user-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');

      const payload = {
        user_id: document.getElementById('edit-user-id').value,
        full_name: document.getElementById('edit-user-fullname').value.trim(),
        role: document.getElementById('edit-user-role').value,
        status: document.getElementById('edit-user-status').value,
        password: document.getElementById('edit-user-password').value.trim()
      };

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner"></span> Saving...`;

      try {
        const res = await App.request('users/update_user.php', {
          method: 'POST',
          body: payload
        });
        App.showToast('success', 'Changes Saved', res.message);
        setTimeout(() => window.location.href = 'users.html', 800);
      } catch (err) {
        App.showToast('error', 'Update Failed', err.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
      }
    });
  }
}

function formatDate(dtStr) {
  if (!dtStr) return '-';
  const d = new Date(dtStr.replace(' ', 'T'));
  return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function capitalize(s) {
  return s.charAt(0).toUpperCase() + s.slice(1);
}
