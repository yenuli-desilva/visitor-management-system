/**
 * Visitors Management Controller
 * Visitor Management System (VMS)
 */

let currentPage = 1;
let currentSearch = '';
let currentDept = '';
let currentStatus = '';
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
  loadDepartmentsDropdown();
  loadVisitors();

  // Search input with debounce
  const searchInput = document.getElementById('visitor-search-input');
  if (searchInput) {
    // Check if query was prefilled via URL param
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('q')) {
      searchInput.value = urlParams.get('q');
      currentSearch = urlParams.get('q');
    }

    searchInput.addEventListener('input', () => {
      clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(() => {
        currentSearch = searchInput.value.trim();
        currentPage = 1;
        loadVisitors();
      }, 300);
    });
  }

  // Department filter
  const deptFilter = document.getElementById('visitor-dept-filter');
  if (deptFilter) {
    deptFilter.addEventListener('change', () => {
      currentDept = deptFilter.value;
      currentPage = 1;
      loadVisitors();
    });
  }

  // Status filter
  const statusFilter = document.getElementById('visitor-status-filter');
  if (statusFilter) {
    statusFilter.addEventListener('change', () => {
      currentStatus = statusFilter.value;
      currentPage = 1;
      loadVisitors();
    });
  }

  // Add Visitor form submit
  const addForm = document.getElementById('add-visitor-form');
  if (addForm) {
    addForm.addEventListener('submit', handleAddVisitor);
  }

  // Edit Visitor form submit
  const editForm = document.getElementById('edit-visitor-form');
  if (editForm) {
    editForm.addEventListener('submit', handleEditVisitor);
  }
});

/**
 * Loads active departments into the filter dropdown and modal selectors.
 */
async function loadDepartmentsDropdown() {
  try {
    const res = await Api.get('server/departments.php');
    const depts = res.data?.departments || [];

    const optionsHtml = depts.map(d => `<option value="${d.DepartmentID}">${escapeStr(d.Name)}</option>`).join('');

    const filterSelect = document.getElementById('visitor-dept-filter');
    if (filterSelect) {
      filterSelect.innerHTML = `<option value="">All Departments</option>` + optionsHtml;
    }

    const addSelect = document.getElementById('add-dept-id');
    if (addSelect) {
      addSelect.innerHTML = `<option value="">Select Department...</option>` + optionsHtml;
    }

    const editSelect = document.getElementById('edit-dept-id');
    if (editSelect) {
      editSelect.innerHTML = `<option value="">Select Department...</option>` + optionsHtml;
    }
  } catch (err) {
    console.error('Failed to load departments:', err);
  }
}

/**
 * Fetches and displays visitors list with active filters and pagination.
 */
async function loadVisitors() {
  const tbody = document.getElementById('visitors-tbody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="8" style="text-align: center; padding: 40px 16px;">
        <span class="spinner" style="border-color: rgba(2,132,199,0.3); border-top-color: var(--accent); width: 28px; height: 28px;"></span>
        <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-muted);">Loading visitors...</div>
      </td>
    </tr>
  `;

  try {
    const params = new URLSearchParams({
      page: currentPage,
      limit: 15,
      q: currentSearch,
      department_id: currentDept,
      status: currentStatus
    });

    const res = await Api.get(`server/visitors.php?${params.toString()}`);
    const { visitors, pagination } = res.data;

    renderVisitorsTable(visitors);
    renderPagination(pagination);
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; color: var(--danger); padding: 32px 16px;">
          Failed to load visitors: ${escapeStr(err.message)}
        </td>
      </tr>
    `;
    UI.showToast('error', 'Load Error', err.message);
  }
}

/**
 * Renders visitor rows in the table.
 */
