<?php
require_once('../../config/db.php');

// Fetch categories and units
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$units      = $pdo->query("SELECT * FROM units ORDER BY name ASC")->fetchAll();
?>

<!-- Close button -->
<button class="close-btn" onclick="hidePanel()">
    <i data-lucide="x"></i>
</button>

<h2>
    <i data-lucide="package-plus"></i>
    Add Product
</h2>

<form method="POST" action="../../controllers/productController.php">

    <!-- Product Name -->
    <div class="form-group">
        <label>
            <i data-lucide="tag"></i> Product Name
        </label>
        <input type="text" name="name" required>
    </div>

    <!-- SKU -->
    <div class="form-group">
        <label>
            <i data-lucide="barcode"></i> SKU
        </label>
        <input type="text" name="sku" required>
    </div>

    <!-- Barcode -->
    <div class="form-group">
        <label>
            <i data-lucide="qr-code"></i> Barcode
        </label>
        <input type="text" name="barcode">
    </div>

    <!-- Buying Price -->
    <div class="form-group">
        <label>
            <i data-lucide="shopping-cart"></i> Buying Price (UGX)
        </label>
        <input
            type="number"
            name="cost_price"
            step="0.01"
            min="0"
            required
        >
    </div>

    <!-- Selling Price -->
    <div class="form-group">
        <label>
            <i data-lucide="banknote"></i> Selling Price (UGX)
        </label>
        <input
            type="number"
            name="price"
            step="0.01"
            min="0"
            required
        >
    </div>

    <!-- Tax Rate -->
    <div class="form-group">
        <label>
            <i data-lucide="percent"></i> Tax Rate (%)
        </label>
        <input
            type="number"
            name="tax_rate"
            step="0.01"
            min="0"
        >
    </div>

    <!-- Stock Alert -->
    <div class="form-group">
        <label>
            <i data-lucide="alert-triangle"></i> Stock Alert Threshold
        </label>
        <input
            type="number"
            name="stock_alert_threshold"
            min="0"
            value="2"
            required
        >
    </div>

    <!-- Initial Stock -->
    <div class="form-group">
        <label>
            <i data-lucide="warehouse"></i> Initial Stock Quantity
        </label>
        <input
            type="number"
            name="initial_stock"
            min="0"
            required
        >
    </div>

    <!-- Unit -->
    <div class="form-group">
        <label>
            <i data-lucide="ruler"></i> Unit
        </label>
        <select name="unit_id" required>
            <option value="">Select Unit</option>
            <?php foreach ($units as $unit): ?>
                <option value="<?= $unit['id'] ?>">
                    <?= htmlspecialchars($unit['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Category -->
    <div class="form-group">
        <label>
            <i data-lucide="folder"></i> Category
        </label>
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Submit -->
    <button type="submit" name="add_product" class="btn-primary">
        <i data-lucide="save"></i>
        Save Product
    </button>

</form>

<script>
  // Make sure Lucide renders the icons
  if (window.lucide) {
      lucide.createIcons();
  }
</script>
