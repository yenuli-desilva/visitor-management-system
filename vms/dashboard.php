<?php
/**
 * Main Corporate Dashboard View
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

$activePage = 'dashboard';
$pageTitle = 'Dashboard Overview';
$extraJs = 'assets/js/dashboard.js';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<section class="dashboard-hero">
  <div class="hero-welcome-text">
    <h2>Welcome back, <?= escapeHtml($currentUser['full_name']) ?>!</h2>
    <p>Real-time organizational visitor statistics, active check-ins, and guest flow monitoring.</p>
  </div>
  <div class="hero-actions">
    <button type="button" class="btn btn-primary" onclick="UI.openModal('dashboard-add-visitor-modal')">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      <span>New Visitor</span>
    </button>
    <a href="reports.php" class="btn btn-secondary">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      <span>Daily Report</span>
    </a>
  </div>
</section>

<!-- KPI Metrics Grid -->
<section class="stats-grid">
  <!-- Total Visitors -->
  <div class="stat-card">
    <div class="stat-info">
      <span class="stat-label">Total Visitors</span>
      <span class="stat-value" id="stat-total-visitors">0</span>
      <span class="stat-desc">Lifetime unique registrations</span>
    </div>
    <div class="stat-icon-wrap stat-icon-blue">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>
  </div>

  <!-- Today's Visits -->
  <div class="stat-card">
    <div class="stat-info">
      <span class="stat-label">Today's Visits</span>
      <span class="stat-value" id="stat-today-visitors">0</span>
      <span class="stat-desc">Visits logged today</span>
    </div>
    <div class="stat-icon-wrap stat-icon-teal">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
  </div>

  <!-- Currently Checked In -->
  <div class="stat-card">
    <div class="stat-info">
      <span class="stat-label">Currently In Premises</span>
      <span class="stat-value text-accent" id="stat-checked-in">0</span>
      <span class="stat-desc">Active badges inside organization</span>
    </div>
    <div class="stat-icon-wrap stat-icon-green">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
  </div>

  <!-- Total Users (Admin Only) -->
  <?php if ($isAdmin): ?>
  <div class="stat-card">
    <div class="stat-info">
      <span class="stat-label">System Accounts</span>
      <span class="stat-value" id="stat-total-users">0</span>
      <span class="stat-desc">Active operators & admins</span>
    </div>
    <div class="stat-icon-wrap stat-icon-purple">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Analytics & Side Info -->
<section class="analytics-grid">
  <!-- Chart Card -->
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">7-Day Visitor Flow Trend</h3>
        <p class="text-muted" style="font-size: 0.82rem;">Daily visitor volume overview for the past week</p>
      </div>
      <a href="reports.php" class="btn btn-sm btn-secondary">Detailed Reports &rarr;</a>
    </div>
    <div class="chart-canvas-wrap" id="trend-chart-container">
      <div style="text-align: center; padding: 60px 0; color: var(--text-muted);">
        <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
      </div>
    </div>
  </div>

  <!-- System Summary / Security Quick Card -->
  <div class="summary-side-card">
    <h3 class="chart-title" style="margin-bottom: 4px;">Security Snapshot</h3>
    <p class="text-muted" style="font-size: 0.82rem; margin-bottom: 16px;">Premises security guidelines</p>

    <div class="quick-stats-list">
      <div class="quick-stat-row">
        <div>
          <div class="font-semibold" style="font-size: 0.88rem;">Badges Handled</div>
          <div class="text-muted" style="font-size: 0.76rem;">All guests must wear badges</div>
        </div>
        <span class="badge badge-success">Enforced</span>
      </div>

      <div class="quick-stat-row">
        <div>
          <div class="font-semibold" style="font-size: 0.88rem;">Checkout Policy</div>
          <div class="text-muted" style="font-size: 0.76rem;">Record check-out on departure</div>
        </div>
        <span class="badge badge-user">Mandatory</span>
      </div>

      <div class="quick-stat-row">
        <div>
          <div class="font-semibold" style="font-size: 0.88rem;">Audit Trail</div>
          <div class="text-muted" style="font-size: 0.76rem;">Daily automated logs saved</div>
        </div>
        <span class="badge badge-admin">Active</span>
      </div>
    </div>

    <div style="margin-top: auto; padding-top: 18px;">
      <a href="visitors.php" class="btn btn-navy btn-block btn-sm">
        View All Visitors Directory
      </a>
    </div>
  </div>
</section>

<!-- Recent Visitors Table Card -->
<section class="table-card">
  <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
    <div>
      <h3 style="font-size: 1.1rem; font-weight: 700;">Recent Guest Activity</h3>
      <p class="text-muted" style="font-size: 0.82rem;">Latest visitor arrivals and check-out events</p>
    </div>
    <a href="visitors.php" class="btn btn-sm btn-secondary">Full Visitors List &rarr;</a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Visitor Name & NIC</th>
          <th>Department</th>
          <th>Host Contact</th>
          <th>Check In</th>
          <th>Check Out</th>
          <th>Premise Status</th>
          <th>Quick Action</th>
        </tr>
      </thead>
      <tbody id="recent-visits-tbody">
        <tr>
          <td colspan="7" style="text-align: center; padding: 32px;">
            <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<!-- Quick Add Visitor Modal for Dashboard -->
<div id="dashboard-add-visitor-modal" class="modal-overlay">
  <div class="modal-dialog modal-dialog-lg">
    <div class="modal-header">
      <h3 class="modal-title">Register New Visitor</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form id="dashboard-add-visitor-form">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="dash-add-name">Full Name <span class="required">*</span></label>
            <input type="text" id="dash-add-name" class="form-control" placeholder="e.g. Ruwan Jayasuriya" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="dash-add-nic">NIC / Identification <span class="required">*</span></label>
            <input type="text" id="dash-add-nic" class="form-control" placeholder="e.g. 199412345678" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="dash-add-phone">Phone Number <span class="required">*</span></label>
            <input type="tel" id="dash-add-phone" class="form-control" placeholder="e.g. +94 77 123 4567" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="dash-add-email">Email Address</label>
            <input type="email" id="dash-add-email" class="form-control" placeholder="e.g. visitor@example.com">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="dash-add-dept-id">Visiting Department <span class="required">*</span></label>
            <select id="dash-add-dept-id" class="form-select" required>
              <option value="">Select department...</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="dash-add-host">Host Employee Name <span class="required">*</span></label>
            <input type="text" id="dash-add-host" class="form-control" placeholder="e.g. Dr. David Perera" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="dash-add-purpose">Purpose of Visit <span class="required">*</span></label>
          <textarea id="dash-add-purpose" class="form-control" placeholder="Describe the reason for meeting..." rows="2" required></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
            <input type="checkbox" id="dash-add-checkin-now" checked style="width: 16px; height: 16px; accent-color: var(--accent);">
            <span><strong>Check In Immediately</strong> (Issue visitor pass & mark as In Premises)</span>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="dash-submit-btn">Register Visitor</button>
      </div>
    </form>
  </div>
</div>

<script>
// Load departments specifically for dashboard quick modal
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await Api.get('server/departments.php');
    const depts = res.data?.departments || [];
    const select = document.getElementById('dash-add-dept-id');
    if (select) {
      select.innerHTML = '<option value="">Select department...</option>' + 
        depts.map(d => `<option value="${d.DepartmentID}">${escapeHtml(d.Name)}</option>`).join('');
    }
  } catch (err) {}

  const form = document.getElementById('dashboard-add-visitor-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('dash-submit-btn');

      const payload = {
        name: document.getElementById('dash-add-name').value.trim(),
        nic: document.getElementById('dash-add-nic').value.trim(),
        phone: document.getElementById('dash-add-phone').value.trim(),
        email: document.getElementById('dash-add-email').value.trim(),
        purpose: document.getElementById('dash-add-purpose').value.trim(),
        host: document.getElementById('dash-add-host').value.trim(),
        department_id: document.getElementById('dash-add-dept-id').value,
        check_in_now: document.getElementById('dash-add-checkin-now').checked
      };

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner"></span> <span>Saving...</span>`;

      try {
        const res = await Api.post('server/visitors.php', payload);
        UI.showToast('success', 'Visitor Registered', res.message);
        UI.closeModal('dashboard-add-visitor-modal');
        form.reset();
        document.getElementById('dash-add-checkin-now').checked = true;
        loadDashboardData();
      } catch (err) {
        UI.showToast('error', 'Registration Failed', err.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Register Visitor';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