function renderVisitorsTable(visitors) {
  const tbody = document.getElementById('visitors-tbody');
  if (!tbody) return;

  if (!visitors || visitors.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; padding: 48px 16px;">
          <div class="empty-state">
            <div class="empty-state-icon">
              <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="empty-state-title">No Visitors Found</div>
            <div class="empty-state-desc">Try adjusting your search criteria or register a new visitor.</div>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  // Current user role from data attribute on body
  const isAdminUser = document.body.getAttribute('data-user-role') === 'admin';

  tbody.innerHTML = visitors.map(v => {
    const isCheckedIn = v.VisitStatus === 'checked_in';
    const statusBadge = isCheckedIn
      ? `<span class="badge badge-success"><span class="badge-dot"></span> In Premises</span>`
      : `<span class="badge badge-secondary"><span class="badge-dot"></span> Checked Out</span>`;

    // Action buttons
    let checkActionBtn = '';
    if (isCheckedIn) {
      checkActionBtn = `
        <button type="button" class="btn btn-sm btn-outline-danger btn-checkout" data-visit-id="${v.CurrentVisitID}" data-name="${escapeStr(v.Name)}" title="Check Out">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Check Out
        </button>
      `;
    } else {
      checkActionBtn = `
        <button type="button" class="btn btn-sm btn-teal btn-checkin" data-visitor-id="${v.VisitorID}" data-name="${escapeStr(v.Name)}" title="Check In">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
          Check In
        </button>
      `;
    }

    const editBtn = `
      <button type="button" class="btn btn-sm btn-secondary btn-edit" data-id="${v.VisitorID}" title="Edit Information">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit
      </button>
    `;

    const deleteBtn = isAdminUser ? `
      <button type="button" class="btn btn-sm btn-secondary text-danger btn-delete" data-id="${v.VisitorID}" data-name="${escapeStr(v.Name)}" title="Delete Visitor Record">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </button>
    ` : '';

    return `
      <tr>
        <td>
          <div class="font-semibold">${escapeStr(v.Name)}</div>
          <div class="text-muted" style="font-size: 0.76rem;">NIC: ${escapeStr(v.NIC)}</div>
        </td>
        <td>
          <div>${escapeStr(v.Phone)}</div>
          ${v.Email ? `<div class="text-muted" style="font-size: 0.76rem;">${escapeStr(v.Email)}</div>` : ''}
        </td>
        <td>
          <span class="badge badge-user">${escapeStr(v.DepartmentName || 'General')}</span>
        </td>
        <td>${escapeStr(v.Host)}</td>
        <td style="max-width: 180px; font-size: 0.82rem; color: var(--text-muted);">${escapeStr(v.Purpose)}</td>
        <td>${statusBadge}</td>
        <td>
          <div class="table-actions">
            ${checkActionBtn}
            ${editBtn}
            ${deleteBtn}
          </div>
        </td>
      </tr>
    `;
  }).join('');

  attachActionListeners();
}

/**
 * Attaches event listeners for checkin, checkout, edit, and delete in table.
 */
function attachActionListeners() {
  // Check-In
  document.querySelectorAll('.btn-checkin').forEach(btn => {
    btn.addEventListener('click', async () => {
      const visitorId = btn.getAttribute('data-visitor-id');
      const name = btn.getAttribute('data-name');
      try {
        const res = await Api.post('server/visits.php?action=checkin', { visitor_id: visitorId });
        UI.showToast('success', 'Visitor Checked In', res.message);
        loadVisitors();
      } catch (err) {
        UI.showToast('error', 'Check-In Failed', err.message);
      }
    });
  });

  // Check-Out
  document.querySelectorAll('.btn-checkout').forEach(btn => {
    btn.addEventListener('click', () => {
      const visitId = btn.getAttribute('data-visit-id');
      const name = btn.getAttribute('data-name');
      UI.confirm('Confirm Check Out', `Check out ${name} from the organization premises?`, async () => {
        try {
          const res = await Api.post('server/visits.php?action=checkout', { visit_id: visitId });
          UI.showToast('success', 'Visitor Checked Out', res.message);
          loadVisitors();
        } catch (err) {
          UI.showToast('error', 'Check-Out Failed', err.message);
        }
      }, 'Check Out', 'btn-danger');
    });
  });

  // Edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', async () => {
      const visitorId = btn.getAttribute('data-id');
      await openEditVisitorModal(visitorId);
    });
  });

  // Delete
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      const visitorId = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name');
      UI.confirm(
        'Delete Visitor Record',
        `Are you sure you want to permanently delete ${name} and all associated visit logs? This cannot be undone.`,
        async () => {
          try {
            const res = await Api.delete('server/visitors.php', { visitor_id: visitorId });
            UI.showToast('success', 'Deleted', res.message);
            loadVisitors();
          } catch (err) {
            UI.showToast('error', 'Delete Failed', err.message);
          }
        },
        'Delete Permanently',
        'btn-danger'
      );
    });
  });
}

