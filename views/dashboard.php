<?php
// views/dashboard.php
include('../includes/auth.php');

$roleId = $_SESSION['role_id'] ?? null;

// CASHIER SHOULD NOT ACCESS ADMIN DASHBOARD
if ($roleId === 2) {
    header('Location: /POS_UG/views/Cashier/index.php');
    exit;
}

// INVENTORY MANAGER REDIRECT
if ($roleId === 3) {
    header('Location: /POS_UG/views/InventoryManager/index.php');
    exit;
}


include('../includes/header.php');
include('../includes/navbar.php');

// --- Simple embed router (keeps your modules opening inside the shell) ---
$view = $_GET['view'] ?? '';
$routes = [
  'suppliers/list' => __DIR__ . '/suppliers/list.php',
  'reports'        => __DIR__ . '/reports/index.php',
  'reports/stock'  => __DIR__ . '/reports/stock_levels.php',
   'reports/valuation'=> __DIR__ . '/reports/stock_valuation.php',

];
$embedHtml = null;
if ($view && isset($routes[$view])) {
  $embed = true;
  ob_start();
  include $routes[$view];
  $embedHtml = ob_get_clean();
}
$user = $_SESSION['user'] ?? ['name'=>'User'];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.css"/>
<style>
 /* =====================================================
   LIGHT DASHBOARD (BLENDS WITH DARK HEADER & SIDEBAR)
===================================================== */

:root{
  /* Light canvas */
  --bg: #f4f6f9;
  --card: #ffffff;

  /* Borders & separators */
  --line: #e5e7eb;

  /* Text */
  --title: #111827;
  --mut: #6b7280;

  /* Brand */
  --brand: #3b82f6;

  /* Soft shadow */
  --shadow: 0 10px 24px rgba(0,0,0,.06);
}

/* ===== PAGE BACKGROUND ===== */
body {
  background: var(--bg);
}

/* ===== MAIN CONTENT WRAPPER ===== */
.container {
  padding: 24px;
}

/* ===== TITLE ===== */
.dash-title {
  font-size: 22px;
  margin: 0 0 12px;
  color: var(--title);
  font-weight: 600;
}

/* ===== QUICK ACTIONS ===== */
.quick {
  display: flex;
  gap: 10px;
  margin: 14px 0 18px;
}

/* ===== BUTTONS ===== */
.btn {
  background: var(--brand);
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 10px;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 6px 14px rgba(59,130,246,.25);
}

.btn:hover {
  filter: brightness(.95);
}

.btn.ghost {
  background: #111827;
  color: #fff;
  box-shadow: 0 6px 14px rgba(0,0,0,.25);
}

/* ===== KPI GRID ===== */
.kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
  gap: 14px;
  margin: 8px 0 20px;
}

.kpi {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 16px;
  box-shadow: var(--shadow);
}

.kpi small {
  color: var(--mut);
  font-size: 13px;
  display: block;
}

.kpi h2 {
  margin: 10px 0 0;
  font-size: 22px;
  color: var(--title);
  font-weight: 600;
}

/* ===== PANELS ===== */
.panels {
  display: grid;
  grid-template-columns: 1.2fr .8fr;
  gap: 14px;
}

.panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 16px;
  box-shadow: var(--shadow);
}

.panel h3 {
  margin: 0 0 12px;
  font-size: 15px;
  color: var(--title);
  font-weight: 600;
}

/* ===== TABLES ===== */
table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 10px;
  border-top: 1px solid var(--line);
}

th {
  background: #f9fafb;
  color: var(--mut);
  font-size: 13px;
  font-weight: 600;
  text-align: left;
}

td {
  color: var(--title);
  font-size: 14px;
}

.right {
  text-align: right;
}

/* ===== GRID BELOW ===== */
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-top: 16px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
  .kpis {
    grid-template-columns: repeat(2, minmax(220px, 1fr));
  }

  .panels {
    grid-template-columns: 1fr;
  }
}

