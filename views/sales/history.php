<?php require_once('../../includes/auth.php'); ?>
<link rel="stylesheet" href="../../assets/css/terminal.css" />
<style>

    /* ========== Sales History (scoped to this page) ========== */
:root {
  --bg:#0f1115;        /* page background */
  --panel:#141821;     /* cards / table */
  --panel-2:#10131a;   /* zebra rows */
  --line:#222838;      /* borders */
  --text:#e7e9ee;      /* main text */
  --muted:#98a2b3;     /* secondary text */
  --accent:#00d084;    /* brand green */
  --accent-2:#00b06a;  /* hover green */
  --danger:#ef5350;
}

.history-wrap{
  background:var(--bg);
  color:var(--text);
  padding:18px 20px;
  display:flex;
  flex-direction:column;
  gap:14px;
  min-height:100vh;
  box-sizing:border-box;
}

/* ---------- Header ---------- */
.history-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.history-head h2{
  margin:0;
  font-weight:700;
  letter-spacing:.2px;
}
.history-head .group{ display:flex; gap:8px; }

/* ---------- Buttons ---------- */
.history-btn{
  appearance:none;
  border:1px solid transparent;
  background:var(--accent);
  color:#071b12;
  font-weight:600;
  padding:10px 14px;
  border-radius:10px;
  cursor:pointer;
  transition:transform .06s ease, background .15s ease, border-color .15s ease;
  text-decoration:none;
}
.history-btn:hover{ background:var(--accent-2); }
.history-btn:active{ transform:translateY(1px); }

/* ---------- Filter bar ---------- */
.history-filters{
  display:grid;
  grid-template-columns: repeat(3, max-content) minmax(260px, 1fr) max-content max-content;
  gap:12px 14px;
  align-items:end;
  background:var(--panel);
  padding:14px;
  border:1px solid var(--line);
  border-radius:14px;
}
.history-filters label{
  display:block;
  font-size:12px;
  color:var(--muted);
  margin:0 0 6px;
}
.history-filters input[type="date"],
.history-filters input[type="text"],
.history-filters select{
  background:#0c0f16;
  color:var(--text);
  border:1px solid var(--line);
  outline:none;
  padding:10px 12px;
  border-radius:10px;
  min-width:190px;
  transition:border-color .15s ease, box-shadow .15s ease;
}
.history-filters input:focus,
.history-filters select:focus{
  border-color:#2b8a6e;
  box-shadow:0 0 0 3px rgba(0,208,132,.15);
}
.history-chip{
  background:#0c0f16;
  border:1px solid var(--line);
  color:var(--muted);
  padding:10px 12px;
  border-radius:10px;
  display:flex;
  align-items:center;
  gap:10px;
}
.page-size{
  background:#0c0f16;
  color:var(--text);
  border:1px solid var(--line);
  padding:8px 10px;
  border-radius:8px;
}

