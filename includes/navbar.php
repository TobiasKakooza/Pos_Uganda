<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'];

// Count unread notifications
$notifStmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$notifCount = $notifStmt->fetchColumn();
?>
<nav>
  <ul>
    <li><a href="/POS_UG/views/dashboard.php" class="active">🏠 Dashboard</a></li>

    <li class="toggle">
      <a href="#">🛒 Products</a>
      <ul>
        <li><a href="/POS_UG/views/products/list.php">List Products</a></li>
        <li><a href="#" onclick="loadPanelFromNav('/POS_UG/views/products/add.php')">Add Product</a></li>
      </ul>
    </li>

    <li class="toggle">
      <a href="#">💰 Sales</a>
      <ul>
        <li><a href="/POS_UG/views/sales/terminal.php">Sales Terminal</a></li>
        <li><a href="/POS_UG/views/sales/history.php">Sales History</a></li>
        <li><a href="/POS_UG/views/sales/invoice.php">Invoice</a></li>
      </ul>
    </li>

    <li class="toggle">
      <a href="#">📦 Inventory</a>
      <ul>
        <li><a href="/POS_UG/views/inventory/manage.php">Manage Inventory</a></li>
        <li><a href="/POS_UG/views/inventory/history.php">Inventory History</a></li>
      </ul>
    </li>

    <li class="toggle">
      <a href="#">👥 Customers</a>
      <ul>
        <li><a href="/POS_UG/views/customers/list.php">Customer List</a></li>
        <li><a href="/POS_UG/views/customers/profile.php">Customer Profile</a></li>
      </ul>
    </li>

    <li class="toggle">
  <a href="#">🏭 Suppliers</a>
  <ul>
    <li><a href="/POS_UG/views/dashboard.php?view=suppliers/list">Supplier List</a></li>
  </ul>
</li>


    <li class="toggle">
  <a href="#">📊 Reports</a>
  <ul>
    <li><a href="/POS_UG/views/dashboard.php?view=reports">Report (embedded)</a></li>
     <li><a href="/POS_UG/views/dashboard.php?view=reports/stock">Stock Levels</a></li>
     <li><a href="/POS_UG/views/dashboard.php?view=reports/valuation">Stock Valuation</a></li>
    <!-- Optional: keep these direct pages if you still use them -->
    <li><a href="/POS_UG/views/reports/sales_report.php">Sales Report</a></li>
    <li><a href="/POS_UG/views/reports/inventory_status.php">Inventory Status</a></li>
    <li><a href="/POS_UG/views/reports/vat_summary.php">VAT Summary</a></li>
  </ul>
</li>


    <li class="toggle">
      <a href="#">🔐 Users</a>
      <ul>
        <li><a href="/POS_UG/views/users/list.php">User List</a></li>
        <li><a href="#" onclick="loadPanelFromNav('/POS_UG/views/users/add.php')">Add User</a></li>
        <li><a href="/POS_UG/views/users/profile.php">User Profile</a></li>
      </ul>
    </li>

    <!-- Notification Bell -->
   <li class="toggle">
  <a href="#">
    🔔 Notifications
    <?php if ($notifCount > 0): ?>
      <span style="background:red;color:white;border-radius:10px;padding:2px 6px;font-size:12px;">
        <?= $notifCount ?>
      </span>
    <?php endif; ?>
  </a>
  <ul>
    <li><a href="/POS_UG/views/notifications/notifications.php">All Notifications</a></li>
  </ul>
</li>


    <li style="margin-top: 20px; font-weight: bold; padding-left: 14px;">
      <?= htmlspecialchars($user['name']) ?>
    </li>
    <li><a href="/POS_UG/controllers/logout.php">🚪 Logout</a></li>
  </ul>
</nav>

<!-- Notification Dropdown Styling -->
<style>
.notif-dropdown {
  position: absolute;
  right: 10px;
  top: 30px;
  background: #fff;
  width: 300px;
  border: 1px solid #ccc;
  z-index: 1000;
  padding: 10px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  border-radius: 4px;
}
.notif-dropdown .notif-header {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #ddd;
  padding-bottom: 6px;
  margin-bottom: 6px;
}
.notif-dropdown .notif-list {
  max-height: 200px;
  overflow-y: auto;
}
.notif-dropdown .notif-item {
  padding: 5px 0;
  border-bottom: 1px solid #eee;
}
.notif-dropdown .notif-item:last-child {
  border-bottom: none;
}
</style>

<!-- Notification JS -->
<script>
document.getElementById('notifBell').addEventListener('click', function(e) {
  e.preventDefault();
  const dropdown = document.getElementById('notifDropdown');
  dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
});
</script>

<!-- Side Panel Loader (already working) -->
<script>
function ensurePanelContainer() {
  let panel = document.getElementById('panel-right');
  if (!panel) {
    panel = document.createElement('div');
    panel.id = 'panel-right';
    panel.className = 'side-panel';
    document.body.appendChild(panel);
  }
  return panel;
}

function loadPanelFromNav(url) {
  const panel = ensurePanelContainer();
  fetch(url)
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = '<button class="close-btn" onclick="hidePanel()">×</button>' + html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    })
    .catch(err => {
      alert('Unable to load panel.');
      console.error(err);
    });
}

function hidePanel() {
  const panel = document.getElementById('panel-right');
  if (panel) {
    panel.classList.add('hidden');
    panel.innerHTML = '';
    document.body.style.overflow = 'auto';
  }
}
</script>

<!-- Panel Styling -->
<style>
.side-panel {
  position: fixed;
  top: 100px;
  right: 0;
  width: 480px;
  max-width: 100%;
  height: calc(100% - 100px);
  background: #ffffff;
  box-shadow: -2px 0 10px rgba(0,0,0,0.15);
  overflow-y: auto;
  padding: 20px;
  z-index: 999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border-left: 1px solid #ddd;
  animation: slideIn 0.3s ease;
}
.side-panel.hidden {
  display: none;
}
@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0%); opacity: 1; }
}
.close-btn {
  align-self: flex-end;
  font-size: 20px;
  border: none;
  background: none;
  color: #333;
  cursor: pointer;
  margin-bottom: -10px;
}
</style>
