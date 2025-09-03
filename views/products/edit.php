<?php
require_once('../../config/db.php');

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>Invalid product ID.</p>";
    exit;
}

$id = $_GET['id'];

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p style='color:red;'>Product not found.</p>";
    exit;
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch units
$units = $pdo->query("SELECT * FROM units ORDER BY name")->fetchAll();
?>

<!-- Close button -->
<button class="close-btn" onclick="document.getElementById('panel-right').classList.add('hidden')">✖</button>

<h2>✏️ Edit Product</h2>

<form method="POST" action="../../controllers/productController.php">
    <input type="hidden" name="id" value="<?= $product['id'] ?>">

    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" placeholder="Product Name" required>
    <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" placeholder="SKU" required>
    <input type="text" name="barcode" value="<?= htmlspecialchars($product['barcode']) ?>" placeholder="Barcode">
    <input type="number" name="price" value="<?= htmlspecialchars($product['price']) ?>" step="0.01" placeholder="Price" required>
    <input type="number" name="tax_rate" value="<?= htmlspecialchars($product['tax_rate']) ?>" step="0.01" placeholder="Tax Rate">

    <!-- Category dropdown -->
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Unit dropdown -->
    <select name="unit_id" required>
        <option value="">Select Unit</option>
        <?php foreach ($units as $unit): ?>
            <option value="<?= $unit['id'] ?>" <?= $unit['id'] == $product['unit_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($unit['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Stock Alert Threshold -->
    <input type="number" name="stock_alert_threshold" value="<?= htmlspecialchars($product['stock_alert_threshold']) ?>" min="0" placeholder="Stock Alert Threshold">

    <button type="submit" name="update_product">Update</button>
</form>
