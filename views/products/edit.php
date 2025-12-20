<?php
require_once('../../config/db.php');

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>Invalid product ID.</p>";
    exit;
}

$id = (int)$_GET['id'];

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<p style='color:red;'>Product not found.</p>";
    exit;
}

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch units
$units = $pdo->query("SELECT id, name FROM units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Close button -->
<button class="close-btn" onclick="hidePanel()">✖</button>

<h2>✏️ Edit Product</h2>

<form method="POST" action="../../controllers/productController.php">

    <!-- REQUIRED -->
    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

    <!-- PRODUCT INFO -->
    <label>Product Name</label>
    <input type="text"
           name="name"
           value="<?= htmlspecialchars($product['name']) ?>"
           required>

    <label>SKU</label>
    <input type="text"
           name="sku"
           value="<?= htmlspecialchars($product['sku']) ?>"
           required>

    <label>Barcode</label>
    <input type="text"
           name="barcode"
           value="<?= htmlspecialchars($product['barcode']) ?>">

    <!-- PRICING -->
    <label>Selling Price (UGX)</label>
    <input type="number"
           name="price"
           value="<?= number_format($product['price'], 2, '.', '') ?>"
           step="0.01"
           min="0.01"
           required>

    <label>Cost Price (UGX)</label>
    <input type="number"
           name="cost_price"
           value="<?= number_format($product['avg_cost'], 2, '.', '') ?>"
           step="0.01"
           min="0.01"
           required>

    <!-- TAX -->
    <label>Tax Rate (%)</label>
    <input type="number"
           name="tax_rate"
           value="<?= number_format($product['tax_rate'], 2, '.', '') ?>"
           step="0.01"
           min="0">

    <!-- CATEGORY -->
    <label>Category</label>
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
                <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- UNIT -->
    <label>Unit</label>
    <select name="unit_id">
        <option value="">Select Unit</option>
        <?php foreach ($units as $unit): ?>
            <option value="<?= $unit['id'] ?>"
                <?= $unit['id'] == $product['unit_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($unit['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- STOCK ALERT -->
    <label>Stock Alert Threshold</label>
    <input type="number"
           name="stock_alert_threshold"
           value="<?= (int)$product['stock_alert_threshold'] ?>"
           min="0">

    <!-- ACTION -->
    <button type="submit" name="update_product">
        ✅ Update Product
    </button>

</form>
