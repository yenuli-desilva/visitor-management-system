<?php
/**
 * Professional Daily & Monthly Visitor Reporting Page
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

$activePage = 'reports';
$pageTitle = 'Visitor Reports';
$extraCss = 'assets/css/reports.css';
$extraJs = 'assets/js/reports.js';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Print-Only Organization Header -->
<div class="print-report-header">
  <div class="print-title">Visitor Management System - Official Visit Log Report</div>
  <div class="print-meta" id="print-report-meta">Generated for enterprise audit and compliance records</div>
</div>

<!-- Report Controls & Filters (Hidden on Print) -->
<div class="report-controls-card">
  <div class="report-controls-row">
    <!-- Tab Switcher -->
    <div class="report-tabs">
      <button type="button" class="report-tab-btn active" id="tab-daily-report">Daily Report</button>
      <button type="button" class="report-tab-btn" id="tab-monthly-report">Monthly Report</button>
    </div>

    <!-- Date / Month Pickers -->
    <div class="report-filter-wrap">
      <!-- Daily Date Picker -->
      <div id="daily-filter-group" style="display: flex; align-items: center; gap: 8px;">
        <label for="report-date-input" style="font-size: 0.85rem; font-weight: 600;">Select Date:</label>
        <input type="date" id="report-date-input" class="form-control" style="width: auto; padding: 7px 12px;">
      </div>

      <!-- Monthly Month Picker -->
      <div id="monthly-filter-group" style="display: none; align-items: center; gap: 8px;">
        <label for="report-month-input" style="font-size: 0.85rem; font-weight: 600;">Select Month:</label>
        <input type="month" id="report-month-input" class="form-control" style="width: auto; padding: 7px 12px;">
      </div>

      <!-- Print Button -->
      <button type="button" class="btn btn-navy" id="print-report-btn">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print / Save PDF</span>
      </button>
    </div>
  </div>
</div>

<!-- ====================================================
     DAILY REPORT VIEW
     ==================================================== -->
<div id="daily-report-view">
  <!-- Summary Cards -->
  <div class="report-summary-cards">
    <div class="report-summary-box">
      <div class="report-summary-label">Total Visits (Day)</div>
      <div class="report-summary-val" id="daily-total-visits">0</div>
    </div>
    <div class="report-summary-box">
      <div class="report-summary-label">In Premises (Checked In)</div>
      <div class="report-summary-val text-accent" id="daily-checked-in">0</div>
    </div>
    <div class="report-summary-box">
      <div class="report-summary-label">Checked Out</div>
      <div class="report-summary-val" id="daily-checked-out">0</div>
    </div>
  </div>

  <!-- Detailed Visits Table -->
  <div class="table-card">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 1.05rem;">
      Daily Guest Register Log
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Visitor Name & NIC</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Host Employee</th>
            <th>Purpose</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="daily-report-tbody">
          <tr>
            <td colspan="8" style="text-align: center; padding: 32px;">
              <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ====================================================
     MONTHLY REPORT VIEW
     ==================================================== -->
<div id="monthly-report-view" style="display: none;">
  <!-- Summary Cards -->
  <div class="report-summary-cards">
    <div class="report-summary-box">
      <div class="report-summary-label">Total Monthly Visits</div>
      <div class="report-summary-val" id="monthly-total-visits">0</div>
    </div>
    <div class="report-summary-box">
      <div class="report-summary-label">Unique Guests</div>
      <div class="report-summary-val" id="monthly-unique-visitors">0</div>
    </div>
    <div class="report-summary-box">
      <div class="report-summary-label">Active / Checked In</div>
      <div class="report-summary-val text-accent" id="monthly-checked-in">0</div>
    </div>
    <div class="report-summary-box">
      <div class="report-summary-label">Checked Out Total</div>
      <div class="report-summary-val" id="monthly-checked-out">0</div>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Daily Breakdown Table -->
    <div class="table-card">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 1.05rem;">
        Daily Visit Volume Breakdown
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Total Visits</th>
              <th>Checked In</th>
              <th>Checked Out</th>
            </tr>
          </thead>
          <tbody id="monthly-breakdown-tbody">
            <tr>
              <td colspan="4" style="text-align: center; padding: 24px;">No data loaded.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Department Distribution & Top Hosts -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <!-- Department breakdown card -->
      <div class="table-card" style="padding: 20px;">
        <h4 style="margin-bottom: 16px;">Department Volume Share</h4>
        <div id="monthly-dept-breakdown">
          <div class="text-muted" style="padding: 12px 0;">Loading department data...</div>
        </div>
      </div>

      <!-- Top Hosts card -->
      <div class="table-card" style="padding: 20px;">
        <h4 style="margin-bottom: 12px;">Top Visited Host Employees</h4>
        <div id="monthly-top-hosts">
          <div class="text-muted" style="padding: 12px 0;">Loading host statistics...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 900px) {
  #monthly-report-view > div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

