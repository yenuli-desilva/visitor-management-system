/**
 * Reports & Analytics Controller
 * Daily & Monthly Reporting with Print Support
 * Visitor Management System (VMS)
 */

let activeReportType = 'daily';

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  const currentMonth = today.slice(0, 7);

  const dateInput = document.getElementById('report-date-input');
  const monthInput = document.getElementById('report-month-input');

  if (dateInput) dateInput.value = today;
  if (monthInput) monthInput.value = currentMonth;

  // Report Tab Switchers
  const dailyTabBtn = document.getElementById('tab-daily-report');
  const monthlyTabBtn = document.getElementById('tab-monthly-report');

  if (dailyTabBtn && monthlyTabBtn) {
    dailyTabBtn.addEventListener('click', () => {
      activeReportType = 'daily';
      dailyTabBtn.classList.add('active');
      monthlyTabBtn.classList.remove('active');
      document.getElementById('daily-filter-group').style.display = 'flex';
      document.getElementById('monthly-filter-group').style.display = 'none';
      document.getElementById('daily-report-view').style.display = 'block';
      document.getElementById('monthly-report-view').style.display = 'none';
      loadReport();
    });

    monthlyTabBtn.addEventListener('click', () => {
      activeReportType = 'monthly';
      monthlyTabBtn.classList.add('active');
      dailyTabBtn.classList.remove('active');
      document.getElementById('daily-filter-group').style.display = 'none';
      document.getElementById('monthly-filter-group').style.display = 'flex';
      document.getElementById('daily-report-view').style.display = 'none';
      document.getElementById('monthly-report-view').style.display = 'block';
      loadReport();
    });
  }

  if (dateInput) {
    dateInput.addEventListener('change', loadReport);
  }

  if (monthInput) {
    monthInput.addEventListener('change', loadReport);
  }

  // Print button
  const printBtn = document.getElementById('print-report-btn');
  if (printBtn) {
    printBtn.addEventListener('click', () => {
      window.print();
    });
  }

  loadReport();
});

async function loadReport() {
  if (activeReportType === 'daily') {
    await loadDailyReport();
  } else {
    await loadMonthlyReport();
  }
}

/**
 * Loads Daily Report Data
 */
async function loadDailyReport() {
  const dateInput = document.getElementById('report-date-input');
  const dateVal = dateInput ? dateInput.value : '';
  const tbody = document.getElementById('daily-report-tbody');

  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align:center; padding: 32px 16px;">
        <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent);"></span>
        <div style="margin-top: 8px; font-size: 0.82rem; color: var(--text-muted);">Generating daily report...</div>
      </td>
    </tr>
  `;

  try {
    const res = await Api.get(`server/reports.php?type=daily&date=${encodeURIComponent(dateVal)}`);
    const { summary, visits, selected_date } = res.data;

    // Update Daily KPI boxes
    document.getElementById('daily-total-visits').textContent = summary.total_visits;
    document.getElementById('daily-checked-in').textContent = summary.checked_in_count;
    document.getElementById('daily-checked-out').textContent = summary.checked_out_count;

    // Update Print Metadata
    const printMeta = document.getElementById('print-report-meta');
    if (printMeta) {
      printMeta.textContent = `Daily Log for: ${selected_date} | Generated on: ${new Date().toLocaleString()}`;
    }

    // Render Table
    if (!visits || visits.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align: center; padding: 48px 16px;">
            <div class="empty-state">
              <div class="empty-state-title">No Visits Recorded</div>
              <div class="empty-state-desc">There are no visit entries logged for the selected date (${selected_date}).</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = visits.map(v => {
      const isCheckedIn = v.Status === 'checked_in';
      const statusBadge = isCheckedIn
        ? `<span class="badge badge-success"><span class="badge-dot"></span> In Premises</span>`
        : `<span class="badge badge-secondary"><span class="badge-dot"></span> Checked Out</span>`;

      return `
        <tr>
          <td>
            <div class="font-semibold">${escapeStr(v.VisitorName)}</div>
            <div class="text-muted" style="font-size: 0.76rem;">NIC: ${escapeStr(v.NIC)}</div>
          </td>
          <td>${escapeStr(v.Phone)}</td>
          <td><span class="badge badge-user">${escapeStr(v.DepartmentName || 'General')}</span></td>
          <td>${escapeStr(v.Host)}</td>
          <td style="max-width: 180px; font-size: 0.82rem; color: var(--text-muted);">${escapeStr(v.Purpose)}</td>
          <td>${formatTime(v.CheckIn)}</td>
          <td>${v.CheckOut ? formatTime(v.CheckOut) : '-'}</td>
          <td>${statusBadge}</td>
        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" style="color:var(--danger); text-align:center; padding: 24px;">Failed: ${escapeStr(err.message)}</td></tr>`;
    UI.showToast('error', 'Report Error', err.message);
  }
}

