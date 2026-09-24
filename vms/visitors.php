<?php
/**
 * Visitors Directory & Check-In / Check-Out Management
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

$activePage = 'visitors';
$pageTitle = 'Visitor Management';
$extraJs = 'assets/js/visitors.js';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Action Header & Filter Toolbar -->
<div class="table-card" style="padding: 20px 24px; margin-bottom: 24px;">
  <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px;">
    <div>
      <h2 style="font-size: 1.3rem;">Visitor Directory</h2>
      <p class="text-muted" style="font-size: 0.85rem;">Manage guest profiles, check-in statuses, and badge allocations</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary" onclick="UI.openModal('add-visitor-modal')">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Register New Visitor</span>
      </button>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; align-items: center;">
    <div class="input-with-icon">
      <span class="input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </span>
      <input type="text" id="visitor-search-input" class="form-control" placeholder="Search by name, NIC, phone, host, or purpose...">
    </div>

    <div>
      <select id="visitor-dept-filter" class="form-select">
        <option value="">All Departments</option>
      </select>
    </div>

    <div>
      <select id="visitor-status-filter" class="form-select">
        <option value="">All Statuses</option>
        <option value="checked_in">In Premises (Checked In)</option>
        <option value="checked_out">Checked Out</option>
      </select>
    </div>
  </div>
</div>

<!-- Visitors Table Card -->
<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Visitor Name & NIC</th>
          <th>Contact Info</th>
          <th>Department</th>
          <th>Host Contact</th>
          <th>Purpose</th>
          <th>Premises Status</th>
          <th style="min-width: 170px;">Actions</th>
        </tr>
      </thead>
      <tbody id="visitors-tbody">
        <tr>
          <td colspan="7" style="text-align: center; padding: 48px;">
            <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination Bar -->
  <div id="pagination-container" style="padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border);"></div>
</div>

<!-- ====================================================
     Add Visitor Modal
     ==================================================== -->
<div id="add-visitor-modal" class="modal-overlay">
  <div class="modal-dialog modal-dialog-lg">
    <div class="modal-header">
      <h3 class="modal-title">Register Visitor</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form id="add-visitor-form">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-name">Full Name <span class="required">*</span></label>
            <input type="text" id="add-name" class="form-control" placeholder="e.g. Kasun Silva" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-nic">NIC / Identification Number <span class="required">*</span></label>
            <input type="text" id="add-nic" class="form-control" placeholder="e.g. 199345678123" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-phone">Phone Number <span class="required">*</span></label>
            <input type="tel" id="add-phone" class="form-control" placeholder="e.g. +94 77 987 6543" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-email">Email Address</label>
            <input type="email" id="add-email" class="form-control" placeholder="e.g. kasun@example.com">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-dept-id">Visiting Department <span class="required">*</span></label>
            <select id="add-dept-id" class="form-select" required>
              <option value="">Select department...</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-host">Host Employee Name <span class="required">*</span></label>
            <input type="text" id="add-host" class="form-control" placeholder="e.g. Emily Watson" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="add-purpose">Purpose of Visit <span class="required">*</span></label>
          <textarea id="add-purpose" class="form-control" placeholder="e.g. Technical consultation meeting" rows="2" required></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
            <input type="checkbox" id="add-checkin-now" checked style="width: 16px; height: 16px; accent-color: var(--accent);">
            <span><strong>Check In Immediately</strong> (Activate premises visit pass)</span>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Visitor</button>
      </div>
    </form>
  </div>
</div>

<!-- ====================================================
     Edit Visitor Modal
     ==================================================== -->
<div id="edit-visitor-modal" class="modal-overlay">
  <div class="modal-dialog modal-dialog-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit Visitor Details</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form id="edit-visitor-form">
      <input type="hidden" id="edit-visitor-id">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-name">Full Name <span class="required">*</span></label>
            <input type="text" id="edit-name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-nic">NIC / Identification <span class="required">*</span></label>
            <input type="text" id="edit-nic" class="form-control" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-phone">Phone Number <span class="required">*</span></label>
            <input type="tel" id="edit-phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-email">Email Address</label>
            <input type="email" id="edit-email" class="form-control">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-dept-id">Visiting Department <span class="required">*</span></label>
            <select id="edit-dept-id" class="form-select" required>
              <option value="">Select department...</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-host">Host Employee <span class="required">*</span></label>
            <input type="text" id="edit-host" class="form-control" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="edit-purpose">Purpose of Visit <span class="required">*</span></label>
          <textarea id="edit-purpose" class="form-control" rows="2" required></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<style>
@media (max-width: 900px) {
  .table-card div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

