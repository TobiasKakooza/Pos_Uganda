<?php
require_once('../config/db.php');
require_once('../includes/auth.php'); // for $_SESSION user

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

/* =========================================================
   🔎 Product Search (GET)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'search') {
    $query = $_GET['query'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR sku = ? OR barcode = ? LIMIT 1");
    $stmt->execute(["%$query%", $query, $query]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // latest stock_after (fast, audit-based)
        $stockStmt = $pdo->prepare("
            SELECT stock_after
            FROM inventories
            WHERE product_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stockStmt->execute([$product['id']]);
        $stock = (int)$stockStmt->fetchColumn();
        $product['stock'] = max(0, $stock);

        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    exit;
}

/* =========================================================
   💾 Save Sale (POST)
   Expects JSON:
   {
     items:[{product_id,quantity,unit_price}],
     payment_type, customer_id?,
     discount_type?, discount_value?, tax_rate?, paid_amount?, comment?
   }
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

    $items         = $data['items'] ?? [];
    $paymentType   = $data['payment_type'] ?? 'Cash';
    $customerId    = $data['customer_id'] ?? null;

    $discountType  = $data['discount_type']  ?? null; // 'percent' | 'amount' | null
    $discountValue = (float)($data['discount_value'] ?? 0);
    $taxRate       = (float)($data['tax_rate'] ?? 0); // %
    $paidAmountIn  = isset($data['paid_amount']) ? (float)$data['paid_amount'] : null;
    $comment       = trim((string)($data['comment'] ?? ''));

    $userId   = $_SESSION['user_id'] ?? 2;
    $alerts   = [];

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items to process']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1) Create sale shell
        $stmt = $pdo->prepare("
            INSERT INTO sales (total_amount, payment_type, user_id, customer_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([0, $paymentType, $userId, $customerId]);
        $saleId = (int)$pdo->lastInsertId();

        // 2) Items + inventory
        $subtotal = 0.0;

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $qty       = max(1, (int)$item['quantity']);
            $price     = (float)$item['unit_price'];
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;

            // current stock
            $stockCheck = $pdo->prepare("
                SELECT COALESCE(SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END),0)
                FROM inventories WHERE product_id = ?
            ");
            $stockCheck->execute([$productId]);
            $stock = (int)$stockCheck->fetchColumn();

            if ($qty > $stock) {
                throw new Exception("Insufficient stock for product ID $productId");
            }

            // sale_items
            $stmt = $pdo->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$saleId, $productId, $qty, $price]);

            // inventory OUT with stock_after
            $newStock = $stock - $qty;
            $invStmt = $pdo->prepare("
                INSERT INTO inventories (product_id, quantity, type, note, stock_after)
                VALUES (?, ?, 'out', ?, ?)
            ");
            $note = "Sale ID $saleId";
            $invStmt->execute([$productId, $qty, $note, $newStock]);

            // Low stock alert
            $remainingStockStmt = $pdo->prepare("
                SELECT COALESCE(SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END),0)
                FROM inventories WHERE product_id = ?
            ");
            $remainingStockStmt->execute([$productId]);
            $remainingStock = (int)$remainingStockStmt->fetchColumn();

            $productInfo = $pdo->prepare("SELECT name, stock_alert_threshold FROM products WHERE id = ?");
            $productInfo->execute([$productId]);
            $info = $productInfo->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Product', 'stock_alert_threshold' => 2];
            $threshold = ($info['stock_alert_threshold'] !== null) ? (int)$info['stock_alert_threshold'] : 2;

            if ($remainingStock <= $threshold) {
                $alerts[] = "{$info['name']} stock is low ({$remainingStock} left)";
            }
        }

        // 3) Discount
        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subtotal * max(0.0, min(100.0, $discountValue)) / 100.0;
        } elseif ($discountType === 'amount') {
            $discountAmount = min($subtotal, max(0.0, $discountValue));
        }
        $baseForTax = max(0.0, $subtotal - $discountAmount);

        // 4) Tax + totals
        $taxAmount = $baseForTax * max(0.0, $taxRate) / 100.0;
        $total     = $baseForTax + $taxAmount;

        // 5) Paid / change
        $paidAmount   = ($paidAmountIn !== null) ? $paidAmountIn : $total;
        $changeAmount = max(0.0, $paidAmount - $total);

        // 6) Update sale
        $update = $pdo->prepare("
            UPDATE sales
            SET subtotal        = ?,
                discount_type   = ?,
                discount_value  = ?,
                discount_amount = ?,
                tax_rate        = ?,
                tax_amount      = ?,
                paid_amount     = ?,
                change_amount   = ?,
                comment         = ?,
                total_amount    = ?
            WHERE id = ?
        ");
        $update->execute([
            $subtotal,
            $discountType,
            $discountValue,
            $discountAmount,
            $taxRate,
            $taxAmount,
            $paidAmount,
            $changeAmount,
            $comment,
            $total,
            $saleId
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'sale_id' => $saleId,
            'alerts'  => $alerts
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* =========================================================
   📜 Recent sales (GET)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
    $where = [];
    $args  = [];

    // Filters
    if (!empty($_GET['from'])) { $where[] = "DATE(s.created_at) >= ?"; $args[] = $_GET['from']; }
    if (!empty($_GET['to']))   { $where[] = "DATE(s.created_at) <= ?"; $args[] = $_GET['to']; }
    if (!empty($_GET['payment'])) {
        // case-insensitive match; allows “cash”, “Cash”, etc.
        $where[] = "LOWER(s.payment_type) LIKE ?";
        $args[]  = strtolower($_GET['payment']) . '%';
    }
    if (!empty($_GET['q'])) {
        // search id, comment, or user name
        $where[] = "(s.id = ? OR s.comment LIKE ? OR u.name LIKE ?)";
        $qLike = '%' . $_GET['q'] . '%';
        $args[] = (int)$_GET['q'];
        $args[] = $qLike;
        $args[] = $qLike;
    }

    // Sorting (whitelist)
    $sortable = ['id','created_at','subtotal','discount_amount','tax_amount','total_amount','paid_amount','change_amount'];
    $sort = in_array($_GET['sort'] ?? 'created_at', $sortable, true) ? $_GET['sort'] : 'created_at';
    $dir  = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    // Pagination
    $limit  = max(1, min((int)($_GET['limit'] ?? 25), 200));  // cap
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count for pager
    $countSql = "SELECT COUNT(*) FROM sales s JOIN users u ON s.user_id = u.id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($args);
    $total = (int)$countStmt->fetchColumn();

    // Data page
    $sql = "
      SELECT s.*, u.name AS user
      FROM sales s
      JOIN users u ON s.user_id = u.id
      $whereSql
      ORDER BY s.$sort $dir
      LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'total' => $total, 'sales' => $rows]);
    exit;
}



/* =========================================================
   📝 Drafts
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'draft_save') {
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $payload = json_encode($data['payload'] ?? []);
    $userId  = $_SESSION['user_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO draft_sales (user_id, payload) VALUES (?, ?)");
    $stmt->execute([$userId, $payload]);

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'draft_list') {
    $rows = $pdo->query("SELECT id, created_at FROM draft_sales WHERE status='open' ORDER BY created_at DESC LIMIT 50")
                ->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'drafts' => $rows]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'draft_get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT payload FROM draft_sales WHERE id=? AND status='open'");
    $stmt->execute([$id]);
    $payload = $stmt->fetchColumn();
    echo json_encode(['success' => (bool)$payload, 'payload' => $payload ? json_decode($payload, true) : null]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'draft_close') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE draft_sales SET status='closed' WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

/* =========================================================
   💵 Cash In / Out
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cash_move') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: [];
    $type   = ($data['type'] ?? 'in') === 'out' ? 'out' : 'in';
    $amount = (float)($data['amount'] ?? 0);
    $note   = $data['note'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if ($amount <= 0) {
        echo json_encode(['success'=>false, 'message'=>'Amount must be > 0']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO cash_movements (user_id, type, amount, note) VALUES (?,?,?,?)");
    $stmt->execute([$userId, $type, $amount, $note]);

    echo json_encode(['success'=>true]);
    exit;
}

/* =========================================================
   🧾 Credit payment
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'credit_pay') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    $customerId = (int)($data['customer_id'] ?? 0);
    $amount     = (float)($data['amount'] ?? 0);
    $method     = $data['method'] ?? 'cash';
    $note       = $data['note'] ?? null;
    $saleId     = isset($data['sale_id']) ? (int)$data['sale_id'] : null;
    $userId     = $_SESSION['user_id'] ?? null;

    if ($customerId <= 0 || $amount <= 0) {
        echo json_encode(['success'=>false, 'message'=>'Invalid input']); exit;
    }

    $stmt = $pdo->prepare("
      INSERT INTO credit_payments (customer_id, sale_id, amount, method, note, user_id)
      VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([$customerId, $saleId, $amount, $method, $note, $userId]);

    $pdo->prepare("UPDATE customers
                   SET outstanding_balance = GREATEST(outstanding_balance - ?, 0)
                   WHERE id = ?")->execute([$amount, $customerId]);

    echo json_encode(['success'=>true]);
    exit;
}

/* Fallback */
echo json_encode(['success'=>false, 'message'=>'Unknown route']);


// GET /controllers/salesController.php?action=draft_list&limit=20&offset=0&q=
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'draft_list') {
    $limit  = max(1, min((int)($_GET['limit'] ?? 10), 100));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $q      = trim($_GET['q'] ?? '');

    $where  = ["status = 'open'"];
    $args   = [];

    // allow simple search by id or date substring
    if ($q !== '') {
        if (ctype_digit($q)) {
            $where[] = "id = ?";
            $args[]  = (int)$q;
        } else {
            // match created_at like '2025-08-08' or partial
            $where[] = "DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') LIKE ?";
            $args[]  = "%{$q}%";
        }
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // total for pager
    $count = $pdo->prepare("SELECT COUNT(*) FROM draft_sales $whereSql");
    $count->execute($args);
    $total = (int)$count->fetchColumn();

    // page
    $sql = "SELECT id, created_at
            FROM draft_sales
            $whereSql
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'total' => $total, 'drafts' => $rows]);
    exit;
}