/**
 * Loads Monthly Report Data
 */
async function loadMonthlyReport() {
  const monthInput = document.getElementById('report-month-input');
  const monthVal = monthInput ? monthInput.value : '';
  const tbody = document.getElementById('monthly-breakdown-tbody');
  const deptContainer = document.getElementById('monthly-dept-breakdown');
  const hostContainer = document.getElementById('monthly-top-hosts');

  try {
    const res = await Api.get(`server/reports.php?type=monthly&month=${encodeURIComponent(monthVal)}`);
    const { summary, daily_breakdown, department_breakdown, top_hosts, selected_month } = res.data;

    // Update Monthly KPI boxes
    document.getElementById('monthly-total-visits').textContent = summary.total_visits;
    document.getElementById('monthly-unique-visitors').textContent = summary.unique_visitors;
    document.getElementById('monthly-checked-in').textContent = summary.checked_in_count;
    document.getElementById('monthly-checked-out').textContent = summary.checked_out_count;

    // Update Print Metadata
    const printMeta = document.getElementById('print-report-meta');
    if (printMeta) {
      printMeta.textContent = `Monthly Summary for: ${selected_month} | Generated on: ${new Date().toLocaleString()}`;
    }

    // Render Daily Breakdown Table
    if (tbody) {
      if (!daily_breakdown || daily_breakdown.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 32px;">No activity logged for ${selected_month}.</td></tr>`;
      } else {
        tbody.innerHTML = daily_breakdown.map(d => `
          <tr>
            <td class="font-semibold">${d.VisitDate}</td>
            <td><strong>${d.total_visits}</strong></td>
            <td><span class="badge badge-success">${d.checked_in}</span></td>
            <td><span class="badge badge-secondary">${d.checked_out}</span></td>
          </tr>
        `).join('');
      }
    }

    // Render Department Breakdown
    if (deptContainer) {
      if (!department_breakdown || department_breakdown.length === 0) {
        deptContainer.innerHTML = `<div class="text-muted" style="padding: 16px;">No department visits recorded.</div>`;
      } else {
        const maxDeptVisits = Math.max(1, ...department_breakdown.map(d => parseInt(d.visit_count, 10)));
        deptContainer.innerHTML = department_breakdown.map(d => {
          const pct = Math.round((d.visit_count / maxDeptVisits) * 100);
          return `
            <div style="margin-bottom: 14px;">
              <div style="display:flex; justify-content:space-between; font-size: 0.85rem; margin-bottom: 4px;">
                <span class="font-semibold">${escapeStr(d.DepartmentName || 'General')}</span>
                <span class="font-bold text-accent">${d.visit_count} visits</span>
              </div>
              <div style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; width: ${pct}%; background: linear-gradient(90deg, var(--accent), var(--teal)); border-radius: 3px;"></div>
              </div>
            </div>
          `;
        }).join('');
      }
    }

    // Render Top Hosts
    if (hostContainer) {
      if (!top_hosts || top_hosts.length === 0) {
        hostContainer.innerHTML = `<div class="text-muted" style="padding: 16px;">No host activity recorded.</div>`;
      } else {
        hostContainer.innerHTML = top_hosts.map(h => `
          <div class="quick-stat-row">
            <div>
              <div class="font-semibold" style="font-size: 0.9rem;">${escapeStr(h.Host)}</div>
            </div>
            <div>
              <span class="badge badge-navy">${h.visit_count} visits</span>
            </div>
          </div>
        `).join('');
      }
    }

  } catch (err) {
    UI.showToast('error', 'Monthly Report Error', err.message);
  }
}

function formatTime(dateTimeStr) {
  if (!dateTimeStr) return '-';
  const dt = new Date(dateTimeStr.replace(' ', 'T'));
  return dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function escapeStr(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
