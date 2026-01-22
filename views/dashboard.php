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
   DASHBOARD DESIGN SYSTEM
   (Scoped, clean, premium)
===================================================== */

/* =======================
   DASHBOARD TOKENS
======================= */
:root {
  --dash-bg: var(--bg-app, #f4f6f9);
  --dash-card: var(--bg-panel, #ffffff);
  --dash-border: var(--border, #e5e7eb);

  --dash-title: var(--text-main, #0f172a);
  --dash-text: var(--text-main, #334155);
  --dash-muted: var(--text-muted, #64748b);

  --dash-brand: var(--primary, #2563eb);
  --dash-brand-soft: color-mix(in srgb, var(--dash-brand) 15%, transparent);

  --dash-radius: 16px;

  --dash-shadow-sm: 0 6px 16px rgba(0,0,0,.08);
  --dash-shadow-md: 0 20px 50px rgba(0,0,0,.14);
}

body[data-theme="dark"] {
  --dash-bg: #020617;
  --dash-card: #0f172a;
  --dash-border: #1e293b;

  --dash-title: #e5e7eb;
  --dash-text: #cbd5e1;
  --dash-muted: #94a3b8;

  --dash-shadow-sm: 0 10px 30px rgba(0,0,0,.6);
  --dash-shadow-md: 0 30px 80px rgba(0,0,0,.75);
}

/* =======================
   DASHBOARD ROOT
======================= */
.dashboard-page,
.container {
  background: var(--dash-bg);
  padding: 24px;
  max-width: 1600px;
  margin-inline: auto;
}

/* =======================
   PAGE HEADER
======================= */
.dash-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 24px;
  font-weight: 600;
  color: var(--dash-title);
  margin-bottom: 18px;
}

.dash-title i {
  width: 22px;
  height: 22px;
  color: var(--dash-brand);
}

/* =======================
   QUICK ACTIONS
======================= */
.quick {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 26px;
}

/* =======================
   KPI GRID
======================= */
.kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.kpi {
  background: linear-gradient(
    180deg,
    var(--dash-card),
    color-mix(in srgb, var(--dash-card) 92%, black)
  );
  border-radius: var(--dash-radius);
  padding: 18px 18px 22px;
  border: 1px solid var(--dash-border);
  box-shadow: var(--dash-shadow-sm);
  transition: transform .18s ease, box-shadow .18s ease;
}

.kpi:hover {
  transform: translateY(-3px);
  box-shadow: var(--dash-shadow-md);
}

.kpi small {
  font-size: 12px;
  letter-spacing: .3px;
  color: var(--dash-muted);
  text-transform: uppercase;
}

.kpi h2 {
  margin-top: 10px;
  font-size: 22px;
  font-weight: 600;
  color: var(--dash-title);
}

/* =======================
   CHART GRID
======================= */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 28px;
}

/* =======================
   PANELS
======================= */
.panel {
  background: linear-gradient(
    180deg,
    var(--dash-card),
    color-mix(in srgb, var(--dash-card) 92%, black)
  );
  border-radius: var(--dash-radius);
  border: 1px solid var(--dash-border);
  box-shadow: var(--dash-shadow-sm);
  padding: 18px;
  display: flex;
  flex-direction: column;
  min-height: 360px;
  transition: transform .18s ease, box-shadow .18s ease;
}

.panel:hover {
  transform: translateY(-3px);
  box-shadow: var(--dash-shadow-md);
}

.panel h3 {
  font-size: 15px;
  font-weight: 600;
  color: var(--dash-title);
  margin-bottom: 12px;
}

/* =======================
   CHART WRAPPER
======================= */
.chart-wrap {
  position: relative;
  width: 100%;
  height: 280px;
}

.chart-wrap.tall {
  height: 360px;
}

.chart-wrap canvas {
  width: 100% !important;
  height: 100% !important;
  filter: drop-shadow(0 6px 16px rgba(0,0,0,.15));
}

/* =======================
   TABLE GRID
======================= */
.grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-top: 28px;
}

/* =======================
   TABLES
======================= */
table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead th {
  background: color-mix(in srgb, var(--dash-card) 90%, black);
  font-size: 12px;
  letter-spacing: .3px;
  color: var(--dash-muted);
  font-weight: 600;
  padding: 12px;
  border-bottom: 1px solid var(--dash-border);
}

tbody td {
  padding: 12px;
  font-size: 14px;
  color: var(--dash-text);
  border-bottom: 1px solid var(--dash-border);
}

tbody tr:hover {
  background: var(--dash-brand-soft);
}

.right { text-align: right; }

/* =====================================================
   DASHBOARD TABLES – PREMIUM DARK/LIGHT
   (Stripe / Linear / Vercel quality)
===================================================== */

/* Table container normalization */
.panel table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  overflow: hidden;
  border-radius: 12px;
  background: transparent;
}

/* =======================
   TABLE HEADER
======================= */
.panel thead th {
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--dash-card) 96%, black),
    color-mix(in srgb, var(--dash-card) 90%, black)
  );
  color: var(--dash-muted);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .4px;
  text-transform: uppercase;
  padding: 14px 14px;
  border-bottom: 1px solid var(--dash-border);
  text-align: left;
}

