<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
require_once('../../config/db.php');

// Setup Pagination
$limit = 10;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$searchQuery = '';
$params = [];

if ($search) {
    $searchQuery = "WHERE p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $searchQuery");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.name,
        p.sku,
        p.barcode,
        p.price,
        p.avg_cost,
        p.tax_rate,
        p.stock_alert_threshold,
        c.name AS category,
        u.name AS unit
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    $searchQuery
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="content-area">

<?php if (isset($_SESSION['success'])): ?>
  <div class="flash-message success">
    <?= $_SESSION['success'] ?>
    <button onclick="this.parentElement.remove()">×</button>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="flash-message error">
    <?= $_SESSION['error'] ?>
    <button onclick="this.parentElement.remove()">×</button>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<div class="topbar">

  <button onclick="loadPanel('add')">
    <i data-lucide="plus-circle"></i>
    New Product
  </button>

  <button onclick="loadPanel('edit')" disabled id="editBtn">
    <i data-lucide="edit-3"></i>
    Edit Product
  </button>

  <button onclick="deleteSelected()" disabled id="deleteBtn">
    <i data-lucide="trash-2"></i>
    Delete Product
  </button>

  <button onclick="loadCategoryPanel()">
    <i data-lucide="folder-tree"></i>
    Manage Categories
  </button>
  <!-- 📁 Category Manager Panel
<div id="panel-right" class="side-panel hidden">
  <div class="category-box">
    <h3>📁 Category Manager</h3>

    <form id="categoryForm">
      <input type="text" name="name" placeholder="Enter New Category" required>
      <button type="submit">➕ Add</button>
    </form>

    <ul id="categoryList"></ul>
  </div>
</div> -->


<!-- Replace just this part of your list.php script block -->
<script>
function enableActions(id) {
  document.getElementById('editBtn').disabled = false;
  document.getElementById('deleteBtn').disabled = false;
}

function deleteSelected() {
  const id = document.querySelector('input[name=selected]:checked')?.value;
  if (confirm('Delete product?')) {
    window.location.href = `/POS_UG/controllers/productController.php?delete=${id}`;
  }
}

function searchProducts(query) {
  window.location.href = '?search=' + encodeURIComponent(query);
}

function loadPanel(action) {
  const panel = document.getElementById('panel-right');
  let url = '';
  
  if (action === 'add') {
    url = '/POS_UG/views/products/add.php';
  } else if (action === 'edit') {
    const id = document.querySelector('input[name=selected]:checked')?.value;
    if (!id) return alert('Select a product first.');
    url = `/POS_UG/views/products/edit.php?id=${id}`;
  }

  fetch(url)
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';


       // 🔥 FORCE lucide to re-scan injected HTML
    if (window.lucide) {
      lucide.createIcons();
    }
    });
}

function hidePanel() {
  const panel = document.getElementById('panel-right');
  panel.classList.add('hidden');
  panel.innerHTML = '';
  document.body.style.overflow = 'auto';
}

// ✅ Load category manager panel into side-panel
function loadCategoryPanel() {
  const panel = document.getElementById('panel-right');
  fetch('/POS_UG/views/products/categories.php')
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';

      // ✅ Bind form events after loading HTML
      bindCategoryFormEvents();
    })
    .catch(err => {
      alert("❌ Failed to load category manager.");
      console.error(err);
    });
}

// ✅ Bind form submission for add/update
function bindCategoryFormEvents() {
  const form = document.getElementById('categoryForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(form);
    const id = formData.get('id');
    const action = id ? 'updateCategory' : 'addCategory';

    fetch(`/POS_UG/controllers/productController.php?action=${action}`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        form.reset();
        fetchCategories(); // Re-fetch updated list
        alert(`✅ Category ${id ? 'updated' : 'added'} successfully.`);
      } else {
        alert('❌ Failed: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      console.error('Save failed:', err);
      alert('❌ Error saving category.');
    });
  });

  // ✅ Also bind deleteCategory button handlers (if any loaded)
  fetchCategories();
}

// ✅ Fetch all categories into <ul id="categoryList">
function fetchCategories() {
  fetch('/POS_UG/controllers/productController.php?action=getCategories')
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('categoryList');
      if (!list) return;
      list.innerHTML = '';

      if (data.length === 0) {
        list.innerHTML = '<li>No categories yet.</li>';
        return;
      }

      data.forEach(cat => {
        const li = document.createElement('li');
        li.innerHTML = `
          <strong>${cat.name}</strong> - <small>${cat.description ?? ''}</small>
          <div>
            <button onclick='editCategory(${JSON.stringify(cat)})'>✏️</button>
            <button onclick='deleteCategory(${cat.id})'>🗑️</button>
          </div>
        `;
        list.appendChild(li);
      });
    });
}

