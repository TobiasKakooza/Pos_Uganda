<?php
session_start();
require '../config/db.php';

// -------------------------
// PRODUCT CRUD (Extended)
// -------------------------

// ADD PRODUCT
if (isset($_POST['add_product'])) {

    $name  = trim($_POST['name']);
    $sku   = trim($_POST['sku']);
    $barcode = trim($_POST['barcode']);

    $buyingPrice  = floatval($_POST['cost_price']);   // COST
    $sellingPrice = floatval($_POST['price']);        // SELLING

    $tax_rate  = floatval($_POST['tax_rate'] ?? 0);
    $category_id = intval($_POST['category_id']);
    $unit_id = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $stock_alert_threshold = intval($_POST['stock_alert_threshold'] ?? 2);
    $initialStock = intval($_POST['initial_stock'] ?? 0);

    if ($name && $sku && $sellingPrice > 0 && $buyingPrice > 0 && $category_id) {

        try {
            $pdo->beginTransaction();

            // 1️⃣ Insert product
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (
                    name, sku, barcode,
                    price, avg_cost, last_cost,
                    tax_rate, category_id, unit_id, stock_alert_threshold
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $sku,
                $barcode,
                $sellingPrice,
                $buyingPrice,
                $buyingPrice,
                $tax_rate,
                $category_id,
                $unit_id,
                $stock_alert_threshold
            ]);

            $productId = $pdo->lastInsertId();

            // 2️⃣ Insert initial stock into inventories
            if ($initialStock > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO inventories
                    (product_id, quantity, type, note)
                    VALUES (?, ?, 'in', ?)
                ");
                $stmt->execute([
                    $productId,
                    $initialStock,
                    'Initial stock'
                ]);
            }

            $pdo->commit();
            $_SESSION['success'] = " Product added with initial stock.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "❌ Failed to add product: " . $e->getMessage();
        }

    } else {
        $_SESSION['error'] = "❌ Please fill all required fields correctly.";
    }

    header('Location: ../views/products/list.php');
    exit;
}







// UPDATE PRODUCT
if (isset($_POST['update_product'])) {

    $id   = intval($_POST['id']);
    $name = trim($_POST['name']);
    $sku  = trim($_POST['sku']);
    $barcode = trim($_POST['barcode']);

    $sellingPrice = floatval($_POST['price']);
    $costPrice    = floatval($_POST['cost_price']); // ✅ NEW

    $tax_rate  = floatval($_POST['tax_rate'] ?? 0);
    $category_id = intval($_POST['category_id']);
    $unit_id = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $stock_alert_threshold = intval($_POST['stock_alert_threshold'] ?? 2);

    if ($id && $name && $sku && $sellingPrice > 0 && $costPrice > 0 && $category_id) {

        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET 
                    name = ?,
                    sku = ?,
                    barcode = ?,
                    price = ?,
                    avg_cost = ?,       -- ✅ update cost
                    last_cost = ?,      -- ✅ update cost
                    tax_rate = ?,
                    category_id = ?,
                    unit_id = ?,
                    stock_alert_threshold = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $sku,
                $barcode,
                $sellingPrice,
                $costPrice,
                $costPrice,
                $tax_rate,
                $category_id,
                $unit_id,
                $stock_alert_threshold,
                $id
            ]);

            $_SESSION['success'] = " Product updated successfully.";

        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Failed to update product: " . $e->getMessage();
        }

    } else {
        $_SESSION['error'] = "❌ Invalid input for update.";
    }

    header('Location: ../views/products/list.php');
    exit;
}



// DELETE PRODUCT
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "🗑️ Product deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Failed to delete product: " . $e->getMessage();
    }

    header('Location: ../views/products/list.php');
    exit;
}

// --------------------------
// CATEGORY AJAX CONTROLLER
// --------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // 1. Add Category
    if ($action === 'addCategory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $name = trim($_POST['name']);
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Category name is required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // 2. Update Category
    if ($action === 'updateCategory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $desc = trim($_POST['description'] ?? '');

        if ($id <= 0 || $name === '') {
            echo json_encode(['success' => false, 'error' => 'Missing ID or name']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // 3. Delete Category
    if ($action === 'deleteCategory' && isset($_GET['id'])) {
        header('Content-Type: application/json');

        $id = intval($_GET['id']);

        // Check if any product is using this category
        $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'Category in use by products.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // 4. Get All Categories
    if ($action === 'getCategories') {
        header('Content-Type: application/json');
        $categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($categories);
        exit;
    }
}