/* Align numeric headers */
.panel thead th.right {
  text-align: right;
}

/* =======================
   TABLE BODY
======================= */
.panel tbody tr {
  background: transparent;
  transition: background .18s ease, transform .12s ease;
}

.panel tbody td {
  padding: 14px 14px;
  font-size: 14px;
  color: var(--dash-text);
  border-bottom: 1px solid var(--dash-border);
  white-space: nowrap;
}

/* Numeric columns */
.panel tbody td.right {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* =======================
   ROW HOVER (SUBTLE)
======================= */
.panel tbody tr:hover {
  background: color-mix(in srgb, var(--dash-brand) 10%, transparent);
}

/* =======================
   LAST ROW CLEANUP
======================= */
.panel tbody tr:last-child td {
  border-bottom: none;
}

/* =======================
   EMPTY / LOADING STATE
======================= */
.panel tbody td[colspan] {
  text-align: center;
  color: var(--dash-muted);
  font-size: 13px;
  padding: 18px;
}

/* =======================
   DARK MODE REFINEMENTS
======================= */
body[data-theme="dark"] .panel thead th {
  background: linear-gradient(
    180deg,
    #020617,
    #0b1220
  );
  color: #94a3b8;
}

body[data-theme="dark"] .panel tbody tr:hover {
  background: rgba(96,165,250,.08);
}

/* =======================
   MOBILE FRIENDLY
======================= */
@media (max-width: 700px) {
  .panel table {
    font-size: 13px;
  }

  .panel thead {
    display: none;
  }

  .panel tbody tr {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    padding: 12px;
    border-bottom: 1px solid var(--dash-border);
  }

  .panel tbody td {
    border: none;
    padding: 6px 0;
  }

  .panel tbody td::before {
    content: attr(data-label);
    font-size: 11px;
    color: var(--dash-muted);
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
  }
}
/* =====================================================
   FIXED: DASHBOARD QUICK ACTION LINKS
   (Targets EXISTING .quick > a.btn)
===================================================== */

.quick a.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;

  height: 46px;
  padding: 0 24px;

  border-radius: 999px;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: .2px;
  text-decoration: none;

  border: none;
  cursor: pointer;

  transition:
    transform .18s ease,
    box-shadow .18s ease,
    background .18s ease,
    color .18s ease;

  position: relative;
  isolation: isolate;
}

/* =======================
   PRIMARY ACTION
======================= */
.quick a.btn:not(.ghost) {
  background: linear-gradient(
    135deg,
    var(--dash-brand),
    color-mix(in srgb, var(--dash-brand) 75%, black)
  );
  color: #fff;

  box-shadow:
    0 12px 34px color-mix(in srgb, var(--dash-brand) 45%, transparent);
}

.quick a.btn:not(.ghost):hover {
  transform: translateY(-2px);
  box-shadow:
    0 22px 60px color-mix(in srgb, var(--dash-brand) 65%, transparent);
}

