<?php
// views/reports/stock_levels.php
ini_set('display_errors',1); error_reporting(E_ALL);

// If someone opens this file directly, push them into the dashboard shell.
if (!isset($embed)) {
  header('Location: /POS_UG/views/dashboard.php?view=reports/stock');
  exit;
}
?>
<style>
  .stk-wrap{padding:10px 0 28px}
  .stk-title{font-size:22px;margin:0 0 12px}
  .stk-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:8px 0 16px}
  .stk-filters input,.stk-filters select{padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
  .stk-run{background:#1976d2;color:#fff;border:0;padding:10px 14px;border-radius:8px;cursor:pointer}
  .stk-cards{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin:8px 0 16px}
  .stk-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px}
  .stk-card small{color:#6b7280}
  .stk-card h2{margin:6px 0 0;font-size:20px}
  .stk-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:12px}
  .stk-table th{background:#f3f4f6;padding:10px;text-align:left;border-bottom:1px solid #e5e7eb}
  .stk-table td{padding:10px;border-top:1px solid #f1f5f9}
  .stk-right{text-align:right}
  .pill{padding:3px 8px;border-radius:999px;font-size:12px;display:inline-block}
  .pill.low{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
  .pill.oos{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
/* ======================================================
   STOCK LEVELS – ENTERPRISE THEME OVERRIDE
   Light & Dark | No HTML / JS Changes
====================================================== */

/* ------------------------------------------------------
   THEME TOKENS
------------------------------------------------------ */
body[data-theme="light"] {
  --stk-bg: #f4f6f9;
  --stk-panel: #ffffff;
  --stk-header: #ffffff;
  --stk-border: #e5e7eb;

  --stk-text: #0f172a;
  --stk-muted: #64748b;

  --stk-hover: #f1f5f9;
}

body[data-theme="dark"] {
  --stk-bg: #020617;
  --stk-panel: #0f172a;
  --stk-header: #020617;
  --stk-border: #1e293b;

  --stk-text: #e5e7eb;
  --stk-muted: #94a3b8;

  --stk-hover: #1e293b;
}

/* ======================================================
   PAGE WRAP
====================================================== */
.stk-wrap {
  padding: 12px 0 32px;
  color: var(--stk-text);
}

/* Title */
.stk-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--stk-text);
}

/* ======================================================
   FILTER BAR
====================================================== */
.stk-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;

  padding: 12px;
  margin-bottom: 18px;

  background: var(--stk-panel);
  border: 1px solid var(--stk-border);
  border-radius: 14px;

  box-shadow: 0 8px 24px rgba(0,0,0,.12);
}

/* Inputs & selects */
.stk-filters input,
.stk-filters select {
  height: 40px;
  padding: 0 14px;

  background: var(--stk-header);
  color: var(--stk-text);

  border: 1px solid var(--stk-border);
  border-radius: 10px;

  font-size: 14px;
}

.stk-filters input::placeholder {
  color: var(--stk-muted);
}

.stk-filters input:focus,
.stk-filters select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 35%, transparent);
}

/* ======================================================
   ACTION BUTTONS
====================================================== */
.stk-run {
  height: 40px;
  padding: 0 18px;

  background: linear-gradient(
    135deg,
    var(--primary),
    color-mix(in srgb, var(--primary) 75%, black)
  );

  color: #fff;
  border: none;
  border-radius: 999px;

  font-size: 14px;
  font-weight: 600;
  letter-spacing: .2px;

  cursor: pointer;

  transition:
    transform .15s ease,
    box-shadow .15s ease;
}

.stk-run:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 40px rgba(0,0,0,.35);
}

.stk-run:active {
  transform: translateY(0);
}

/* ======================================================
   KPI CARDS
====================================================== */
.stk-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.stk-card {
  background: var(--stk-panel);
  border: 1px solid var(--stk-border);
  border-radius: 16px;
  padding: 16px;

  box-shadow: 0 12px 40px rgba(0,0,0,.14);

  transition: transform .15s ease, box-shadow .15s ease;
}

.stk-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 24px 60px rgba(0,0,0,.28);
}

.stk-card small {
  font-size: 12px;
  letter-spacing: .3px;
  text-transform: uppercase;
  color: var(--stk-muted);
}

.stk-card h2 {
  margin-top: 6px;
  font-size: 22px;
  font-weight: 600;
  color: var(--stk-text);
}

/* ======================================================
   TABLE
====================================================== */
.stk-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;

  background: var(--stk-panel);
  border: 1px solid var(--stk-border);
  border-radius: 16px;
  overflow: hidden;

  box-shadow: 0 16px 50px rgba(0,0,0,.16);
}

/* Header */
.stk-table th {
  padding: 14px;

  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--stk-panel) 95%, black),
    color-mix(in srgb, var(--stk-panel) 90%, black)
  );

  font-size: 12px;
  font-weight: 600;
  letter-spacing: .4px;
  text-transform: uppercase;

  color: var(--stk-muted);
  border-bottom: 1px solid var(--stk-border);
}

