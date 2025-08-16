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
    SELECT p.*, c.name AS category
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
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
  <button onclick="loadPanel('add')">➕ New Product</button>
  <button onclick="loadPanel('edit')" disabled id="editBtn">✏️ Edit Product</button>
  <button onclick="deleteSelected()" disabled id="deleteBtn">🗑️ Delete Product</button>
  <button onclick="loadCategoryPanel()">📁 Manage Categories</button>
  
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
  placeholder="Search..." 
  value="<?= htmlspecialchars($search) ?>" 
  onkeydown="if(event.key === 'Enter'){ searchProducts(this.value); }" 
  style="padding:8px; border-radius:4px; margin-left:auto;">

</div>


  <div id="panel-right" class="side-panel hidden"></div>

  <div class="main-table">
    <h2>🛒 Product List</h2>
    <table border="1" cellpadding="10" id="productsTable">
      <thead>
        <tr>
          <th></th>
          <th>Name</th><th>SKU</th><th>Barcode</th><th>Price</th><th>Tax</th><th>Category</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td><input type="radio" name="selected" value="<?= $product['id'] ?>" onclick="enableActions(this.value)"></td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= $product['sku'] ?></td>
            <td><?= $product['barcode'] ?></td>
            <td>UGX <?= number_format($product['price'], 2) ?></td>
            <td><?= $product['tax_rate'] ?>%</td>
            <td><?= $product['category'] ?? 'None' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p>Page <?= $page ?> of <?= $totalPages ?></p>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" <?= $i == $page ? 'style="font-weight:bold;"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>
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
</script>


<!-- Styles -->
<style>
body {
  overflow-x: hidden;
}

.content-area {
  margin-left: 240px;
  margin-top: 80px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}

.topbar {
  background: #0d47a1;
  color: white;
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  position: sticky;
  top: 60px;
  z-index: 50;
}

.topbar input {
  padding: 5px;
  border-radius: 4px;
  border: none;
}

.side-panel {
  position: fixed;
  top: 100px;
  right: 0;
  width: 480px;
  max-width: 100%;
  height: calc(100% - 100px);
  background: #ffffff;
  box-shadow: -2px 0 10px rgba(0,0,0,0.15);
  overflow-y: auto; /* ✅ Ensure this is present */
  overflow-x: hidden;
  padding: 20px;
  z-index: 999;
  display: flex;
  flex-direction: column;
  gap: 10px;
}


.side-panel.hidden {
  display: none;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0%);
    opacity: 1;
  }
}

.side-panel h2 {
  font-size: 20px;
  color: #0d47a1;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.side-panel form {
  display: flex;
  flex-direction: column;
}

.side-panel form input,
.side-panel form select,
.side-panel form button {
  width: 100%;
  padding: 12px 10px;
  margin-bottom: 14px;
  font-size: 14px;
  border-radius: 5px;
  border: 1px solid #ccc;
}

.side-panel form input:focus,
.side-panel form select:focus {
  border-color: #1e88e5;
  outline: none;
}

.side-panel form button {
  background-color: #0d47a1;
  color: white;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.side-panel form button:hover {
  background-color: #1565c0;
}

.side-panel .close-btn {
  align-self: flex-end;
  font-size: 20px;
  border: none;
  background: none;
  color: #333;
  cursor: pointer;
  margin-bottom: -10px;
}

.main-table {
  margin-top: 1rem;
  min-height: 400px;
  padding-bottom: 60px;
}

/* Topbar Button Styling */
.topbar button {
  background: #1565c0;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s;
}
.topbar button:hover {
  background: #1976d2;
}
.topbar button:disabled {
  background: #90caf9;
  cursor: not-allowed;
}

/* Product Table Styling */
.main-table table {
  border-collapse: collapse;
  width: 100%;
  background: white;
  border: 1px solid #ddd;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.main-table th,
.main-table td {
  text-align: left;
  padding: 12px 10px;
  border-bottom: 1px solid #e0e0e0;
}

.main-table th {
  background: #f5f5f5;
  font-weight: 600;
}

.main-table tr:hover {
  background-color: #f0f8ff;
}

/* Pagination Links */
.main-table a {
  margin: 0 5px;
  padding: 6px 12px;
  border-radius: 4px;
  text-decoration: none;
  color: #0d47a1;
  background-color: #e3f2fd;
  font-weight: 500;
}
.main-table a[style*="bold"] {
  background-color: #0d47a1;
  color: white;
}

/* Flash Message (if needed in future) */
.flash-message {
  margin: 10px 0;
  padding: 12px 16px;
  border-radius: 5px;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.flash-message.success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #81c784;
}
.flash-message.error {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ef9a9a;
}
.flash-message button {
  background: transparent;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: inherit;
}

</style>

<?php include('../../includes/footer.php'); ?>
