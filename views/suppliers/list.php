<?php
// views/suppliers/list.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// If $embed is NOT set, render as a full page
$standalone = !isset($embed);

if ($standalone) {
  require_once __DIR__ . '/../../includes/auth.php';
  require_once __DIR__ . '/../../config/db.php';
}

// Fetch suppliers
$sql = "
  SELECT
    s.id, s.name, s.contact_person, s.phone, s.email,
    s.status, s.city, s.region, s.country,
    s.currency_code, s.current_balance, s.credit_limit,
    s.tax_id, s.payment_terms_id, pt.name AS terms_name, pt.days AS terms_days,
    s.created_at
  FROM suppliers s
  LEFT JOIN payment_terms pt ON pt.id = s.payment_terms_id
  ORDER BY s.name ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<link rel="stylesheet" href="/POS_UG/assets/css/terminal.css">
<link rel="stylesheet" href="/POS_UG/assets/css/suppliers.css">

<?php if ($standalone): ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Suppliers</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#111;--fg:#eee;--card:#1b1b1b;--card2:#222;--line:#2a2a2a;
      --pri:#2563eb;--mut:#9ca3af;--ok:#064e3b;--okfg:#d1fae5;--blk:#7f1d1d;--blkfg:#fee2e2;
    }
    body{background:var(--bg);color:var(--fg);font:16px/1.4 system-ui,Segoe UI,Roboto,Arial;margin:0}
  </style>
</head>
<body>
<?php endif; ?>

<style>
  /* ===========================
   Suppliers – Dark Theme Pro
   =========================== */

/* 1) Design tokens */
:root,
:root.theme-dark {
  --bg: #0b0e13;
  --surface: #0f141b;
  --surface-2: #131a23;
  --surface-3: #182230;
  --line: #233244;
  --muted: #8aa0b5;
  --text: #e6eef6;
  --text-dim: #c6d3df;

  --primary: #4ea4ff;
  --primary-2: #2d7fe0;
  --success-bg: #0f2a1c;
  --success-fg: #7ef1b0;
  --danger-bg: #2a1515;
  --danger-fg: #ff9a9a;
  --warning: #ffc56d;

  --radius: 12px;
  --radius-pill: 999px;
  --shadow-lg: 0 10px 40px rgba(4, 12, 22, .65);
  --shadow-md: 0 8px 26px rgba(4, 12, 22, .45);
  --ring: 0 0 0 2px rgba(78,164,255,.35);
  --focus: 2px solid rgba(78,164,255,.55);

  --btn-h: 40px;
  --trans-fast: .15s;
  --trans: .25s;
}

/* Prefer dark if system supports */
@media (prefers-color-scheme: dark) {
  :root { color-scheme: dark; }
}

/* 2) Page shell */
body {
  background: var(--bg);
  color: var(--text);
  font: 15px/1.45 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  text-rendering: optimizeLegibility;
}

.sup-wrap {
  width: 100%;
  margin: 0;
  padding: 12px 0 28px;
}

@media (min-width: 992px) {
  .sup-wrap { padding: 18px 0 36px; }
}

/* If opened standalone (your PHP already toggles this block): */
<?php if ($standalone): ?>
body      { background: var(--bg); }
.sup-wrap { max-width: 1280px; margin: 30px auto; padding: 0 18px; }
<?php endif; ?>

/* 3) Heading + actions */
.sup-title,
.sup-wrap h1 {
  margin: 0 0 14px;
  font-size: 22px;
  letter-spacing: .2px;
}

.sup-actions {
  display: flex;
  gap: 10px;
  margin: 12px 0 16px;
  flex-wrap: wrap;
}

.sup-btn {
  height: var(--btn-h);
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid transparent;
  border-radius: var(--radius);
  background: linear-gradient(180deg, var(--primary) 0%, var(--primary-2) 100%);
  color: #fff;
  cursor: pointer;
  transition: transform var(--trans-fast) ease, box-shadow var(--trans-fast) ease, opacity var(--trans-fast) ease;
  box-shadow: 0 6px 18px rgba(78,164,255,.25);
}
.sup-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(78,164,255,.35); }
.sup-btn:active { transform: translateY(0); }
.sup-btn:focus-visible { outline: none; box-shadow: var(--ring); }

