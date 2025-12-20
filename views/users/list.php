<?php 
require_once '../../includes/auth.php';
require_permission('users_manage');
require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="/POS_UG/assets/css/userlist.css">

<main class="users-page">

  <div class="page-header">
    <div>
      <h2>Users Management</h2>
      <p class="subtitle">Manage system users, roles, and permissions</p>
    </div>
    <button class="btn-primary" type="button" onclick="openAddUser()">+ Add User</button>
  </div>

  <div class="card">
    <table class="users-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Role</th>
          <th width="160">Actions</th>
        </tr>
      </thead>
      <tbody id="usersTable">
        <tr>
          <td colspan="4" class="loading">Loading users…</td>
        </tr>
      </tbody>
    </table>
  </div>

</main>

<!-- ================= USER PANEL ================= -->
<div id="userPanel" class="side-panel hidden">
  <div class="panel-header">
    <h3 id="panelTitle">Add User</h3>
    <button class="close-btn" onclick="closeUserPanel()">×</button>
  </div>

  <form id="userForm" class="panel-form">
    <input type="hidden" name="id" id="userId">

    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Role</label>
    <select name="role_id" required>
      <option value="1">Admin</option>
      <option value="2">Cashier</option>
      <option value="3">Inventory Manager</option>
    </select>

    <label>Password <small>(optional)</small></label>
    <input type="password" name="password">

    <button class="btn-primary full">Save User</button>
  </form>
</div>

<!-- ================= PERMISSIONS MODAL ================= -->
<div id="permissionModal" class="modal hidden">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3>User Permissions</h3>
      <button onclick="closePermissions()">×</button>
    </div>

    <form id="permForm" class="permissions-list"></form>

    <div class="modal-actions">
      <button class="btn-primary" onclick="savePermissions()">Save</button>
      <button class="btn-ghost" onclick="closePermissions()">Cancel</button>
    </div>
  </div>
</div>

<!-- ================= SUCCESS / ERROR POPUP ================= -->
<div id="statusPopup" class="status-popup hidden">
  <div class="status-box">
    <div class="status-icon"></div>
    <p id="statusMessage"></p>
  </div>
</div>


<script>
(() => {

const usersTable = document.getElementById('usersTable');
const userPanel  = document.getElementById('userPanel');
const userForm   = document.getElementById('userForm');
const permModal  = document.getElementById('permissionModal');
const permForm   = document.getElementById('permForm');

/* ===== STATUS POPUP ===== */
function showStatus(type, message) {
  const popup = document.getElementById('statusPopup');
  const icon  = popup.querySelector('.status-icon');
  const text  = document.getElementById('statusMessage');

  popup.className = `status-popup ${type}`;
  text.innerText = message;

  popup.classList.remove('hidden');
  setTimeout(() => popup.classList.add('hidden'), 2500);
}

/* ===== LOAD USERS ===== */
fetch('../../controllers/userController.php?action=list')
  .then(r => r.json())
  .then(users => {
    usersTable.innerHTML = users.map(u => `
      <tr>
        <td><strong>${u.name}</strong></td>
        <td>${u.email}</td>
        <td><span class="role-badge ${u.role}">${u.role.replace('_',' ')}</span></td>
        <td>
  <div class="actions">
    <button class="icon-btn" onclick="openEditUser(${u.id})" title="Edit">✏️</button>
    <button class="icon-btn" onclick="openPermissions(${u.id})" title="Permissions">🔐</button>
    <button class="icon-btn danger" onclick="deleteUser(${u.id})" title="Delete">🗑️</button>
  </div>
</td>

      </tr>
    `).join('');
  });

/* ===== ADD / EDIT ===== */
window.openAddUser = () => {
  userForm.reset();
  userId.value = '';
  panelTitle.innerText = 'Add User';
  userPanel.classList.remove('hidden');
};

window.openEditUser = id => {
  fetch(`../../controllers/userController.php?action=get&id=${id}`)
    .then(r => r.json())
    .then(u => {
      openAddUser();
      panelTitle.innerText = 'Edit User';
      userForm.id.value = u.id;
      userForm.name.value = u.name;
      userForm.email.value = u.email;
      userForm.role_id.value = {admin:1,cashier:2,inventory_manager:3}[u.role];
    });
};

window.closeUserPanel = () => userPanel.classList.add('hidden');

userForm.onsubmit = e => {
  e.preventDefault();
  fetch('../../controllers/userController.php?action=save', {
    method: 'POST',
    body: new FormData(userForm)
  })
  .then(r => r.ok ? showStatus('success','User saved successfully') :
                   showStatus('error','Failed to save user'))
  .then(() => setTimeout(()=>location.reload(),1500));
};

/* ===== PERMISSIONS ===== */
window.openPermissions = id => {
  fetch(`../../controllers/userController.php?action=permissions&user_id=${id}`)
    .then(r => r.json())
    .then(perms => {

      const MODULE_ORDER = [
        'Sales',
        'Inventory',
        'Products',
        'Expenses',
        'Suppliers',
        'Reports',
        'Users'
      ];

      /* -------- GROUP PERMISSIONS BY MODULE -------- */
      const grouped = {};
      perms.forEach(p => {
        if (!grouped[p.module]) grouped[p.module] = [];
        grouped[p.module].push(p);
      });

      /* -------- RENDER IN NAVBAR ORDER -------- */
      let html = '';

      MODULE_ORDER.forEach(module => {
        if (!grouped[module]) return;

        html += `
          <div class="perm-module">
            <h4 class="perm-module-title">${module}</h4>
        `;

        grouped[module].forEach(p => {
          html += `
            <div class="perm-row">
              <span>${p.label}</span>

              <label class="switch">
                <input type="checkbox"
                      class="perm-switch"
                      data-id="${p.id}"
                      data-code="${p.code}"
                      ${p.allowed ? 'checked' : ''}>
                <span class="slider"></span>
              </label>
            </div>
          `;
        });

        html += `</div>`;
      });

      permForm.innerHTML = html;
      permForm.dataset.user = id;
      permModal.classList.remove('hidden');
    });
};



window.savePermissions = () => {

  const data = new FormData();
  data.append('user_id', permForm.dataset.user);

  document.querySelectorAll('.perm-switch:checked').forEach(sw => {
    data.append('permissions[]', sw.dataset.id);
  });

  fetch('../../controllers/userController.php?action=save_permissions',{
    method:'POST',
    body: data
  }).then(() => {
    showStatus('success','Permissions updated');
    closePermissions();
  });
};


window.closePermissions = () => permModal.classList.add('hidden');

/* ===== DELETE ===== */
window.deleteUser = id => {
  if(!confirm('Delete this user?')) return;
  fetch(`../../controllers/userController.php?action=delete&id=${id}`)
    .then(()=> {
      showStatus('success','User deleted');
      setTimeout(()=>location.reload(),1500);
    });
};

})();
</script>

<?php require_once '../../includes/footer.php'; ?>