/* =======================
   GHOST / SECONDARY
======================= */
.quick a.btn.ghost {
  background: color-mix(in srgb, var(--dash-card) 92%, black);
  color: var(--dash-title);

  border: 1px solid var(--dash-border);
  box-shadow: 0 6px 18px rgba(0,0,0,.18);
}

.quick a.btn.ghost:hover {
  background: var(--dash-brand-soft);
  color: var(--dash-brand);
  transform: translateY(-2px);
}

/* =======================
   DARK MODE TUNING
======================= */
body[data-theme="dark"] .quick a.btn.ghost {
  background: #020617;
  color: #e5e7eb;
}

body[data-theme="dark"] .quick a.btn.ghost:hover {
  background: rgba(96,165,250,.14);
  color: #60a5fa;
}

/* =======================
   FOCUS (ACCESSIBILITY)
======================= */
.quick a.btn:focus-visible {
  outline: none;
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--dash-brand) 35%, transparent),
    0 14px 40px rgba(0,0,0,.35);
}

/* =======================
   MOBILE
======================= */
@media (max-width: 600px) {
  .quick a.btn {
    height: 44px;
    padding: 0 18px;
    font-size: 13px;
  }
}


/* =======================
   RESPONSIVE
======================= */
@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }

  .panel {
    min-height: 320px;
  }
}

@media (max-width: 600px) {
  .container {
    padding: 18px;
  }
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

  <!--  SALES & OPERATIONS -->
  <div class="kpi"><small>Today Sales</small><h2 id="kpiToday">UGX 0</h2></div>
  <div class="kpi"><small>Today Orders</small><h2 id="kpiTodayOrders">0</h2></div>
  <div class="kpi"><small>MTD Sales</small><h2 id="kpiMTD">UGX 0</h2></div>
  <div class="kpi"><small>Products / Customers</small><h2 id="kpiCounts">0 / 0</h2></div>
  <div class="kpi"><small>Low-Stock Alerts</small><h2 id="kpiLow">0</h2></div>
  <div class="kpi"><small>Suppliers</small><h2 id="kpiSup">0</h2></div>
  <div class="kpi"><small>Receivables</small><h2 id="kpiAR">UGX 0</h2></div>
  <div class="kpi"><small>Payables</small><h2 id="kpiAP">UGX 0</h2></div>

</div>


<!-- =======================
     ANALYTICS & CHARTS
======================= -->
<div class="charts-grid">

  <div class="panel">
    <h3>Last 14 Days – Sales Trend</h3>
    <div class="chart-wrap">
      <canvas id="cTrend"></canvas>
    </div>
  </div>

  <div class="panel">
    <h3>MTD Mix – Sales by Category</h3>
    <div class="chart-wrap">
      <canvas id="cCat"></canvas>
    </div>
  </div>

  <div class="panel">
    <h3>MTD Financial Performance</h3>
    <div class="chart-wrap">
      <canvas id="cFinance"></canvas>
    </div>
  </div>

  <div class="panel">
    <h3>Payment Methods (MTD)</h3>
    <div class="chart-wrap">
      <canvas id="cPay"></canvas>
    </div>
  </div>

</div>

<div class="panel" style="margin-top:18px">
  <h3>Top Products by Profit (MTD)</h3>
  <div class="chart-wrap tall">
    <canvas id="cMargin"></canvas>
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
/* =======================
   GLOBAL HELPERS
======================= */
const $ = s => document.querySelector(s);
const fmtUGX = n => 'UGX ' + Number(n || 0).toLocaleString();

/* =======================
   INVENTORY SUMMARY
======================= */
async function loadInventorySummary(){
  const r = await fetch('/POS_UG/controllers/reportsController.php?action=inventory_summary')
    .then(x => x.json());

  if (!r.success) return;

  $('#kpiInvProducts').textContent = (r.total_products || 0).toLocaleString();
  $('#kpiInvUnits').textContent    = (r.total_units || 0).toLocaleString();
  $('#kpiInvCost').textContent     = fmtUGX(r.total_cost_value);
  $('#kpiInvSell').textContent     = fmtUGX(r.total_selling_value);
}

/* =======================
   KPI DATA
======================= */
async function loadKPIs(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=kpis')
    .then(x => x.json());

  if (!r.success) return;

  $('#kpiToday').textContent       = fmtUGX(r.today_total);
  $('#kpiTodayOrders').textContent = (r.today_orders || 0).toLocaleString();
  $('#kpiMTD').textContent         = fmtUGX(r.mtd_total);
  $('#kpiCounts').textContent      =
    `${(r.products || 0).toLocaleString()} / ${(r.customers || 0).toLocaleString()}`;
  $('#kpiLow').textContent         = (r.low_stock || 0).toLocaleString();
  $('#kpiSup').textContent         = (r.suppliers || 0).toLocaleString();
  $('#kpiAR').textContent          = fmtUGX(r.receivables || 0);
  $('#kpiAP').textContent          = fmtUGX(r.payables || 0);
}

/* =======================
   SALES TREND (ADVANCED)
======================= */
let chartTrend;

async function loadTrend(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=sales_trend')
    .then(x => x.json());

  if (!r.success) return;

  const labels = r.rows.map(x => x.d);
  const totals = r.rows.map(x => Number(x.total || 0));

  if (chartTrend) chartTrend.destroy();

  const ctx = $('#cTrend').getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(59,130,246,0.35)');
  gradient.addColorStop(1, 'rgba(59,130,246,0)');

  const maxVal = Math.max(...totals);
  const minVal = Math.min(...totals);

  /* =========================
     CHART CREATION (HERE)
  ========================= */
  chartTrend = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Sales',
        data: totals,
        fill: true,
        backgroundColor: gradient,
        borderColor: '#3b82f6',
        tension: 0.35,
        borderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: false,
          suggestedMin: minVal === 0 ? -maxVal * 0.05 : minVal * 0.95,
          suggestedMax: maxVal * 1.1,
          ticks: { callback: v => 'UGX ' + v.toLocaleString() }
        },
        x: {
          ticks: { maxRotation: 45, minRotation: 30 }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: c => 'UGX ' + c.raw.toLocaleString()
          }
        }
      }
    }
  });

  /* =========================
     AFTER CHART CREATION 👇
  ========================= */
  animateChart(chartTrend, 3000);
}



