/**
 * Visitors Controller (List, Search, Filter, Check-In, Check-Out, Add & Edit)
 * Visitor Management System (VMS) Frontend
 */

let debounceTimer = null;
let currentUserProfile = null;

document.addEventListener('DOMContentLoaded', async () => {
  currentUserProfile = await App.checkAuth();
  if (!currentUserProfile) return;

  App.initSidebar();

  // Determine current page context
  const path = window.location.pathname.split('/').pop();

  if (path === 'visitors.html') {
    initVisitorsDirectory();
  } else if (path === 'add-visitor.html') {
    initAddVisitorPage();
  } else if (path === 'edit-visitor.html') {
    initEditVisitorPage();
  }
});

// ----------------------------------------------------
// 1. VISITORS DIRECTORY PAGE
// ----------------------------------------------------
function initVisitorsDirectory() {
  loadDepartmentFilter();
  loadVisitors();

  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) {
      searchInput.value = urlParams.get('search');
    }

    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(loadVisitors, 300);
    });
  }

  const deptFilter = document.getElementById('filter-department');
  if (deptFilter) deptFilter.addEventListener('change', loadVisitors);

  const statusFilter = document.getElementById('filter-status');
  if (statusFilter) statusFilter.addEventListener('change', loadVisitors);

  const dateFilter = document.getElementById('filter-date');
  if (dateFilter) dateFilter.addEventListener('change', loadVisitors);
}

async function loadDepartmentFilter() {
  try {
    const res = await App.request('departments/get_departments.php');
    const depts = res.data?.departments || [];
    const select = document.getElementById('filter-department');
    if (select) {
      select.innerHTML = '<option value="">All Departments</option>' + 
        depts.map(d => `<option value="${d.DepartmentID}">${App.escapeHtml(d.Name)}</option>`).join('');
    }
  } catch (e) {
    console.error('Failed to load departments filter:', e);
  }
}

async function loadVisitors() {
  const tbody = document.getElementById('visitors-tbody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="11" style="text-align:center; padding: 40px;">
        <span class="spinner"></span>
        <div style="margin-top: 8px; color: var(--text-muted); font-size: 0.85rem;">Loading visitors...</div>
      </td>
    </tr>
  `;

  const search = document.getElementById('search-input')?.value.trim() || '';
  const dept = document.getElementById('filter-department')?.value || '';
  const status = document.getElementById('filter-status')?.value || '';
  const date = document.getElementById('filter-date')?.value || '';

  const params = new URLSearchParams();
  if (search) params.append('search', search);
  if (dept) params.append('department_id', dept);
  if (status) params.append('status', status);
  if (date) params.append('date', date);

  try {
    const res = await App.request(`visitors/get_visitors.php?${params.toString()}`);
    const visitors = res.data?.visitors || [];
    renderVisitorsTable(visitors);
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" style="text-align:center; color:var(--danger); padding:32px;">
          Failed to load visitors: ${App.escapeHtml(err.message)}
        </td>
      </tr>
    `;
    App.showToast('error', 'Error', err.message);
  }
}

