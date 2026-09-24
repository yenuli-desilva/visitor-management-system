/**
 * Dashboard Controller
 * Visitor Management System (VMS) Frontend
 */

document.addEventListener('DOMContentLoaded', async () => {
  const user = await App.checkAuth();
  if (!user) return;

  App.initSidebar();
  loadDashboardData();
});

async function loadDashboardData() {
  try {
    const res = await App.request('dashboard/get_stats.php');
    const data = res.data;

    // 1. Update KPI Card numbers
    setCardValue('stat-total-visitors', data.total_visitors);
    setCardValue('stat-today-visits', data.today_visits);
    setCardValue('stat-checked-in', data.checked_in);
    setCardValue('stat-completed-visits', data.completed_visits);

    // Admin-only user count card
    if (data.is_admin && data.total_users !== null) {
      const userCard = document.getElementById('card-total-users');
      if (userCard) {
        userCard.style.display = 'flex';
        setCardValue('stat-total-users', data.total_users);
      }
    }

    // 2. Render SVG Visitor Trend Chart
    renderTrendChart(data.trend || []);

    // 3. Render Recent Visitors Table
    renderRecentVisitors(data.recent_visitors || []);

  } catch (err) {
    console.error('Dashboard load error:', err);
    App.showToast('error', 'Dashboard Error', 'Could not load statistics from server.');
  }
}

function setCardValue(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = Number(val).toLocaleString();
}

/**
 * Pure Vanilla SVG Trend Chart
 */
function renderTrendChart(trend) {
  const container = document.getElementById('dashboard-chart-container');
  if (!container) return;

  if (!trend || trend.length === 0) {
    container.innerHTML = '<div class="text-muted" style="padding: 40px; text-align: center;">No activity recorded for this period.</div>';
    return;
  }

  const width = 650;
  const height = 210;
  const padding = { top: 25, right: 30, bottom: 35, left: 35 };

  const chartW = width - padding.left - padding.right;
  const chartH = height - padding.top - padding.bottom;

  const maxVal = Math.max(5, ...trend.map(d => parseInt(d.visit_count, 10)));
  const stepX = chartW / (trend.length - 1 || 1);

  const points = trend.map((d, i) => {
    const x = padding.left + (i * stepX);
    const count = parseInt(d.visit_count, 10);
    const y = padding.top + chartH - ((count / maxVal) * chartH);
    return { x, y, count, date: d.visit_date };
  });

  const pathD = points.reduce((acc, pt, i) => {
    return i === 0 ? `M ${pt.x} ${pt.y}` : `${acc} L ${pt.x} ${pt.y}`;
  }, '');

  const areaD = `${pathD} L ${points[points.length - 1].x} ${padding.top + chartH} L ${points[0].x} ${padding.top + chartH} Z`;

  const formatShortDate = (isoStr) => {
    const dt = new Date(isoStr + 'T00:00:00');
    return dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
  };

  container.innerHTML = `
    <svg viewBox="0 0 ${width} ${height}" style="width: 100%; height: 210px; overflow: visible;" preserveAspectRatio="none">
      <defs>
        <linearGradient id="chartGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#0284c7" stop-opacity="0.3"/>
          <stop offset="100%" stop-color="#0284c7" stop-opacity="0.0"/>
        </linearGradient>
      </defs>

      <line x1="${padding.left}" y1="${padding.top}" x2="${width - padding.right}" y2="${padding.top}" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4,4"/>
      <line x1="${padding.left}" y1="${padding.top + chartH / 2}" x2="${width - padding.right}" y2="${padding.top + chartH / 2}" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4,4"/>
      <line x1="${padding.left}" y1="${padding.top + chartH}" x2="${width - padding.right}" y2="${padding.top + chartH}" stroke="#e2e8f0" stroke-width="1"/>

      <path d="${areaD}" fill="url(#chartGrad)"/>
      <path d="${pathD}" fill="none" stroke="#0284c7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

      ${points.map(pt => `
        <circle cx="${pt.x}" cy="${pt.y}" r="4.5" fill="#ffffff" stroke="#0284c7" stroke-width="3">
          <title>${formatShortDate(pt.date)}: ${pt.count} visits</title>
        </circle>
        <text x="${pt.x}" y="${pt.y - 10}" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">${pt.count}</text>
        <text x="${pt.x}" y="${height - 10}" text-anchor="middle" font-size="11" fill="#64748b">${formatShortDate(pt.date)}</text>
      `).join('')}
    </svg>
  `;
}

/**
 * Recent Visitors Table
 */
function renderRecentVisitors(visits) {
  const tbody = document.getElementById('recent-visitors-tbody');
  if (!tbody) return;

  if (!visits || visits.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="empty-state">
          <div class="empty-state-title">No Recent Visitors</div>
          <div class="empty-state-desc">Newly registered guests will appear here.</div>
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

    const checkInTime = formatTime(v.CheckIn);
    const checkOutTime = v.CheckOut ? formatTime(v.CheckOut) : '<span class="text-muted">-</span>';

    const actionBtn = isCheckedIn
      ? `<button type="button" class="btn btn-sm btn-outline-danger btn-quick-checkout" data-visit-id="${v.VisitID}" data-name="${App.escapeHtml(v.VisitorName)}">Check Out</button>`
      : `<a href="visitors.html?search=${encodeURIComponent(v.NIC)}" class="btn btn-sm btn-secondary">Details</a>`;

    return `
      <tr>
        <td>
          <div class="font-semibold">${App.escapeHtml(v.VisitorName)}</div>
          <div class="text-muted" style="font-size: 0.78rem;">NIC: ${App.escapeHtml(v.NIC)}</div>
        </td>
        <td><span class="badge badge-user">${App.escapeHtml(v.DepartmentName || 'General')}</span></td>
        <td>${App.escapeHtml(v.Host)}</td>
        <td>${checkInTime}</td>
        <td>${checkOutTime}</td>
        <td>${statusBadge}</td>
        <td>${actionBtn}</td>
      </tr>
    `;
  }).join('');

  // Quick checkout buttons
  tbody.querySelectorAll('.btn-quick-checkout').forEach(btn => {
    btn.addEventListener('click', () => {
      const visitId = btn.getAttribute('data-visit-id');
      const name = btn.getAttribute('data-name');
      App.confirm('Confirm Check Out', `Check out ${name} from organizational premises?`, async () => {
        try {
          const res = await App.request('visitors/checkout.php', {
            method: 'POST',
            body: { visit_id: visitId }
          });
          App.showToast('success', 'Checked Out', res.message);
          loadDashboardData();
        } catch (err) {
          App.showToast('error', 'Check Out Failed', err.message);
        }
      }, 'Check Out', 'btn-danger');
    });
  });
}

function formatTime(dtStr) {
  if (!dtStr) return '-';
  const d = new Date(dtStr.replace(' ', 'T'));
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