/* =======================
   ANIMATE CHART DATA*/
function animateLineChart(chart) {
  let phase = 0;
  const baseData = chart.data.datasets.map(ds => [...ds.data]);

  function tick() {
    phase += 0.004; // 🐢 SLOW like a snake

    chart.data.datasets.forEach((ds, di) => {
      ds.data = baseData[di].map((v, i) => {
        const wave = Math.sin(phase + i * 0.5);
        return v * (1 + wave * 0.018); // ✨ 1.8% premium motion
      });
    });

    chart.update('none');
    requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);
}
function animatePieChart(chart) {
  let angle = 0;

  function tick() {
    angle += 0.002; // 🐢 very slow rotation
    chart.options.rotation = angle;
    chart.update('none');
    requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);
}




/* =======================
   SALES BY CATEGORY
======================= */
let chartCat;

async function loadCategory(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=sales_by_category')
    .then(x => x.json());

  if (!r.success) return;

  if (chartCat) chartCat.destroy();

  chartCat = new Chart($('#cCat'), {
    type: 'doughnut',
    data: {
      labels: r.rows.map(x => x.name),
      datasets: [{
        data: r.rows.map(x => x.revenue)
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: c => 'UGX ' + c.raw.toLocaleString()
          }
        }
      }
    }
  });
  animatePieChart(chartCat);
}

/* =======================
   FINANCIAL PERFORMANCE
======================= */
let chartFinance;

