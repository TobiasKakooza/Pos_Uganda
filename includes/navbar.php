<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? [];
$notifCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM notifications WHERE is_read = 0"
)->fetchColumn();
?>


<nav class="sidebar">
<ul class="menu">

  <!-- DASHBOARD (everyone) -->
  <li class="menu-item">
    <a href="/POS_UG/views/dashboard.php" class="menu-link">
      <i data-lucide="layout-dashboard"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <!-- PRODUCTS -->
  <?php if (can('products_manage')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="shopping-cart"></i>
      <span>Products</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/products/list.php">List Products</a></li>
      <!-- <li><a href="/POS_UG/views/products/add.php">Add Product</a></li> -->
    </ul>
  </li>
  <?php endif; ?>

  <!-- SALES -->
  <?php if (can('sales_access')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="credit-card"></i>
      <span>Sales</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/sales/terminal.php">Sales Terminal</a></li>
      <li><a href="/POS_UG/views/sales/history.php">Sales History</a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- INVENTORY -->
  <?php if (can('inventory_view')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="package"></i>
      <span>Inventory</span>
    </a>
    <ul class="submenu">
      <?php if (can('inventory_adjust')): ?>
        <li><a href="/POS_UG/views/inventory/manage.php">Manage Inventory</a></li>
      <?php endif; ?>
      <li><a href="/POS_UG/views/inventory/history.php">Inventory History</a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- CUSTOMERS -->
  <?php if (can('sales_access')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="users"></i>
      <span>Customers</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/customers/list.php">Customer List</a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- SUPPLIERS -->
  <?php if (can('suppliers_manage')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="factory"></i>
      <span>Suppliers</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/suppliers/list.php">Supplier List</a></li>
    </ul>
  </li>
  <?php endif; ?>


  <!-- EXPENSES (ADMIN ONLY) -->
<?php if (can('expenses_manage')): ?>
<li class="menu-item">
  <a href="/POS_UG/views/Expenses/index.php" class="menu-link">
    <i data-lucide="wallet"></i>
    <span>Expenses</span>
  </a>
</li>
<?php endif; ?>

  <!-- REPORTS (ADMIN ONLY – BY PERMISSION) -->
  <?php if (can('reports_view')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="bar-chart-3"></i>
      <span>Reports</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/reports/index.php">Dashboard</a></li>
      <li><a href="/POS_UG/views/reports/stock_levels.php">Stock Levels</a></li>
      <li><a href="/POS_UG/views/reports/stock_valuation.php">Stock Valuation</a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- USERS (ADMIN ONLY) -->
  <?php if (can('users_manage')): ?>
  <li class="menu-item has-sub">
    <a class="menu-link toggle-link">
      <i data-lucide="shield"></i>
      <span>Users</span>
    </a>
    <ul class="submenu">
      <li><a href="/POS_UG/views/users/list.php">User List</a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- PROFILE -->
  <li class="menu-item">
    <a href="/POS_UG/views/users/profile.php" class="menu-link">
      <i data-lucide="user"></i>
      <span>My Profile</span>
    </a>
  </li>

  <!-- LOGOUT -->
  <li class="menu-item">
    <a href="/POS_UG/controllers/logout.php" class="menu-link danger">
      <i data-lucide="log-out"></i>
      <span>Logout</span>
    </a>
  </li>

</ul>
</nav>


<style>
/* =====================================================
   DARK SIDEBAR (MATCH HEADER)
===================================================== */
.sidebar {
  position: fixed;
  top: 64px;
  left: 0;
  bottom: 0;
  width: 220px;
  background: linear-gradient(180deg, #020617, #020617);
  border-right: 1px solid #1f2a44;
  overflow-y: auto;
  z-index: 1100;
}

.menu {
  list-style: none;
  padding: 10px 0;
  margin: 0;
}

.menu-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  color: #e5e7eb;
  text-decoration: none;
  font-size: 14px;
  border-radius: 10px;
  margin: 2px 10px;
  transition: background .15s ease, color .15s ease;
}

.menu-link i {
  width: 18px;
  height: 18px;
  opacity: .8;
}

.menu-link:hover {
  background: #1e293b;
}

.menu-item.has-sub > .menu-link::after {
  content: "▸";
  margin-left: auto;
  opacity: .4;
  transition: transform .2s ease;
}

.menu-item.open > .menu-link::after {
  transform: rotate(90deg);
}

.submenu {
  display: none;
  margin-left: 22px;
}

.menu-item.open > .submenu {
  display: block;
}


.submenu a {
  display: block;
  padding: 8px 14px;
  font-size: 13px;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 8px;
}

.submenu a:hover {
  background: #1e293b;
  color: #e5e7eb;
}

.menu-footer {
  margin: 14px 16px;
  font-size: 13px;
  color: #64748b;
}

.menu-link.danger {
  color: #ef4444;
}

.menu-link.danger:hover {
  background: rgba(239,68,68,.15);
}


/* ===== ARROW ===== */
.has-sub > .menu-link::after {
  content: "▸";
  margin-left: auto;
  transition: transform .2s ease;
  opacity: .7;
}

/* Rotate arrow on hover OR open */
.has-sub:hover > .menu-link::after,
.has-sub.open > .menu-link::after {
  transform: rotate(90deg);
}

/* ===== SUBMENU ===== */
.submenu {
  display: none;
  background: #0b3d91;
}

/* Hover opens submenu */
.has-sub:hover > .submenu {
  display: block;
}

/* Click opens submenu */
.has-sub.open > .submenu {
  display: block;
}

.submenu a {
  display: block;
  padding: 8px 36px;
  font-size: 13px;
  color: #e3f2fd;
}

.submenu a:hover {
  background: #1565c0;
}

/* ===== FOOTER ===== */
.menu-footer {
  margin-top: 16px;
  padding: 12px 18px;
  font-weight: 600;
  font-size: 13px;
  color: #bbdefb;
}

/* BADGE */
.badge {
  background: #e53935;
  color: #fff;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 10px;
}
</style>

<script>
document.querySelectorAll('.toggle-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();

    const parent = link.closest('.has-sub');

    document.querySelectorAll('.has-sub.open').forEach(item => {
      if (item !== parent) item.classList.remove('open');
    });

    parent.classList.toggle('open');
  });
});

/* Close click-open menus when clicking outside */
document.addEventListener('click', () => {
  document.querySelectorAll('.has-sub.open').forEach(item => {
    item.classList.remove('open');
  });
});

/* Prevent sidebar clicks from bubbling */
document.querySelector('.sidebar').addEventListener('click', e => {
  e.stopPropagation();
});
</script>