.sup-btn.secondary {
  background: linear-gradient(180deg, var(--surface-2), var(--surface-3));
  color: var(--text);
  border: 1px solid var(--line);
  box-shadow: 0 6px 18px rgba(9,18,28,.25);
}
.sup-btn.secondary:hover { background: linear-gradient(180deg, #17202c, #1b2736); }

/* 4) Filters */
.sup-filters {
  display: flex;
  gap: 10px;
  align-items: center;
  margin: 8px 0 16px;
}
.sup-input, .sup-select {
  height: var(--btn-h);
  padding: 0 12px;
  border-radius: var(--radius);
  border: 1px solid var(--line);
  background: var(--surface-2);
  color: var(--text);
  transition: border var(--trans-fast), background var(--trans-fast);
}
.sup-input::placeholder { color: var(--muted); }
.sup-input:focus, .sup-select:focus { outline: none; border-color: var(--primary); box-shadow: var(--ring); }

/* 5) Table */
.sup-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow-md);
}

.sup-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: linear-gradient(180deg, var(--surface-2), var(--surface));
  color: var(--text-dim);
  padding: 12px;
  text-align: left;
  font-weight: 600;
  border-bottom: 1px solid var(--line);
  font-size: 13px;
}

.sup-table tbody td {
  padding: 12px;
  border-top: 1px solid rgba(255,255,255,.03);
  color: var(--text);
}
.sup-table tbody tr {
  transition: background var(--trans), transform var(--trans-fast);
}
.sup-table tbody tr:hover {
  background: rgba(78,164,255,.06);
}

.sup-right { text-align: right; }

/* Clickable link */
.sup-link {
  color: var(--primary);
  text-decoration: none;
  transition: opacity var(--trans-fast);
}
.sup-link:hover { opacity: .9; }

/* 6) Status pills */
.sup-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px;
  font-size: 12px;
  border-radius: var(--radius-pill);
  border: 1px solid transparent;
}
.sup-pill.ok {
  background: var(--success-bg);
  color: var(--success-fg);
  border-color: rgba(126,241,176,.3);
}
.sup-pill.blocked {
  background: var(--danger-bg);
  color: var(--danger-fg);
  border-color: rgba(255,154,154,.25);
}

/* 7) Modal */
.sup-modal.hidden { display: none; }
.sup-modal {
  position: fixed; inset: 0;
  background: radial-gradient(1200px 800px at 70% -10%, rgba(78,164,255,.15), transparent 50%),
              rgba(2,8,15,.70);
  display: flex; align-items: center; justify-content: center;
  z-index: 10050;
  backdrop-filter: blur(3px);
}
.sup-modal-card {
  width: min(980px, 96vw);
  max-height: 90vh;
  overflow: auto;
  background: linear-gradient(180deg, var(--surface-2), var(--surface));
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
}

.sup-modal-head, .sup-modal-foot {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(180deg, var(--surface-3), var(--surface-2));
}
.sup-modal-foot { border-top: 1px solid var(--line); border-bottom: none; }

.sup-modal-head strong { color: var(--text); font-weight: 600; }

.sup-close {
  border: 1px solid rgba(255,154,154,.25);
  background: linear-gradient(180deg, #d14949, #b92f2f);
  color: #fff; border-radius: 10px;
  padding: 6px 10px; cursor: pointer;
  transition: transform var(--trans-fast), box-shadow var(--trans-fast);
}
.sup-close:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(185,47,47,.35); }

.sup-modal-body { padding: 16px; }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.grid-full { grid-column: 1 / -1; }

.sup-modal-body label {
  display: block; font-size: 12px; color: var(--muted); margin: 4px 0 6px;
  letter-spacing: .2px;
}
.sup-modal-body input,
.sup-modal-body select,
.sup-modal-body textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: var(--surface-2);
  color: var(--text);
  transition: border var(--trans-fast), box-shadow var(--trans-fast), background var(--trans-fast);
}
.sup-modal-body input:focus,
.sup-modal-body select:focus,
.sup-modal-body textarea:focus {
  outline: none; border-color: var(--primary); box-shadow: var(--ring);
}
.sup-modal-body textarea { resize: vertical; min-height: 92px; }

