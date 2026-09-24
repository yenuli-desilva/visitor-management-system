/**
 * Users Management Controller (Administrator Only)
 * Visitor Management System (VMS)
 */

document.addEventListener('DOMContentLoaded', () => {
  loadUsers();

  const addForm = document.getElementById('add-user-form');
  if (addForm) {
    addForm.addEventListener('submit', handleAddUser);
  }

  const editForm = document.getElementById('edit-user-form');
  if (editForm) {
    editForm.addEventListener('submit', handleEditUser);
  }
});

async function loadUsers() {
  const tbody = document.getElementById('users-tbody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align: center; padding: 40px 16px;">
        <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent); width: 28px; height: 28px;"></span>
        <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-muted);">Loading system users...</div>
      </td>
    </tr>
  `;

  try {
    const res = await Api.get('server/users.php');
    const users = res.data?.users || [];
    renderUsersTable(users);
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; color: var(--danger); padding: 32px 16px;">
          Failed to load users: ${escapeStr(err.message)}
        </td>
      </tr>
    `;
    UI.showToast('error', 'Error', err.message);
  }
}

function renderUsersTable(users) {
  const tbody = document.getElementById('users-tbody');
  if (!tbody) return;

  const currentUserId = parseInt(document.body.getAttribute('data-user-id'), 10);

  if (users.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 32px;">No users found.</td></tr>`;
    return;
  }

  tbody.innerHTML = users.map(u => {
    const isSelf = parseInt(u.UserID, 10) === currentUserId;
    const roleBadge = u.Role === 'admin'
      ? `<span class="badge badge-admin">Administrator</span>`
      : `<span class="badge badge-user">Staff / User</span>`;

    const statusBadge = u.Status === 'active'
      ? `<span class="badge badge-success"><span class="badge-dot"></span> Active</span>`
      : `<span class="badge badge-danger"><span class="badge-dot"></span> Blocked</span>`;

    const blockBtn = !isSelf ? `
      <button type="button" class="btn btn-sm btn-secondary btn-toggle-status" data-id="${u.UserID}" data-status="${u.Status}" data-name="${escapeStr(u.Username)}" title="${u.Status === 'active' ? 'Block User' : 'Unblock User'}">
        ${u.Status === 'active' ? 'Block' : 'Unblock'}
      </button>
    ` : '';

    const editBtn = `
      <button type="button" class="btn btn-sm btn-secondary btn-edit-user" data-id="${u.UserID}">
        Edit
      </button>
    `;

    const deleteBtn = !isSelf ? `
      <button type="button" class="btn btn-sm btn-secondary text-danger btn-delete-user" data-id="${u.UserID}" data-name="${escapeStr(u.FullName || u.Username)}">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </button>
    ` : '';

    return `
      <tr>
        <td>#${u.UserID}</td>
        <td>
          <div class="font-semibold">${escapeStr(u.FullName)} ${isSelf ? '<span class="badge badge-navy" style="font-size:0.68rem; margin-left:4px;">You</span>' : ''}</div>
        </td>
        <td><code>${escapeStr(u.Username)}</code></td>
        <td>${roleBadge}</td>
        <td>${statusBadge}</td>
        <td style="font-size: 0.82rem; color: var(--text-muted);">${formatDate(u.CreatedAt)}</td>
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

  attachUserTableListeners();
}

function attachUserTableListeners() {
  // Toggle Block/Unblock
  document.querySelectorAll('.btn-toggle-status').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const current = btn.getAttribute('data-status');
      const name = btn.getAttribute('data-name');
      const actionWord = current === 'active' ? 'block' : 'unblock';

      UI.confirm(
        `${capitalize(actionWord)} User`,
        `Are you sure you want to ${actionWord} the account for "${name}"?`,
        async () => {
          try {
            const res = await Api.put('server/users.php', { user_id: id, toggle_status: true });
            UI.showToast('success', 'User Status Updated', res.message);
            loadUsers();
          } catch (err) {
            UI.showToast('error', 'Status Update Failed', err.message);
          }
        },
        capitalize(actionWord),
        current === 'active' ? 'btn-danger' : 'btn-teal'
      );
    });
  });

  // Edit User
  document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      try {
        const res = await Api.get(`server/users.php?id=${id}`);
        const u = res.data?.user;
        if (!u) throw new Error('User not found');

        document.getElementById('edit-user-id').value = u.UserID;
        document.getElementById('edit-user-fullname').value = u.FullName;
        document.getElementById('edit-user-username').value = u.Username;
        document.getElementById('edit-user-role').value = u.Role;
        document.getElementById('edit-user-status').value = u.Status;
        document.getElementById('edit-user-password').value = '';

        UI.openModal('edit-user-modal');
      } catch (err) {
        UI.showToast('error', 'Error', err.message);
      }
    });
  });

  // Delete User
  document.querySelectorAll('.btn-delete-user').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name');

      UI.confirm(
        'Delete User Account',
        `Are you sure you want to permanently delete user "${name}"? This action cannot be reverted.`,
        async () => {
          try {
            const res = await Api.delete('server/users.php', { user_id: id });
            UI.showToast('success', 'User Deleted', res.message);
            loadUsers();
          } catch (err) {
            UI.showToast('error', 'Delete Failed', err.message);
          }
        },
        'Delete Account',
        'btn-danger'
      );
    });
  });
}

async function handleAddUser(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  const payload = {
    full_name: document.getElementById('add-user-fullname').value.trim(),
    username: document.getElementById('add-user-username').value.trim(),
    password: document.getElementById('add-user-password').value,
    role: document.getElementById('add-user-role').value,
    status: document.getElementById('add-user-status').value
  };

  if (!payload.full_name || !payload.username || !payload.password) {
    UI.showToast('warning', 'Missing Information', 'Please fill in all required fields.');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span class="spinner"></span> <span>Saving...</span>`;

  try {
    const res = await Api.post('server/users.php', payload);
    UI.showToast('success', 'User Created', res.message);
    UI.closeModal('add-user-modal');
    form.reset();
    loadUsers();
  } catch (err) {
    UI.showToast('error', 'Failed to Create User', err.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Create User';
  }
}

async function handleEditUser(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  const payload = {
    user_id: document.getElementById('edit-user-id').value,
    full_name: document.getElementById('edit-user-fullname').value.trim(),
    role: document.getElementById('edit-user-role').value,
    status: document.getElementById('edit-user-status').value,
    password: document.getElementById('edit-user-password').value.trim()
  };

  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span class="spinner"></span> <span>Saving...</span>`;

  try {
    const res = await Api.put('server/users.php', payload);
    UI.showToast('success', 'User Updated', res.message);
    UI.closeModal('edit-user-modal');
    loadUsers();
  } catch (err) {
    UI.showToast('error', 'Failed to Update User', err.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Save Changes';
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const dt = new Date(dateStr.replace(' ', 'T'));
  return dt.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function capitalize(s) {
  return s.charAt(0).toUpperCase() + s.slice(1);
}

function escapeStr(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