/* ---------- Table ---------- */
.history-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:14px;
  overflow:hidden; /* clip sticky header corners */
  font-size:14px;
}
.history-table thead th{
  position:sticky; top:0; z-index:1;
  background:linear-gradient(#1a2030,#171b28);
  color:#dfe5ef;
  padding:12px 14px;
  text-align:left;
  border-bottom:1px solid var(--line);
  white-space:nowrap;
}
.history-table thead th.sortable{
  cursor:pointer;
  user-select:none;
}
.history-table thead th.sortable::after{
  content:"";
  display:inline-block;
  margin-left:8px;
  border:5px solid transparent;
  border-top-color:#7f8aaa; /* caret */
  transform:translateY(2px);
  opacity:.6;
}
.history-table thead th.sortable[data-dir="asc"]::after{
  border-top-color:transparent;
  border-bottom-color:#7f8aaa;
  transform:translateY(-2px) rotate(180deg);
  opacity:1;
}
.history-table thead th.sortable[data-dir="desc"]::after{
  border-top-color:#7f8aaa;
  opacity:1;
}
.history-table tbody td{
  padding:12px 14px;
  border-bottom:1px solid #1b2030;
  vertical-align:middle;
}
.history-table tbody tr:nth-child(even){ background:var(--panel-2); }
.history-table tbody tr:hover{ background:#171d2a; }

.history-table td[style*="text-align:right"],
.history-table .num{ text-align:right; font-variant-numeric:tabular-nums; }

/* long comments: keep single-line but show full on hover */
.history-table td:last-child{
  max-width:560px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* ---------- Payment badges ---------- */
.pay-badge{
  display:inline-block;
  padding:4px 10px;
  border-radius:999px;
  font-weight:700;
  font-size:12px;
  letter-spacing:.2px;
  border:1px solid transparent;
}
.pay-cash   { background:#0b2a1e; color:#7ef0b6; border-color:#1e5a42; }
.pay-card   { background:#111f3d; color:#9dc0ff; border-color:#27487a; }
.pay-credit { background:#3a1525; color:#ff9cb5; border-color:#6e2a45; }
.pay-voucher{ background:#2a1f0b; color:#ffd38a; border-color:#5c4821; }
.pay-gift   { background:#23163b; color:#e3bcff; border-color:#4c3079; }

/* ---------- Pager ---------- */
.history-paging{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.history-paging .group{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

.page-btn{
  background:#0c0f16;
  color:var(--text);
  border:1px solid var(--line);
  padding:8px 12px;
  border-radius:10px;
  cursor:pointer;
  transition:background .15s ease, border-color .15s ease, transform .06s ease;
}
.page-btn:hover{ background:#131828; border-color:#2b3245; }
.page-btn:active{ transform:translateY(1px); }
.page-btn[disabled]{ opacity:.45; cursor:not-allowed; }
.page-btn.active{ background:var(--accent); color:#071b12; border-color:transparent; }

/* ---------- Custom scrollbar (webkit) ---------- */
.history-wrap::-webkit-scrollbar,
.history-table::-webkit-scrollbar{ width:10px; height:10px; }
.history-wrap::-webkit-scrollbar-thumb,
.history-table::-webkit-scrollbar-thumb{
  background:#2a3246; border-radius:10px;
}
.history-wrap::-webkit-scrollbar-track{ background:#0c0f16; }

/* ---------- Responsive ---------- */
@media (max-width: 900px){
  .history-filters{
    grid-template-columns: 1fr 1fr;
  }
  .history-filters > div[style*="min-width"]{ grid-column:1 / -1; }
  .history-head{ flex-direction:column; align-items:flex-start; gap:10px; }
  .history-head .group{ width:100%; }
}
    </style>
<div class="history-wrap">
  <div class="history-head">
    <h2>Sales History</h2>
    <div class="group">
      <a href="./terminal.php" class="history-btn" style="text-decoration:none; display:inline-block;">← Back to Terminal</a>
      <button id="btnRefresh" class="history-btn">Refresh</button>
    </div>
  </div>

  <div class="history-filters">
    <div>
      <label for="dateFrom">Date From</label>
      <input type="date" id="dateFrom">
    </div>
    <div>
      <label for="dateTo">Date To</label>
      <input type="date" id="dateTo">
    </div>
    <div>
      <label for="paymentType">Payment Type</label>
      <select id="paymentType">
        <option value="">All</option>
        <option value="cash">Cash</option>
        <option value="credit">Credit</option>
        <option value="debit">Debit</option>
        <option value="voucher">Voucher</option>
        <option value="gift">Gift Card</option>
      </select>
    </div>
    <div style="min-width:260px">
      <label for="q">Search</label>
      <input type="text" id="q" placeholder="Sale ID, comment, user…">
    </div>
    <button id="btnApply" class="history-btn">Apply</button>

    <div class="history-chip">
      <label for="pageSize">Rows</label>
      <select id="pageSize" class="page-size">
        <option>10</option><option selected>25</option><option>50</option><option>100</option>
      </select>
    </div>
  </div>

  <div style="overflow:auto; max-height: calc(100vh - 260px); border-radius:12px;">
    <table class="history-table" id="tbl">
      <thead>
        <tr>
          <th class="sortable" data-col="id">ID</th>
          <th class="sortable" data-col="created_at">Created</th>
          <th>Payment</th>
          <th class="sortable" data-col="subtotal" style="text-align:right">Subtotal</th>
          <th class="sortable" data-col="discount_amount" style="text-align:right">Discount</th>
          <th class="sortable" data-col="tax_amount" style="text-align:right">Tax</th>
          <th class="sortable" data-col="total_amount" style="text-align:right">Total</th>
          <th class="sortable" data-col="paid_amount" style="text-align:right">Paid</th>
          <th class="sortable" data-col="change_amount" style="text-align:right">Change</th>
          <th>User</th>
          <th>Comment</th>
        </tr>
      </thead>
      <tbody id="rows">
        <tr><td colspan="11" style="padding:14px;color:#9aa0a6">Loading…</td></tr>
      </tbody>
    </table>
  </div>

  <div class="history-paging">
    <div class="group" id="pageInfo">Showing 0–0 of 0</div>
    <div class="group" id="pager">
      <button class="page-btn" id="first">« First</button>
      <button class="page-btn" id="prev">‹ Prev</button>
      <div class="group" id="pages"></div>
      <button class="page-btn" id="next">Next ›</button>
      <button class="page-btn" id="last">Last »</button>
    </div>
  </div>
</div>

<script>
const state = {
  page: 1,
  pageSize: 25,
  sortCol: 'created_at',
  sortDir: 'desc',
  filters: { from:'', to:'', payment:'', q:'' },
  total: 0
};

const els = {
  rows: document.getElementById('rows'),
  pageSize: document.getElementById('pageSize'),
  dateFrom: document.getElementById('dateFrom'),
  dateTo: document.getElementById('dateTo'),
  paymentType: document.getElementById('paymentType'),
  q: document.getElementById('q'),
  pageInfo: document.getElementById('pageInfo'),
  pages: document.getElementById('pages'),
  first: document.getElementById('first'),
  prev: document.getElementById('prev'),
  next: document.getElementById('next'),
  last: document.getElementById('last')
};

function toParams(obj){ return Object.entries(obj).filter(([,v]) => v!=='' && v!=null).map(([k,v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&'); }
function fmt(n){ return Number(n||0).toFixed(2); }

async function loadSales(){
  els.rows.innerHTML = `<tr><td colspan="11" style="padding:14px;color:#9aa0a6">Loading…</td></tr>`;

  const offset = (state.page-1)*state.pageSize;
  const qs = toParams({
    action: 'history',
    limit: state.pageSize,
    offset,
    sort: state.sortCol,
    dir: state.sortDir,
    from: state.filters.from,
    to: state.filters.to,
    payment: state.filters.payment,
    q: state.filters.q
  });

  const res = await fetch(`../../controllers/salesController.php?${qs}`, {cache:'no-store'});
  const data = await res.json();

  if(!data.success){
    els.rows.innerHTML = `<tr><td colspan="11" style="padding:14px;color:#e65b5b">Error loading sales.</td></tr>`;
    return;
  }

  state.total = data.total || (data.sales?.length || 0);

  // Render
  const rows = (data.sales||[]).map(s => {
    const pay = (s.payment_type||'').toLowerCase();
    let badgeClass = 'pay-badge';
    if(pay.includes('cash')) badgeClass+=' pay-cash';
    else if(pay.includes('credit') || pay.includes('card')) badgeClass+=' pay-card';
    else badgeClass+=' pay-credit';

    return `
      <tr>
        <td>${s.id}</td>
        <td>${s.created_at}</td>
        <td><span class="${badgeClass}">${s.payment_type||''}</span></td>
        <td style="text-align:right">${fmt(s.subtotal)}</td>
        <td style="text-align:right">${fmt(s.discount_amount)}</td>
        <td style="text-align:right">${fmt(s.tax_amount)}</td>
        <td style="text-align:right">${fmt(s.total_amount)}</td>
        <td style="text-align:right">${fmt(s.paid_amount)}</td>
        <td style="text-align:right">${fmt(s.change_amount)}</td>
        <td>${s.user||''}</td>
        <td>${(s.comment||'').replace(/</g,'&lt;')}</td>
      </tr>
    `;
  }).join('');
  els.rows.innerHTML = rows || `<tr><td colspan="11" style="padding:14px;color:#9aa0a6">No results.</td></tr>`;

  renderPager();
}

function renderPager(){
  const totalPages = Math.max(1, Math.ceil(state.total / state.pageSize));
  state.page = Math.min(state.page, totalPages);
  const start = (state.page-1)*state.pageSize + 1;
  const end = Math.min(state.page*state.pageSize, state.total);
  els.pageInfo.textContent = `Showing ${state.total?start:0}–${state.total?end:0} of ${state.total}`;

  els.pages.innerHTML = '';
  const pages = [];
  const win = 2; // window around current page
  let from = Math.max(1, state.page - win);
  let to   = Math.min(totalPages, state.page + win);
  for(let p=from; p<=to; p++){
    const btn = document.createElement('button');
    btn.className = 'page-btn' + (p===state.page?' active':'');
    btn.textContent = p;
    btn.onclick = () => { state.page = p; loadSales(); };
    els.pages.appendChild(btn);
  }

  // controls
  els.first.disabled = state.page===1;
  els.prev.disabled  = state.page===1;
  els.next.disabled  = state.page===totalPages;
  els.last.disabled  = state.page===totalPages;

  els.first.onclick = () => { state.page=1; loadSales(); };
  els.prev.onclick  = () => { if(state.page>1){state.page--; loadSales();} };
  els.next.onclick  = () => { if(state.page<totalPages){state.page++; loadSales();} };
  els.last.onclick  = () => { state.page=totalPages; loadSales(); };
}

// sorting
document.querySelectorAll('.history-table thead th.sortable').forEach(th=>{
  th.addEventListener('click', ()=>{
    const col = th.dataset.col;
    if(state.sortCol===col){ state.sortDir = (state.sortDir==='asc'?'desc':'asc'); }
    else { state.sortCol = col; state.sortDir = 'asc'; }
    document.querySelectorAll('.history-table thead th.sortable').forEach(h=>h.removeAttribute('data-dir'));
    th.setAttribute('data-dir', state.sortDir);
    state.page = 1;
    loadSales();
  });
});

// events
document.getElementById('btnApply').addEventListener('click', ()=>{
  state.filters = {
    from: els.dateFrom.value || '',
    to: els.dateTo.value || '',
    payment: els.paymentType.value || '',
    q: els.q.value.trim()
  };
  state.page = 1;
  loadSales();
});
document.getElementById('btnRefresh').addEventListener('click', loadSales);
els.pageSize.addEventListener('change', ()=>{ state.pageSize = +els.pageSize.value; state.page = 1; loadSales(); });

// debounce search on typing
let t; els.q.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>document.getElementById('btnApply').click(), 350); });

loadSales();
</script>
