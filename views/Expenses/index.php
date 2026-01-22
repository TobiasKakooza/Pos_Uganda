<?php
require_once '../../includes/auth.php';
require_permission('expenses_manage');

require_once '../../includes/header.php';
// require_once '../../includes/navbar.php';
?>

<div class="expenses-page">

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
/* =====================================================
   DESIGN TOKENS (GLOBAL)
===================================================== */
:root {
  --bg-app: #020617;
  --bg-surface: #0b1220;
  --bg-panel: #0f172a;
  --bg-panel-soft: #111827;

  --border: #1e293b;

  --text-main: #e5e7eb;
  --text-muted: #94a3b8;
  --text-dim: #64748b;

  --primary: #2563eb;
  --primary-hover: #1d4ed8;

  --danger: #ef4444;

  --radius: 14px;
  --radius-sm: 10px;

  --shadow-lg: 0 30px 80px rgba(0,0,0,.6);
  --shadow-md: 0 16px 40px rgba(0,0,0,.45);
}

/* =====================================================
   PAGE BASE
===================================================== */
main {
  background: var(--bg-app);
}

.expenses-page {
  padding: 24px;
  color: var(--text-main);
}

/* =====================================================
   PAGE HEADER
===================================================== */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 22px;
  font-weight: 600;
  color: var(--text-main);
}

/* =====================================================
   PANELS / CARDS
===================================================== */
.card {
  background: linear-gradient(
    180deg,
    var(--bg-panel),
    var(--bg-panel-soft)
  );
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
  padding: 18px;
}

/* =====================================================
   FILTER BAR
===================================================== */
.filter-bar {
  margin-bottom: 18px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-muted);
}

.filter-group input {
  height: 40px;
  padding: 0 12px;
  border-radius: var(--radius-sm);
  background: var(--bg-app);
  border: 1px solid var(--border);
  color: var(--text-main);
}

.filter-group input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,.25);
}

.filter-group.wide {
  grid-column: span 2;
}

.filter-actions {
  display: flex;
  gap: 10px;
}

/* =====================================================
   BUTTONS
===================================================== */
.btn-primary,
.btn-secondary,
.btn-void {
  height: 40px;
  padding: 0 16px;
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(180deg, #2563eb, #1e40af);
  color: #fff;
  border: none;
}

.btn-primary:hover {
  background: linear-gradient(180deg, #1d4ed8, #1e3a8a);
}

.btn-secondary {
  background: var(--bg-app);
  color: var(--text-main);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: #020617;
}

.btn-void {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
}

.btn-void:hover {
  color: var(--text-main);
}

/* =====================================================
   TABLE
===================================================== */
.expenses-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.expenses-table thead th {
  background: #020617;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
  padding: 12px;
  border-bottom: 1px solid var(--border);
  text-align: left;
}

.expenses-table tbody td {
  padding: 14px 12px;
  font-size: 14px;
  color: var(--text-main);
  border-bottom: 1px solid var(--border);
}

.expenses-table tbody tr:hover {
  background: rgba(37,99,235,.08);
}

.right { text-align: right; }
.center { text-align: center; }
.muted { color: var(--text-dim); }

/* =====================================================
   ACTION ICONS
===================================================== */
.icon-btn {
  background: none;
  border: none;
  padding: 6px;
  cursor: pointer;
  color: var(--text-dim);
}

.icon-btn:hover {
  color: var(--primary);
}

.icon-btn.danger:hover {
  color: var(--danger);
}

/* =====================================================
   MODAL
===================================================== */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(2,6,23,.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal.hidden {
  display: none;
}

.modal-box {
  width: 720px;
  max-width: 95%;
  background: linear-gradient(
    180deg,
    var(--bg-panel),
    var(--bg-panel-soft)
  );
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: var(--shadow-lg);
  color: var(--text-main);
}

.modal-header {
  padding: 18px 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
}

.modal-header h3 {
  display: flex;
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
  font-size: 12px;
  color: var(--text-muted);
}

.form-grid input,
.form-grid select {
  height: 40px;
  padding: 0 12px;
  border-radius: var(--radius-sm);
  background: var(--bg-app);
  border: 1px solid var(--border);
  color: var(--text-main);
}

.form-grid input:focus,
.form-grid select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,.25);
}

.modal-footer {
  padding: 18px 22px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* =====================================================
   RESPONSIVE
===================================================== */
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
}
/* =====================================================
   HARD RESET BOOTSTRAP / LEGACY TABLE STYLES
===================================================== */
.expenses-table,
.expenses-table th,
.expenses-table td {
  background: transparent !important;
}

/* =====================================================
   TABLE BACKGROUND + STRUCTURE (DARK)
===================================================== */
.expenses-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

/* ===== HEADER ===== */
.expenses-table thead th {
  background: linear-gradient(
    180deg,
    #020617,
    #020617
  );
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
  padding: 14px 12px;
  border-bottom: 1px solid var(--border);
  text-transform: uppercase;
  letter-spacing: .4px;
}

/* ===== BODY ===== */
.expenses-table tbody td {
  background: var(--bg-panel);
  color: var(--text-main);
  padding: 14px 12px;
  font-size: 14px;
  border-bottom: 1px solid var(--border);
}

/* ===== STRIPED ROWS (WORLD CLASS) ===== */
.expenses-table tbody tr:nth-child(even) td {
  background: var(--bg-panel-soft);
}

/* ===== HOVER ===== */
.expenses-table tbody tr:hover td {
  background: rgba(37,99,235,.12);
}

/* ===== ALIGN HELPERS ===== */
.expenses-table .right {
  text-align: right;
}

.expenses-table .center {
  text-align: center;
}

</style>

<!-- ================= SCRIPTS ================= -->
<script src="../../assets/js/expenses.js"></script>
<script>
  if (window.lucide) lucide.createIcons();
</script>

<?php require_once '../../includes/footer.php'; ?>
