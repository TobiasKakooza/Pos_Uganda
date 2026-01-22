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

  /* ======================================================
   STOCK VALUATION – ENTERPRISE THEME OVERRIDE
   Light & Dark | No HTML / JS Changes
====================================================== */

/* ------------------------------------------------------
   THEME TOKENS
------------------------------------------------------ */
body[data-theme="light"] {
  --val-bg: #f4f6f9;
  --val-panel: #ffffff;
  --val-header: #ffffff;
  --val-border: #e5e7eb;

  --val-text: #0f172a;
  --val-muted: #64748b;

  --val-hover: #f1f5f9;
}

body[data-theme="dark"] {
  --val-bg: #020617;
  --val-panel: #0f172a;
  --val-header: #020617;
  --val-border: #1e293b;

  --val-text: #e5e7eb;
  --val-muted: #94a3b8;

  --val-hover: #1e293b;
}

/* ======================================================
   PAGE WRAPPER
====================================================== */
.val-wrap {
  color: var(--val-text) !important;
}

/* ======================================================
   HEADINGS
====================================================== */
.val-title,
.val-wrap h1,
.val-panel h3 {
  color: var(--val-text) !important;
  font-weight: 600;
}

/* ======================================================
   FILTER BAR
====================================================== */
.val-filters {
  background: var(--val-panel) !important;
  border: 1px solid var(--val-border) !important;
  border-radius: 14px;
  padding: 12px;

  box-shadow: 0 10px 30px rgba(0,0,0,.12);
}

.val-filters input,
.val-filters select {
  background: var(--val-header) !important;
  color: var(--val-text) !important;
  border-color: var(--val-border) !important;
}

.val-filters input::placeholder {
  color: var(--val-muted) !important;
}

.val-filters input:focus,
.val-filters select:focus {
  outline: none;
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 35%, transparent);
}

.val-filters label {
  color: var(--val-muted) !important;
}

/* ======================================================
   RUN / EXPORT BUTTONS
====================================================== */
.val-run {
  background: linear-gradient(
    135deg,
    var(--primary),
    color-mix(in srgb, var(--primary) 75%, black)
  ) !important;

  color: #fff !important;
  border: none !important;

  font-weight: 600;
  border-radius: 999px;

  transition:
    transform .15s ease,
    box-shadow .15s ease,
    opacity .15s ease;
}

.val-run:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 40px rgba(0,0,0,.35);
}

/* ======================================================
   KPI CARDS
====================================================== */
.val-card {
  background: var(--val-panel) !important;
  border-color: var(--val-border) !important;

  border-radius: 16px;
  box-shadow: 0 14px 40px rgba(0,0,0,.15);
}

.val-card small {
  color: var(--val-muted) !important;
}

.val-card h2 {
  color: var(--val-text) !important;
  font-variant-numeric: tabular-nums;
}

/* ======================================================
   CHART PANELS
====================================================== */
.val-panel {
  background: var(--val-panel) !important;
  border-color: var(--val-border) !important;

  border-radius: 16px;
  box-shadow: 0 14px 40px rgba(0,0,0,.15);
}

/* ======================================================
   DATA TABLE
====================================================== */
.val-table {
  background: var(--val-panel) !important;
  border-color: var(--val-border) !important;

  border-radius: 16px;
  box-shadow: 0 16px 50px rgba(0,0,0,.16);
}

.val-table th {
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--val-panel) 95%, black),
    color-mix(in srgb, var(--val-panel) 90%, black)
  ) !important;

  color: var(--val-muted) !important;
  border-bottom: 1px solid var(--val-border) !important;
}

.val-table td {
  color: var(--val-text) !important;
  border-top: 1px solid var(--val-border) !important;
}

.val-table tbody tr:hover {
  background: var(--val-hover) !important;
}

/* Right-aligned numbers */
.val-table .right {
  font-variant-numeric: tabular-nums;
}

/* ======================================================
   CHART.JS COLOR FIX (LIGHT + DARK)
====================================================== */
body[data-theme="dark"] canvas {
  filter: brightness(1.05) contrast(1.05);
}

body[data-theme="light"] canvas {
  filter: none;
}