function renderVisitorsTable(visitors) {
  const tbody = document.getElementById('visitors-tbody');
  if (!tbody) return;

  // Compute live directory KPIs
  const totalCount = visitors.length;
  const checkedInCount = visitors.filter(v => v.VisitStatus === 'checked-in').length;
  const checkedOutCount = visitors.filter(v => v.VisitStatus === 'checked-out').length;
  
  const todayStr = new Date().toISOString().split('T')[0];
  const todayCount = visitors.filter(v => {
    const d = v.CheckInTime || v.CreatedAt || '';
    return d.includes(todayStr);
  }).length;

  const elTotal = document.getElementById('dir-stat-total'); if (elTotal) elTotal.textContent = totalCount;
  const elIn = document.getElementById('dir-stat-checkedin'); if (elIn) elIn.textContent = checkedInCount;
  const elToday = document.getElementById('dir-stat-today'); if (elToday) elToday.textContent = todayCount;
  const elOut = document.getElementById('dir-stat-checkedout'); if (elOut) elOut.textContent = checkedOutCount;
  const elRec = document.getElementById('table-record-count'); if (elRec) elRec.textContent = `${totalCount} Record${totalCount === 1 ? '' : 's'}`;

  if (visitors.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" class="empty-state">
          <div class="empty-state-title">No Visitors Found</div>
          <div class="empty-state-desc">Try modifying your search or register a new guest.</div>
        </td>
      </tr>
    `;
    return;
  }

  const isAdmin = currentUserProfile && currentUserProfile.role === 'admin';

  tbody.innerHTML = visitors.map(v => {
    const isCheckedIn = v.VisitStatus === 'checked-in';
    const statusBadge = isCheckedIn
      ? `<span class="badge badge-success"><span class="badge-dot"></span> In Premises</span>`
      : `<span class="badge badge-secondary"><span class="badge-dot"></span> Checked Out</span>`;

    const checkInBtn = !isCheckedIn
      ? `<button type="button" class="btn btn-sm btn-teal btn-action-checkin" data-id="${v.VisitorID}" data-name="${App.escapeHtml(v.Name)}" title="Check In">Check-In</button>`
      : '';

    const checkOutBtn = isCheckedIn
      ? `<button type="button" class="btn btn-sm btn-outline-danger btn-action-checkout" data-visit-id="${v.CurrentVisitID}" data-name="${App.escapeHtml(v.Name)}" title="Check Out">Check-Out</button>`
      : '';

    const editBtn = `<a href="edit-visitor.html?id=${v.VisitorID}" class="btn btn-sm btn-secondary" title="Edit Visitor">Edit</a>`;

    const deleteBtn = isAdmin
      ? `<button type="button" class="btn btn-sm btn-secondary text-danger btn-action-delete" data-id="${v.VisitorID}" data-name="${App.escapeHtml(v.Name)}" title="Delete Visitor">&times;</button>`
      : '';

    const viewBtn = `<button type="button" class="btn btn-sm btn-secondary btn-action-view" data-id="${v.VisitorID}" title="View Details">View</button>`;

    return `
      <tr>
        <td>#${v.VisitorID}</td>
        <td>
          <div class="font-semibold">${App.escapeHtml(v.Name)}</div>
        </td>
        <td><code>${App.escapeHtml(v.NIC)}</code></td>
        <td>${App.escapeHtml(v.Phone || '-')}</td>
        <td style="max-width:160px; font-size:0.82rem; color:var(--text-muted);">${App.escapeHtml(v.Purpose || '-')}</td>
        <td>${App.escapeHtml(v.Host || '-')}</td>
        <td><span class="badge badge-user">${App.escapeHtml(v.DepartmentName || 'General')}</span></td>
        <td>${formatDateTime(v.CheckIn)}</td>
        <td>${v.CheckOut ? formatDateTime(v.CheckOut) : '<span class="text-muted">-</span>'}</td>
        <td>${statusBadge}</td>
        <td>
          <div class="table-actions">
            ${viewBtn}
            ${checkInBtn}
            ${checkOutBtn}
            ${editBtn}
            ${deleteBtn}
          </div>
        </td>
      </tr>
    `;
  }).join('');

  attachVisitorActions();
}

function attachVisitorActions() {
  // Check-In
  document.querySelectorAll('.btn-action-checkin').forEach(btn => {
    btn.addEventListener('click', async () => {
      const visitorId = btn.getAttribute('data-id');
      try {
        const res = await App.request('visitors/checkin.php', {
          method: 'POST',
          body: { visitor_id: visitorId }
        });
        App.showToast('success', 'Checked In', res.message);
        loadVisitors();
      } catch (err) {
        App.showToast('error', 'Check In Failed', err.message);
      }
    });
  });

  // Check-Out
  document.querySelectorAll('.btn-action-checkout').forEach(btn => {
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
          loadVisitors();
        } catch (err) {
          App.showToast('error', 'Check Out Failed', err.message);
        }
      }, 'Check Out', 'btn-danger');
    });
  });

  // Delete
  document.querySelectorAll('.btn-action-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      const visitorId = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name');
      App.confirm('Delete Visitor Record', `Permanently delete ${name} and all associated visit records?`, async () => {
        try {
          const res = await App.request('visitors/delete_visitor.php', {
            method: 'POST',
            body: { visitor_id: visitorId }
          });
          App.showToast('success', 'Deleted', res.message);
          loadVisitors();
        } catch (err) {
          App.showToast('error', 'Delete Failed', err.message);
        }
      }, 'Delete Permanently', 'btn-danger');
    });
  });

  // View Details Modal
  document.querySelectorAll('.btn-action-view').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      try {
        const res = await App.request(`visitors/get_visitor.php?id=${id}`);
        const v = res.data?.visitor;
        if (!v) return;

        let modal = document.getElementById('visitor-detail-modal');
        if (!modal) {
          modal = document.createElement('div');
          modal.id = 'visitor-detail-modal';
          modal.className = 'modal-overlay';
          document.body.appendChild(modal);
        }

        const visitsHtml = (v.visits || []).map(vt => `
          <tr>
            <td>${formatDateTime(vt.CheckIn)}</td>
            <td>${vt.CheckOut ? formatDateTime(vt.CheckOut) : 'In Premises'}</td>
            <td><span class="badge ${vt.Status === 'checked-in' ? 'badge-success' : 'badge-secondary'}">${vt.Status}</span></td>
          </tr>
        `).join('') || '<tr><td colspan="3" class="text-muted">No historical visits.</td></tr>';

        modal.innerHTML = `
          <div class="modal-dialog" style="max-width: 600px;">
            <div class="modal-header">
              <h3 class="modal-title">Visitor Record #${v.VisitorID}</h3>
              <button type="button" class="modal-close" onclick="App.closeModal('visitor-detail-modal')">&times;</button>
            </div>
            <div class="modal-body">
              <div style="margin-bottom: 16px;">
                <h2 style="font-size: 1.3rem;">${App.escapeHtml(v.Name)}</h2>
                <div class="text-muted">NIC: ${App.escapeHtml(v.NIC)} | Phone: ${App.escapeHtml(v.Phone || '-')} | Email: ${App.escapeHtml(v.Email || '-')}</div>
              </div>
              <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                <div><strong>Visiting Department:</strong> ${App.escapeHtml(v.DepartmentName || 'General')}</div>
                <div><strong>Host Employee:</strong> ${App.escapeHtml(v.Host || '-')}</div>
                <div><strong>Purpose:</strong> ${App.escapeHtml(v.Purpose || '-')}</div>
              </div>
              <h4 style="margin-bottom: 8px;">Visit History Log</h4>
              <div class="table-responsive">
                <table class="data-table" style="font-size: 0.82rem;">
                  <thead>
                    <tr><th>Check-In</th><th>Check-Out</th><th>Status</th></tr>
                  </thead>
                  <tbody>${visitsHtml}</tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="App.closeModal('visitor-detail-modal')">Close</button>
            </div>
          </div>
        `;
        App.openModal('visitor-detail-modal');
      } catch (err) {
        App.showToast('error', 'Error', err.message);
      }
    });
  });
}

// ----------------------------------------------------
// 2. ADD VISITOR PAGE
// ----------------------------------------------------
async function initAddVisitorPage() {
  let cachedDepts = [];
  let cachedHosts = [];

  // Populate departments dropdown
  try {
    const res = await App.request('departments/get_departments.php');
    cachedDepts = res.data?.departments || [];
    const deptSelect = document.getElementById('visitor-department');
    if (deptSelect) {
      deptSelect.innerHTML = '<option value="">Select Department...</option>' + 
        cachedDepts.map(d => `<option value="${d.DepartmentID}">${App.escapeHtml(d.Name)}</option>`).join('');
    }
  } catch (err) {}

  // Populate hosts dropdown
  try {
    const hRes = await App.request('hosts/get_hosts.php');
    cachedHosts = hRes.data?.hosts || [];
    const hostSelect = document.getElementById('visitor-host');
    if (hostSelect) {
      hostSelect.innerHTML = '<option value="">Select Host Officer to Meet...</option>' +
        cachedHosts.map(h => `
          <option value="${App.escapeHtml(h.Name)}" data-dept-id="${h.DepartmentID}">
            ${App.escapeHtml(h.Name)} (${App.escapeHtml(h.DepartmentName)} - ${App.escapeHtml(h.Designation)})
          </option>
        `).join('');

      hostSelect.addEventListener('change', () => {
        const opt = hostSelect.options[hostSelect.selectedIndex];
        const deptSelect = document.getElementById('visitor-department');
        if (opt && opt.dataset.deptId && deptSelect) {
          deptSelect.value = opt.dataset.deptId;
        }
      });
    }
  } catch (err) {}

  const form = document.getElementById('add-visitor-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');

      const payload = {
        name: document.getElementById('visitor-name').value.trim(),
        nic: document.getElementById('visitor-nic').value.trim(),
        phone: document.getElementById('visitor-phone').value.trim(),
        email: document.getElementById('visitor-email').value.trim(),
        purpose: document.getElementById('visitor-purpose').value.trim(),
        host: document.getElementById('visitor-host').value.trim(),
        department_id: document.getElementById('visitor-department').value
      };

      if (!payload.name || !payload.nic || !payload.host) {
        App.showToast('error', 'Validation Error', 'Full Name, NIC, and Host Officer are required.');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner"></span> Registering...`;

      try {
        const res = await App.request('visitors/add_visitor.php', {
          method: 'POST',
          body: payload
        });
        App.showToast('success', 'Visitor Registered', res.message);
        setTimeout(() => {
          window.location.href = 'visitors.html';
        }, 800);
      } catch (err) {
        App.showToast('error', 'Registration Failed', err.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Register Visitor';
      }
    });
  }
}