.dash-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.dash-title i {
  width: 22px;
  height: 22px;
  color: #3b82f6; /* brand blue */
  stroke-width: 2;
}

</style>

<div class="container">
  <?php if ($embedHtml !== null): ?>
    <?= $embedHtml ?>
  <?php else: ?>
<h1 class="dash-title">
  <i data-lucide="sparkles"></i>
  Welcome back, <?= htmlspecialchars($user['name']) ?>
</h1>

  <div class="quick">
  <?php if (can('sales_access')): ?>
    <a class="btn" href="/POS_UG/views/sales/terminal.php">Open Sales Terminal</a>
  <?php endif; ?>

  <?php if (can('reports_view')): ?>
    <a class="btn ghost" href="/POS_UG/views/dashboard.php?view=reports">View Reports</a>
  <?php endif; ?>

  <?php if (can('products_manage')): ?>
    <a class="btn ghost" href="/POS_UG/views/products/list.php">Manage Products</a>
  <?php endif; ?>
</div>


<!-- KPIs -->
<div class="kpis" id="kpiRow">

  <!-- 📦 INVENTORY SUMMARY -->
  <div class="kpi">
    <small>Total Products</small>
    <h2 id="kpiInvProducts">0</h2>
  </div>

  <div class="kpi">
    <small>Units in Stock</small>
    <h2 id="kpiInvUnits">0</h2>
  </div>

  <div class="kpi">
    <small>Inventory Cost Value</small>
    <h2 id="kpiInvCost">UGX 0</h2>
  </div>

  <div class="kpi">
    <small>Inventory Selling Value</small>
    <h2 id="kpiInvSell">UGX 0</h2>
  </div>

  <!-- 📊 SALES & OPERATIONS -->
  <div class="kpi"><small>Today Sales</small><h2 id="kpiToday">UGX 0</h2></div>
  <div class="kpi"><small>Today Orders</small><h2 id="kpiTodayOrders">0</h2></div>
  <div class="kpi"><small>MTD Sales</small><h2 id="kpiMTD">UGX 0</h2></div>
  <div class="kpi"><small>Products / Customers</small><h2 id="kpiCounts">0 / 0</h2></div>
  <div class="kpi"><small>Low-Stock Alerts</small><h2 id="kpiLow">0</h2></div>
  <div class="kpi"><small>Suppliers</small><h2 id="kpiSup">0</h2></div>
  <div class="kpi"><small>Receivables</small><h2 id="kpiAR">UGX 0</h2></div>
  <div class="kpi"><small>Payables</small><h2 id="kpiAP">UGX 0</h2></div>

</div>


    <!-- Charts -->
    <div class="panels">
      <div class="panel">
        <h3>Last 14 Days – Sales Trend</h3>
        <canvas id="cTrend" height="110"></canvas>
      </div>
      <div class="panel">
        <h3>MTD Mix – Sales by Category</h3>
        <canvas id="cCat" height="110"></canvas>
      </div>
    </div>

    <!-- Tables -->
    <div class="grid-2">
      <div class="panel">
        <h3>Top Products (MTD)</h3>
        <table>
          <thead><tr><th>Product</th><th class="right">Qty</th><th class="right">Revenue</th></tr></thead>
          <tbody id="topProducts"><tr><td>Loading…</td></tr></tbody>
        </table>
      </div>
      <div class="panel">
        <h3>Recent Sales</h3>
        <table>
          <thead><tr><th>#</th><th>Payment</th><th class="right">Total</th><th class="right">When</th></tr></thead>
          <tbody id="recentSales"><tr><td>Loading…</td></tr></tbody>
        </table>
        <h3 style="margin-top:16px">Low-Stock Alerts</h3>
        <table>
          <thead><tr><th>SKU</th><th>Product</th><th class="right">On Hand</th><th class="right">Min</th></tr></thead>
          <tbody id="lowStock"><tr><td>Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const $ = s => document.querySelector(s);
