<?php
// Handle AJAX Select2 search
if (isset($_GET['q'])) {
    require_once('../../config/db.php');
    header('Content-Type: application/json');

    $search = $_GET['q'];
    $stmt = $pdo->prepare("
        SELECT p.id, p.name,
            IFNULL(SUM(CASE WHEN i.type = 'in' THEN i.quantity ELSE -i.quantity END), 0) AS stock
        FROM products p
        LEFT JOIN inventories i ON p.id = i.product_id
        WHERE p.name LIKE ?
        GROUP BY p.id
        ORDER BY p.name
        LIMIT 50
    ");
    $stmt->execute(["%$search%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "results" => array_map(function ($prod) {
            return [
                "id" => $prod['id'],
                "text" => $prod['name'] . " (Stock: " . $prod['stock'] . ")"
            ];
        }, $results)
    ]);
    exit;
}

// Normal page content
include('../../includes/auth.php');
include('../../includes/header.php');
require_once('../../config/db.php');
include('../../includes/navbar.php');

if (!in_array($_SESSION['user']['role_id'], [1, 3])) {
    die('❌ Unauthorized access.');
}

$stmt = $pdo->query("
    SELECT p.id, p.name,
        IFNULL(SUM(CASE WHEN i.type = 'in' THEN i.quantity ELSE -i.quantity END), 0) AS stock 
    FROM products p 
    LEFT JOIN inventories i ON p.id = i.product_id 
    GROUP BY p.id 
    ORDER BY p.name
");
$products = $stmt->fetchAll();
?>

<div class="container">
    <h2>📦 Inventory Management</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST" action="../../controllers/inventoryController.php" class="inventory-form">
        <div class="form-group">
            <label for="product_id">Product:</label>
            <select name="product_id" id="product_id" style="width:100%;" required></select>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1" required>
        </div>

        <div class="form-group">
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="in">Stock In</option>
                <option value="out">Stock Out</option>
            </select>
        </div>

        <div class="form-group">
            <label for="note">Note:</label>
            <textarea name="note" id="note" placeholder="Optional note..."></textarea>
        </div>

        <button type="submit" name="inventory_submit">✅ Save Entry</button>
    </form>
</div>

<!-- Scripts (jQuery first) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#product_id').select2({
        placeholder: "-- Select Product --",
        ajax: {
            url: window.location.href, // ⬅️ Using current page for AJAX
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 1
    });
});
</script>

<?php include('../../includes/footer.php'); ?>
