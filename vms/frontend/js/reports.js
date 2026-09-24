/**
 * Reports Controller (Daily & Monthly Reporting & Print)
 * Visitor Management System (VMS) Frontend
 */

let currentReportMode = 'daily';

document.addEventListener('DOMContentLoaded', async () => {
  const user = await App.checkAuth();
  if (!user) return;

  App.initSidebar();
  initReports();
});

function initReports() {
  const today = new Date();
  const dateStr = today.toISOString().split('T')[0];
  const yearStr = today.getFullYear().toString();
  const monthStr = String(today.getMonth() + 1).padStart(2, '0');

  const dateInput = document.getElementById('report-date-input');
  if (dateInput) dateInput.value = dateStr;

  const yearSelect = document.getElementById('report-year-select');
  if (yearSelect) {
    yearSelect.innerHTML = '';
    for (let y = today.getFullYear(); y >= today.getFullYear() - 5; y--) {
      yearSelect.innerHTML += `<option value="${y}" ${y === today.getFullYear() ? 'selected' : ''}>${y}</option>`;
    }
  }

  const monthSelect = document.getElementById('report-month-select');
  if (monthSelect) monthSelect.value = monthStr;

  // Tab buttons
  const dailyTab = document.getElementById('tab-daily');
  const monthlyTab = document.getElementById('tab-monthly');

  if (dailyTab && monthlyTab) {
    dailyTab.addEventListener('click', () => {
      currentReportMode = 'daily';
      dailyTab.classList.add('active');
      monthlyTab.classList.remove('active');
      document.getElementById('daily-controls').style.display = 'flex';
      document.getElementById('monthly-controls').style.display = 'none';
      document.getElementById('daily-report-section').style.display = 'block';
      document.getElementById('monthly-report-section').style.display = 'none';
      loadDailyReport();
    });

    monthlyTab.addEventListener('click', () => {
      currentReportMode = 'monthly';
      monthlyTab.classList.add('active');
      dailyTab.classList.remove('active');
      document.getElementById('daily-controls').style.display = 'none';
      document.getElementById('monthly-controls').style.display = 'flex';
      document.getElementById('daily-report-section').style.display = 'none';
      document.getElementById('monthly-report-section').style.display = 'block';
      loadMonthlyReport();
    });
  }

  // Generate buttons
  document.getElementById('btn-generate-daily')?.addEventListener('click', loadDailyReport);
  document.getElementById('btn-generate-monthly')?.addEventListener('click', loadMonthlyReport);

  // Print button
  document.getElementById('btn-print-report')?.addEventListener('click', () => {
    window.print();
  });

  // Initial load
  loadDailyReport();
}

async function loadDailyReport() {
  const dateVal = document.getElementById('report-date-input')?.value || '';
  const tbody = document.getElementById('daily-report-tbody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="8" style="text-align:center; padding:32px;">
        <span class="spinner"></span>
        <div style="margin-top:8px; color:var(--text-muted); font-size:0.85rem;">Generating report...</div>
      </td>
    </tr>
  `;

  try {
    const res = await App.request(`reports/daily_report.php?date=${encodeURIComponent(dateVal)}`);
    const data = res.data;

    document.getElementById('daily-stat-total').textContent = data.total_visits;
    document.getElementById('daily-stat-checkedin').textContent = data.checked_in_count;
    document.getElementById('daily-stat-checkedout').textContent = data.checked_out_count;

    const printHeader = document.getElementById('print-meta');
    if (printHeader) {
      printHeader.textContent = `Daily Log Report for ${data.date} | Generated: ${new Date().toLocaleString()}`;
    }

    const visits = data.visitor_details || [];
    if (visits.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="empty-state">
            <div class="empty-state-title">No Visits Recorded</div>
            <div class="empty-state-desc">No entries logged for date: ${data.date}</div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = visits.map(v => {
      const isCheckedIn = v.Status === 'checked-in';
      const statusBadge = isCheckedIn
        ? `<span class="badge badge-success"><span class="badge-dot"></span> In Premises</span>`
        : `<span class="badge badge-secondary"><span class="badge-dot"></span> Checked Out</span>`;

      return `
        <tr>
          <td>
            <div class="font-semibold">${App.escapeHtml(v.VisitorName)}</div>
          </td>
          <td><code>${App.escapeHtml(v.NIC)}</code></td>
          <td>${App.escapeHtml(v.Phone || '-')}</td>
          <td><span class="badge badge-user">${App.escapeHtml(v.DepartmentName || 'General')}</span></td>
          <td>${App.escapeHtml(v.Host || '-')}</td>
          <td style="font-size:0.82rem;">${App.escapeHtml(v.Purpose || '-')}</td>
          <td>${formatTime(v.CheckIn)}</td>
          <td>${v.CheckOut ? formatTime(v.CheckOut) : '-'}</td>
          <td>${statusBadge}</td>
        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:var(--danger); padding:24px;">Failed: ${App.escapeHtml(err.message)}</td></tr>`;
    App.showToast('error', 'Report Error', err.message);
  }
}

