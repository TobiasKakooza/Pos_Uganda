/* =========================================================
   EXPENSES MODULE — ADMIN
   Uses: expensesController.php
========================================================= */

let expenseState = {
  page: 1,
  limit: 25,
  q: '',
  from: '',
  to: ''
};

/* =========================================================
   INIT
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  loadCategories();
  loadExpenses();

  document.getElementById('expenseForm')?.addEventListener('submit', submitExpense);
});

/* =========================================================
   LOAD CATEGORIES
========================================================= */
async function loadCategories() {
  try {
    const res = await fetch('../../controllers/expensesController.php?action=categories');
    const data = await res.json();

    const select = document.getElementById('expenseCategory');
    if (!select) return;

    select.innerHTML = '<option value="">Select category</option>';

    (data.rows || []).forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = `${cat.name} (${cat.type})`;
      select.appendChild(opt);
    });

  } catch (e) {
    console.error('Failed to load categories', e);
  }
}

/* =========================================================
   LOAD EXPENSES
========================================================= */
async function loadExpenses() {
  const tbody = document.querySelector('#expensesTable tbody');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="8">Loading…</td></tr>`;

  expenseState.from = document.getElementById('filterFrom')?.value || '';
  expenseState.to   = document.getElementById('filterTo')?.value || '';
  expenseState.q    = document.getElementById('filterQuery')?.value.trim() || '';

  const qs = new URLSearchParams({
    action: 'list',
    limit: expenseState.limit,
    offset: (expenseState.page - 1) * expenseState.limit,
    from: expenseState.from,
    to: expenseState.to,
    q: expenseState.q
  }).toString();

  try {
    const res = await fetch(`../../controllers/expensesController.php?${qs}`);
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="8">Failed to load expenses</td></tr>`;
      return;
    }

    if (!data.rows.length) {
      tbody.innerHTML = `<tr><td colspan="8">No expenses found</td></tr>`;
      return;
    }

    tbody.innerHTML = data.rows.map(exp => `
      <tr>
        <td>${exp.expense_date}</td>
        <td>${exp.category}</td>
        <td><span class="badge">${exp.category_type}</span></td>
        <td style="text-align:right">${formatMoney(exp.amount)}</td>
        <td>${exp.payment_method}</td>
        <td>${exp.reference || '-'}</td>
        <td>${exp.user}</td>
        <td class="actions">
          <button class="btn-icon" onclick="editExpense(${exp.id})">✏️</button>
          <button class="btn-icon danger" onclick="deleteExpense(${exp.id})">🗑️</button>
        </td>
      </tr>
    `).join('');

  } catch (e) {
    console.error(e);
    tbody.innerHTML = `<tr><td colspan="8">Server error</td></tr>`;
  }
}

/* =========================================================
   MODAL CONTROL
========================================================= */
function openAddExpense() {
  resetExpenseForm();
  document.getElementById('expenseModalTitle').innerText = 'Add Expense';
  showExpenseModal();
}

function closeExpenseModal() {
  document.getElementById('expenseModal')?.classList.add('hidden');
}

function showExpenseModal() {
  document.getElementById('expenseModal')?.classList.remove('hidden');
}

/* =========================================================
   FORM
========================================================= */
function resetExpenseForm() {
  const form = document.getElementById('expenseForm');
  if (!form) return;
  form.reset();
  form.id.value = '';
}

/* =========================================================
   CREATE / UPDATE
========================================================= */
async function submitExpense(e) {
  e.preventDefault();

  const form = e.target;
  const payload = new FormData(form);

  const id = payload.get('id');
  payload.append('action', id ? 'update' : 'add');

  try {
    const res = await fetch('../../controllers/expensesController.php', {
      method: 'POST',
      body: payload
    });

    const data = await res.json();

    if (!data.success) {
      alert(data.message || 'Failed to save expense');
      return;
    }

    closeExpenseModal();
    loadExpenses();
    toast(' Expense Saved/Updated');

  } catch (e) {
    console.error(e);
    alert('Server error');
  }
}

/* =========================================================
   EDIT
========================================================= */
async function editExpense(id) {
  try {
    const res = await fetch(`../../controllers/expensesController.php?action=get&id=${id}`);
    const data = await res.json();

    if (!data.success) {
      alert('Failed to load expense');
      return;
    }

    const e = data.expense;
    const form = document.getElementById('expenseForm');

    form.id.value              = e.id;
    form.expense_date.value    = e.expense_date;
    form.category_id.value     = e.category_id;
    form.amount.value          = e.amount;
    form.payment_method.value  = e.payment_method;
    form.reference.value       = e.reference || '';
    form.description.value     = e.description || '';

    document.getElementById('expenseModalTitle').innerText = 'Edit Expense';
    showExpenseModal();

  } catch (err) {
    console.error(err);
    alert('Server error');
  }
}

/* =========================================================
   DELETE
========================================================= */
async function deleteExpense(id) {
  if (!confirm('Delete this expense?')) return;

  try {
    const body = new URLSearchParams({ action: 'delete', id });

    const res = await fetch('../../controllers/expensesController.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    const data = await res.json();

    if (!data.success) {
      alert(data.message || 'Delete failed');
      return;
    }

    loadExpenses();
    toast('🗑️ Expense deleted');

  } catch (e) {
    console.error(e);
    alert('Server error');
  }
}

/* =========================================================
   FILTER HELPERS
========================================================= */
function resetFilters() {
  document.getElementById('filterFrom').value = '';
  document.getElementById('filterTo').value = '';
  document.getElementById('filterQuery').value = '';
  expenseState.page = 1;
  loadExpenses();
}

/* =========================================================
   UTILITIES
========================================================= */
function formatMoney(n) {
  return 'UGX ' + Number(n || 0).toLocaleString();
}

function toast(msg) {
  const el = document.createElement('div');
  el.textContent = msg;
  el.style.cssText = `
    position:fixed; bottom:20px; right:20px;
    background:#222; color:#fff;
    padding:10px 14px; border-radius:6px;
    z-index:10000; opacity:.95;
  `;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 2200);
}
