<?php
/**
 * User Guide & System Help Documentation
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

$activePage = 'help';
$pageTitle = 'Help & Guide';

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto;">

  <!-- Hero Header -->
  <div class="table-card" style="padding: 28px 32px; margin-bottom: 28px; background: linear-gradient(135deg, #0f172a, #1e293b); color: #ffffff;">
    <div style="display: flex; align-items: center; gap: 16px;">
      <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(2, 132, 199, 0.2); color: #38bdf8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <h2 style="font-size: 1.4rem; color: #ffffff;">Visitor Management System (VMS) Operations Guide</h2>
        <p style="color: #94a3b8; font-size: 0.9rem;">Comprehensive documentation on visitor check-in, tracking, reporting, and role permissions.</p>
      </div>
    </div>
  </div>

  <!-- Help Sections Grid -->
  <div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Section 1: Visitor Workflow -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
        <span class="badge badge-user" style="font-size: 0.8rem; padding: 4px 10px;">Step 1</span>
        <h3 style="font-size: 1.15rem;">Registering Guests & Check-In</h3>
      </div>
      <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 14px;">
        When a guest arrives at the facility reception or front gate:
      </p>
      <ol style="margin-left: 20px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8;">
        <li>Navigate to <strong>Visitors</strong> or click <strong>New Visitor</strong> on the Dashboard.</li>
        <li>Enter the visitor's <strong>Full Name</strong>, <strong>NIC / National ID</strong>, <strong>Phone Number</strong>, and optional <strong>Email Address</strong>.</li>
        <li>Select the designated <strong>Visiting Department</strong> and enter the <strong>Host Employee Name</strong>.</li>
        <li>Record the <strong>Purpose of Visit</strong>.</li>
        <li>Ensure <strong>Check In Immediately</strong> is checked to immediately issue an active badge status.</li>
        <li>Click <strong>Register Visitor</strong>. The visitor status will instantly transition to <code>In Premises</code>.</li>
      </ol>
    </div>

    <!-- Section 2: Checking Out -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
        <span class="badge badge-teal" style="font-size: 0.8rem; padding: 4px 10px; background: #ccfbf1; color: #0f766e;">Step 2</span>
        <h3 style="font-size: 1.15rem;">Visitor Check-Out Procedure</h3>
      </div>
      <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 14px;">
        When the guest concludes their meeting and departs from the building:
      </p>
      <ul style="margin-left: 20px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8;">
        <li>On the <strong>Dashboard</strong> or <strong>Visitors</strong> page, locate the guest record. You can quickly search by their name or NIC.</li>
        <li>Click the red <strong>Check Out</strong> button next to their name.</li>
        <li>Confirm the action in the prompt. The system will record the departure timestamp, and the status will update to <code>Checked Out</code>.</li>
      </ul>
    </div>

    <!-- Section 3: Reports & PDF Export -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
        <span class="badge badge-admin" style="font-size: 0.8rem; padding: 4px 10px;">Step 3</span>
        <h3 style="font-size: 1.15rem;">Daily & Monthly Audit Reports</h3>
      </div>
      <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 14px;">
        The system provides automated tracking for internal compliance and executive reporting:
      </p>
      <ul style="margin-left: 20px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8;">
        <li><strong>Daily Report</strong>: View every visitor check-in, check-out time, host, and department for any chosen date.</li>
        <li><strong>Monthly Report</strong>: Review overall guest traffic trends, unique visitor numbers, and department distribution breakdown.</li>
        <li><strong>Print / Export PDF</strong>: Click the <strong>Print / Save PDF</strong> button on the reports page. The system formats clean printable layouts while hiding sidebar and topbar elements automatically.</li>
      </ul>
    </div>

    <!-- Section 4: Role-Based Access Control -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
        <span class="badge badge-navy" style="font-size: 0.8rem; padding: 4px 10px;">Security</span>
        <h3 style="font-size: 1.15rem;">User Roles & Permissions Matrix</h3>
      </div>
      <div class="table-responsive">
        <table class="data-table" style="font-size: 0.86rem;">
          <thead>
            <tr>
              <th>Feature / Operation</th>
              <th>Administrator</th>
              <th>Ordinary User (Staff)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Access Dashboard & Statistics</td>
              <td><span class="text-success font-semibold">&check; Full Access</span></td>
              <td><span class="text-success font-semibold">&check; Full Access</span></td>
            </tr>
            <tr>
              <td>Register & Search Visitors</td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
            </tr>
            <tr>
              <td>Perform Check-In & Check-Out</td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
            </tr>
            <tr>
              <td>Edit Visitor Information</td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
              <td><span class="text-success font-semibold">&check; Permitted Records</span></td>
            </tr>
            <tr>
              <td>Delete Visitor Records</td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
              <td><span class="text-danger font-semibold">&times; Disabled</span></td>
            </tr>
            <tr>
              <td>User Management (Add, Block, Edit, Delete)</td>
              <td><span class="text-success font-semibold">&check; Full Access</span></td>
              <td><span class="text-danger font-semibold">&times; Hidden / Forbidden (403)</span></td>
            </tr>
            <tr>
              <td>View & Print Audit Reports</td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
              <td><span class="text-success font-semibold">&check; Yes</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

