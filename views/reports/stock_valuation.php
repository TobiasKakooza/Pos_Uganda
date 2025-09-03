<?php
// views/reports/stock_valuation.php
ini_set('display_errors',1); error_reporting(E_ALL);
if (!isset($embed)) {
  header('Location: /POS_UG/views/dashboard.php?view=reports/valuation');
  exit;
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.css"/>

<style>
  .val-wrap{padding:10px 0 28px}
  .val-title{font-size:22px;margin:0 0 12px}
  .val-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:8px 0 16px}
  .val-filters input,.val-filters select,.val-filters label{padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
  .val-filters label.chk{display:flex;gap:8px;align-items:center;border:none;padding:0;background:transparent}
  .val-run{background:#1976d2;color:#fff;border:0;padding:10px 14px;border-radius:8px;cursor:pointer}

  .val-cards{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px;margin:8px 0 16px}
  .val-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px}
  .val-card small{color:#6b7280}
  .val-card h2{margin:6px 0 0;font-size:20px}

  .val-panels{display:grid;grid-template-columns:1.2fr .8fr;gap:12px;align-items:start}
  .val-panel{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px}
  .val-panel h3{margin:0 0 10px;font-size:16px}

  .val-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:12px}
  .val-table th{background:#f3f4f6;padding:10px;text-align:left;border-bottom:1px solid #e5e7eb}
  .val-table td{padding:10px;border-top:1px solid #f1f5f9}
  .right{text-align:right}
</style>

<div class="val-wrap">
  <h1 class="val-title">💰 Stock Valuation</h1>

  <div class="val-filters">
    <input id="q" placeholder="Search name or SKU">
    <select id="cat"><option value="">All categories</option></select>
    <select id="basis">
      <option value="smart">Cost basis: Smart (avg→last)</option>
      <option value="avg">Cost basis: Average Cost</option>
      <option value="last">Cost basis: Last Cost</option>
    </select>
    <input id="minoh" type="number" min="0" step="1" value="0" placeholder="Min on-hand">
    <label class="chk"><input id="zero" type="checkbox"> Include zero stock</label>
    <button class="val-run" id="run">Run</button>
    <button class="val-run" id="export">Export CSV</button>
  </div>

  <div class="val-cards">
    <div class="val-card"><small>Total Inventory Value</small><h2 id="kVal">UGX 0</h2></div>
    <div class="val-card"><small>Total Units On-Hand</small><h2 id="kUnits">0</h2></div>
    <div class="val-card"><small>Items Count</small><h2 id="kItems">0</h2></div>
    <div class="val-card"><small>Cost Basis</small><h2 id="kBasis">Smart</h2></div>
  </div>

  <div class="val-panels">
    <div class="val-panel">
      <h3>Top Items by Value</h3>
      <canvas id="chartTop" height="120"></canvas>
    </div>
    <div class="val-panel">
      <h3>Value Distribution by Category</h3>
      <canvas id="chartCat" height="120"></canvas>
    </div>
  </div>

  <table class="val-table">
    <thead>
      <tr>
        <th>SKU</th>
        <th>Product</th>
        <th>Category</th>
        <th class="right">On Hand</th>
        <th class="right">Cost</th>
        <th class="right">Value</th>
      </tr>
    </thead>
    <tbody id="tbody"><tr><td>Run report…</td></tr></tbody>
  </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const $ = s => document.querySelector(s);
let topChart, catChart;

function money(n){ n=Number(n||0); return 'UGX ' + n.toLocaleString(undefined,{maximumFractionDigits:0}); }
function num(n){ return Number(n||0).toLocaleString(); }

function qs(){
  const p = new URLSearchParams();
  const q = $('#q').value.trim();
  const cat = $('#cat').value;
  const basis = $('#basis').value;
  const minoh = $('#minoh').value || 0;
  const zero = $('#zero').checked ? 1 : 0;
  if (q) p.set('q', q);
  if (cat) p.set('category_id', cat);
  p.set('cost_basis', basis);
  p.set('min_on_hand', minoh);
  p.set('include_zero', zero);
  return p.toString();
}
async function j(url){ const r=await fetch(url); const t=await r.text(); try{return JSON.parse(t)}catch(e){console.error(t);return {success:false}} }

async function loadCats(){
  const jx = await j('/POS_UG/controllers/reportsController.php?action=categories');
  const sel = $('#cat');
  (jx.rows||[]).forEach(c=>{
    const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; sel.appendChild(o);
  });
}

function renderTop(items){
  const top = items.slice(0,15);
  if (topChart) topChart.destroy();
  topChart = new Chart($('#chartTop'), {
    type:'bar',
    data:{ labels: top.map(r=>r.name),
      datasets:[{ label:'Value', data: top.map(r=>Number(r.value||0)) }]
    },
    options:{ indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{ticks:{callback:v=>v.toLocaleString()}}} }
  });
}

function renderCats(summary){
  if (catChart) catChart.destroy();
  catChart = new Chart($('#chartCat'), {
    type:'doughnut',
    data:{ labels: summary.map(r=>r.category), datasets:[{ data: summary.map(r=>Number(r.value||0)) }] },
    options:{ plugins:{legend:{position:'bottom'}} }
  });
}

function renderTable(rows){
  $('#tbody').innerHTML = rows.map(r=>`
    <tr>
      <td>${r.sku||''}</td>
      <td>${r.name}</td>
      <td>${r.category||''}</td>
      <td class="right">${num(r.on_hand)}</td>
      <td class="right">${money(r.cost_basis_used)}</td>
      <td class="right"><strong>${money(r.value)}</strong></td>
    </tr>
  `).join('') || '<tr><td>No rows</td></tr>';
}

async function run(){
  const res = await j('/POS_UG/controllers/reportsController.php?action=stock_valuation&'+qs());
  const rows = res.rows||[];
  $('#kVal').textContent   = money(res.total_value||0);
  $('#kUnits').textContent = num(res.total_units||0);
  $('#kItems').textContent = num(rows.length);
  $('#kBasis').textContent = (res.basis||'smart').toUpperCase();

  renderTop(rows);
  renderCats(res.category_summary||[]);
  renderTable(rows);
}

$('#run').addEventListener('click', run);
$('#export').addEventListener('click', ()=>{
  const a=document.createElement('a');
  a.href='/POS_UG/controllers/reportsController.php?action=stock_valuation&'+qs()+'&export=csv';
  a.click();
});

loadCats().then(run);
</script>