async function loadMonthlyReport() {
  const yearVal = document.getElementById('report-year-select')?.value || '';
  const monthVal = document.getElementById('report-month-select')?.value || '';
  const tbody = document.getElementById('monthly-breakdown-tbody');
  const monthParam = `${yearVal}-${monthVal}`;

  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="4" style="text-align:center; padding:32px;">
        <span class="spinner"></span>
      </td>
    </tr>
  `;

  try {
    const res = await App.request(`reports/monthly_report.php?month=${encodeURIComponent(monthParam)}`);
    const data = res.data;

    document.getElementById('monthly-stat-total').textContent = data.total_visits;
    document.getElementById('monthly-stat-unique').textContent = data.summary_information?.unique_visitors || 0;
    document.getElementById('monthly-stat-checkedin').textContent = data.summary_information?.checked_in_count || 0;
    document.getElementById('monthly-stat-checkedout').textContent = data.summary_information?.checked_out_count || 0;

    const printHeader = document.getElementById('print-meta');
    if (printHeader) {
      printHeader.textContent = `Monthly Summary Report for ${data.month} | Generated: ${new Date().toLocaleString()}`;
    }

    const dailyCounts = data.daily_visit_counts || [];
    if (dailyCounts.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="empty-state">No visit activity logged for this month.</td></tr>`;
    } else {
      tbody.innerHTML = dailyCounts.map(d => `
        <tr>
          <td class="font-semibold">${d.VisitDate}</td>
          <td><strong>${d.total_visits}</strong></td>
          <td><span class="badge badge-success">${d.checked_in}</span></td>
          <td><span class="badge badge-secondary">${d.checked_out}</span></td>
        </tr>
      `).join('');
    }

    // Department volume share
    const deptDiv = document.getElementById('monthly-dept-list');
    if (deptDiv) {
      const depts = data.summary_information?.department_breakdown || [];
      if (depts.length === 0) {
        deptDiv.innerHTML = '<div class="text-muted" style="padding:12px 0;">No department visit entries.</div>';
      } else {
        const max = Math.max(1, ...depts.map(d => parseInt(d.visit_count, 10)));
        deptDiv.innerHTML = depts.map(d => {
          const pct = Math.round((d.visit_count / max) * 100);
          return `
            <div style="margin-bottom: 12px;">
              <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
                <span class="font-semibold">${App.escapeHtml(d.DepartmentName || 'General')}</span>
                <span class="font-bold text-accent">${d.visit_count} visits</span>
              </div>
              <div style="height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                <div style="height:100%; width:${pct}%; background:linear-gradient(90deg, var(--accent), var(--teal)); border-radius:3px;"></div>
              </div>
            </div>
          `;
        }).join('');
      }
    }

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--danger); padding:24px;">Failed: ${App.escapeHtml(err.message)}</td></tr>`;
    App.showToast('error', 'Report Error', err.message);
  }
}

function formatTime(dtStr) {
  if (!dtStr) return '-';
  const d = new Date(dtStr.replace(' ', 'T'));
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
