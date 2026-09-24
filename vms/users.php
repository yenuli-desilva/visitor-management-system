<?php
/**
 * User Management Page (Administrator Only)
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Strict Admin protection
requireAdmin(false);

$activePage = 'users';
$pageTitle = 'User Management';
$extraJs = 'assets/js/users.js';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Action Header -->
<div class="table-card" style="padding: 20px 24px; margin-bottom: 24px;">
  <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <h2 style="font-size: 1.3rem;">System User Accounts</h2>
      <p class="text-muted" style="font-size: 0.85rem;">Manage operator privileges, staff logins, and account security statuses</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary" onclick="UI.openModal('add-user-modal')">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Add New User</span>
      </button>
    </div>
  </div>
</div>

<!-- Users Table Card -->
<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>System Role</th>
          <th>Account Status</th>
          <th>Created Date</th>
          <th style="min-width: 160px;">Actions</th>
        </tr>
      </thead>
      <tbody id="users-tbody">
        <tr>
          <td colspan="7" style="text-align: center; padding: 48px;">
            <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ====================================================
     Add User Modal
     ==================================================== -->
<div id="add-user-modal" class="modal-overlay">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">Create System User</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form id="add-user-form">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="add-user-fullname">Full Name <span class="required">*</span></label>
          <input type="text" id="add-user-fullname" class="form-control" placeholder="e.g. Rachel Adams" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="add-user-username">Username <span class="required">*</span></label>
          <input type="text" id="add-user-username" class="form-control" placeholder="e.g. radams" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-user-role">Role <span class="required">*</span></label>
            <select id="add-user-role" class="form-select" required>
              <option value="user">Staff / User</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-user-status">Status <span class="required">*</span></label>
            <select id="add-user-status" class="form-select" required>
              <option value="active">Active</option>
              <option value="blocked">Blocked</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="add-user-password">Initial Password <span class="required">*</span></label>
          <input type="password" id="add-user-password" class="form-control"  required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- ====================================================
     Edit User Modal
     ==================================================== -->
<div id="edit-user-modal" class="modal-overlay">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">Edit User Account</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form id="edit-user-form">
      <input type="hidden" id="edit-user-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="edit-user-fullname">Full Name <span class="required">*</span></label>
          <input type="text" id="edit-user-fullname" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit-user-username">Username</label>
          <input type="text" id="edit-user-username" class="form-control" disabled style="background-color: #f1f5f9; cursor: not-allowed;">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-user-role">Role <span class="required">*</span></label>
            <select id="edit-user-role" class="form-select" required>
              <option value="user">Staff / User</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-user-status">Status <span class="required">*</span></label>
            <select id="edit-user-status" class="form-select" required>
              <option value="active">Active</option>
              <option value="blocked">Blocked</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="edit-user-password">Change Password</label>
          <input type="password" id="edit-user-password" class="form-control" placeholder="Leave blank to retain current password">
          <div class="form-text">Optional: enter a new password (min 6 characters).</div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