/* ======================================================
   SCROLLBARS (WEBKIT)
====================================================== */
.val-table::-webkit-scrollbar {
  height: 10px;
}

.val-table::-webkit-scrollbar-thumb {
  background: color-mix(in srgb, var(--val-border) 70%, black);
  border-radius: 8px;
}

.val-table::-webkit-scrollbar-track {
  background: transparent;
}
/* ======================================================
   STOCK VALUATION – ADVANCED BUTTON SYSTEM
====================================================== */

/* Base button */
.val-run {
  position: relative;
  isolation: isolate;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  min-height: 42px;
  padding: 0 18px;

  font-size: 14px;
  font-weight: 600;
  letter-spacing: .25px;

  border-radius: 999px;
  border: none;

  cursor: pointer;
  user-select: none;

  background:
    linear-gradient(
      135deg,
      var(--primary),
      color-mix(in srgb, var(--primary) 70%, black)
    ) !important;

  color: #fff !important;

  box-shadow:
    0 10px 30px rgba(0,0,0,.25),
    inset 0 1px 0 rgba(255,255,255,.18);

  transition:
    transform .18s cubic-bezier(.4,0,.2,1),
    box-shadow .18s ease,
    filter .18s ease;
}

/* Glow layer */
.val-run::before {
  content: "";
  position: absolute;
  inset: -2px;
  z-index: -1;

  background:
    linear-gradient(
      135deg,
      color-mix(in srgb, var(--primary) 80%, white),
      color-mix(in srgb, var(--primary) 60%, black)
    );

  opacity: 0;
  border-radius: inherit;
  filter: blur(10px);

  transition: opacity .25s ease;
}

/* Hover */
.val-run:hover {
  transform: translateY(-1px);
  box-shadow:
    0 18px 45px rgba(0,0,0,.35),
    inset 0 1px 0 rgba(255,255,255,.22);
}

.val-run:hover::before {
  opacity: .85;
}

/* Active (press) */
.val-run:active {
  transform: translateY(0);
  box-shadow:
    0 8px 20px rgba(0,0,0,.28),
    inset 0 2px 6px rgba(0,0,0,.25);
}

/* Keyboard focus */
.val-run:focus-visible {
  outline: none;
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--primary) 35%, transparent),
    0 18px 45px rgba(0,0,0,.35);
}

/* Secondary appearance (Export CSV differentiation) */
#export.val-run {
  background:
    linear-gradient(
      135deg,
      color-mix(in srgb, var(--primary) 55%, transparent),
      color-mix(in srgb, var(--primary) 35%, black)
    ) !important;

  color: var(--val-text) !important;

  box-shadow:
    0 10px 30px rgba(0,0,0,.18),
    inset 0 1px 0 rgba(255,255,255,.12);
}

#export.val-run:hover {
  filter: brightness(1.05);
}

/* ======================================================
   CHECKBOX (INCLUDE ZERO STOCK)
====================================================== */
.val-filters label.chk {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  font-size: 13px;
  font-weight: 500;

  color: var(--val-muted) !important;
  cursor: pointer;
}

/* Custom checkbox */
.val-filters label.chk input {
  appearance: none;
  width: 18px;
  height: 18px;

  border-radius: 6px;
  border: 1.5px solid var(--val-border);

  background: var(--val-header);
  cursor: pointer;

  display: grid;
  place-items: center;

  transition: all .18s ease;
}

.val-filters label.chk input::before {
  content: "✓";
  font-size: 12px;
  font-weight: 700;
  color: white;
  opacity: 0;
  transform: scale(.6);
  transition: all .15s ease;
}

.val-filters label.chk input:checked {
  background: var(--primary);
  border-color: var(--primary);
}

.val-filters label.chk input:checked::before {
  opacity: 1;
  transform: scale(1);
}

.val-filters label.chk input:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 35%, transparent);
}

/* ======================================================
   MOTION SAFETY
====================================================== */
@media (prefers-reduced-motion: reduce) {
  .val-run,
  .val-run::before,
  .val-filters label.chk input {
    transition: none !important;
  }
}

</style>

<div class="val-wrap">
  <h1 class="val-title">Stock Valuation</h1>

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
