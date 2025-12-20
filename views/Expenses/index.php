<?php
require_once '../../includes/auth.php';
require_permission('expenses_manage');

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container expenses-page">

  <!-- ================= HEADER ================= -->
  <div class="page-header flex-between">
    <h2 class="page-title">
      <i data-lucide="wallet"></i>
      Expenses Management
    </h2>

    <button class="btn-primary" onclick="openAddExpense()">
      <i data-lucide="plus-circle"></i>
      Add Expense
    </button>
  </div>

  <!-- ================= FILTER BAR ================= -->
  <div class="card filter-bar">
    <div class="filter-grid">

      <div class="filter-group">
        <label>From</label>
        <input type="date" id="filterFrom">
      </div>

      <div class="filter-group">
        <label>To</label>
        <input type="date" id="filterTo">
      </div>

      <div class="filter-group wide">
        <label>Search</label>
        <input type="text" id="filterQuery" placeholder="Category, reference, description">
      </div>

      <div class="filter-actions">
        <button class="btn-secondary" onclick="loadExpenses()">
          <i data-lucide="filter"></i> Apply
        </button>
        <button class="btn-void" onclick="resetFilters()">
          <i data-lucide="rotate-ccw"></i> Reset
        </button>
      </div>

    </div>
  </div>

  <!-- ================= EXPENSE TABLE ================= -->
  <div class="card">
    <table class="table expenses-table" id="expensesTable">
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Type</th>
          <th class="right">Amount (UGX)</th>
          <th>Payment</th>
          <th>Reference</th>
          <th>Added By</th>
          <th class="center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="8" class="muted center">Loading expenses…</td>
        </tr>
      </tbody>
    </table>
  </div>

</div>

<!-- ================= ADD / EDIT MODAL ================= -->
<div class="modal hidden" id="expenseModal">
  <div class="modal-box large">

    <div class="modal-header">
      <h3>
        <i data-lucide="file-plus"></i>
        <span id="expenseModalTitle">Add Expense</span>
      </h3>
      <button class="icon-btn" onclick="closeExpenseModal()">
        <i data-lucide="x"></i>
      </button>
    </div>

    <form id="expenseForm" class="modal-body">
      <input type="hidden" name="id" id="expenseId">

      <div class="form-grid">

        <div>
          <label>Date</label>
          <input type="date" name="expense_date" required>
        </div>

        <div>
          <label>Category</label>
          <select name="category_id" id="expenseCategory" required></select>
        </div>

        <div>
          <label>Amount (UGX)</label>
          <input type="number" name="amount" min="0" step="0.01" required>
        </div>

        <div>
          <label>Payment Method</label>
          <select name="payment_method">
            <option value="cash">Cash</option>
            <option value="bank">Bank</option>
            <option value="mobile">Mobile Money</option>
            <option value="card">Card</option>
          </select>
        </div>

        <div>
          <label>Reference</label>
          <input type="text" name="reference">
        </div>

        <div>
          <label>Description</label>
          <input type="text" name="description">
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-void" onclick="closeExpenseModal()">
          Cancel
        </button>
        <button type="submit" class="btn-primary">
          <i data-lucide="save"></i>
          Save Expense
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ================= STYLES ================= -->
<style>

    /* ================= MODAL CORE ================= */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal.hidden {
  display: none;
}

.modal-box {
  background: #0f172a;
  color: #e5e7eb;
  width: 720px;
  max-width: 95%;
  border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0,0,0,.6);
  animation: modalFade .2s ease-out;
}

@keyframes modalFade {
  from {
    transform: translateY(10px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid #1e293b;
}

.modal-header h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
}

.modal-body {
  padding: 22px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-grid label {
  font-size: 13px;
  color: #94a3b8;
  margin-bottom: 4px;
}

.form-grid input,
.form-grid select {
  height: 42px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #020617;
  color: #e5e7eb;
}

.form-grid input:focus,
.form-grid select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37,99,235,.25);
}

.modal-footer {
  padding: 18px 22px;
  border-top: 1px solid #1e293b;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* =====================================================
   EXPENSES PAGE — ADVANCED UI
===================================================== */

.expenses-page {
  padding-bottom: 40px;
}

/* ================= HEADER ================= */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 22px;
  font-weight: 600;
  color: #0f172a;
}

/* ================= FILTER BAR ================= */
.filter-bar {
  margin-bottom: 20px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 500;
  color: #475569;
}

.filter-group input {
  height: 40px;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 14px;
}

.filter-group input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37,99,235,.15);
}

.filter-group.wide {
  grid-column: span 2;
}

.filter-actions {
  display: flex;
  gap: 10px;
}

/* ================= BUTTONS ================= */
.btn-primary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-secondary {
  background: #334155;
  color: #e5e7eb;
}

.btn-secondary:hover {
  background: #1e293b;
}

.btn-void {
  background: #fff;
  border: 1px solid #cbd5e1;
  color: #334155;
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 500;
}

.btn-void:hover {
  background: #f1f5f9;
}

/* ================= TABLE ================= */
.expenses-table {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
}

.expenses-table thead th {
  background: #f8fafc;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  padding: 12px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}

.expenses-table tbody td {
  padding: 12px;
  font-size: 14px;
  border-bottom: 1px solid #e2e8f0;
  color: #0f172a;
}

.expenses-table tbody tr:hover {
  background: #f8fafc;
}

.right {
  text-align: right;
}

.center {
  text-align: center;
}

.muted {
  color: #94a3b8;
}

/* ================= BADGES ================= */
.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: #e0e7ff;
  color: #3730a3;
}

/* ================= ACTION ICONS ================= */
.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #64748b;
}

.icon-btn:hover {
  color: #2563eb;
}

.icon-btn.danger:hover {
  color: #ef4444;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 900px) {
  .filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .filter-group.wide {
    grid-column: span 2;
  }
}

@media (max-width: 520px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .filter-actions {
    justify-content: flex-start;
  }
}

</style>

<!-- ================= SCRIPTS ================= -->
<script src="../../assets/js/expenses.js"></script>
<script>
  if (window.lucide) lucide.createIcons();
</script>

<?php require_once '../../includes/footer.php'; ?>