/* Modal buttons reuse .sup-btn styles */

/* 8) Scrollbar (WebKit) */
.sup-modal-card::-webkit-scrollbar,
.sup-table::-webkit-scrollbar { height: 10px; width: 10px; }
.sup-modal-card::-webkit-scrollbar-thumb,
.sup-table::-webkit-scrollbar-thumb {
  background: #2a394d; border-radius: 8px;
  border: 2px solid transparent; background-clip: padding-box;
}
.sup-modal-card::-webkit-scrollbar-track,
.sup-table::-webkit-scrollbar-track { background: transparent; }

/* 9) Tiny utilities */
.sup-muted { color: var(--muted); }
.sup-badge-warn {
  background: rgba(255,197,109,.15); color: var(--warning);
  border: 1px solid rgba(255,197,109,.35);
  padding: 2px 8px; border-radius: var(--radius-pill); font-size: 12px;
}

/* 10) Responsive */
@media (max-width: 880px) {
  .grid2 { grid-template-columns: 1fr; }
  .sup-actions { gap: 8px; }
  .sup-table thead th, .sup-table tbody td { padding: 10px; }
}

/* 11) Motion-safety */
@media (prefers-reduced-motion: reduce) {
  .sup-btn, .sup-table tbody tr, .sup-close { transition: none; }
}


</style>

<div class="sup-wrap">
  <h1>🏭 Suppliers</h1>

 <div class="sup-actions">
  <button class="sup-btn" id="btnSupNew">+ New Supplier</button>
  <a class="sup-btn secondary" href="/POS_UG/views/dashboard.php">← Back</a>
</div>
<div class="sup-filters">
  <input id="supFilter" class="sup-input" placeholder="Search name / phone / email">
  <select id="supStatusFilter" class="sup-select">
    <option value="">All</option>
    <option value="active">Active</option>
    <option value="blocked">Blocked</option>
  </select>
</div>


<!-- Supplier Modal -->
<div id="supModal" class="sup-modal hidden">
  <div class="sup-modal-card">
    <div class="sup-modal-head">
      <strong id="supModalTitle">New Supplier</strong>
      <button class="sup-close" id="supClose">×</button>
    </div>
    <div class="sup-modal-body">
      <div class="grid2">
        <div>
          <label>Name *</label>
          <input id="f_name">
        </div>
        <div>
          <label>Status</label>
          <select id="f_status">
            <option value="active" selected>active</option>
            <option value="blocked">blocked</option>
          </select>
        </div>
        <div>
          <label>Contact person</label>
          <input id="f_contact">
        </div>
        <div>
          <label>Phone</label>
          <input id="f_phone">
        </div>
        <div>
          <label>Email</label>
          <input id="f_email">
        </div>
        <div>
          <label>Currency</label>
          <input id="f_currency" value="UGX">
        </div>
        <div>
          <label>Payment terms</label>
          <select id="f_terms"><option value="">—</option></select>
        </div>
        <div>
          <label>Credit limit</label>
          <input id="f_credit" type="number" step="0.01" value="0">
        </div>
        <div>
          <label>Opening balance</label>
          <input id="f_opening" type="number" step="0.01" value="0">
        </div>
        <div>
          <label>Current balance</label>
          <input id="f_balance" type="number" step="0.01" value="0">
        </div>
        <div>
          <label>Tax ID</label>
          <input id="f_tax">
        </div>
        <div>
          <label>Postal code</label>
          <input id="f_postal">
        </div>
        <div>
          <label>Address 1</label>
          <input id="f_addr1">
        </div>
        <div>
          <label>Address 2</label>
          <input id="f_addr2">
        </div>
        <div>
          <label>City</label>
          <input id="f_city">
        </div>
        <div>
          <label>Region</label>
          <input id="f_region">
        </div>
        <div>
          <label>Country</label>
          <input id="f_country">
        </div>
        <div class="grid-full">
          <label>Notes</label>
          <textarea id="f_notes" rows="3"></textarea>
        </div>
      </div>
    </div>
    <div class="sup-modal-foot">
      <button class="sup-btn secondary" id="supCancel">Cancel</button>
      <button class="sup-btn" id="supSave">Save</button>
    </div>
  </div>