const fmtUGX = n => 'UGX ' + Number(n||0).toLocaleString();

async function loadInventorySummary(){
  const r = await fetch(
    '/POS_UG/controllers/reportsController.php?action=inventory_summary'
  ).then(x => x.json());

  if (!r.success) return;

  $('#kpiInvProducts').textContent =
    (r.total_products || 0).toLocaleString();

  $('#kpiInvUnits').textContent =
    (r.total_units || 0).toLocaleString();

  $('#kpiInvCost').textContent =
    fmtUGX(r.total_cost_value);

  $('#kpiInvSell').textContent =
    fmtUGX(r.total_selling_value);
}


let chartTrend, chartCat;

async function loadKPIs(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=kpis').then(x=>x.json());
  if (!r.success) return;

  $('#kpiToday').textContent       = fmtUGX(r.today_total);
  $('#kpiTodayOrders').textContent = (r.today_orders||0).toLocaleString();
  $('#kpiMTD').textContent         = fmtUGX(r.mtd_total);
  $('#kpiCounts').textContent      = `${(r.products||0).toLocaleString()} / ${(r.customers||0).toLocaleString()}`;
  $('#kpiLow').textContent         = (r.low_stock||0).toLocaleString();
  $('#kpiSup').textContent         = (r.suppliers||0).toLocaleString();
  $('#kpiAR').textContent          = fmtUGX(r.receivables||0);
  $('#kpiAP').textContent          = fmtUGX(r.payables||0);
}

async function loadTrend(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=sales_trend').then(x=>x.json());
  const rows = r.rows||[];
  const labels = rows.map(x=>x.d);
  const totals = rows.map(x=>Number(x.total||0));

  if (chartTrend) chartTrend.destroy();
  chartTrend = new Chart($('#cTrend'), {
    type:'line',
    data:{ labels, datasets:[{ label:'Sales', data:totals, tension:.25 }] }
  });
}

async function loadCategory(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=sales_by_category').then(x=>x.json());
  const rows = r.rows||[];
  if (chartCat) chartCat.destroy();
  chartCat = new Chart($('#cCat'), {
    type:'doughnut',
    data:{ labels:rows.map(x=>x.name), datasets:[{ data:rows.map(x=>x.revenue) }] }
  });
}

async function loadTopProducts(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=top_products').then(x=>x.json());
  const rows = r.rows||[];
  $('#topProducts').innerHTML = rows.map(x => `
    <tr><td>${x.name}</td><td class="right">${Number(x.qty||0).toLocaleString()}</td><td class="right">${fmtUGX(x.revenue)}</td></tr>
  `).join('') || '<tr><td>No sales</td></tr>';
}

async function loadRecentSales(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=recent_sales').then(x=>x.json());
  const rows = r.rows||[];
  $('#recentSales').innerHTML = rows.map(x => `
    <tr><td>#${x.id}</td><td>${x.payment_type||''}</td><td class="right">${fmtUGX(x.total_amount)}</td><td class="right">${x.created_at}</td></tr>
  `).join('');
}

async function loadLowStock(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=low_stock').then(x=>x.json());
  const rows = r.rows||[];
  $('#lowStock').innerHTML = rows.map(x=>`
    <tr><td>${x.sku||''}</td><td>${x.name}</td><td class="right">${x.on_hand||0}</td><td class="right">${x.stock_alert_threshold||0}</td></tr>
  `).join('') || '<tr><td>No alerts</td></tr>';
}

(async function init(){
  await Promise.all([
    loadInventorySummary(), // ✅ NEW (BUSINESS WORTH)
    loadKPIs(),
    loadTrend(),
    loadCategory(),
    loadTopProducts(),
    loadRecentSales(),
    loadLowStock()
  ]);
})();

</script>
<script>
  lucide.createIcons();
</script>
