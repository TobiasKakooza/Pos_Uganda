<?php ini_set('display_errors',1); error_reporting(E_ALL); ?>
<?php
// views/reports/index.php
// If someone opens this file directly, send them to the embedded version:
if (!isset($embed)) {
  header('Location: /POS_UG/views/dashboard.php?view=reports');
  exit;
}
?>


<?php
// embedded in dashboard; DB not required here because we call controllers via fetch
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.css"/>

<style>
  /* Wrapper */
  .rp-wrap{padding:10px 0 28px;}
  .rp-title{font-size:22px;margin:0 0 12px}

  /* Filter bar */
  .rp-filters{
    display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:8px 0 16px;
  }
  .rp-filters input, .rp-filters select{
    padding:10px; border:1px solid #d1d5db; border-radius:8px; background:#fff;
  }
  .rp-run{background:#1976d2;color:#fff;border:0;padding:10px 14px;border-radius:8px;cursor:pointer}

  /* KPI cards */
  .rp-cards{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px;margin:8px 0 16px}
  .rp-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px}
  .rp-card small{color:#6b7280}
  .rp-card h2{margin:6px 0 0;font-size:20px}

  /* Panels */
  .rp-panels{display:grid;grid-template-columns:1.2fr .8fr; gap:12px; align-items:start}
  .rp-panel{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px}
  .rp-panel h3{margin:0 0 10px;font-size:16px}

  /* Table */
  .rp-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:12px}
  .rp-table th{background:#f3f4f6;padding:10px;text-align:left;border-bottom:1px solid #e5e7eb}
  .rp-table td{padding:10px;border-top:1px solid #f1f5f9}
  .rp-right{text-align:right}

  /* Dark mode toggleable (optional) */
  .dark .rp-card,.dark .rp-panel,.dark .rp-table{background:#0f172a;border-color:#1f2937;color:#e5e7eb}
  .dark .rp-table th{background:#111827;border-bottom-color:#1f2937}
  .dark .rp-filters input,.dark .rp-filters select{background:#0b1220;border-color:#1f2937;color:#e5e7eb}
  .dark .rp-run{background:#2563eb}
</style>

<div class="rp-wrap">
  <h1 class="rp-title">📊 Reports</h1>

  <!-- Filters -->
  <div class="rp-filters">
    <label>From <input type="date" id="rpFrom" value="<?= $monthStart ?>"></label>
    <label>To <input type="date" id="rpTo" value="<?= $today ?>"></label>
    <select id="rpGroup">
      <option value="day">Group: Daily</option>
      <option value="week">Group: Weekly</option>
      <option value="month">Group: Monthly</option>
    </select>
    <select id="rpTab">
        <option value="sales">View: Sales</option>
        <option value="inventory">View: Inventory</option>
        <option value="financial">View: Financial</option>
        <option value="staff">View: Staff</option>
        <option value="payments">View: Payments</option>
        <option value="customers">View: Customers</option>
        <option value="drawer">View: Cash Drawer</option>
    </select>

    <button class="rp-run" id="rpRun">Run</button>
    <button class="rp-run" id="rpExport">Export CSV</button>
  </div>

  <!-- KPIs -->
  <div class="rp-cards" id="rpCards">
    <div class="rp-card"><small>Total Sales</small><h2 id="kpiTotal">UGX 0</h2></div>
    <div class="rp-card"><small>Orders</small><h2 id="kpiOrders">0</h2></div>
    <div class="rp-card"><small>Avg Order</small><h2 id="kpiAOV">UGX 0</h2></div>
    <div class="rp-card"><small>Tax</small><h2 id="kpiTax">UGX 0</h2></div>
  </div>

  <!-- Charts -->
  <div class="rp-panels">
    <div class="rp-panel">
      <h3 id="chartTitle">Sales Trend</h3>
      <canvas id="rpChart" height="120"></canvas>
    </div>
    <div class="rp-panel">
      <h3>Breakdown</h3>
      <canvas id="rpBreakdown" height="120"></canvas>
    </div>
  </div>

  <!-- Details Table -->
  <table class="rp-table" id="rpTable">
    <thead><tr id="rpHead"></tr></thead>
    <tbody id="rpBody"><tr><td>Run a report…</td></tr></tbody>
  </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const $ = sel => document.querySelector(sel);

let chartLine, chartPie;

function fmt(n){ n = Number(n||0); return 'UGX ' + n.toLocaleString(undefined,{maximumFractionDigits:0}); }
function csv(url){ const a=document.createElement('a'); a.href=url; a.click(); }

function params(){
  return new URLSearchParams({
    from: $('#rpFrom').value,
    to:   $('#rpTo').value,
    group: $('#rpGroup').value
  }).toString();
}

async function runSales(){
  // 1) Trend
  const t = await fetch('/POS_UG/controllers/reportsController.php?action=sales_overview&'+params()).then(r=>r.json());
  const rows = t.rows||[];
  const labels = rows.map(r => r.bucket);
  const totals  = rows.map(r => Number(r.total_sales||0));
  const orders  = rows.map(r => Number(r.sales_count||0));
  const taxes   = rows.map(r => Number(r.tax_total||0));

  const sum = a => a.reduce((x,y)=>x+Number(y||0),0);
  const total = sum(totals), count = sum(orders), tax = sum(taxes);
  $('#kpiTotal').textContent = fmt(total);
  $('#kpiOrders').textContent = count.toLocaleString();
  $('#kpiAOV').textContent    = fmt(count? (total/count) : 0);
  $('#kpiTax').textContent    = fmt(tax);

  // 2) Line chart
  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'line',
    data:{ labels,
      datasets:[{ label:'Total Sales', data: totals, tension:.25 }]
    }
  });
  $('#chartTitle').textContent='Sales Trend';

  // 3) Breakdown: by Category (bar) + update table
  const cat = await fetch('/POS_UG/controllers/reportsController.php?action=sales_by_category&'+params()).then(r=>r.json());
  const cats = (cat.rows||[]);
  if (chartPie) chartPie.destroy();
  chartPie = new Chart($('#rpBreakdown'), {
    type:'bar',
    data:{ labels: cats.map(x=>x.name), datasets:[{ label:'Revenue', data: cats.map(x=>x.revenue) }] }
  });

  // Table – Best sellers
  const prod = await fetch('/POS_UG/controllers/reportsController.php?action=sales_by_product&'+params()).then(r=>r.json());
  const pr = prod.rows||[];
  $('#rpHead').innerHTML = '<th>Product</th><th class="rp-right">Qty</th><th class="rp-right">Revenue</th>';
  $('#rpBody').innerHTML = pr.map(r=>`
    <tr><td>${r.name}</td><td class="rp-right">${Number(r.qty||0).toLocaleString()}</td>
        <td class="rp-right">${fmt(r.revenue)}</td></tr>`).join('') || '<tr><td>No data</td></tr>';

  // Hook export for this view
  $('#rpExport').onclick = () => {
    csv('/POS_UG/controllers/reportsController.php?action=sales_by_product&'+params()+'&export=csv');
  };
}

async function runInventory(){
  // KPIs: total stock value
  const val = await fetch('/POS_UG/controllers/reportsController.php?action=stock_valuation').then(r=>r.json());
  $('#kpiTotal').textContent = fmt(val.total_value||0);
  $('#kpiOrders').textContent = '—';
  $('#kpiAOV').textContent = '—';
  $('#kpiTax').textContent = '—';

  // Chart: top values
  const rows = (val.rows||[]).slice(0,15);
  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'bar',
    data:{ labels: rows.map(r=>r.name), datasets:[{ label:'Value', data: rows.map(r=>r.value) }] }
  });
  $('#chartTitle').textContent='Stock Valuation (Top)';

  // Breakdown: reorder alerts count vs ok
  const re = await fetch('/POS_UG/controllers/reportsController.php?action=reorder_alerts').then(r=>r.json());
  const alerts = (re.rows||[]).length;
  const ok = Math.max(0, (val.rows||[]).length - alerts);
  if (chartPie) chartPie.destroy();
  chartPie = new Chart($('#rpBreakdown'), {
    type:'doughnut',
    data:{ labels:['OK','Needs Reorder'], datasets:[{ data:[ok, alerts] }] }
  });

  // Table: low stock list
  $('#rpHead').innerHTML = '<th>SKU</th><th>Product</th><th class="rp-right">On Hand</th><th class="rp-right">Threshold</th>';
  $('#rpBody').innerHTML = (re.rows||[]).map(r=>`
    <tr><td>${r.sku||''}</td><td>${r.name}</td><td class="rp-right">${r.on_hand}</td><td class="rp-right">${r.stock_alert_threshold||0}</td></tr>
  `).join('') || '<tr><td>No alerts</td></tr>';

  $('#rpExport').onclick = () => {
    csv('/POS_UG/controllers/reportsController.php?action=reorder_alerts&export=csv');
  };
}

async function runFinancial(){
  const p = await fetch('/POS_UG/controllers/reportsController.php?action=profitability&'+params()).then(r=>r.json());
  $('#kpiTotal').textContent = fmt(p.revenue||0);
  $('#kpiOrders').textContent = '—';
  $('#kpiAOV').textContent    = fmt((p.revenue||0)-(p.gross_profit||0)); // shows COGS quickly
  $('#kpiTax').textContent    = fmt(p.gross_profit||0);

  // Chart: revenue vs cogs
  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'bar',
    data:{ labels:['Revenue','COGS','Gross Profit'],
           datasets:[{ label:'Amount', data:[p.revenue||0, p.cogs||0, p.gross_profit||0] }] }
  });
  $('#chartTitle').textContent='Revenue vs COGS';

  // Breakdown: tax by day
  const tx = await fetch('/POS_UG/controllers/reportsController.php?action=tax_summary&'+params()).then(r=>r.json());
  const rows = tx.rows||[];
  if (chartPie) chartPie.destroy();
  chartPie = new Chart($('#rpBreakdown'), {
    type:'line',
    data:{ labels: rows.map(r=>r.d), datasets:[{ label:'Tax', data: rows.map(r=>r.tax) }] }
  });

  // Table: discounts & returns
  const dr = await fetch('/POS_UG/controllers/reportsController.php?action=discounts_returns&'+params()).then(r=>r.json());
  $('#rpHead').innerHTML = '<th>Metric</th><th class="rp-right">Amount</th>';
  $('#rpBody').innerHTML = `
    <tr><td>Discount Total</td><td class="rp-right">${fmt(dr.discount_total||0)}</td></tr>
    <tr><td>Returned Qty</td><td class="rp-right">${(dr.returns_qty||0).toLocaleString()}</td></tr>
  `;

  $('#rpExport').onclick = () => {
    csv('/POS_UG/controllers/reportsController.php?action=tax_summary&'+params()+'&export=csv');
  };
}