/**
 * Handles adding a new visitor.
 */
async function handleAddVisitor(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  const payload = {
    name: document.getElementById('add-name').value.trim(),
    nic: document.getElementById('add-nic').value.trim(),
    phone: document.getElementById('add-phone').value.trim(),
    email: document.getElementById('add-email').value.trim(),
    purpose: document.getElementById('add-purpose').value.trim(),
    host: document.getElementById('add-host').value.trim(),
    department_id: document.getElementById('add-dept-id').value,
    check_in_now: document.getElementById('add-checkin-now').checked
  };

  if (!payload.name || !payload.nic || !payload.phone || !payload.purpose || !payload.host || !payload.department_id) {
    UI.showToast('warning', 'Incomplete Form', 'Please complete all required fields.');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span class="spinner"></span> <span>Saving...</span>`;

  try {
    const res = await Api.post('server/visitors.php', payload);
    UI.showToast('success', 'Visitor Added', res.message);
    UI.closeModal('add-visitor-modal');
    form.reset();
    document.getElementById('add-checkin-now').checked = true;
    loadVisitors();
  } catch (err) {
    UI.showToast('error', 'Failed to Add Visitor', err.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Register Visitor';
  }
}

/**
 * Loads visitor data and opens the Edit modal.
 */
async function openEditVisitorModal(visitorId) {
  try {
    const res = await Api.get(`server/visitors.php?id=${visitorId}`);
    const v = res.data?.visitor;
    if (!v) throw new Error('Visitor details not available');

    document.getElementById('edit-visitor-id').value = v.VisitorID;
    document.getElementById('edit-name').value = v.Name;
    document.getElementById('edit-nic').value = v.NIC;
    document.getElementById('edit-phone').value = v.Phone;
    document.getElementById('edit-email').value = v.Email || '';
    document.getElementById('edit-purpose').value = v.Purpose;
    document.getElementById('edit-host').value = v.Host;
    document.getElementById('edit-dept-id').value = v.DepartmentID;

    UI.openModal('edit-visitor-modal');
  } catch (err) {
    UI.showToast('error', 'Unable to Load Details', err.message);
  }
}

/**
 * Handles saving changes to an existing visitor.
 */
async function handleEditVisitor(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  const payload = {
    visitor_id: document.getElementById('edit-visitor-id').value,
    name: document.getElementById('edit-name').value.trim(),
    nic: document.getElementById('edit-nic').value.trim(),
    phone: document.getElementById('edit-phone').value.trim(),
    email: document.getElementById('edit-email').value.trim(),
    purpose: document.getElementById('edit-purpose').value.trim(),
    host: document.getElementById('edit-host').value.trim(),
    department_id: document.getElementById('edit-dept-id').value
  };

  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span class="spinner"></span> <span>Updating...</span>`;

  try {
    const res = await Api.put('server/visitors.php', payload);
    UI.showToast('success', 'Changes Saved', res.message);
    UI.closeModal('edit-visitor-modal');
    loadVisitors();
  } catch (err) {
    UI.showToast('error', 'Update Failed', err.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Save Changes';
  }
}

/**
 * Renders pagination controls.
 */
function renderPagination(pagination) {
  const container = document.getElementById('pagination-container');
  if (!container || !pagination) return;

  const { current_page, total_pages, total_records } = pagination;

  if (total_records === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = `
    <div style="font-size: 0.82rem; color: var(--text-muted);">
      Showing page <strong>${current_page}</strong> of <strong>${Math.max(1, total_pages)}</strong> (${total_records} total visitors)
    </div>
    <div style="display: flex; gap: 8px;">
      <button type="button" class="btn btn-sm btn-secondary" id="page-prev-btn" ${current_page <= 1 ? 'disabled' : ''}>&larr; Previous</button>
      <button type="button" class="btn btn-sm btn-secondary" id="page-next-btn" ${current_page >= total_pages ? 'disabled' : ''}>Next &rarr;</button>
    </div>
  `;

  const prevBtn = document.getElementById('page-prev-btn');
  const nextBtn = document.getElementById('page-next-btn');

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        loadVisitors();
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (currentPage < total_pages) {
        currentPage++;
        loadVisitors();
      }
    });
  }
}

function escapeStr(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
