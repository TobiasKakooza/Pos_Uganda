<?php
require_once('../../config/db.php');

// Fetch categories and units
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$units = $pdo->query("SELECT * FROM units")->fetchAll();
?>

<!-- Close button to hide the side panel -->
<button class="close-btn" onclick="hidePanel()">✖</button>

<h2>➕ Add Product</h2>

<form method="POST" action="../../controllers/productController.php">
    <input type="text" name="name" placeholder="Product Name" required>
    <input type="text" name="sku" placeholder="SKU" required>
    <input type="text" name="barcode" placeholder="Barcode">

    <input type="number" name="price" placeholder="Price (UGX)" step="0.01" required>
    <input type="number" name="tax_rate" placeholder="Tax Rate (%)" step="0.01">

    <!-- Stock Alert Threshold -->
    <input type="number" name="stock_alert_threshold" placeholder="Stock Alert Threshold" min="0" value="2" required>

    <!-- Initial Quantity (for inventory) -->
    <input type="number" name="initial_stock" placeholder="Initial Stock Quantity" min="0" required>

    <!-- Unit selection -->
    <select name="unit_id" required>
        <option value="">Select Unit</option>
        <?php foreach ($units as $unit): ?>
            <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Category selection -->
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="add_product">💾 Save Product</button>
</form>