async function runStaff(){
  const sh = await fetch('/POS_UG/controllers/reportsController.php?action=shift_report&'+params()).then(r=>r.json());
  const rows = sh.rows||[];

  // Simple totals
  const totalSales = rows.reduce((a,r)=>a+Number(r.sales_total||0),0);
  $('#kpiTotal').textContent = fmt(totalSales);
  $('#kpiOrders').textContent = rows.length.toString();
  $('#kpiAOV').textContent = '—'; $('#kpiTax').textContent = '—';

  // Chart: sales per cashier (top 10)
  const byCashier = {};
  rows.forEach(r=>{ byCashier[r.cashier]=(byCashier[r.cashier]||0)+Number(r.sales_total||0); });
  const names = Object.keys(byCashier).slice(0,10);
  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'bar',
    data:{ labels:names, datasets:[{ label:'Sales', data:names.map(n=>byCashier[n]) }] }
  });
  $('#chartTitle').textContent='Sales by Cashier';

  // Table: shifts
  $('#rpHead').innerHTML = '<th>Cashier</th><th>Opened</th><th>Closed</th><th class="rp-right">Opening</th><th class="rp-right">Sales</th><th class="rp-right">Closing</th>';
  $('#rpBody').innerHTML = rows.map(r=>`
    <tr>
      <td>${r.cashier}</td>
      <td>${r.opened_at||''}</td>
      <td>${r.closed_at||''}</td>
      <td class="rp-right">${fmt(r.opening_balance||0)}</td>
      <td class="rp-right">${fmt(r.sales_total||0)}</td>
      <td class="rp-right">${fmt(r.closing_balance||0)}</td>
    </tr>`).join('') || '<tr><td>No shifts</td></tr>';

  $('#rpExport').onclick = () => {
    csv('/POS_UG/controllers/reportsController.php?action=shift_report&'+params()+'&export=csv');
  };
}

