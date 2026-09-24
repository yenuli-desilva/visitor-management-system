/**
 * Dashboard Controller & Analytics Chart
 * Visitor Management System (VMS)
 */

document.addEventListener('DOMContentLoaded', () => {
  loadDashboardData();
  startLiveClock();
});

function startLiveClock() {
  const clockEl = document.getElementById('topbar-live-clock');
  if (!clockEl) return;

  const update = () => {
    const now = new Date();
    clockEl.textContent = now.toLocaleDateString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  update();
  setInterval(update, 30000);
}

async function loadDashboardData() {
  try {
    const response = await Api.get('server/dashboard.php');
    const { stats, trend, recent_visits } = response.data;

    // Update KPI counters
    updateCounter('stat-total-visitors', stats.total_visitors);
    updateCounter('stat-today-visitors', stats.today_visitors);
    updateCounter('stat-checked-in', stats.currently_checked_in);
    
    // Update badge in sidebar
    const activeBadge = document.getElementById('nav-checked-in-badge');
    if (activeBadge) {
      activeBadge.textContent = stats.currently_checked_in;
      activeBadge.style.display = stats.currently_checked_in > 0 ? 'inline-block' : 'none';
    }

    if (stats.is_admin && stats.total_users !== null) {
      updateCounter('stat-total-users', stats.total_users);
    }

    // Render Vanilla SVG Trend Chart
    renderTrendChart(trend);

    // Render Recent Visits Table
    renderRecentVisits(recent_visits);

  } catch (err) {
    console.error('Failed to load dashboard data:', err);
    UI.showToast('error', 'Dashboard Error', 'Could not refresh dashboard statistics.');
  }
}

function updateCounter(elementId, targetValue) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = targetValue.toLocaleString();
}

/**
 * Pure Vanilla SVG Trend Chart Renderer
 * Creates a responsive, smooth line & gradient fill chart.
 */
function renderTrendChart(trendData) {
  const container = document.getElementById('trend-chart-container');
  if (!container || !trendData || trendData.length === 0) return;

  const width = 600;
  const height = 200;
  const padding = { top: 25, right: 30, bottom: 35, left: 35 };

  const chartW = width - padding.left - padding.right;
  const chartH = height - padding.top - padding.bottom;

  const maxVal = Math.max(5, ...trendData.map(d => parseInt(d.visit_count, 10)));
  const stepX = chartW / (trendData.length - 1 || 1);

  // Compute points
  const points = trendData.map((d, i) => {
    const x = padding.left + (i * stepX);
    const count = parseInt(d.visit_count, 10);
    const y = padding.top + chartH - ((count / maxVal) * chartH);
    return { x, y, count, date: d.visit_date };
  });

  const pathD = points.reduce((acc, pt, i) => {
    return i === 0 ? `M ${pt.x} ${pt.y}` : `${acc} L ${pt.x} ${pt.y}`;
  }, '');

  const areaD = `${pathD} L ${points[points.length - 1].x} ${padding.top + chartH} L ${points[0].x} ${padding.top + chartH} Z`;

  // Format short date (e.g. Sep 08)
  const formatShortDate = (isoStr) => {
    const dt = new Date(isoStr + 'T00:00:00');
    return dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
  };

  let svgHtml = `
    <svg class="vms-chart-svg" viewBox="0 0 ${width} ${height}" preserveAspectRatio="none">
      <defs>
        <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#0284c7" stop-opacity="0.35"/>
          <stop offset="100%" stop-color="#0284c7" stop-opacity="0.0"/>
        </linearGradient>
      </defs>

      <!-- Grid lines -->
      <line x1="${padding.left}" y1="${padding.top}" x2="${width - padding.right}" y2="${padding.top}" class="chart-grid-line" />
      <line x1="${padding.left}" y1="${padding.top + chartH / 2}" x2="${width - padding.right}" y2="${padding.top + chartH / 2}" class="chart-grid-line" />
      <line x1="${padding.left}" y1="${padding.top + chartH}" x2="${width - padding.right}" y2="${padding.top + chartH}" class="chart-axis-line" />

      <!-- Area and Line -->
      <path d="${areaD}" class="chart-area" />
      <path d="${pathD}" class="chart-line" />

      <!-- Data Dots and Labels -->
      ${points.map(pt => `
        <circle cx="${pt.x}" cy="${pt.y}" r="4.5" class="chart-dot">
          <title>${formatShortDate(pt.date)}: ${pt.count} visitor${pt.count === 1 ? '' : 's'}</title>
        </circle>
        <text x="${pt.x}" y="${pt.y - 10}" class="chart-label-text" font-weight="700">${pt.count}</text>
        <text x="${pt.x}" y="${height - 10}" class="chart-label-text">${formatShortDate(pt.date)}</text>
      `).join('')}
    </svg>
  `;

  container.innerHTML = svgHtml;
}

/**
 * Renders Recent Visits table rows with quick Check-out button.
 */
function renderRecentVisits(visits) {
  const tbody = document.getElementById('recent-visits-tbody');
  if (!tbody) return;

  if (!visits || visits.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center" style="padding: 32px 16px;">
          <div class="empty-state">
            <div class="empty-state-title">No Recent Visits</div>
            <div class="empty-state-desc">New check-ins will automatically show up here.</div>
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

    const checkInTime = formatTime(v.CheckIn);
    const checkOutTime = v.CheckOut ? formatTime(v.CheckOut) : '<span class="text-muted">-</span>';

    const actionBtn = isCheckedIn
      ? `<button type="button" class="btn btn-sm btn-outline-danger quick-checkout-btn" data-visit-id="${v.VisitID}" data-name="${escapeStr(v.VisitorName)}">Check Out</button>`
      : `<a href="visitors.php?q=${encodeURIComponent(v.NIC)}" class="btn btn-sm btn-secondary">Details</a>`;

    return `
      <tr>
        <td>
          <div class="font-semibold">${escapeStr(v.VisitorName)}</div>
          <div class="text-muted" style="font-size: 0.78rem;">NIC: ${escapeStr(v.NIC)}</div>
        </td>
        <td>
          <span class="badge badge-user">${escapeStr(v.DepartmentName || 'General')}</span>
        </td>
        <td>${escapeStr(v.Host)}</td>
        <td>${checkInTime}</td>
        <td>${checkOutTime}</td>
        <td>${statusBadge}</td>
        <td>${actionBtn}</td>
      </tr>
    `;
  }).join('');

  // Attach quick checkout listeners
  tbody.querySelectorAll('.quick-checkout-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const visitId = btn.getAttribute('data-visit-id');
      const visitorName = btn.getAttribute('data-name');

      UI.confirm('Confirm Check Out', `Check out ${visitorName} from the premises?`, async () => {
        try {
          const res = await Api.post('server/visits.php?action=checkout', { visit_id: visitId });
          UI.showToast('success', 'Visitor Checked Out', res.message);
          loadDashboardData();
        } catch (err) {
          UI.showToast('error', 'Action Failed', err.message);
        }
      }, 'Check Out', 'btn-danger');
    });
  });
}

function formatTime(dateTimeStr) {
  if (!dateTimeStr) return '-';
  const dt = new Date(dateTimeStr.replace(' ', 'T'));
  return dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function escapeStr(str) {
  if (!str) return '';
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