/* Body */
.stk-table td {
  padding: 14px;
  font-size: 14px;
  color: var(--stk-text);

  border-bottom: 1px solid var(--stk-border);
}

.stk-table tbody tr:hover {
  background: var(--stk-hover);
}

.stk-table tbody tr:last-child td {
  border-bottom: none;
}

.stk-right {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* ======================================================
   STATUS PILLS
====================================================== */
.pill {
  padding: 4px 10px;
  border-radius: 999px;

  font-size: 12px;
  font-weight: 600;

  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Low stock */
.pill.low {
  background: rgba(245,158,11,.15);
  color: #f59e0b;
  border: 1px solid rgba(245,158,11,.35);
}

/* Out of stock */
.pill.oos {
  background: rgba(239,68,68,.15);
  color: #ef4444;
  border: 1px solid rgba(239,68,68,.35);
}

</style>

<div class="stk-wrap">
  <h1 class="stk-title"> Stock Levels</h1>

  <div class="stk-filters">
    <input id="q" placeholder="Search name or SKU">
    <select id="cat">
      <option value="">All categories</option>
    </select>
    <select id="only">
      <option value="">All items</option>
      <option value="low">Low stock (≤ threshold)</option>
      <option value="out">Out of stock (≤ 0)</option>
    </select>
    <button class="stk-run" id="run">Run</button>
    <button class="stk-run" id="export">Export CSV</button>
  </div>

  <div class="stk-cards">
    <div class="stk-card"><small>Products (total)</small><h2 id="kCount">0</h2></div>
    <div class="stk-card"><small>Low Stock</small><h2 id="kLow">0</h2></div>
    <div class="stk-card"><small>Out of Stock</small><h2 id="kOut">0</h2></div>
    <div class="stk-card"><small>Total On-Hand</small><h2 id="kOnHand">0</h2></div>
  </div>

  <table class="stk-table" id="tbl">
    <thead>
      <tr>
        <th>SKU</th>
        <th>Product</th>
        <th>Category</th>
        <th class="stk-right">On Hand</th>
        <th class="stk-right">Threshold</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody id="tbody"><tr><td>Loading…</td></tr></tbody>
  </table>
</div>

<script>
const $ = s => document.querySelector(s);

function qs(){
  const p = new URLSearchParams();
  const q = $('#q').value.trim();
  const cat = $('#cat').value;
  const only = $('#only').value;
  if (q) p.set('q', q);
  if (cat) p.set('category_id', cat);
  if (only) p.set('only', only); // low|out
  return p.toString();
}

async function json(url){
  const r = await fetch(url); const t = await r.text();
  try { return JSON.parse(t); } catch(e){ console.error('Bad JSON', t); return {success:false, rows:[]} }
}

async function loadCategories(){
  // simple categories list (no new controller; we can reuse stock_levels result to infer, but better fetch directly)
  const r = await fetch('/POS_UG/controllers/reportsController.php?action=categories');
  const j = await r.json();
  const sel = $('#cat');
  (j.rows||[]).forEach(c=>{
    const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; sel.appendChild(o);
  });
}

function num(n){ return Number(n||0).toLocaleString(); }

async function run(){
  $('#tbody').innerHTML = '<tr><td>Loading…</td></tr>';
  const data = await json('/POS_UG/controllers/reportsController.php?action=stock_levels&'+qs());
  const rows = data.rows||[];

  // KPIs
  const low = rows.filter(r => Number(r.on_hand) <= Number(r.stock_alert_threshold||0)).length;
  const out = rows.filter(r => Number(r.on_hand) <= 0).length;
  const onhandTotal = rows.reduce((a,r)=>a+Number(r.on_hand||0),0);

  $('#kCount').textContent = num(rows.length);
  $('#kLow').textContent   = num(low);
  $('#kOut').textContent   = num(out);
  $('#kOnHand').textContent= num(onhandTotal);

  // Table
  const tr = rows.map(r=>{
    const status = Number(r.on_hand)<=0 ? '<span class="pill oos">Out</span>'
                 : Number(r.on_hand) <= Number(r.stock_alert_threshold||0) ? '<span class="pill low">Low</span>'
                 : '';
    return `<tr>
      <td>${r.sku||''}</td>
      <td>${r.name}</td>
      <td>${r.category||''}</td>
      <td class="stk-right">${num(r.on_hand)}</td>
      <td class="stk-right">${num(r.stock_alert_threshold||0)}</td>
      <td>${status}</td>
    </tr>`;
  }).join('');
  $('#tbody').innerHTML = tr || '<tr><td>No items found</td></tr>';
}

$('#run').addEventListener('click', run);
$('#export').addEventListener('click', ()=>{
  const url = '/POS_UG/controllers/reportsController.php?action=stock_levels&'+qs()+'&export=csv';
  const a=document.createElement('a'); a.href=url; a.click();
});
loadCategories().then(run);
</script>