async function run(){
  const tab = $('#rpTab').value;
  if (tab==='sales') await runSales();
  else if (tab==='inventory') await runInventory();
  else if (tab==='financial') await runFinancial();
  else await runStaff();
}

$('#rpRun').addEventListener('click', run);
// auto-run on load
run();


async function safeJSON(url){
  const r = await fetch(url);
  const t = await r.text();
  try { return JSON.parse(t); } catch (e){ console.error('Bad JSON from', url, t); return {success:false,message:'Bad JSON'}; }
}

async function runPayments(){
  const r = await fetch('/POS_UG/controllers/reportsController.php?action=sales_by_payment&'+params()).then(x=>x.json());
  const rows = r.rows||[];

  // KPIs
  const total = rows.reduce((a,b)=>a+Number(b.total||0),0);
  $('#kpiTotal').textContent = fmt(total);
  $('#kpiOrders').textContent = rows.reduce((a,b)=>a+Number(b.sales_count||0),0).toLocaleString();
  $('#kpiAOV').textContent = '—'; $('#kpiTax').textContent = '—';

  // Chart
  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'pie',
    data:{ labels: rows.map(x=>x.payment_type||'Unknown'),
           datasets:[{ data: rows.map(x=>x.total) }] }
  });
  $('#chartTitle').textContent='Sales by Payment Method';

  // Table
  $('#rpHead').innerHTML = '<th>Payment</th><th class="rp-right">Orders</th><th class="rp-right">Total</th>';
  $('#rpBody').innerHTML = rows.map(r=>`
    <tr><td>${r.payment_type||'Unknown'}</td>
        <td class="rp-right">${(r.sales_count||0).toLocaleString()}</td>
        <td class="rp-right">${fmt(r.total)}</td></tr>`).join('') || '<tr><td>No data</td></tr>';

  $('#rpExport').onclick = () => csv('/POS_UG/controllers/reportsController.php?action=sales_by_payment&'+params()+'&export=csv');
}

