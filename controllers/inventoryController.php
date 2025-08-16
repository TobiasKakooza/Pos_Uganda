<?php
session_start();
require '../config/db.php';

// ================================
// Handle New Inventory Submission
// ================================
if (isset($_POST['inventory_submit'])) {
    $product_id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
    $type = isset($_POST['type']) ? trim($_POST['type']) : null;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';

    if ($product_id && $quantity > 0 && in_array($type, ['in', 'out'])) {
        try {
            // Get current stock
            $stockStmt = $pdo->prepare("
                SELECT IFNULL(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) AS current_stock 
                FROM inventories 
                WHERE product_id = ?
            ");
            $stockStmt->execute([$product_id]);
            $currentStock = (int) $stockStmt->fetchColumn();

            // Prevent invalid stock deduction
            if ($type === 'out' && $quantity > $currentStock) {
                $_SESSION['error'] = '❌ Cannot reduce more than available stock. Current stock: ' . $currentStock;
                header('Location: ../views/inventory/manage.php');
                exit;
            }

            // Calculate new stock
            $newStock = ($type === 'in') ? $currentStock + $quantity : $currentStock - $quantity;

            // Insert into inventories table
            $stmt = $pdo->prepare("
                INSERT INTO inventories (product_id, quantity, stock_after, type, note) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$product_id, $quantity, $newStock, $type, $note]);

            // 🔔 Log low stock notification if new stock is below threshold
            if ($newStock < 5) {
                $message = '⚠️ Low stock alert: Product ID ' . $product_id . ' has only ' . $newStock . ' items left.';
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE product_id = ? AND is_read = 0");
                $checkStmt->execute([$product_id]);

                if ($checkStmt->fetchColumn() == 0) {
                    $noteStmt = $pdo->prepare("INSERT INTO notifications (product_id, message) VALUES (?, ?)");
                    $noteStmt->execute([$product_id, $message]);
                }
            }

            $_SESSION['success'] = '✅ Inventory updated successfully. New stock: ' . $newStock;

        } catch (PDOException $e) {
            $_SESSION['error'] = '❌ Error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = '❌ Invalid input. Please check all fields.';
    }

    header('Location: ../views/inventory/manage.php');
    exit;
}

// ========================
// AJAX: Edit Inventory Row
// ========================
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $quantity = (int)$_POST['quantity'];
    $type = $_POST['type'];
    $note = trim($_POST['note']);

    try {
        $stmt = $pdo->prepare("UPDATE inventories SET quantity = ?, type = ?, note = ? WHERE id = ?");
        $stmt->execute([$quantity, $type, $note, $id]);

        echo json_encode(['success' => true, 'message' => '✅ Inventory entry updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
    }
    exit;
}

// ===========================
// AJAX: Delete Inventory Row
// ===========================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM inventories WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => '✅ Inventory entry deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
    }
    exit;
}
