const loginCard = document.getElementById('loginCard');
const adminPanel = document.getElementById('adminPanel');
const logoutBtn = document.getElementById('logoutBtn');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');
const refreshBtn = document.getElementById('refreshBtn');
const adminTableBody = document.querySelector('#adminTable tbody');
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
const editForm = document.getElementById('editForm');
const editError = document.getElementById('editError');
let tableInstance = null;

function getToken() {
  return localStorage.getItem('admin_token');
}

function setToken(token) {
  localStorage.setItem('admin_token', token);
}

function clearToken() {
  localStorage.removeItem('admin_token');
}

function showLogin() {
  loginCard.classList.remove('d-none');
  adminPanel.classList.add('d-none');
  logoutBtn.classList.add('d-none');
}

function showAdminPanel() {
  loginCard.classList.add('d-none');
  adminPanel.classList.remove('d-none');
  logoutBtn.classList.remove('d-none');
  loadAdminData();
}

function handleLogin(event) {
  event.preventDefault();
  loginError.classList.add('d-none');
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  fetch('/api/admin/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  })
    .then(async res => {
      if (!res.ok) {
        const body = await res.json();
        throw new Error(body.error || 'Login gagal');
      }
      return res.json();
    })
    .then(data => {
      setToken(data.token);
      showAdminPanel();
    })
    .catch(err => {
      loginError.textContent = err.message;
      loginError.classList.remove('d-none');
    });
}

function loadAdminData() {
  const token = getToken();
  if (!token) {
    showLogin();
    return;
  }

  fetch('/api/monitoring', {
    headers: { Authorization: `Bearer ${token}` }
  })
    .then(async res => {
      if (!res.ok) {
        throw new Error('Gagal memuat data admin. Coba login ulang.');
      }
      return res.json();
    })
    .then(data => {
      renderAdminTable(data);
    })
    .catch(() => {
      clearToken();
      showLogin();
    });
}

function renderAdminTable(data) {
  adminTableBody.innerHTML = '';
  data.forEach(record => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${record.id}</td>
      <td>${record.Project_Code || '-'}</td>
      <td>${record.PR_No || '-'}</td>
      <td>${record.PO_No || '-'}</td>
      <td>${record.PR_Status || ''}</td>
      <td>${record.PO_Payment || ''}</td>
      <td>${record.ETA || ''}</td>
      <td>${record.Remark || ''}</td>
      <td><button class="btn btn-sm btn-primary edit-btn" data-id="${record.id}">Edit</button></td>
    `;
    adminTableBody.appendChild(row);
  });

  if ($.fn.DataTable.isDataTable('#adminTable')) {
    $('#adminTable').DataTable().destroy();
  }
  tableInstance = $('#adminTable').DataTable({
    responsive: true,
    pageLength: 25,
    language: {
      search: 'Cari:',
      lengthMenu: 'Tampilkan _MENU_ entri',
      info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
      paginate: {
        first: 'Pertama',
        last: 'Terakhir',
        next: 'Berikutnya',
        previous: 'Sebelumnya'
      }
    },
    columnDefs: [{ orderable: false, targets: 8 }]
  });

  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const row = data.find(item => item.id.toString() === id);
      openEditModal(row);
    });
  });
}

function openEditModal(record) {
  if (!record) return;
  document.getElementById('editId').value = record.id;
  document.getElementById('editProjectCode').value = record.Project_Code || '';
  document.getElementById('editPrNo').value = record.PR_No || '';
  document.getElementById('editPoNo').value = record.PO_No || '';
  document.getElementById('editPrStatus').value = record.PR_Status || '';
  document.getElementById('editPoPayment').value = record.PO_Payment || '';
  document.getElementById('editEta').value = record.ETA || '';
  document.getElementById('editRemark').value = record.Remark || '';
  editError.classList.add('d-none');
  editModal.show();
}

function submitEdit(event) {
  event.preventDefault();
  const token = getToken();
  if (!token) {
    clearToken();
    showLogin();
    return;
  }

  const id = document.getElementById('editId').value;
  const body = {
    PR_Status: document.getElementById('editPrStatus').value.trim(),
    PO_Payment: document.getElementById('editPoPayment').value.trim(),
    ETA: document.getElementById('editEta').value.trim(),
    Remark: document.getElementById('editRemark').value.trim()
  };

  fetch(`/api/monitoring/${id}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(body)
  })
    .then(async res => {
      if (!res.ok) {
        const json = await res.json().catch(() => ({}));
        throw new Error(json.error || 'Gagal menyimpan perubahan.');
      }
      return res.json();
    })
    .then(() => {
      editModal.hide();
      loadAdminData();
    })
    .catch(err => {
      editError.textContent = err.message;
      editError.classList.remove('d-none');
    });
}

function handleLogout() {
  clearToken();
  showLogin();
}

loginForm.addEventListener('submit', handleLogin);
refreshBtn.addEventListener('click', loadAdminData);
logoutBtn.addEventListener('click', handleLogout);
editForm.addEventListener('submit', submitEdit);

if (getToken()) {
  showAdminPanel();
} else {
  showLogin();
}