async function loadFinance(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=sales_vs_expenses')
    .then(x => x.json());

  if (!r.success || !r.rows.length) return;

  if (chartFinance) chartFinance.destroy();

  chartFinance = new Chart($('#cFinance'), {
    type: 'line',
    data: {
      labels: r.rows.map(x => x.date),
      datasets: [
        { label:'Sales', data:r.rows.map(x=>x.sales), tension:.35 },
        { label:'Expenses', data:r.rows.map(x=>x.expenses), tension:.35 },
        { label:'Net Profit', data:r.rows.map(x=>x.profit), tension:.35 }
      ]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{ legend:{ position:'top' }},
      scales:{
        y:{ ticks:{ callback:v=>'UGX '+v.toLocaleString() } }
      }
    }
  });

  animateChart(chartFinance, 3200);

}

/* =======================
   PAYMENT METHODS
======================= */
let chartPay;

async function loadPayments(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=payment_methods')
    .then(x=>x.json());

  if (!r.success) return;

  if (chartPay) chartPay.destroy();

  chartPay = new Chart($('#cPay'), {
    type:'doughnut',
    data:{
      labels:r.rows.map(x=>x.payment_type),
      datasets:[{ data:r.rows.map(x=>x.total) }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      cutout:'65%'
    }
  });
  animatePieChart(chartPay);

}

/* =======================
   PRODUCT MARGIN
======================= */
let chartMargin;

async function loadMargin(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=product_margin')
    .then(x=>x.json());

  if (!r.success) return;

  if (chartMargin) chartMargin.destroy();

  const ctx = $('#cMargin').getContext('2d');
  const grad = ctx.createLinearGradient(0,0,0,360);
  grad.addColorStop(0,'rgba(34,197,94,.45)');
  grad.addColorStop(1,'rgba(34,197,94,0)');

  chartMargin = new Chart(ctx, {
    type:'line',
    data:{
      labels:r.rows.map(x=>x.name),
      datasets:[{
        label:'Profit',
        data:r.rows.map(x=>x.profit),
        fill:true,
        tension:.4,
        borderColor:'#22c55e',
        backgroundColor:grad,
        borderWidth:2,
        pointRadius:4,
        pointHoverRadius:6
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      plugins:{ legend:{ display:false }},
      scales:{
        y:{ ticks:{ callback:v=>'UGX '+v.toLocaleString() } }
      }
    }
  });

  animateChart(chartMargin, 2800);
}


      /* =======================
        TABLES
      ======================= */
   
/* =======================
   TABLES
======================= */
async function loadTopProducts(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=top_products')
    .then(x=>x.json());

  $('#topProducts').innerHTML =
    (r.rows||[]).map(x=>`
      <tr>
        <td>${x.name}</td>
        <td class="right">${Number(x.qty).toLocaleString()}</td>
        <td class="right">${fmtUGX(x.revenue)}</td>
      </tr>
    `).join('') || '<tr><td>No sales</td></tr>';
}

async function loadRecentSales(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=recent_sales')
    .then(x=>x.json());

  $('#recentSales').innerHTML =
    (r.rows||[]).map(x=>`
      <tr>
        <td>#${x.id}</td>
        <td>${x.payment_type||''}</td>
        <td class="right">${fmtUGX(x.total_amount)}</td>
        <td class="right">${x.created_at}</td>
      </tr>
    `).join('');
}

async function loadLowStock(){
  const r = await fetch('/POS_UG/controllers/dashboardController.php?action=low_stock')
    .then(x=>x.json());

  $('#lowStock').innerHTML =
    (r.rows||[]).map(x=>`
      <tr>
        <td>${x.sku}</td>
        <td>${x.name}</td>
        <td class="right">${x.on_hand}</td>
        <td class="right">${x.stock_alert_threshold}</td>
      </tr>
    `).join('');
}


/* =======================
   INIT
======================= */
(async function init(){
  await Promise.all([
    loadInventorySummary(),
    loadKPIs(),
    loadTrend(),
    loadCategory(),
    loadFinance(),
    loadPayments(),
    loadMargin(),
    loadTopProducts(),
    loadRecentSales(),
    loadLowStock()
  ]);
})();
</script>

<script>
  lucide.createIcons();
</script>
