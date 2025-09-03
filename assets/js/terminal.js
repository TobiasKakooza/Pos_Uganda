/* =========================================================
   Sale state kept on the page
========================================================= */
window.saleCtx = {
  discountType: null,   // 'percent' | 'amount' | null
  discountValue: 0,
  comment: '',
  customer_id: null
};

function setDiscount(type, value) {
  window.saleCtx.discountType = (type === 'amount' ? 'amount' : type === 'percent' ? 'percent' : null);
  window.saleCtx.discountValue = Math.max(0, +value || 0);
  updateTotals(); // re-calc whenever discount changes
}

/* =========================================================
   DOM Ready bindings
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('productSearch');

  // Enter or F3 triggers search
  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === 'F3') triggerSearch();
    });
  }

  // Auto-update totals when tax changes
  document.getElementById('taxRate')?.addEventListener('input', updateTotals);

  // Button bindings
  document.getElementById('btnSearch')?.addEventListener('click', triggerSearch);
  document.getElementById('btnNewSale')?.addEventListener('click', clearSale);
  document.getElementById('btnVoid')?.addEventListener('click', onVoidClick);
  document.getElementById('btnQuantity')?.addEventListener('click', focusLastQuantityInput);
  document.getElementById('btnPayment')?.addEventListener('click', makePayment);

  // Payment method buttons
  document.getElementById('btncash')?.addEventListener('click', () => choosePayment('Cash'));
  document.getElementById('btncredit')?.addEventListener('click', () => choosePayment('Credit Card'));
  document.getElementById('btndebitcard')?.addEventListener('click', () => choosePayment('Debit Card'));
  document.getElementById('btnvoucher')?.addEventListener('click', () => choosePayment('Voucher'));
  document.getElementById('giftcard')?.addEventListener('click', () => choosePayment('Gift Card'));

  // Live balance update
  document.getElementById('amountPaid')?.addEventListener('input', updateBalance);

  // Admin toggle (hamburger)
  document.querySelector('.toggle-admin')?.addEventListener('click', toggleAdminPanel);

  // Keyboard shortcuts (if not in an input)
  document.addEventListener('keydown', (e) => {
    const tag = (e.target?.tagName || '').toLowerCase();
    const inInput = tag === 'input' || tag === 'textarea' || e.target?.isContentEditable;
    switch (e.key) {
      case 'F2': e.preventDefault(); document.getElementById('btndiscount')?.click(); break;
      case 'F3': e.preventDefault(); if (!inInput) document.getElementById('productSearch')?.focus(); break;
      case 'F4': e.preventDefault(); document.getElementById('btnQuantity')?.click(); break;
      case 'F8': e.preventDefault(); document.getElementById('btnNewSale')?.click(); break;
      case 'F9': e.preventDefault(); document.getElementById('btnsave')?.click(); break;
      case 'F10': e.preventDefault(); document.getElementById('btnPayment')?.click(); break;
      case 'F12': e.preventDefault(); document.getElementById('btncash')?.click(); break;
      default: break;
    }
  });

  // Utility buttons
  document.getElementById('btncashdrawer')?.addEventListener('click', () => toast('🧾 Opening cash drawer… (hook hardware here)'));
  document.getElementById('btndiscount')?.addEventListener('click', onDiscountClick);
  document.getElementById('btncomment')?.addEventListener('click', onCommentClick);
  document.getElementById('btncustomer')?.addEventListener('click', () => toast('👤 Customer picker not implemented.'));
  document.getElementById('btntransfer')?.addEventListener('click', () => toast('🔁 Stock transfer workflow not implemented.'));
  document.getElementById('btnrefund')?.addEventListener('click', () => toast('🔄 Refund workflow not implemented.'));
  document.getElementById('btnlock')?.addEventListener('click', () => toast('🔒 Locking terminal…'));
  document.getElementById('btnsave')?.addEventListener('click', saveDraft);

  // Admin panel item clicks (from terminal.php)
  window.navigateTo = navigateTo;

  // start with a clean total
  updateTotals();
});

/* =========================================================
   Search
========================================================= */
function triggerSearch() {
  const query = document.getElementById('productSearch').value.trim();
  if (query.length < 1) return;

  fetch(`../../controllers/salesController.php?action=search&query=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.product) {
        addProductToReceipt(data.product);
      } else {
        alert('Product not found!');
      }
    })
    .catch(err => {
      console.error('Search failed:', err);
      alert('Error fetching product data.');
    });
}

/* =========================================================
   Add product row
========================================================= */
function addProductToReceipt(product) {
  const table = document.getElementById('receiptItems');

  const existingRow = [...table.querySelectorAll('tr')].find(tr =>
    tr.getAttribute('data-product-id') === String(product.id)
  );

  if (existingRow) {
    const input = existingRow.querySelector('.qty-input');
    const currentQty = parseInt(input.value) || 1;
    const newQty = Math.min(currentQty + 1, parseInt(input.getAttribute('data-stock')));
    input.value = newQty;
    const price = parseFloat(input.getAttribute('data-price'));
    existingRow.querySelector('.amount-cell').innerText = (price * newQty).toFixed(2);
    updateTotals();
    return;
  }

  const row = document.createElement('tr');
  row.setAttribute('data-product-id', product.id);

  const price = parseFloat(product.price).toFixed(2);

  row.innerHTML = `
    <td>${product.name} <span class="stock-label">(Stock: ${product.stock})</span></td>
    <td><input type="number" min="1" max="${product.stock}" value="1"
      class="qty-input" data-price="${price}" data-stock="${product.stock}"></td>
    <td>${price}</td>
    <td class="amount-cell">${price}</td>
    <td><button class="btn-remove" data-id="${product.id}">❌</button></td>
  `;

  const placeholder = table.querySelector('.no-items');
  if (placeholder) placeholder.remove();

  table.appendChild(row);

  if (!window.productCache) window.productCache = [];
  window.productCache.push(product);

  updateTotals();

  const qtyInput = row.querySelector('.qty-input');
  qtyInput.addEventListener('input', function () {
    let qty = parseInt(this.value) || 1;
    const max = parseInt(this.getAttribute('data-stock'));
    const price = parseFloat(this.getAttribute('data-price'));

    qty = Math.max(1, Math.min(qty, max));
    this.value = qty;

    const amount = (qty * price).toFixed(2);
    row.querySelector('.amount-cell').innerText = amount;
    updateTotals();
  });

  row.querySelector('.btn-remove').addEventListener('click', function () {
    row.remove();
    updateTotals();

    // Show placeholder if empty
    const hasItems = document.querySelectorAll('#receiptItems tr[data-product-id]').length;
    if (!hasItems) {
      table.innerHTML = `
        <tr class="no-items">
          <td colspan="5" class="center-text">
            No items<br><small>Add products using barcode, code or search (F3)</small>
          </td>
        </tr>
      `;
    }
  });
}

/* =========================================================
   Totals (subtotal -> discount -> tax -> total)
========================================================= */
function updateTotals() {
  let subtotal = 0;
  const rows = document.querySelectorAll('#receiptItems tr');

  rows.forEach(row => {
    if (row.classList.contains('no-items')) return;
    const amount = parseFloat(row.querySelector('.amount-cell')?.innerText || 0);
    subtotal += amount;
  });

  // Discount
  const ctx = window.saleCtx || {};
  let discountAmt = 0;
  if (ctx.discountType === 'percent') {
    discountAmt = subtotal * ((+ctx.discountValue || 0) / 100);
  } else if (ctx.discountType === 'amount') {
    discountAmt = Math.min(subtotal, (+ctx.discountValue || 0));
  }
  const discountedBase = Math.max(0, subtotal - discountAmt);

  // Tax on discounted base
  const taxRate   = parseFloat(document.getElementById('taxRate')?.value) || 0;
  const taxAmount = discountedBase * (taxRate / 100);
  const total     = discountedBase + taxAmount;

  document.getElementById('subtotal').innerText  = subtotal.toFixed(2);
  document.getElementById('taxAmount').innerText = taxAmount.toFixed(2);
  document.getElementById('total').innerText     = total.toFixed(2);
}

/* =========================================================
   Clear sale / quantity focus
========================================================= */
function clearSale() {
  const table = document.getElementById('receiptItems');
  table.innerHTML = `
    <tr class="no-items">
      <td colspan="5" class="center-text">
        No items<br><small>Add products using barcode, code or search (F3)</small>
      </td>
    </tr>
  `;
  window.saleCtx.discountType = null;
  window.saleCtx.discountValue = 0;
  window.saleCtx.comment = '';
  updateTotals();
  const si = document.getElementById('productSearch');
  if (si) si.value = '';
}

function focusLastQuantityInput() {
  const inputs = document.querySelectorAll('.qty-input');
  if (inputs.length > 0) inputs[inputs.length - 1].focus();
}

/* =========================================================
   Payment flow
========================================================= */
function makePayment() {
  const total = parseFloat(document.getElementById('total').innerText);
  if (total === 0) {
    alert('Cannot make payment for empty sale.');
    return;
  }
  openPaymentModal(total);
}

let totalAmount = 0;

function openPaymentModal(total) {
  totalAmount = total;
  document.getElementById('paymentTotal').innerText = total.toFixed(2);
  document.getElementById('paymentModal')?.classList.remove('hidden');
  document.getElementById('step1')?.classList.remove('hidden');
  document.getElementById('step2')?.classList.add('hidden');
  document.getElementById('amountPaid').value = '';
  document.getElementById('balanceDue').innerText = '0.00';
}

function choosePayment(method) {
  document.getElementById('selectedMethodLabel').innerText = `${method} Payment`;
  document.getElementById('step1')?.classList.add('hidden');
  document.getElementById('step2')?.classList.remove('hidden');
}

function updateBalance() {
  const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
  const balance = paid - totalAmount;
  document.getElementById('balanceDue').innerText = balance.toFixed(2);
}

function completePayment() {
  const balance = parseFloat(document.getElementById('balanceDue').innerText);
  if (isNaN(balance)) return alert('Invalid payment amount.');
  if (balance < 0)   return alert('Insufficient payment.');

  const rows  = document.querySelectorAll('#receiptItems tr');
  const items = [];
  rows.forEach(row => {
    const qtyInput = row.querySelector('.qty-input');
    if (!qtyInput) return;
    const productId = row.getAttribute('data-product-id');
    if (!productId) return;
    items.push({
      product_id: parseInt(productId),
      quantity:   parseInt(qtyInput.value),
      unit_price: parseFloat(qtyInput.getAttribute('data-price')),
    });
  });
  if (!items.length) return alert('No items to save.');

  const paymentType = document.getElementById('selectedMethodLabel').innerText.split(' ')[0];
  const taxRate   = parseFloat(document.getElementById('taxRate').value || '0');
  const paidInput = parseFloat(document.getElementById('amountPaid').value || '0');

  const payload = {
    items,
    payment_type:  paymentType,
    discount_type:  window.saleCtx.discountType,
    discount_value: window.saleCtx.discountValue,
    comment:        window.saleCtx.comment,
    customer_id:    window.saleCtx.customer_id || null,
    tax_rate:       taxRate,
    paid_amount:    isNaN(paidInput) || paidInput <= 0 ? undefined : paidInput
  };

  fetch('../../controllers/salesController.php?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) return alert('❌ Sale failed: ' + data.message);

    let message = `✅ Sale recorded successfully!\n🧾 Sale ID: ${data.sale_id}`;
    if (data.alerts?.length) message += `\n⚠️ Low Stock Alerts:\n- ${data.alerts.join('\n- ')}`;
    alert(message);

    // Ask to print
    const shouldPrint = confirm('🖨️ Do you want to view/print the receipt?');
    if (shouldPrint) {
      const subtotal  = parseFloat(document.getElementById('subtotal').innerText || '0');
      const taxRate   = parseFloat(document.getElementById('taxRate').value || '0');
      const taxAmount = parseFloat(document.getElementById('taxAmount').innerText || '0');
      const total     = parseFloat(document.getElementById('total').innerText || '0');
      const paid      = parseFloat(document.getElementById('amountPaid').value || total);
      const change    = (paid - total).toFixed(2);

      const url = `/POS_UG/controllers/printReceipt.php?sale_id=${data.sale_id}`
        + `&subtotal=${encodeURIComponent(subtotal.toFixed(2))}`
        + `&taxRate=${encodeURIComponent(taxRate)}`
        + `&taxAmount=${encodeURIComponent(taxAmount.toFixed(2))}`
        + `&paid=${encodeURIComponent(paid.toFixed(2))}`
        + `&change=${encodeURIComponent(change)}`;

      openReceipt(url);
    }

    closePaymentModal();
    clearSale();
  })
  .catch(err => {
    console.error('Payment failed:', err);
    alert('❌ Network or server error.');
  });
}

function closePaymentModal() {
  document.getElementById('paymentModal')?.classList.add('hidden');
  document.getElementById('amountPaid').value = '';
  document.getElementById('balanceDue').innerText = '0.00';
}

function backToMethods() {
  document.getElementById('step1')?.classList.remove('hidden');
  document.getElementById('step2')?.classList.add('hidden');
}

function onVoidClick() {
  if (!confirm('Are you sure you want to void this order?')) return;
  const receiptItems = document.getElementById('receiptItems');
  receiptItems.innerHTML = `
    <tr class="no-items">
      <td colspan="5" class="center-text">No items<br><small>Add products using barcode, code or search (F3)</small></td>
    </tr>
  `;
  document.getElementById('subtotal').textContent = '0.00';
  document.getElementById('taxRate').value = '0';
  document.getElementById('taxAmount').textContent = '0.00';
  document.getElementById('total').textContent = '0.00';
  if (!document.getElementById('paymentModal').classList.contains('hidden')) {
    closePaymentModal();
  }
}

/* =========================================================
   Discount & Comment helpers
========================================================= */
function onDiscountClick() {
  const type = prompt("Discount type: 'percent' or 'amount'?", window.saleCtx.discountType || 'percent');
  if (!type) return;
  const val = prompt(`Enter ${type === 'amount' ? 'amount' : '% (0-100)'}:`, window.saleCtx.discountValue ?? 0);
  if (val == null) return;
  setDiscount(type, parseFloat(val));
  alert('🏷️ Discount applied');
}

function onCommentClick() {
  const note = prompt('Add a comment to this sale:', window.saleCtx.comment || '');
  if (note != null) { window.saleCtx.comment = note; alert('💬 Comment saved'); }
}

/* =========================================================
   Admin panel popup + routing
========================================================= */
function toggleAdminPanel() {
  const panel = document.getElementById('adminPanel');
  panel?.classList.toggle('visible');
}

// Simple router for the sidebar items
function navigateTo(module) {
  switch (module) {
    case 'management':
      window.location.href = '/POS_UG/views/dashboard.php';
      break;
    case 'sales-history':
      openSalesHistory(); break;
    case 'open-sales':
      openOpenSales(); break;
    case 'cash-in-out':
      openCashModal(); break;
    case 'credit-payments':
      openCreditModal(); break;
    case 'end-of-day':
      toast('📅 End of day not implemented'); break;
    case 'user-info':
      openUserInfo(); break;
    case 'sign-out':
      window.location.href = '/POS_UG/logout.php'; break;
    case 'feedback':
      openFeedbackModal(); break;
    default:
      toast('Unknown option');
  }
  toggleAdminPanel();
}

/* =========================================================
   Lightweight admin modal (no dependency)
========================================================= */
function ensureAdminModal() {
  if (document.getElementById('adminModal')) return;
  const wrap = document.createElement('div');
  wrap.id = 'adminModal';
  wrap.style.cssText = `
    position:fixed; inset:0; background:rgba(0,0,0,.55);
    display:none; align-items:center; justify-content:center; z-index:10050;
  `;
  wrap.innerHTML = `
    <div style="background:#1f1f1f; color:#fff; width:min(900px,90vw); max-height:90vh; border-radius:10px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.5); display:flex; flex-direction:column;">
      <div style="padding:10px 12px; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center;">
        <strong id="adminModalTitle">Modal</strong>
        <button id="adminModalClose" class="wide-btn btn-void">✖</button>
      </div>
      <div id="adminModalBody" style="padding:12px; overflow:auto;"></div>
      <div id="adminModalFooter" style="padding:10px; border-top:1px solid #333; display:flex; gap:10px; justify-content:flex-end;"></div>
    </div>
  `;
  document.body.appendChild(wrap);
  document.getElementById('adminModalClose').addEventListener('click', closeAdminModal);
}
function openAdminModal(title, html, footerHtml = '') {
  ensureAdminModal();
  document.getElementById('adminModalTitle').innerText = title || 'Details';
  document.getElementById('adminModalBody').innerHTML = html || '';
  document.getElementById('adminModalFooter').innerHTML = footerHtml || '';
  const M = document.getElementById('adminModal');
  M.style.display = 'flex';
}
function closeAdminModal() {
  const M = document.getElementById('adminModal');
  if (M) M.style.display = 'none';
}

/* =========================================================
   Sales history (Admin)
========================================================= */
async function openSalesHistory() {
  try {
    const res = await fetch('../../controllers/salesController.php?action=history');
    const data = await res.json();
    if (!data.success) throw new Error('Failed to fetch');

    const rows = (data.sales || []).map(s => `
      <tr>
        <td>${s.id}</td>
        <td>${s.created_at}</td>
        <td>${s.payment_type || ''}</td>
        <td style="text-align:right">${Number(s.total_amount).toFixed(2)}</td>
        <td>${s.user || ''}</td>
      </tr>`).join('');

    openAdminModal(
      'Sales history (latest 20)',
      `
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="border-bottom:1px solid #444; text-align:left; padding:6px 4px;">ID</th>
            <th style="border-bottom:1px solid #444; text-align:left; padding:6px 4px;">Date</th>
            <th style="border-bottom:1px solid #444; text-align:left; padding:6px 4px;">Payment</th>
            <th style="border-bottom:1px solid #444; text-align:right; padding:6px 4px;">Total</th>
            <th style="border-bottom:1px solid #444; text-align:left; padding:6px 4px;">User</th>
          </tr>
        </thead>
        <tbody>${rows || `<tr><td colspan="5" style="padding:8px;">No data</td></tr>`}</tbody>
      </table>
      `
    );
  } catch (e) {
    //alert('Could not load history');
    console.error(e);
  }
}

/* =========================================================
   Drafts (Open sales)
========================================================= */
function collectCart() {
  const rows = Array.from(document.querySelectorAll('#receiptItems tr[data-product-id]'));
  const lines = rows.map(tr => {
    const id = parseInt(tr.getAttribute('data-product-id')) || 0;
    const qtyInput = tr.querySelector('.qty-input');
    const qty = parseInt(qtyInput ? qtyInput.value : '1');
    const price = parseFloat(qtyInput ? qtyInput.getAttribute('data-price') : '0');
    const name = tr.children[0] ? tr.children[0].innerText.trim() : '';
    return { product_id: id, name, quantity: qty, unit_price: price };
  });

  return {
    items: lines,
    tax_rate: parseFloat(document.getElementById('taxRate')?.value || '0'),
    subtotal: parseFloat(document.getElementById('subtotal')?.innerText || '0'),
    tax_amount: parseFloat(document.getElementById('taxAmount')?.innerText || '0'),
    total: parseFloat(document.getElementById('total')?.innerText || '0'),
    comment: window.__saleComment || '',
    discount_amount: window.__discount || 0
  };
}

async function saveDraft() {
  try {
    const payload = collectCart();
    const res = await fetch('../../controllers/salesController.php?action=draft_save', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ payload })
    });
    const data = await res.json();
    if (data.success) toast('💾 Draft saved'); else alert('Failed to save draft');
  } catch {
    alert('Failed to save draft');
  }
}

const draftUI = { page:1, size:10, q:'', total:0 };

async function openOpenSales() {
  draftUI.page = 1; draftUI.q = '';
  renderDrafts();
}

async function renderDrafts() {
  const offset = (draftUI.page-1)*draftUI.size;
  const qs = new URLSearchParams({
    action: 'draft_list',
    limit:  draftUI.size,
    offset: offset,
    q: draftUI.q
  }).toString();

  const res = await fetch(`../../controllers/salesController.php?${qs}`);
  const data = await res.json();

  if (!data.success) {
    return openAdminModal('Open sales (drafts)', `<p style="padding:10px">Failed to load drafts.</p>`);
  }
  draftUI.total = data.total || 0;
  const rows = (data.drafts||[]).map(d => `
    <tr>
      <td style="padding:10px 8px">${d.id}</td>
      <td style="padding:10px 8px">${d.created_at}</td>
      <td style="padding:10px 8px; display:flex; gap:8px;">
        <button class="wide-btn" onclick="loadDraft(${d.id})">Load</button>
        <button class="wide-btn btn-void" onclick="closeDraft(${d.id})">Close</button>
      </td>
    </tr>
  `).join('');

  const totalPages = Math.max(1, Math.ceil(draftUI.total/draftUI.size));
  const pager = `
    <div style="display:flex; align-items:center; gap:8px; justify-content:flex-end; padding:10px 0;">
      <button class="wide-btn" ${draftUI.page<=1?'disabled':''} onclick="draftGoto(1)">«</button>
      <button class="wide-btn" ${draftUI.page<=1?'disabled':''} onclick="draftGoto(${draftUI.page-1})">‹</button>
      <span style="color:#aaa">Page ${draftUI.page} / ${totalPages}</span>
      <button class="wide-btn" ${draftUI.page>=totalPages?'disabled':''} onclick="draftGoto(${draftUI.page+1})">›</button>
      <button class="wide-btn" ${draftUI.page>=totalPages?'disabled':''} onclick="draftGoto(${totalPages})">»</button>
    </div>
  `;

  openAdminModal(
    'Open sales (drafts)',
    `
    <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
      <input id="draftSearch" placeholder="Search by ID or date…" style="flex:1; padding:10px; background:#111; color:#eee; border:1px solid #333; border-radius:8px" value="${draftUI.q}">
      <select id="draftPageSize" style="padding:10px; background:#111; color:#eee; border:1px solid #333; border-radius:8px">
        <option ${draftUI.size==10?'selected':''}>10</option>
        <option ${draftUI.size==20?'selected':''}>20</option>
        <option ${draftUI.size==50?'selected':''}>50</option>
      </select>
      <button class="wide-btn" onclick="applyDraftSearch()">Apply</button>
    </div>

    <table style="width:100%; border-collapse:collapse; background:#1b1b1b; border:1px solid #333; border-radius:8px; overflow:hidden">
      <thead style="background:#242424; color:#ddd; position:sticky; top:0">
        <tr>
          <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #333">ID</th>
          <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #333">Created</th>
          <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #333">Action</th>
        </tr>
      </thead>
      <tbody>
        ${rows || `<tr><td colspan="3" style="padding:12px; color:#aaa">No drafts</td></tr>`}
      </tbody>
    </table>

    ${pager}
    `
  );

  // wire search + size in the freshly opened modal
  const s = document.getElementById('draftSearch');
  const ps = document.getElementById('draftPageSize');

  let t;
  s.oninput = () => { clearTimeout(t); t = setTimeout(()=>applyDraftSearch(), 300); };
  s.onkeydown = (e) => { if (e.key === 'Enter') applyDraftSearch(); };
  ps.onchange = () => applyDraftSearch();
}

// pagination / search helpers
function draftGoto(p){ draftUI.page = Math.max(1, p|0); renderDrafts(); }
function applyDraftSearch(){
  const psEl = document.getElementById('draftPageSize');
  const sEl  = document.getElementById('draftSearch');
  draftUI.size = parseInt(psEl?.value || '10', 10);
  draftUI.q    = (sEl?.value || '').trim();
  draftUI.page = 1;
  renderDrafts();
}

// close a draft (archive)
async function closeDraft(id){
  if (!confirm('Close this draft?')) return;
  const res = await fetch(`../../controllers/salesController.php?action=draft_close&id=${id}`, { method:'POST' });
  const data = await res.json();
  if (data.success){ toast('✅ Draft closed'); renderDrafts(); }
  else { alert('Failed to close draft'); }
}


async function loadDraft(id){
  const res = await fetch(`../../controllers/salesController.php?action=draft_get&id=${id}`);
  const data = await res.json();
  if (!data.success || !data.payload) return alert('Failed to load draft');

  // clear & render lines from the draft payload
  clearSale();
  (data.payload.items || []).forEach(addDraftLine);

  // restore discount/tax/comment if present
  document.getElementById('taxRate').value = data.payload.tax_rate || 0;
  window.saleCtx.discountType  = data.payload.discount_type || null;
  window.saleCtx.discountValue = data.payload.discount_value || 0;
  window.saleCtx.comment       = data.payload.comment || '';

  updateTotals();
  closeAdminModal();
  toast('🧾 Draft loaded');
}


/* =========================================================
   Cash In/Out (Admin)
========================================================= */
function openCashModal() {
  openAdminModal('Cash In / Out', `
   <div class="custom-modal grid-2-cols">
      <div>
        <label>Type</label>
        <select id="cashType" style="width:100%">
          <option value="in">Cash In</option>
          <option value="out">Cash Out</option>
        </select>
      </div>
      <div>
        <label>Amount</label>
        <input id="cashAmount" type="number" step="0.01" style="width:100%"/>
      </div>
      <div style="grid-column:1 / -1">
        <label>Note</label>
        <input id="cashNote" type="text" style="width:100%"/>
      </div>
    </div>
  `,
  `<button class="wide-btn" onclick="submitCashMove()">Save</button>`
  );
}
async function submitCashMove() {
  const payload = {
    type: document.getElementById('cashType').value,
    amount: parseFloat(document.getElementById('cashAmount').value || '0'),
    note: document.getElementById('cashNote').value || null
  };
  const res = await fetch('../../controllers/salesController.php?action=cash_move', {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) { toast('💸 Movement recorded'); closeAdminModal(); } else { alert(data.message||'Failed'); }
}

/* =========================================================
   Credit Payments (Admin)
========================================================= */
function openCreditModal() {
  openAdminModal('Record credit payment', `
    <div class="pos-form">
      <div class="field">
        <label>Customer ID</label>
        <input id="cpCustomer" type="number" placeholder="e.g. 1024"/>
      </div>
      <div class="field">
        <label>Amount</label>
        <input id="cpAmount" type="number" step="0.01" placeholder="0.00"/>
      </div>
      <div class="field">
        <label>Method</label>
        <select id="cpMethod">
          <option>cash</option>
          <option>mobile</option>
          <option>card</option>
        </select>
      </div>
      <div class="field">
        <label>Sale ID (optional)</label>
        <input id="cpSaleId" type="number" placeholder="Link to sale…"/>
      </div>
      <div class="field full">
        <label>Note</label>
        <input id="cpNote" type="text" placeholder="Optional note…"/>
      </div>
    </div>
  `,
  `<button class="save-btn" onclick="submitCreditPay()">Save</button>`
  );
}

async function submitCreditPay() {
  const payload = {
    customer_id: parseInt(document.getElementById('cpCustomer').value || '0'),
    amount: parseFloat(document.getElementById('cpAmount').value || '0'),
    method: document.getElementById('cpMethod').value,
    sale_id: parseInt(document.getElementById('cpSaleId').value || '0') || null,
    note: document.getElementById('cpNote').value || null
  };
  const res = await fetch('../../controllers/salesController.php?action=credit_pay', {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) { toast('🏦 Payment recorded'); closeAdminModal(); } else { alert(data.message||'Failed'); }
}

/* =========================================================
   User Info & Feedback (Admin)
========================================================= */
function openUserInfo() {
  openAdminModal('User info', `
    <div>
      <p>This panel can render a server endpoint with session details.</p>
      <p><em>Hook up a /controllers/user.php?action=me for live data if needed.</em></p>
    </div>
  `);
}

function openFeedbackModal() {
  openAdminModal('Feedback', `
    <div>
      <p>Tell us what’s up:</p>
      <textarea id="fbText" style="width:100%; height:180px;"></textarea>
    </div>
  `,
  `<button class="wide-btn" onclick="submitFeedback()">Send</button>`
  );
}
async function submitFeedback() {
  const text = document.getElementById('fbText').value.trim();
  if (!text) return alert('Please write something');
  console.log('Feedback:', text); // stub
  toast('📢 Thanks for the feedback!');
  closeAdminModal();
}

/* =========================================================
   Toast Helper
========================================================= */
function toast(msg) {
  let el = document.createElement('div');
  el.textContent = msg;
  el.style.cssText = `
    position:fixed; bottom:20px; right:20px; background:#333; color:#fff;
    padding:10px 12px; border-radius:6px; font-size:14px; z-index:10001; opacity:.95;
  `;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 2000);
}

/* =========================================================
   Receipt modal controls (used by PHP page)
========================================================= */
function openReceipt(url) {
  const modal = document.getElementById('receiptModal');
  const frame = document.getElementById('receiptFrame');
  if (!modal || !frame) return window.open(url, '_blank');
  frame.src = url;
  modal.style.display = 'flex';
  modal.classList.remove('hidden');
}
function closeReceipt() {
  const modal = document.getElementById('receiptModal');
  const frame = document.getElementById('receiptFrame');
  if (!modal || !frame) return;
  frame.src = '';
  modal.style.display = 'none';
  modal.classList.add('hidden');
}



function addDraftLine(line) {
  const table = document.getElementById('receiptItems');

  const row = document.createElement('tr');
  row.setAttribute('data-product-id', line.product_id);

  const price = parseFloat(line.unit_price || 0);
  const qty   = Math.max(1, parseInt(line.quantity || 1));
  const amount = (price * qty).toFixed(2);

  row.innerHTML = `
    <td>${line.name || ('Item #' + line.product_id)} <span class="stock-label">(Draft)</span></td>
    <td><input type="number" class="qty-input" min="1" value="${qty}" data-price="${price.toFixed(2)}" data-stock="999999" /></td>
    <td>${price.toFixed(2)}</td>
    <td class="amount-cell">${amount}</td>
    <td><button class="btn-remove" data-id="${line.product_id}">❌</button></td>
  `;

  // remove placeholder
  const ph = table.querySelector('.no-items'); if (ph) ph.remove();
  table.appendChild(row);

  // listeners
  const qtyInput = row.querySelector('.qty-input');
  qtyInput.addEventListener('input', function(){
    let q = Math.max(1, parseInt(this.value||'1'));
    this.value = q;
    row.querySelector('.amount-cell').innerText = (q * price).toFixed(2);
    updateTotals();
  });
  row.querySelector('.btn-remove').addEventListener('click', () => { row.remove(); updateTotals(); });

  updateTotals();
}


function collectCart() {
  const rows = Array.from(document.querySelectorAll('#receiptItems tr[data-product-id]'));
  const items = rows.map(tr => {
    const id = parseInt(tr.getAttribute('data-product-id'));
    const qtyInput = tr.querySelector('.qty-input');
    return {
      product_id: id,
      name: tr.children[0]?.innerText.replace(/\s*\(Draft\)\s*$/, '') || ('Item #' + id),
      quantity: Math.max(1, parseInt(qtyInput?.value || '1')),
      unit_price: parseFloat(qtyInput?.dataset.price || '0')
    };
  });

  return {
    items,
    tax_rate: parseFloat(document.getElementById('taxRate')?.value || '0'),
    comment: window.saleCtx?.comment || '',
    discount_type: window.saleCtx?.discountType || null,
    discount_value: window.saleCtx?.discountValue || 0
  };
}
// expose entry points
window.toggleAdminPanel = toggleAdminPanel;
window.navigateTo       = navigateTo;

window.openSalesHistory = openSalesHistory;
window.openOpenSales    = openOpenSales;
window.renderDrafts     = renderDrafts;
window.loadDraft        = loadDraft;
window.closeDraft       = closeDraft;

// (optional but handy)
window.openCashModal    = openCashModal;
window.openCreditModal  = openCreditModal;
async function openEndOfDayModal() {
  const today = new Date().toISOString().slice(0,10);
  openAdminModal('End of day', `
    <div class="pos-form" style="grid-template-columns: 1fr auto; align-items:end;">
      <div class="field">
        <label>Report date</label>
        <input id="eodDate" type="date" value="${today}">
      </div>
      <div class="field">
        <button class="wide-btn" id="eodRunBtn">Run</button>
      </div>
      <div class="full" id="eodBody" style="margin-top:8px">
        <p style="color:#aaa">Pick a date and click <em>Run</em>.</p>
      </div>
    </div>
  `, `
    <button class="wide-btn" id="eodPrintBtn" disabled>Print</button>
    <button class="wide-btn" id="eodCsvBtn"   disabled>Export CSV</button>
  `);

  document.getElementById('eodRunBtn').onclick = async () => {
    const d = document.getElementById('eodDate').value || today;
    const res = await fetch(`../../controllers/reportsController.php?action=end_of_day&date=${encodeURIComponent(d)}`);
    const data = await res.json();
    if (!data.success) return alert('Failed: ' + (data.message||'unknown'));

    const pRows = (data.payments||[]).map(p => `
      <tr>
        <td>${p.payment_type||'-'}</td>
        <td style="text-align:right">${Number(p.total).toFixed(2)}</td>
        <td style="text-align:right">${Number(p.paid).toFixed(2)}</td>
        <td style="text-align:right">${Number(p.change_due).toFixed(2)}</td>
      </tr>
    `).join('');

    const html = `
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
        <div class="card">
          <h4 style="margin:0 0 8px">Sales Summary</h4>
          <div style="display:grid; grid-template-columns:1fr auto; gap:6px">
            <div>Sales count</div><div style="text-align:right">${data.sales.sales_count}</div>
            <div>Subtotal</div><div style="text-align:right">${Number(data.sales.subtotal).toFixed(2)}</div>
            <div>Discounts</div><div style="text-align:right">-${Number(data.sales.discount_total).toFixed(2)}</div>
            <div>Tax</div><div style="text-align:right">${Number(data.sales.tax_total).toFixed(2)}</div>
            <div style="font-weight:700">Total Sales</div><div style="text-align:right; font-weight:700">${Number(data.sales.total_sales).toFixed(2)}</div>
          </div>
        </div>
        <div class="card">
          <h4 style="margin:0 0 8px">Cash Drawer</h4>
          <div style="display:grid; grid-template-columns:1fr auto; gap:6px">
            <div>Opening balance</div><div style="text-align:right">${Number(data.opening_balance).toFixed(2)}</div>
            <div>Cash from sales (paid - change)</div><div style="text-align:right">${Number(data.cash_from_sales_net).toFixed(2)}</div>
            <div>Cash In</div><div style="text-align:right">${Number(data.cash_movements.cash_in).toFixed(2)}</div>
            <div>Cash Out</div><div style="text-align:right">-${Number(data.cash_movements.cash_out).toFixed(2)}</div>
            <div style="font-weight:700">Expected in drawer</div>
            <div style="text-align:right; font-weight:700">${Number(data.expected_drawer).toFixed(2)}</div>
          </div>
        </div>
        <div class="card full">
          <h4 style="margin:0 0 8px">Payments Breakdown</h4>
          <table style="width:100%; border-collapse:collapse">
            <thead>
              <tr>
                <th style="text-align:left; border-bottom:1px solid #333; padding:6px 4px">Method</th>
                <th style="text-align:right; border-bottom:1px solid #333; padding:6px 4px">Total</th>
                <th style="text-align:right; border-bottom:1px solid #333; padding:6px 4px">Paid</th>
                <th style="text-align:right; border-bottom:1px solid #333; padding:6px 4px">Change</th>
              </tr>
            </thead>
            <tbody>${pRows || `<tr><td colspan="4" style="padding:8px; color:#aaa">No payments</td></tr>`}</tbody>
          </table>
          <div style="margin-top:8px; color:#aaa">Other receipts today (credit payments): <b>${Number(data.credit_payments).toFixed(2)}</b></div>
        </div>
      </div>
    `;

    const body = document.getElementById('eodBody');
    body.innerHTML = html;

    // enable print/export
    const csvBtn = document.getElementById('eodCsvBtn');
    const prtBtn = document.getElementById('eodPrintBtn');
    csvBtn.disabled = prtBtn.disabled = false;

    csvBtn.onclick = () => exportEodCsv(data);
    prtBtn.onclick = () => printEod(html, d);
  };
}

function exportEodCsv(data){
  const lines = [];
  lines.push(['Section','Key','Value'].join(','));
  lines.push(['Sales','Count', data.sales.sales_count]);
  lines.push(['Sales','Subtotal', data.sales.subtotal]);
  lines.push(['Sales','Discounts', data.sales.discount_total]);
  lines.push(['Sales','Tax', data.sales.tax_total]);
  lines.push(['Sales','Total', data.sales.total_sales]);

  (data.payments||[]).forEach(p=>{
    lines.push(['Payments', `${p.payment_type} total`,  p.total]);
    lines.push(['Payments', `${p.payment_type} paid`,   p.paid]);
    lines.push(['Payments', `${p.payment_type} change`, p.change_due]);
  });

  lines.push(['Cash Drawer','Opening', data.opening_balance]);
  lines.push(['Cash Drawer','Cash from sales (net)', data.cash_from_sales_net]);
  lines.push(['Cash Drawer','Cash In', data.cash_movements.cash_in]);
  lines.push(['Cash Drawer','Cash Out', data.cash_movements.cash_out]);
  lines.push(['Cash Drawer','Expected', data.expected_drawer]);
  lines.push(['Other','Credit Payments', data.credit_payments]);

  const blob = new Blob([lines.join('\n')], {type:'text/csv'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `end_of_day_${data.date}.csv`;
  a.click();
}

function printEod(innerHtml, dateStr){
  const w = window.open('', '_blank');
  w.document.write(`
    <html>
      <head>
        <title>End of Day - ${dateStr}</title>
        <style>
          body{font-family:system-ui,Segoe UI,Roboto,Arial; background:#fff; color:#111; padding:16px;}
          h1{margin:0 0 12px;}
          .card{border:1px solid #ddd; border-radius:8px; padding:12px;}
          table{border-collapse:collapse;}
          th,td{padding:6px 4px; border-bottom:1px solid #eee;}
        </style>
      </head>
      <body>
        <h1>End of Day — ${dateStr}</h1>
        ${innerHtml}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  w.print();
  w.close();
}
// --- End of Day popup -------------------------------------------------------
async function runEOD(dateStr) {
  const qs = new URLSearchParams({ action: 'end_of_day', date: dateStr }).toString();
  const res = await fetch(`../../controllers/reportsController.php?${qs}`);
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Failed to run report');
  return data;
}

function formatMoney(n){ return Number(n||0).toFixed(2); }

function renderEODInto(container, data){
  const p = data.payments || [];
  const payRows = p.map(x => `
    <tr>
      <td>${x.payment_type || '-'}</td>
      <td style="text-align:right">${formatMoney(x.total)}</td>
      <td style="text-align:right">${formatMoney(x.paid)}</td>
      <td style="text-align:right">${formatMoney(x.change_due)}</td>
    </tr>`).join('');

  container.innerHTML = `
    <div class="eod-grid">
      <section class="card">
        <h4>Sales Summary</h4>
        <div class="row"><span>Sales count</span><b>${data.sales.sales_count}</b></div>
        <div class="row"><span>Subtotal</span><b>${formatMoney(data.sales.subtotal)}</b></div>
        <div class="row"><span>Discounts</span><b>${formatMoney(data.sales.discount_total * -1)}</b></div>
        <div class="row"><span>Tax</span><b>${formatMoney(data.sales.tax_total)}</b></div>
        <div class="row total"><span>Total Sales</span><b>${formatMoney(data.sales.total_sales)}</b></div>
      </section>

      <section class="card">
        <h4>Cash Drawer</h4>
        <div class="row"><span>Opening balance</span><b>${formatMoney(data.opening_balance)}</b></div>
        <div class="row"><span>Cash from sales (paid - change)</span><b>${formatMoney(data.cash_from_sales_net)}</b></div>
        <div class="row"><span>Cash In</span><b>${formatMoney(data.cash_movements.cash_in)}</b></div>
        <div class="row"><span>Cash Out</span><b>${formatMoney(-1 * (data.cash_movements.cash_out||0))}</b></div>
        <div class="row total"><span>Expected in drawer</span><b>${formatMoney(data.expected_drawer)}</b></div>
      </section>

      <section class="card span-2">
        <h4>Payments Breakdown</h4>
        <table class="eod-table">
          <thead>
            <tr><th>Method</th><th style="text-align:right">Total</th><th style="text-align:right">Paid</th><th style="text-align:right">Change</th></tr>
          </thead>
          <tbody>${payRows || `<tr><td colspan="4" style="color:#aaa">No payments</td></tr>`}</tbody>
        </table>
        <div class="muted" style="margin-top:8px">
          Other receipts today (credit payments): <b>${formatMoney(data.credit_payments)}</b>
        </div>
      </section>
    </div>
  `;
}

function openEndOfDay(){
  // shell: header with date input + Run
  const today = new Date().toISOString().slice(0,10);
  const bodyHTML = `
    <div class="eod-header">
      <label>Report date</label>
      <input id="eodDate" type="date" value="${today}" />
      <button id="eodRun" class="wide-btn">Run</button>
    </div>
    <div id="eodContent" style="margin-top:12px"></div>

    <div class="eod-close" style="margin-top:16px; display:flex; gap:10px; align-items:center">
      <label style="white-space:nowrap">Counted cash:</label>
      <input id="eodCounted" type="number" step="0.01" placeholder="optional" style="max-width:160px">
      <span class="muted">Enter what the cashier counted to save & close the day.</span>
    </div>
  `;

  const footerHTML = `
    <button id="eodPrint" class="wide-btn">Print</button>
    <button id="eodCsv" class="wide-btn">Export CSV</button>
    <button id="eodSave" class="wide-btn btn-pay">Save EOD</button>   <!-- << the missing button -->
  `;

  openAdminModal('End of day', bodyHTML, footerHTML);

  // wire actions
  const dateEl = document.getElementById('eodDate');
  const contentEl = document.getElementById('eodContent');

  async function doRun(){
    try {
      const data = await runEOD(dateEl.value);
      renderEODInto(contentEl, data);
      // keep last result on the element for Save use
      contentEl.__eodData = data;
    } catch(e){ alert(e.message || 'Failed'); }
  }

  document.getElementById('eodRun').onclick = doRun;
  doRun(); // run once for today

  document.getElementById('eodSave').onclick = async () => {
    const counted = document.getElementById('eodCounted').value;
    const v = counted === '' ? null : parseFloat(counted);
    const ok = confirm('Save End of Day for ' + dateEl.value + (v!=null? ` with counted cash ${v.toFixed(2)}?`:'?'));
    if (!ok) return;
    await closeDay(dateEl.value, v);
  };

  // optional: stubs
  document.getElementById('eodPrint').onclick = () => toast('🖨️ Print coming soon');
  document.getElementById('eodCsv').onclick   = () => toast('⬇️ CSV coming soon');
}

// existing helper you already had:
async function closeDay(dateStr, countedCashOrNull){
  const body = new URLSearchParams();
  body.set('date', dateStr);
  if (countedCashOrNull != null) body.set('closing_balance', countedCashOrNull);

  const res = await fetch('../../controllers/reportsController.php?action=close_day', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body
  });
  const data = await res.json();
  if (data.success){
    toast('✅ End of day saved');
    closeAdminModal();
  } else {
    alert('❌ ' + (data.message || 'Save failed'));
  }
}


// ===== Suppliers Module (popup) =====
const supUI = { page:1, size:10, q:'' };

function openSuppliers(){
  supUI.page = 1; supUI.q = '';
  renderSuppliers();
}

async function fetchSuppliers(){
  const offset = (supUI.page-1)*supUI.size;
  const qs = new URLSearchParams({
    action:'list', q: supUI.q, limit: supUI.size, offset
  }).toString();
  const res = await fetch(`../../controllers/suppliersController.php?${qs}`);
  return res.json();
}

async function renderSuppliers(){
  const data = await fetchSuppliers();
  const rows = (data.rows||[]).map(s => `
    <tr>
      <td>${s.name}</td>
      <td>${s.contact_person||''}</td>
      <td>${s.phone||''}</td>
      <td>${s.email||''}</td>
      <td>${s.status||''}</td>
      <td style="text-align:right">${Number(s.current_balance||0).toFixed(2)}</td>
      <td>
        <button class="wide-btn" onclick="editSupplier(${s.id})">Edit</button>
        <button class="wide-btn btn-void" onclick="deleteSupplier(${s.id})">Del</button>
      </td>
    </tr>
  `).join('');

  const total = data.total || 0;
  const totalPages = Math.max(1, Math.ceil(total/supUI.size));

  openAdminModal('Suppliers', `
    <div style="display:flex; gap:10px; margin-bottom:10px">
      <input id="supSearch" placeholder="Search name/phone/email…" style="flex:1; padding:10px; background:#111; color:#eee; border:1px solid #333; border-radius:8px">
      <select id="supPageSize" style="padding:10px; background:#111; color:#eee; border:1px solid #333; border-radius:8px">
        <option ${supUI.size==10?'selected':''}>10</option>
        <option ${supUI.size==20?'selected':''}>20</option>
        <option ${supUI.size==50?'selected':''}>50</option>
      </select>
      <button class="wide-btn" onclick="newSupplier()">+ New</button>
    </div>

    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left; padding:8px">Name</th>
          <th style="text-align:left; padding:8px">Contact</th>
          <th style="text-align:left; padding:8px">Phone</th>
          <th style="text-align:left; padding:8px">Email</th>
          <th style="text-align:left; padding:8px">Status</th>
          <th style="text-align:right; padding:8px">Balance</th>
          <th style="text-align:left; padding:8px">Action</th>
        </tr>
      </thead>
      <tbody>
        ${rows || `<tr><td colspan="7" style="padding:10px;color:#aaa">No suppliers</td></tr>`}
      </tbody>
    </table>

    <div style="display:flex; gap:8px; justify-content:flex-end; padding-top:10px">
      <button class="wide-btn" ${supUI.page<=1?'disabled':''} onclick="supGo(1)">«</button>
      <button class="wide-btn" ${supUI.page<=1?'disabled':''} onclick="supGo(${supUI.page-1})">‹</button>
      <span style="color:#aaa; align-self:center">Page ${supUI.page} / ${totalPages}</span>
      <button class="wide-btn" ${supUI.page>=totalPages?'disabled':''} onclick="supGo(${supUI.page+1})">›</button>
      <button class="wide-btn" ${supUI.page>=totalPages?'disabled':''} onclick="supGo(${totalPages})">»</button>
    </div>
  `);

  const s = document.getElementById('supSearch');
  const ps = document.getElementById('supPageSize');
  s.value = supUI.q;
  let t; s.oninput = () => { clearTimeout(t); t=setTimeout(()=>{ supUI.q=s.value.trim(); supUI.page=1; renderSuppliers(); }, 350); };
  s.onkeydown = e => { if (e.key==='Enter'){ supUI.q=s.value.trim(); supUI.page=1; renderSuppliers(); } };
  ps.onchange = () => { supUI.size=parseInt(ps.value,10); supUI.page=1; renderSuppliers(); };
}
function supGo(p){ supUI.page = Math.max(1,p|0); renderSuppliers(); }

function newSupplier(){ editSupplier(0); }

async function editSupplier(id){
  let s = {
    id:0, name:'', contact_person:'', phone:'', email:'',
    status:'active', tax_id:'', address1:'', address2:'',
    city:'', region:'', country:'', postal_code:'',
    currency_code:'UGX', payment_terms_id:null, credit_limit:0,
    opening_balance:0, current_balance:0, notes:''
  };
  if (id){
    const res = await fetch(`../../controllers/suppliersController.php?action=get&id=${id}`);
    const data = await res.json(); if (data.success) s = data.supplier;
  }

  const body = `
    <div class="eod-grid" style="grid-template-columns:1fr 1fr">
      <section class="card">
        <h4>Main</h4>
        <label>Name</label>
        <input id="sup_name" value="${s.name||''}">
        <label>Contact person</label>
        <input id="sup_contact" value="${s.contact_person||''}">
        <label>Phone</label>
        <input id="sup_phone" value="${s.phone||''}">
        <label>Email</label>
        <input id="sup_email" value="${s.email||''}">
        <label>Status</label>
        <select id="sup_status">
          <option ${s.status==='active'?'selected':''} value="active">active</option>
          <option ${s.status==='blocked'?'selected':''} value="blocked">blocked</option>
        </select>
        <label>Notes</label>
        <textarea id="sup_notes">${s.notes||''}</textarea>
      </section>

      <section class="card">
        <h4>Finance / Address</h4>
        <label>Currency</label>
        <input id="sup_currency" value="${s.currency_code||'UGX'}">
        <label>Credit limit</label>
        <input id="sup_credit" type="number" step="0.01" value="${s.credit_limit||0}">
        <label>Opening balance</label>
        <input id="sup_opening" type="number" step="0.01" value="${s.opening_balance||0}">
        <label>Current balance</label>
        <input id="sup_balance" type="number" step="0.01" value="${s.current_balance||0}">
        <label>Tax ID</label>
        <input id="sup_tax" value="${s.tax_id||''}">
        <label>Address 1</label>
        <input id="sup_addr1" value="${s.address1||''}">
        <label>Address 2</label>
        <input id="sup_addr2" value="${s.address2||''}">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div>
            <label>City</label>
            <input id="sup_city" value="${s.city||''}">
          </div>
          <div>
            <label>Region</label>
            <input id="sup_region" value="${s.region||''}">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div>
            <label>Country</label>
            <input id="sup_country" value="${s.country||''}">
          </div>
          <div>
            <label>Postal code</label>
            <input id="sup_postal" value="${s.postal_code||''}">
          </div>
        </div>
      </section>
    </div>
  `;
  const footer = `
    <button class="wide-btn btn-pay" id="sup_save">Save</button>
    ${id ? '<button class="wide-btn btn-void" id="sup_delete">Delete</button>' : ''}
  `;
  openAdminModal(id?`Edit Supplier #${id}`:'New Supplier', body, footer);

  document.getElementById('sup_save').onclick = () => saveSupplier(id);
  if (id) document.getElementById('sup_delete').onclick = () => deleteSupplier(id);
}

async function saveSupplier(id){
  const payload = {
    id,
    name: document.getElementById('sup_name').value.trim(),
    contact_person: document.getElementById('sup_contact').value.trim(),
    phone: document.getElementById('sup_phone').value.trim(),
    email: document.getElementById('sup_email').value.trim(),
    status: document.getElementById('sup_status').value,
    notes: document.getElementById('sup_notes').value,
    currency_code: document.getElementById('sup_currency').value.trim()||'UGX',
    credit_limit: parseFloat(document.getElementById('sup_credit').value||'0'),
    opening_balance: parseFloat(document.getElementById('sup_opening').value||'0'),
    current_balance: parseFloat(document.getElementById('sup_balance').value||'0'),
    tax_id: document.getElementById('sup_tax').value.trim(),
    address1: document.getElementById('sup_addr1').value.trim(),
    address2: document.getElementById('sup_addr2').value.trim(),
    city: document.getElementById('sup_city').value.trim(),
    region: document.getElementById('sup_region').value.trim(),
    country: document.getElementById('sup_country').value.trim(),
    postal_code: document.getElementById('sup_postal').value.trim()
  };
  if (!payload.name) return alert('Name is required');

  const res = await fetch('../../controllers/suppliersController.php?action=save', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success){ toast('✅ Saved'); renderSuppliers(); }
  else alert('❌ '+data.message);
}

async function deleteSupplier(id){
  if (!confirm('Delete this supplier?')) return;
  const body = new URLSearchParams(); body.set('id', id);
  const res = await fetch('../../controllers/suppliersController.php?action=delete', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body
  });
  const data = await res.json();
  if (data.success){ toast('🗑️ Deleted'); renderSuppliers(); }
  else alert('❌ '+data.message);
}

// expose to sidebar item (if needed)
window.openSuppliers = openSuppliers;