// Add this to your existing script block
document.addEventListener("input", function (e) {
  if (e.target && e.target.id === "filterInput") {
    const filterValue = e.target.value.toLowerCase();
    const listItems = document.querySelectorAll("#categoryList li");
    listItems.forEach(li => {
      const name = li.querySelector("strong")?.innerText.toLowerCase() || "";
      const desc = li.querySelector("small")?.innerText.toLowerCase() || "";
      const match = name.includes(filterValue) || desc.includes(filterValue);
      li.style.display = match ? "flex" : "none";
    });
  }
});

// ✅ Populate category form for edit
function editCategory(cat) {
  document.getElementById('categoryId').value = cat.id;
  document.getElementById('categoryName').value = cat.name;
  document.getElementById('categoryDesc').value = cat.description ?? '';
}

// ✅ Handle delete category
function deleteCategory(id) {
  if (!confirm('Are you sure to delete this category?')) return;

  fetch(`/POS_UG/controllers/productController.php?action=deleteCategory&id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        fetchCategories();
        alert('🗑️ Category deleted.');
      } else {
        alert('❌ Failed: ' + data.error);
      }
    });
}
</script>

<!-- Styles for Category Manager -->
<style>
.category-area {
  margin-top: 12px;
  background: #ffffff;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 20px;
  max-width: 480px;
}

.category-area.hidden {
  display: none;
}

.category-box h3 {
  font-size: 18px;
  margin-bottom: 12px;
  color: #0d47a1;
  display: flex;
  align-items: center;
  gap: 8px;
}

#categoryForm {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}
#categoryForm input {
  flex: 1;
  padding: 10px;
  border: 1px solid #bbb;
  border-radius: 4px;
}
#categoryForm button {
  background: #0d47a1;
  color: white;
  border: none;
  padding: 10px 14px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}
#categoryForm button:hover {
  background: #1565c0;
}

#categoryList {
  list-style: none;
  padding-left: 0;
}
#categoryList li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 4px;
  border-bottom: 1px solid #eee;
}
#categoryList li span {
  flex-grow: 1;
}
#categoryList li button {
  background: none;
  border: none;
  color: #c62828;
  cursor: pointer;
  font-size: 16px;
}
</style>

<input
  type="text"
  id="searchInput"
  placeholder="Search products…"
  autocomplete="off"
  style="padding:8px;border-radius:4px;margin-left:auto;min-width:220px"
>


</div>


<div id="panel-right" class="side-panel hidden"></div>

<div class="main-table">

  <h2 style="display:flex;align-items:center;gap:8px">
    <i data-lucide="shopping-cart"></i>
    Product List
  </h2>

  <table id="productsTable">
    <thead>
      <tr>
        <th></th>
        <th>Product</th>
        <th>SKU</th>
        <th>Cost</th>
        <th>Price</th>
        <th>Tax</th>
        <th>Unit</th>
        <th>Category</th>
        <th>Alert</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($products as $product): ?>
        <tr>

          <!-- Select -->
          <td>
            <input type="radio"
                   name="selected"
                   value="<?= $product['id'] ?>"
                   onclick="enableActions(this.value)">
          </td>

          <!-- Product Name + Barcode -->
          <td>
            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
            <small style="color:#607d8b">
              <i data-lucide="barcode"></i>
              <?= htmlspecialchars($product['barcode'] ?? '—') ?>
            </small>
          </td>

          <!-- SKU -->
          <td>
            <i data-lucide="tag"></i>
            <?= htmlspecialchars($product['sku']) ?>
          </td>

          <!-- Cost -->
          <td>
            UGX <?= number_format($product['avg_cost'], 2) ?>
          </td>

          <!-- Selling Price -->
          <td>
            <strong>
              UGX <?= number_format($product['price'], 2) ?>
            </strong>
          </td>

          <!-- Tax -->
          <td>
            <i data-lucide="percent"></i>
            <?= $product['tax_rate'] ?>%
          </td>

          <!-- Unit -->
          <td>
            <i data-lucide="box"></i>
            <?= $product['unit'] ?? '—' ?>
          </td>

          <!-- Category -->
          <td>
            <i data-lucide="folder"></i>
            <?= $product['category'] ?? 'None' ?>
          </td>

          <!-- Stock Alert -->
          <td>
            <i data-lucide="alert-triangle"></i>
            <?= (int)$product['stock_alert_threshold'] ?>
          </td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div style="margin-top:14px">
    <small>Page <?= $page ?> of <?= $totalPages ?></small><br>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
         <?= $i == $page ? 'style="font-weight:bold;"' : '' ?>>
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>

</div>

<script>
  // Re-render lucide icons after dynamic content
  lucide.createIcons();
</script>

<!-- Inside your <script> tag -->
<script>
function enableActions(id) {
  document.getElementById('editBtn').disabled = false;
  document.getElementById('deleteBtn').disabled = false;
}

function deleteSelected() {
  const id = document.querySelector('input[name=selected]:checked')?.value;
  if (confirm('Delete product?')) {
    window.location.href = `/POS_UG/controllers/productController.php?delete=${id}`;
  }
}

function searchProducts(query) {
  const currentPage = 1; // always reset to first page when searching
  window.location.href = '?page=' + currentPage + '&search=' + encodeURIComponent(query);
}


function loadPanel(action) {
  const panel = document.getElementById('panel-right');
  let url = '';
  
  if (action === 'add') {
    url = '/POS_UG/views/products/add.php';
  } else if (action === 'edit') {
    const id = document.querySelector('input[name=selected]:checked')?.value;
    if (!id) return alert('Select a product first.');
    url = `/POS_UG/views/products/edit.php?id=${id}`;
  }

  fetch(url)
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    });
}

function hidePanel() {
  const panel = document.getElementById('panel-right');
  panel.classList.add('hidden');
  panel.innerHTML = '';
  document.body.style.overflow = 'auto';
}

document.getElementById('searchInput').addEventListener('input', function () {
  const query = this.value.toLowerCase().trim();
  const rows = document.querySelectorAll('#productsTable tbody tr');

  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(query) ? '' : 'none';
  });
});
</script>


<!-- Styles -->
<style>
/* ======================================================
   PRODUCTS LIST – ENTERPRISE DESIGN SYSTEM
   (Light & Dark | No JS Changes | No HTML Changes)
====================================================== */

/* -----------------------------
   BASE SAFETY
----------------------------- */
body {
  overflow-x: hidden;
}

.content-area {
  display: flex;
  flex-direction: column;
}

/* ======================================================
   TOP ACTION BAR
====================================================== */
.topbar {
  position: sticky;
  top: 64px;
  z-index: 60;

  display: flex;
  align-items: center;
  gap: 12px;

  padding: 12px 16px;

  background: var(--bg-header);
  color: var(--text-main);

  border-bottom: 1px solid var(--border);
  box-shadow: 0 6px 20px rgba(0,0,0,.12);
}

/* Buttons */
.topbar button {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  height: 42px;
  padding: 0 16px;

  background: linear-gradient(
    135deg,
    var(--primary),
    color-mix(in srgb, var(--primary) 70%, black)
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
    box-shadow .15s ease,
    opacity .15s ease;
}

.topbar button:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 40px rgba(0,0,0,.35);
}

.topbar button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

/* Search Input */
.topbar input {
  margin-left: auto;
  height: 38px;
  padding: 0 14px;

  background: color-mix(in srgb, var(--bg-panel) 92%, black);
  color: var(--text-main);

  border: 1px solid var(--border);
  border-radius: 10px;

  font-size: 14px;
}

.topbar input::placeholder {
  color: var(--text-muted);
}

/* ======================================================
   MAIN TABLE CONTAINER
====================================================== */
.main-table {
  margin-top: 16px;
  padding-bottom: 80px;
}

/* ======================================================
   PRODUCT TABLE
====================================================== */
.main-table table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;

  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: 16px;

  overflow: hidden;
  box-shadow: 0 16px 50px rgba(0,0,0,.12);
}

/* Table Head */
.main-table th {
  padding: 14px 14px;

  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--bg-panel) 95%, black),
    color-mix(in srgb, var(--bg-panel) 90%, black)
  );

  font-size: 12px;
  font-weight: 600;
  letter-spacing: .4px;
  text-transform: uppercase;

  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
}

/* Table Body */
.main-table td {
  padding: 14px 14px;
  font-size: 14px;
  color: var(--text-main);

  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}

.main-table tbody tr {
  transition: background .15s ease;
}

.main-table tbody tr:hover {
  background: var(--bg-hover);
}

/* Last row cleanup */
.main-table tbody tr:last-child td {
  border-bottom: none;
}

/* ======================================================
   PAGINATION
====================================================== */
.main-table a {
  display: inline-block;
  margin: 6px 4px;
  padding: 6px 14px;

  border-radius: 999px;
  text-decoration: none;

  background: color-mix(in srgb, var(--bg-panel) 90%, black);
  color: var(--text-main);

  font-size: 13px;
  font-weight: 600;

  border: 1px solid var(--border);
  transition: all .15s ease;
}

.main-table a:hover {
  background: var(--bg-hover);
}

.main-table a[style*="bold"] {
  background: var(--primary);
  color: #fff;
  border-color: transparent;
}

/* ======================================================
   SLIDE-IN RIGHT PANEL
====================================================== */
.side-panel {
  position: fixed;
  top: 64px;
  right: 0;

  width: 480px;
  max-width: 100%;
  height: calc(100% - 64px);

  background: var(--bg-panel);
  color: var(--text-main);

  border-left: 1px solid var(--border);
  box-shadow: -24px 0 80px rgba(0,0,0,.45);

  padding: 22px;
  overflow-y: auto;
  overflow-x: hidden;

  z-index: 1000;

  transform: translateX(100%);
  opacity: 0;

  transition:
    transform .35s cubic-bezier(.4,0,.2,1),
    opacity .25s ease;
}

.side-panel:not(.hidden) {
  transform: translateX(0);
  opacity: 1;
}

.side-panel.hidden {
  pointer-events: none;
}

/* ======================================================
   PANEL HEADINGS
====================================================== */
.side-panel h2,
.side-panel h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 16px;

  display: flex;
  align-items: center;
  gap: 8px;
}

/* ======================================================
   PANEL FORMS
====================================================== */
.side-panel form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.side-panel input,
.side-panel select,
.side-panel textarea {
  background: color-mix(in srgb, var(--bg-panel) 92%, black);
  color: var(--text-main);

  border: 1px solid var(--border);
  border-radius: 10px;

  padding: 12px;
  font-size: 14px;
}

.side-panel input::placeholder {
  color: var(--text-muted);
}

.side-panel input:focus,
.side-panel select:focus,
.side-panel textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 35%, transparent);
}

/* Panel Buttons */
.side-panel form button {
  height: 44px;

  background: linear-gradient(
    135deg,
    var(--primary),
    color-mix(in srgb, var(--primary) 75%, black)
  );

  color: #fff;
  font-weight: 600;
  border: none;
  border-radius: 12px;

  cursor: pointer;
  transition: transform .15s ease, box-shadow .15s ease;
}

.side-panel form button:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 36px rgba(0,0,0,.35);
}

/* ======================================================
   PANEL CLOSE BUTTON
====================================================== */
.side-panel .close-btn {
  position: absolute;
  top: 14px;
  right: 14px;

  width: 36px;
  height: 36px;

  display: grid;
  place-items: center;

  background: transparent;
  border: 1px solid var(--border);
  border-radius: 50%;

  color: var(--text-muted);
  cursor: pointer;

  transition: all .2s ease;
}

.side-panel .close-btn:hover {
  background: var(--bg-hover);
  color: var(--text-main);
}

.side-panel .close-btn svg {
  width: 18px;
  height: 18px;
  stroke-width: 2.5;
}

/* ======================================================
   FLASH MESSAGES
====================================================== */
.flash-message {
  background: var(--bg-panel);
  color: var(--text-main);

  border: 1px solid var(--border);
  border-radius: 14px;

  padding: 14px 18px;
  margin-bottom: 16px;

  display: flex;
  justify-content: space-between;
  align-items: center;

  box-shadow: 0 12px 40px rgba(0,0,0,.18);
}

.flash-message button {
  background: none;
  border: none;
  color: inherit;
  font-size: 18px;
  cursor: pointer;
}
/* ======================================================
   PRODUCTS LIST – THEME OVERRIDE LAYER
   (Fixes table + side panel mismatch)
====================================================== */

/* ------------------------------------------------------
   LIGHT MODE – FORCE CLEAN, NEUTRAL UI
------------------------------------------------------ */
body[data-theme="light"] {

  --prod-bg: #f4f6f9;
  --prod-panel: #ffffff;
  --prod-header: #ffffff;
  --prod-border: #e5e7eb;

  --prod-text: #0f172a;
  --prod-muted: #64748b;

  --prod-hover: #f1f5f9;
}

/* ------------------------------------------------------
   DARK MODE – FORCE TRUE DARK UI
------------------------------------------------------ */
body[data-theme="dark"] {

  --prod-bg: #020617;
  --prod-panel: #0f172a;
  --prod-header: #020617;
  --prod-border: #1e293b;

  --prod-text: #e5e7eb;
  --prod-muted: #94a3b8;

  --prod-hover: #1e293b;
}

/* ======================================================
   APPLY OVERRIDES (TABLE)
====================================================== */
body[data-theme] .main-table table {
  background: var(--prod-panel) !important;
  border-color: var(--prod-border) !important;
}

body[data-theme] .main-table th {
  background: var(--prod-header) !important;
  color: var(--prod-muted) !important;
  border-bottom: 1px solid var(--prod-border) !important;
}

body[data-theme] .main-table td {
  color: var(--prod-text) !important;
  border-bottom: 1px solid var(--prod-border) !important;
}

body[data-theme] .main-table tbody tr:hover {
  background: var(--prod-hover) !important;
}

/* ======================================================
   APPLY OVERRIDES (TOPBAR)
====================================================== */
body[data-theme] .topbar {
  background: var(--prod-header) !important;
  color: var(--prod-text) !important;
  border-bottom: 1px solid var(--prod-border) !important;
}

body[data-theme] .topbar input {
  background: var(--prod-panel) !important;
  color: var(--prod-text) !important;
  border-color: var(--prod-border) !important;
}

/* ======================================================
   APPLY OVERRIDES (SLIDE-IN PANEL)
====================================================== */
body[data-theme] .side-panel {
  background: var(--prod-panel) !important;
  color: var(--prod-text) !important;
  border-left: 1px solid var(--prod-border) !important;
}

body[data-theme] .side-panel h2,
body[data-theme] .side-panel h3 {
  color: var(--prod-text) !important;
}

body[data-theme] .side-panel input,
body[data-theme] .side-panel select,
body[data-theme] .side-panel textarea {
  background: var(--prod-header) !important;
  color: var(--prod-text) !important;
  border-color: var(--prod-border) !important;
}

body[data-theme] .side-panel input::placeholder {
  color: var(--prod-muted) !important;
}

/* ======================================================
   CLOSE BUTTON – CONSISTENT IN BOTH MODES
====================================================== */
body[data-theme] .side-panel .close-btn {
  border-color: var(--prod-border) !important;
  color: var(--prod-muted) !important;
}

body[data-theme] .side-panel .close-btn:hover {
  background: var(--prod-hover) !important;
  color: var(--prod-text) !important;
}

/* ======================================================
   PAGINATION FIX
====================================================== */
body[data-theme] .main-table a {
  background: var(--prod-panel) !important;
  color: var(--prod-text) !important;
  border-color: var(--prod-border) !important;
}

body[data-theme] .main-table a:hover {
  background: var(--prod-hover) !important;
}

body[data-theme] .main-table a[style*="bold"] {
  background: var(--primary) !important;
  color: #fff !important;
  border-color: transparent !important;
}
/* ======================================================
   FINAL POLISH LAYER – BUTTONS & LIGHT MODE
   (Safe override – paste at the VERY BOTTOM)
====================================================== */

/* ------------------------------------------------------
   LIGHT MODE – VISUAL BALANCE (NOT PURE WHITE)
------------------------------------------------------ */
body[data-theme="light"] {
  --lm-bg: #f6f8fb;
  --lm-panel: #ffffff;
  --lm-header: #f9fafb;

  --lm-border: #dce1ea;

  --lm-text: #0f172a;
  --lm-muted: #5b6b82;

  --lm-hover: #eef2f7;
}

/* Apply to page background */
body[data-theme="light"] main {
  background: var(--lm-bg);
}

/* ------------------------------------------------------
   TOPBAR (LIGHT MODE REFINEMENT)
------------------------------------------------------ */
body[data-theme="light"] .topbar {
  background: var(--lm-header) !important;
  border-bottom: 1px solid var(--lm-border) !important;
}

/* ------------------------------------------------------
   BUTTON SYSTEM (ENTERPRISE-GRADE)
------------------------------------------------------ */
.topbar button {
  position: relative;
  isolation: isolate;

  display: inline-flex;
  align-items: center;
  gap: 8px;

  padding: 0 18px;
  height: 44px;

  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;

  letter-spacing: .2px;
}

/* Icon sizing */
.topbar button svg {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

/* PRIMARY (New / Edit / Manage) */
.topbar button:not(:disabled) {
  background: linear-gradient(
    135deg,
    var(--primary),
    color-mix(in srgb, var(--primary) 70%, black)
  );
  box-shadow: 0 6px 16px rgba(0,0,0,.15);
}

/* Hover */
.topbar button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 34px rgba(0,0,0,.25);
}

/* Active */
.topbar button:not(:disabled):active {
  transform: translateY(0);
  box-shadow: 0 4px 12px rgba(0,0,0,.2);
}

/* DISABLED */
.topbar button:disabled {
  background: #cbd5e1 !important;
  color: #64748b !important;
  box-shadow: none !important;
}

/* ------------------------------------------------------
   DELETE BUTTON – DANGER SIGNAL
------------------------------------------------------ */
#deleteBtn:not(:disabled) {
  background: linear-gradient(135deg, #ef4444, #b91c1c) !important;
}

#deleteBtn:not(:disabled):hover {
  box-shadow: 0 14px 34px rgba(239,68,68,.35);
}

/* ------------------------------------------------------
   TABLE – LIGHT MODE READABILITY
------------------------------------------------------ */
body[data-theme="light"] .main-table table {
  background: var(--lm-panel) !important;
  border-color: var(--lm-border) !important;
}

body[data-theme="light"] .main-table th {
  background: var(--lm-header) !important;
  color: var(--lm-muted) !important;
  border-bottom: 1px solid var(--lm-border) !important;
}

body[data-theme="light"] .main-table td {
  color: var(--lm-text) !important;
  border-bottom: 1px solid var(--lm-border) !important;
}

/* Hover rows */
body[data-theme="light"] .main-table tbody tr:hover {
  background: var(--lm-hover) !important;
}

/* ------------------------------------------------------
   PRICE EMPHASIS
------------------------------------------------------ */
.main-table td strong {
  font-weight: 700;
  letter-spacing: .2px;
}

/* ------------------------------------------------------
   LUCIDE ICON COLOR TUNING
------------------------------------------------------ */
body[data-theme="light"] .main-table svg {
  color: #334155;
}

body[data-theme="dark"] .main-table svg {
  color: #cbd5e1;
}

/* ------------------------------------------------------
   SIDE PANEL – LIGHT MODE CLEAN LOOK
------------------------------------------------------ */
body[data-theme="light"] .side-panel {
  background: var(--lm-panel) !important;
  border-left: 1px solid var(--lm-border) !important;
}

body[data-theme="light"] .side-panel input,
body[data-theme="light"] .side-panel select,
body[data-theme="light"] .side-panel textarea {
  background: #f8fafc !important;
  border-color: var(--lm-border) !important;
}

/* ------------------------------------------------------
   CLOSE BUTTON – SUBTLE & MODERN
------------------------------------------------------ */
body[data-theme="light"] .side-panel .close-btn {
  background: #f1f5f9 !important;
}

body[data-theme="light"] .side-panel .close-btn:hover {
  background: #e2e8f0 !important;
}
/* ======================================================
   ENTERPRISE BUTTON SYSTEM
   Light & Dark | Animated | No HTML changes
====================================================== */

/* ------------------------------------------------------
   BASE BUTTON RESET
------------------------------------------------------ */
.topbar button {
  position: relative;
  isolation: isolate;

  display: inline-flex;
  align-items: center;
  gap: 10px;

  height: 44px;
  padding: 0 18px;

  border-radius: 14px;
  border: none;

  font-size: 14px;
  font-weight: 600;
  letter-spacing: .25px;

  cursor: pointer;
  overflow: hidden;

  transition:
    transform .18s cubic-bezier(.4,0,.2,1),
    box-shadow .18s ease,
    background .18s ease,
    color .18s ease,
    opacity .18s ease;
}

/* Icon sizing */
.topbar button svg {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

/* ------------------------------------------------------
   LIGHT MODE – BUTTON COLORS
------------------------------------------------------ */
body[data-theme="light"] .topbar button:not(:disabled) {
  background: linear-gradient(
    135deg,
    #2563eb,
    #1d4ed8
  );
  color: #ffffff;

  box-shadow:
    0 6px 16px rgba(37,99,235,.25),
    inset 0 1px 0 rgba(255,255,255,.25);
}

/* ------------------------------------------------------
   DARK MODE – BUTTON COLORS
------------------------------------------------------ */
body[data-theme="dark"] .topbar button:not(:disabled) {
  background: linear-gradient(
    135deg,
    #3b82f6,
    #1e40af
  );
  color: #ffffff;

  box-shadow:
    0 6px 20px rgba(0,0,0,.55),
    inset 0 1px 0 rgba(255,255,255,.12);
}

/* ------------------------------------------------------
   SHINE EFFECT (SUBTLE)
------------------------------------------------------ */
.topbar button::before {
  content: '';
  position: absolute;
  inset: 0;

  background: linear-gradient(
    120deg,
    transparent 30%,
    rgba(255,255,255,.35),
    transparent 70%
  );

  transform: translateX(-120%);
  transition: transform .6s ease;
}

.topbar button:hover::before {
  transform: translateX(120%);
}

/* ------------------------------------------------------
   HOVER – LIFT + GLOW
------------------------------------------------------ */
.topbar button:not(:disabled):hover {
  transform: translateY(-2px);

  box-shadow:
    0 18px 40px rgba(0,0,0,.35),
    inset 0 1px 0 rgba(255,255,255,.35);
}

/* ------------------------------------------------------
   ACTIVE – PRESS EFFECT
------------------------------------------------------ */
.topbar button:not(:disabled):active {
  transform: translateY(0);

  box-shadow:
    0 6px 14px rgba(0,0,0,.35),
    inset 0 3px 6px rgba(0,0,0,.35);
}

/* ------------------------------------------------------
   FOCUS – ACCESSIBLE RING
------------------------------------------------------ */
.topbar button:focus-visible {
  outline: none;
  box-shadow:
    0 0 0 3px rgba(59,130,246,.45),
    0 12px 32px rgba(0,0,0,.35);
}

/* ------------------------------------------------------
   DISABLED STATE – CLEAR BUT ELEGANT
------------------------------------------------------ */
.topbar button:disabled {
  background: #cbd5e1 !important;
  color: #64748b !important;

  box-shadow: none !important;
  cursor: not-allowed;

  opacity: .7;
}

body[data-theme="dark"] .topbar button:disabled {
  background: #1e293b !important;
  color: #64748b !important;
}

/* ------------------------------------------------------
   DANGER BUTTON (DELETE)
------------------------------------------------------ */
#deleteBtn:not(:disabled) {
  background: linear-gradient(
    135deg,
    #ef4444,
    #b91c1c
  ) !important;

  box-shadow:
    0 6px 18px rgba(239,68,68,.35),
    inset 0 1px 0 rgba(255,255,255,.25);
}

#deleteBtn:not(:disabled):hover {
  box-shadow:
    0 20px 44px rgba(239,68,68,.45),
    inset 0 1px 0 rgba(255,255,255,.35);
}

/* ------------------------------------------------------
   ICON COLOR SYNC
------------------------------------------------------ */
.topbar button svg {
  color: currentColor;
}

/* ------------------------------------------------------
   SUBTLE TEXT SMOOTHING
------------------------------------------------------ */
.topbar button {
  -webkit-font-smoothing: antialiased;
}
/* ======================================================
   PRODUCTS LIST VISIBILITY FIX
   (Fix hidden Product List under sticky topbar)
====================================================== */

/* Ensure content starts BELOW the sticky topbar */
.main-table {
  margin-top: 88px !important; /* 64px topbar + spacing */
}

/* Prevent sticky bar from overlapping content */
.topbar {
  position: sticky;
  top: 64px;
}

/* Make sure content-area does not collapse */
.content-area {
  position: relative;
  z-index: 1;
}

/* Ensure table is above background layers */
.main-table {
  position: relative;
  z-index: 2;
}

/* Remove excessive left squeeze from global layout */
.main-table,
.content-area {
  max-width: 100%;
  padding-left: 0;
}

/* If sidebar padding exists, neutralize it for products page */
body:has(.main-table) .dashboard-content,
body:has(.main-table) main {
  padding-left: 0 !important;
}

/* Defensive: prevent clipping */
.main-table table {
  overflow: visible;
}

</style>

<?php include('../../includes/footer.php'); ?>
