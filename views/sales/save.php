<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$userId = $_SESSION['user']['id'] ?? null;

$data = json_decode($_POST['payload'] ?? '', true);
if (!$data || empty($data['items'])) {
    http_response_code(400);
    exit('Invalid sale data');
}

try {
    $pdo->beginTransaction();

    /* ===============================
       1. CREATE SALE
    =============================== */
    $stmt = $pdo->prepare("
        INSERT INTO sales (
            subtotal, discount_type, discount_value,
            discount_amount, tax_rate, tax_amount,
            paid_amount, change_amount, comment,
            total_amount, payment_type, status, user_id
        ) VALUES (
            :subtotal, :discount_type, :discount_value,
            :discount_amount, :tax_rate, :tax_amount,
            :paid_amount, :change_amount, :comment,
            :total_amount, :payment_type, 'completed', :user_id
        )
    ");

    $stmt->execute([
        ':subtotal'        => $data['subtotal'] ?? 0,
        ':discount_type'   => $data['discount_type'] ?? null,
        ':discount_value'  => $data['discount_value'] ?? 0,
        ':discount_amount' => $data['discount_amount'] ?? 0,
        ':tax_rate'        => $data['tax_rate'] ?? 0,
        ':tax_amount'      => $data['tax_amount'] ?? 0,
        ':paid_amount'     => $data['paid_amount'] ?? 0,
        ':change_amount'   => $data['change_amount'] ?? 0,
        ':comment'         => $data['comment'] ?? '',
        ':total_amount'    => $data['total_amount'],
        ':payment_type'    => $data['payment_type'] ?? 'Cash',
        ':user_id'         => $userId
    ]);

    $saleId = $pdo->lastInsertId();

    /* ===============================
       2. SALE ITEMS + INVENTORY
    =============================== */
    $itemStmt = $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");

    $invStmt = $pdo->prepare("
        INSERT INTO inventories (
            product_id, quantity, stock_after, type, note,
            source_type, source_id
        )
        VALUES (?, ?, ?, 'out', ?, 'sale', ?)
    ");

    foreach ($data['items'] as $item) {
        $itemStmt->execute([
            $saleId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price']
        ]);

        // Get current stock
        $stockStmt = $pdo->prepare("
            SELECT COALESCE(SUM(
                CASE WHEN type='in' THEN quantity ELSE -quantity END
            ),0) FROM inventories WHERE product_id = ?
        ");
        $stockStmt->execute([$item['product_id']]);
        $stockAfter = $stockStmt->fetchColumn() - $item['quantity'];

        $invStmt->execute([
            $item['product_id'],
            $item['quantity'],
            $stockAfter,
            "Sale ID $saleId",
            $saleId
        ]);
    }

    /* ===============================
       3. ADMIN NOTIFICATION
    =============================== */
    $userName = $_SESSION['user']['name'] ?? 'Cashier';

    $pdo->prepare("
        INSERT INTO notifications (user_id, message, type, target_role, link)
        VALUES (?, ?, 'sale', 'admin', ?)
    ")->execute([
        $userId,
        "Sale #$saleId completed by $userName (UGX " . number_format($data['total_amount']) . ")",
        "/POS_UG/views/sales/invoice.php?id=$saleId"
    ]);

    $pdo->commit();

    echo json_encode(['success' => true, 'sale_id' => $saleId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