async function runCustomers(){
  // Top customers (MTD or date range)
  const top = await fetch('/POS_UG/controllers/reportsController.php?action=top_customers&'+params()).then(x=>x.json());
  const rows = top.rows||[];

  $('#kpiTotal').textContent = fmt(rows.reduce((a,x)=>a+Number(x.total_spent||0),0));
  $('#kpiOrders').textContent = rows.reduce((a,x)=>a+Number(x.orders||0),0).toLocaleString();
  $('#kpiAOV').textContent = '—'; $('#kpiTax').textContent = '—';

  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'bar',
    data:{ labels: rows.map(x=>x.name), datasets:[{ label:'Spent', data: rows.map(x=>x.total_spent) }] }
  });
  $('#chartTitle').textContent='Top Customers';

  // Table
  $('#rpHead').innerHTML = '<th>Customer</th><th class="rp-right">Orders</th><th class="rp-right">Spent</th>';
  $('#rpBody').innerHTML = rows.map(x=>`
    <tr><td>${x.name}</td>
        <td class="rp-right">${(x.orders||0).toLocaleString()}</td>
        <td class="rp-right">${fmt(x.total_spent)}</td></tr>`).join('') || '<tr><td>No customers</td></tr>';

  $('#rpExport').onclick = () => csv('/POS_UG/controllers/reportsController.php?action=top_customers&'+params()+'&export=csv');
}

async function runDrawer(){
  const r = await fetch('/POS_UG/controllers/reportsController.php?action=cash_drawer&'+params()).then(x=>x.json());
  const rows = r.rows||[];

  $('#kpiTotal').textContent = fmt(rows.reduce((a,x)=>a+Number(x.total_sales||0),0));
  $('#kpiOrders').textContent = rows.length.toString();
  $('#kpiAOV').textContent = '—'; $('#kpiTax').textContent = '—';

  if (chartLine) chartLine.destroy();
  chartLine = new Chart($('#rpChart'), {
    type:'line',
    data:{ labels: rows.map(x=>x.report_date),
           datasets:[{ label:'Expected Drawer', data: rows.map(x=>Number(x.expected_drawer||0)), tension:.25 }] }
  });
  $('#chartTitle').textContent='Expected Drawer (by day)';

  $('#rpHead').innerHTML = '<th>Date</th><th class="rp-right">Opening</th><th class="rp-right">Sales</th><th class="rp-right">Cash In</th><th class="rp-right">Cash Out</th><th class="rp-right">Expected</th><th class="rp-right">Closing</th>';
  $('#rpBody').innerHTML = rows.map(x=>`
    <tr>
      <td>${x.report_date}</td>
      <td class="rp-right">${fmt(x.opening_balance)}</td>
      <td class="rp-right">${fmt(x.total_sales)}</td>
      <td class="rp-right">${fmt(x.cash_in)}</td>
      <td class="rp-right">${fmt(x.cash_out)}</td>
      <td class="rp-right">${fmt(x.expected_drawer)}</td>
      <td class="rp-right">${x.closing_balance===null ? '—' : fmt(x.closing_balance)}</td>
    </tr>
  `).join('') || '<tr><td>No EOD rows</td></tr>';

  $('#rpExport').onclick = () => csv('/POS_UG/controllers/reportsController.php?action=cash_drawer&'+params()+'&export=csv');
}
async function run(){
  const tab = $('#rpTab').value;
  if (tab==='sales') await runSales();
  else if (tab==='inventory') await runInventory();
  else if (tab==='financial') await runFinancial();
  else if (tab==='staff') await runStaff();
  else if (tab==='payments') await runPayments();
  else if (tab==='customers') await runCustomers();
  else if (tab==='drawer') await runDrawer();
}


</script>