</div>

  <?php if (!$rows): ?>
    <p class="sup-muted">No suppliers yet.</p>
  <?php else: ?>
    <table class="sup-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Supplier</th>
          <th>Contact</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Status</th>
          <th>City</th>
          <th class="sup-right">Balance</th>
          <th>Terms</th>
<th class="sup-right">Credit Limit</th>
<th style="width:120px">Actions</th>

        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td>
            <a class="sup-link" href="/POS_UG/views/dashboard.php?view=suppliers/view&id=<?= urlencode($r['id']) ?>">
              <?= htmlspecialchars($r['name']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($r['contact_person'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
          <td>
            <?php $isActive = strtolower($r['status'] ?? 'active') === 'active'; ?>
            <span class="sup-pill <?= $isActive ? 'ok' : 'blocked' ?>">
              <?= $isActive ? 'active' : 'blocked' ?>
            </span>
          </td>
          <td><?= htmlspecialchars(trim(($r['city']??'').' '.($r['country']??''))) ?></td>
          <td class="sup-right">
            <?= htmlspecialchars(($r['currency_code'] ?? 'UGX').' '.number_format((float)$r['current_balance'],2)) ?>
          </td>
          <td><?= htmlspecialchars(($r['terms_name'] ?? '—') . (isset($r['terms_days']) ? " ({$r['terms_days']}d)" : '')) ?></td>
<td class="sup-right"><?= htmlspecialchars(($r['currency_code'] ?? 'UGX').' '.number_format((float)$r['credit_limit'],2)) ?></td>
<td>
  <button class="sup-btn" data-edit="<?= (int)$r['id'] ?>">Edit</button>
  <?php if (($r['status'] ?? 'active') === 'active'): ?>
    <button class="sup-btn secondary" data-block="<?= (int)$r['id'] ?>">Block</button>
  <?php else: ?>
    <button class="sup-btn secondary" data-unblock="<?= (int)$r['id'] ?>">Unblock</button>
  <?php endif; ?>
</td>

        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php if ($standalone): ?>
</body>
</html>
<?php endif; ?>


<script>
(function () {
  const $  = (sel) => document.querySelector(sel);
  const M  = $('#supModal');
  const btnSave = $('#supSave');

  // ---- helpers -------------------------------------------------------------
  async function loadTerms() {
    const r = await fetch('/POS_UG/controllers/suppliersController.php?action=terms');
    const j = await r.json();
    const sel = $('#f_terms');
    sel.innerHTML = '<option value="">—</option>';
    (j.rows || []).forEach(t => {
      const o = document.createElement('option');
      o.value = t.id;
      o.textContent = `${t.name} (${t.days}d)`;
      sel.appendChild(o);
    });
  }

  function resetForm() {
    $('#supModalTitle').textContent = 'New Supplier';
    [
      'f_name','f_contact','f_phone','f_email','f_currency','f_credit','f_opening','f_balance',
      'f_tax','f_postal','f_addr1','f_addr2','f_city','f_region','f_country','f_notes'
    ].forEach(id => {
      const el = $('#'+id);
      if (!el) return;
      if (el.type === 'number') el.value = 0;
      else if (id === 'f_currency') el.value = 'UGX';
      else el.value = '';
    });
    $('#f_status').value = 'active';
    $('#f_terms').innerHTML = '<option value="">—</option>';
    btnSave.dataset.id = '';               // clear edit context
  }

  function fillForm(s) {
    $('#supModalTitle').textContent = 'Edit Supplier';
    $('#f_name').value     = s.name || '';
    $('#f_status').value   = s.status || 'active';
    $('#f_contact').value  = s.contact_person || '';
    $('#f_phone').value    = s.phone || '';
    $('#f_email').value    = s.email || '';
    $('#f_currency').value = s.currency_code || 'UGX';
    $('#f_credit').value   = s.credit_limit || 0;
    $('#f_opening').value  = s.opening_balance || 0;
    $('#f_balance').value  = s.current_balance || 0;
    $('#f_tax').value      = s.tax_id || '';
    $('#f_postal').value   = s.postal_code || '';
    $('#f_addr1').value    = s.address1 || '';
    $('#f_addr2').value    = s.address2 || '';
    $('#f_city').value     = s.city || '';
    $('#f_region').value   = s.region || '';
    $('#f_country').value  = s.country || '';
    $('#f_notes').value    = s.notes || '';
  }

  function getPayload() {
    return {
      id: btnSave.dataset.id ? parseInt(btnSave.dataset.id, 10) : 0,
      name: $('#f_name').value.trim(),
      status: $('#f_status').value,
      contact_person: $('#f_contact').value.trim(),
      phone: $('#f_phone').value.trim(),
      email: $('#f_email').value.trim(),
      currency_code: ($('#f_currency').value || 'UGX').trim().toUpperCase(),
      payment_terms_id: $('#f_terms').value || null,
      credit_limit: parseFloat($('#f_credit').value || '0'),
      opening_balance: parseFloat($('#f_opening').value || '0'),
      current_balance: parseFloat($('#f_balance').value || '0'),
      tax_id: $('#f_tax').value.trim(),
      postal_code: $('#f_postal').value.trim(),
      address1: $('#f_addr1').value.trim(),
      address2: $('#f_addr2').value.trim(),
      city: $('#f_city').value.trim(),
      region: $('#f_region').value.trim(),
      country: $('#f_country').value.trim(),
      notes: $('#f_notes').value
    };
  }

  // ---- modal open/close ----------------------------------------------------
  function openNewModal() {
    resetForm();
    loadTerms();
    M.classList.remove('hidden');
  }
  function closeModal() { M.classList.add('hidden'); }

  // ---- save (handles both new + edit) -------------------------------------
  async function save() {
    const payload = getPayload();
    if (!payload.name) { alert('Name is required'); return; }

    const res = await fetch('/POS_UG/controllers/suppliersController.php?action=save', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      closeModal();
      location.reload();
    } else {
      alert('❌ ' + (data.message || 'Save failed'));
    }
  }

  // ---- events --------------------------------------------------------------
  document.getElementById('btnSupNew').addEventListener('click', openNewModal);
  document.getElementById('supClose').addEventListener('click', closeModal);
  document.getElementById('supCancel').addEventListener('click', closeModal);
  btnSave.addEventListener('click', save);

  // Edit / Block / Unblock via event delegation
  document.addEventListener('click', async (e) => {
    // Edit
    if (e.target.matches('[data-edit]')) {
      const id = e.target.dataset.edit;
      const r  = await fetch('/POS_UG/controllers/suppliersController.php?action=get&id=' + id);
      const j  = await r.json();
      if (!j.success) return alert(j.message || 'Load failed');
      resetForm();
      fillForm(j.supplier || {});
      await loadTerms();
      if (j.supplier && j.supplier.payment_terms_id) {
        $('#f_terms').value = String(j.supplier.payment_terms_id);
      }
      btnSave.dataset.id = id;
      M.classList.remove('hidden');
    }

    // Block
    if (e.target.matches('[data-block]')) {
      const id = e.target.dataset.block;
      if (!confirm('Block this supplier?')) return;
      await fetch('/POS_UG/controllers/suppliersController.php?action=set_status', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({id, status:'blocked'})
      });
      location.reload();
    }

    // Unblock
    if (e.target.matches('[data-unblock]')) {
      const id = e.target.dataset.unblock;
      await fetch('/POS_UG/controllers/suppliersController.php?action=set_status', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({id, status:'active'})
      });
      location.reload();
    }
  });

  // Search / filter
  const fI = document.getElementById('supFilter');
  const fS = document.getElementById('supStatusFilter');
  function applyRowFilter() {
    const q  = (fI.value || '').toLowerCase();
    const st = fS.value;
    document.querySelectorAll('.sup-table tbody tr').forEach(tr => {
      const t = tr.innerText.toLowerCase();
      const status = tr.querySelector('.sup-pill')?.textContent.trim() || '';
      const ok = (!q || t.includes(q)) && (!st || status === st);
      tr.style.display = ok ? '' : 'none';
    });
  }
  fI.addEventListener('input', () => setTimeout(applyRowFilter, 150));
  fS.addEventListener('change', applyRowFilter);
})();
</script>
