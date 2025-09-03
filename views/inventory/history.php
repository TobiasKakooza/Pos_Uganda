<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
require_once('../../config/db.php');

if (!in_array($_SESSION['user']['role_id'], [1, 3])) {
    die('❌ Unauthorized access.');
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM inventories");
$totalEntries = $totalStmt->fetchColumn();
$totalPages = ceil($totalEntries / $perPage);

$stmt = $pdo->prepare("
    SELECT i.id, i.quantity, i.type, i.note, i.stock_after, i.created_at, 
           p.name AS product_name 
    FROM inventories i 
    JOIN products p ON i.product_id = p.id 
    ORDER BY i.created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$entries = $stmt->fetchAll();
?>

<link rel="stylesheet" href="../../assets/css/inventory.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="container">
    <h2>📜 Inventory History</h2>
<div style="margin-bottom: 20px;">
  <input 
    type="text" 
    id="inventorySearch" 
    placeholder="🔍 Search by Product, Type, or Note..." 
    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px;"
  >
</div>
<div style="margin-bottom: 20px; display: flex; gap: 10px;">
  <button onclick="exportTableToCSV()" class="modal-btn" style="background-color: #007bff; color: #fff;">📄 Download CSV</button>
  <button onclick="exportTableToPDF()" class="modal-btn" style="background-color: #dc3545; color: #fff;">🧾 Download PDF</button>
</div>
<div style="margin-bottom: 15px;">
  <button onclick="document.getElementById('previewModal').style.display='block'" class="modal-btn" style="background-color:#17a2b8; color:white;">👁 Preview & Export</button>
</div>


    <table class="table">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Date</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Type</th>
                <th>Stock After</th>
                <th>Note</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($entries) > 0): ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= (int)$entry['id'] ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($entry['created_at'])) ?></td>
                        <td><?= htmlspecialchars($entry['product_name']) ?></td>
                        <td><?= (int)$entry['quantity'] ?></td>
                        <td class="<?= $entry['type'] === 'in' ? 'in' : 'out' ?>">
                            <?= strtoupper($entry['type']) ?>
                        </td>
                        <td><?= $entry['stock_after'] !== null ? (int)$entry['stock_after'] : 'N/A' ?></td>
                        <td><?= htmlspecialchars($entry['note']) ?></td>
                        <td class="actions">
                            <button class="edit-btn"
                                data-id="<?= $entry['id'] ?>"
                                data-quantity="<?= $entry['quantity'] ?>"
                                data-type="<?= $entry['type'] ?>"
                                data-note="<?= htmlspecialchars($entry['note']) ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-btn" data-id="<?= $entry['id'] ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;">No inventory activity found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active-page' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3>Edit Inventory Entry</h3>
    <form id="editForm" class="modal-form">
      <input type="hidden" name="id" id="edit-id">

      <label for="edit-quantity">Quantity:</label>
      <input type="number" name="quantity" id="edit-quantity" class="modal-input" required>

      <label for="edit-type">Type:</label>
      <select name="type" id="edit-type" class="modal-input">
        <option value="in">IN</option>
        <option value="out">OUT</option>
      </select>

      <label for="edit-note">Note:</label>
      <textarea name="note" id="edit-note" class="modal-input" rows="3"></textarea>

      <button type="submit" class="modal-btn save-btn">
        💾 Save Changes
      </button>
    </form>
  </div>
</div>


<!-- Delete Modal -->
<div id="deleteModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3>🗑 Confirm Delete</h3>
    <p>Are you sure you want to delete this inventory entry?</p>

    <form id="deleteForm" class="modal-form">
      <input type="hidden" name="id" id="delete-id">

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="modal-btn" style="background-color: #6c757d;" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" class="modal-btn danger-btn">🗑 Yes, Delete</button>
      </div>
    </form>
  </div>
</div>
<!-- Preview Modal -->
<div id="previewModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width: 90%; max-height: 80vh; overflow-y: auto;">
    <span class="close" onclick="closeModal('previewModal')">&times;</span>
    <h3>📄 Preview Export Data</h3>

    <div style="margin-bottom: 15px;">
      <button onclick="loadVisibleData()" class="modal-btn">👁 Preview Visible</button>
      <button onclick="loadAllData()" class="modal-btn">🌐 Preview All</button>
    </div>

    <div id="previewTableContainer"></div>

    <div style="margin-top: 15px; display:flex; gap: 10px;">
      <button onclick="exportPreviewToCSV()" class="modal-btn" style="background-color:#007bff; color:white;">⬇ Download CSV</button>
      <button onclick="exportPreviewToPDF()" class="modal-btn" style="background-color:#dc3545; color:white;">⬇ Download PDF</button>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-quantity').value = btn.dataset.quantity;
        document.getElementById('edit-type').value = btn.dataset.type;
        document.getElementById('edit-note').value = btn.dataset.note;
        document.getElementById('editModal').style.display = 'block';
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('delete-id').value = btn.dataset.id;
        document.getElementById('deleteModal').style.display = 'block';
    });
});

