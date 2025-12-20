<?php
/**
 * EXPENSES CONTROLLER
 * -------------------
 * Handles all expense & expense category operations
 * Admin-only module
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// ===============================
// 🔐 SECURITY
// ===============================
require_permission('expenses_manage');

$userId = $_SESSION['user_id'];


$action = $_REQUEST['action'] ?? '';

// ===============================
// 📌 LIST EXPENSES
// ===============================
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $from = $_GET['from'] ?? null;
    $to   = $_GET['to']   ?? null;
    $q    = trim($_GET['q'] ?? '');

    $where = [];
    $args  = [];

    if ($from) {
        $where[] = "e.expense_date >= ?";
        $args[]  = $from;
    }

    if ($to) {
        $where[] = "e.expense_date <= ?";
        $args[]  = $to;
    }

    if ($q !== '') {
        $where[] = "(c.name LIKE ? OR e.description LIKE ? OR e.reference LIKE ?)";
        $args[]  = "%$q%";
        $args[]  = "%$q%";
        $args[]  = "%$q%";
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT 
            e.id,
            e.expense_date,
            e.amount,
            e.payment_method,
            e.reference,
            e.description,
            e.created_at,
            c.name AS category,
            c.type AS category_type,
            u.name AS user
        FROM expenses e
        JOIN expense_categories c ON e.category_id = c.id
        JOIN users u ON e.user_id = u.id
        $whereSql
        ORDER BY e.expense_date DESC, e.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);

    echo json_encode([
        'success' => true,
        'rows'    => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
}

// ===============================
// ➕ ADD EXPENSE
// ===============================
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $date        = $_POST['expense_date'] ?? null;
    $category_id = (int)($_POST['category_id'] ?? 0);
    $amount      = (float)($_POST['amount'] ?? 0);
    $method      = $_POST['payment_method'] ?? 'cash';
    $reference   = trim($_POST['reference'] ?? '');
    $desc        = trim($_POST['description'] ?? '');

    if (!$date || !$category_id || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense data']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO expenses
        (expense_date, category_id, amount, payment_method, reference, description, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $date,
        $category_id,
        $amount,
        $method,
        $reference,
        $desc,
        $userId
    ]);

    echo json_encode(['success' => true, 'message' => 'Expense added']);
    exit;
}
// ===============================
// ✏️ UPDATE EXPENSE (FULL & SAFE)
// ===============================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id          = (int)($_POST['id'] ?? 0);
    $date        = $_POST['expense_date'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $amount      = (float)($_POST['amount'] ?? 0);
    $method      = $_POST['payment_method'] ?? 'cash';
    $reference   = trim($_POST['reference'] ?? '');
    $desc        = trim($_POST['description'] ?? '');

    /* -------------------------------
       VALIDATION
    -------------------------------- */
    if (
        !$id ||
        !$date ||
        !$category_id ||
        $amount <= 0 ||
        !in_array($method, ['cash', 'bank', 'mobile', 'card'], true)
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expense data'
        ]);
        exit;
    }

    /* -------------------------------
       ENSURE EXPENSE EXISTS
    -------------------------------- */
    $check = $pdo->prepare("SELECT id FROM expenses WHERE id = ?");
    $check->execute([$id]);

    if (!$check->fetchColumn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Expense not found'
        ]);
        exit;
    }

    /* -------------------------------
       UPDATE EXPENSE
    -------------------------------- */
    $stmt = $pdo->prepare("
        UPDATE expenses
        SET
            expense_date   = ?,
            category_id    = ?,
            amount         = ?,
            payment_method = ?,
            reference      = ?,
            description    = ?
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $date,
        $category_id,
        $amount,
        $method,
        $reference,
        $desc,
        $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Expense updated successfully'
    ]);
    exit;
}

// ===============================
// 🗑 DELETE EXPENSE
// ===============================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Expense deleted']);
    exit;
}

// ===============================
// 📂 LIST CATEGORIES
// ===============================
if ($action === 'categories' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $rows = $pdo->query("
        SELECT id, name, type
        FROM expense_categories
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rows' => $rows]);
    exit;
}

// ===============================
// ➕ ADD CATEGORY
// ===============================
if ($action === 'category_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'variable';

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Category name required']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO expense_categories (name, type)
        VALUES (?, ?)
    ");
    $stmt->execute([$name, $type]);

    echo json_encode(['success' => true, 'message' => 'Category added']);
    exit;
}

// ===============================
// 🗑 DELETE CATEGORY (SAFE)
// ===============================
if ($action === 'category_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    // Prevent deleting category in use
    $check = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE category_id = ?");
    $check->execute([$id]);

    if ($check->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category is in use'
        ]);
        exit;
    }

    $pdo->prepare("DELETE FROM expense_categories WHERE id = ?")->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Category deleted']);
    exit;
}
// ===============================
// 📄 GET SINGLE EXPENSE (FOR EDIT)
// ===============================
if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expense ID'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.expense_date,
            e.category_id,
            e.amount,
            e.payment_method,
            e.reference,
            e.description
        FROM expenses e
        WHERE e.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);

    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        echo json_encode([
            'success' => false,
            'message' => 'Expense not found'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);
    exit;
}

// ===============================
// ❌ FALLBACK
// ===============================
echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