// ----------------------------------------------------
// 3. EDIT VISITOR PAGE
// ----------------------------------------------------
async function initEditVisitorPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const visitorId = urlParams.get('id');

  if (!visitorId) {
    App.showToast('error', 'Error', 'No visitor ID provided.');
    window.location.href = 'visitors.html';
    return;
  }

  // Populate departments
  let depts = [];
  try {
    const dRes = await App.request('departments/get_departments.php');
    depts = dRes.data?.departments || [];
    const select = document.getElementById('edit-visitor-department');
    if (select) {
      select.innerHTML = '<option value="">Select Department...</option>' + 
        depts.map(d => `<option value="${d.DepartmentID}">${App.escapeHtml(d.Name)}</option>`).join('');
    }
  } catch (err) {}

  // Populate hosts
  let hosts = [];
  try {
    const hRes = await App.request('hosts/get_hosts.php');
    hosts = hRes.data?.hosts || [];
    const hostSelect = document.getElementById('edit-visitor-host');
    if (hostSelect) {
      hostSelect.innerHTML = '<option value="">Select Host Officer...</option>' +
        hosts.map(h => `
          <option value="${App.escapeHtml(h.Name)}" data-dept-id="${h.DepartmentID}">
            ${App.escapeHtml(h.Name)} (${App.escapeHtml(h.DepartmentName)} - ${App.escapeHtml(h.Designation)})
          </option>
        `).join('');

      hostSelect.addEventListener('change', () => {
        const opt = hostSelect.options[hostSelect.selectedIndex];
        const deptSelect = document.getElementById('edit-visitor-department');
        if (opt && opt.dataset.deptId && deptSelect) {
          deptSelect.value = opt.dataset.deptId;
        }
      });
    }
  } catch (err) {}

  // Load existing visitor data
  try {
    const res = await App.request(`visitors/get_visitor.php?id=${visitorId}`);
    const v = res.data?.visitor;
    if (!v) throw new Error('Visitor not found');

    document.getElementById('edit-visitor-id').value = v.VisitorID;
    document.getElementById('edit-visitor-name').value = v.Name || '';
    document.getElementById('edit-visitor-nic').value = v.NIC || '';
    document.getElementById('edit-visitor-phone').value = v.Phone || '';
    document.getElementById('edit-visitor-email').value = v.Email || '';
    document.getElementById('edit-visitor-purpose').value = v.Purpose || '';
    
    const hostSelect = document.getElementById('edit-visitor-host');
    if (hostSelect) {
      hostSelect.value = v.Host || '';
      // If host wasn't found in options, add it
      if (v.Host && hostSelect.selectedIndex <= 0) {
        const customOpt = document.createElement('option');
        customOpt.value = v.Host;
        customOpt.textContent = `${v.Host} (Assigned)`;
        customOpt.selected = true;
        hostSelect.appendChild(customOpt);
      }
    }

    document.getElementById('edit-visitor-department').value = v.DepartmentID || '';
  } catch (err) {
    App.showToast('error', 'Error', err.message);
    setTimeout(() => window.location.href = 'visitors.html', 1500);
    return;
  }

  const form = document.getElementById('edit-visitor-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');

      const payload = {
        visitor_id: document.getElementById('edit-visitor-id').value,
        name: document.getElementById('edit-visitor-name').value.trim(),
        nic: document.getElementById('edit-visitor-nic').value.trim(),
        phone: document.getElementById('edit-visitor-phone').value.trim(),
        email: document.getElementById('edit-visitor-email').value.trim(),
        purpose: document.getElementById('edit-visitor-purpose').value.trim(),
        host: document.getElementById('edit-visitor-host').value.trim(),
        department_id: document.getElementById('edit-visitor-department').value
      };

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner"></span> Saving...`;

      try {
        const res = await App.request('visitors/update_visitor.php', {
          method: 'POST',
          body: payload
        });
        App.showToast('success', 'Changes Saved', res.message);
        setTimeout(() => {
          window.location.href = 'visitors.html';
        }, 800);
      } catch (err) {
        App.showToast('error', 'Update Failed', err.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
      }
    });
  }
}

function formatDateTime(dtStr) {
  if (!dtStr) return '-';
  const d = new Date(dtStr.replace(' ', 'T'));
  return d.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