document.querySelectorAll('.modal .close').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.closest('.modal').style.display = 'none';
    });
});

// AJAX Edit
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../../controllers/inventoryController.php?action=edit', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
});

// AJAX Delete
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../../controllers/inventoryController.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
});

function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}
document.getElementById('inventorySearch').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('.table tbody tr');

    rows.forEach(row => {
        const product = row.cells[2]?.textContent.toLowerCase();
        const type = row.cells[4]?.textContent.toLowerCase();
        const note = row.cells[6]?.textContent.toLowerCase();

        if (
            product.includes(query) ||
            type.includes(query) ||
            note.includes(query)
        ) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function exportTableToCSV() {
    const rows = document.querySelectorAll(".table tr");
    let csvContent = "";

    rows.forEach(row => {
        const cols = row.querySelectorAll("th, td");
        const rowData = Array.from(cols)
            .map(col => `"${col.innerText.replace(/"/g, '""')}"`)
            .join(",");
        csvContent += rowData + "\r\n";
    });

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", "inventory_history.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
async function exportTableToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const headers = [["ID", "Date", "Product", "Qty", "Type", "Stock After", "Note"]];
    const data = [];

    document.querySelectorAll(".table tbody tr").forEach(row => {
        if (row.style.display === 'none') return;
        const cols = row.querySelectorAll("td");
        if (cols.length > 0) {
            const rowData = Array.from(cols).slice(0, 7).map(col => col.innerText.trim());
            data.push(rowData);
        }
    });

    doc.text("Inventory History", 14, 15);
    doc.autoTable({
        startY: 20,
        head: headers,
        body: data,
        styles: { fontSize: 9 }
    });

    doc.save("inventory_history.pdf");
}

let previewData = [];

function loadVisibleData() {
  const rows = document.querySelectorAll(".table tbody tr");
  previewData = [];

  rows.forEach(row => {
    if (row.style.display === 'none') return;
    const cols = row.querySelectorAll("td");
    if (cols.length > 0) {
      const rowData = Array.from(cols).slice(0, 7).map(col => col.innerText.trim());
      previewData.push(rowData);
    }
  });

  renderPreviewTable();
}

function loadAllData() {
  fetch('../../controllers/exportInventory.php')
    .then(res => res.json())
    .then(data => {
      previewData = data.map(entry => [
        entry.id,
        entry.created_at,
        entry.product_name,
        entry.quantity,
        entry.type.toUpperCase(),
        entry.stock_after ?? "N/A",
        entry.note
      ]);
      renderPreviewTable();
    });
}

function renderPreviewTable() {
  const container = document.getElementById('previewTableContainer');
  if (previewData.length === 0) {
    container.innerHTML = "<p>No data to preview.</p>";
    return;
  }

  let html = "<table class='table'><thead><tr>";
  const headers = ["ID", "Date", "Product", "Qty", "Type", "Stock After", "Note"];
  headers.forEach(h => html += `<th>${h}</th>`);
  html += "</tr></thead><tbody>";

  previewData.forEach(row => {
    html += "<tr>";
    row.forEach(cell => html += `<td>${cell}</td>`);
    html += "</tr>";
  });

  html += "</tbody></table>";
  container.innerHTML = html;
}

function exportPreviewToCSV() {
  if (!previewData.length) return alert("No preview data loaded.");
  let csv = "ID,Date,Product,Qty,Type,Stock After,Note\n";
  previewData.forEach(row => csv += row.map(val => `"${val}"`).join(",") + "\n");

  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.setAttribute("download", "inventory_export.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function exportPreviewToPDF() {
  if (!previewData.length) return alert("No preview data loaded.");
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  doc.text("Inventory Export", 14, 15);
  doc.autoTable({
    startY: 20,
    head: [["ID", "Date", "Product", "Qty", "Type", "Stock After", "Note"]],
    body: previewData,
    styles: { fontSize: 8 }
  });
  doc.save("inventory_export.pdf");
}

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>


<?php include('../../includes/footer.php'); ?>
