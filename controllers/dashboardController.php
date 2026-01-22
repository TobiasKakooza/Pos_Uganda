<?php
// controllers/dashboardController.php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

$userId = $_SESSION['user_id'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;

if (!$userId || !$roleId) {
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

$action = $_GET['action'] ?? 'kpis';

/* ================= HELPERS ================= */

function daterange($daysBack = 13) {
  return [
    date('Y-m-d', strtotime("-{$daysBack} days")) . ' 00:00:00',
    date('Y-m-d') . ' 23:59:59'
  ];
}
function mtd() {
  return [
    date('Y-m-01') . ' 00:00:00',
    date('Y-m-d') . ' 23:59:59'
  ];
}
function today() {
  return [
    date('Y-m-d') . ' 00:00:00',
    date('Y-m-d') . ' 23:59:59'
  ];
}

/* ================= ROLE SCOPING ================= */

$salesWhere = '';
$params = [];

if ($roleId == 2) { // CASHIER
  $salesWhere = ' AND user_id = :uid';
  $params[':uid'] = $userId;
}

/* ================= CONTROLLER ================= */

try {

switch ($action) {

  /* ---------- KPIs ---------- */
  case 'kpis': {

    if ($roleId == 3) { // INVENTORY MANAGER
      echo json_encode([
        'success'=>true,
        'today_total'=>0,
        'today_orders'=>0,
        'mtd_total'=>0,
        'mtd_orders'=>0,
        'products'=>(int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'customers'=>0,
        'suppliers'=>(int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
        'low_stock'=>(int)$pdo->query("
          SELECT COUNT(*) FROM (
            SELECT p.id,
              COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='in'),0)
            - COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='out'),0) AS on_hand,
              p.stock_alert_threshold
            FROM products p
          ) t
          WHERE t.stock_alert_threshold IS NOT NULL AND t.on_hand <= t.stock_alert_threshold
        ")->fetchColumn(),
        'receivables'=>0,
        'payables'=>0
      ]);
      exit;
    }

    [$sToday,$eToday] = today();
    [$sMTD,$eMTD]     = mtd();

    $stmt = $pdo->prepare("
      SELECT COALESCE(SUM(total_amount),0) total, COUNT(*) orders
      FROM sales
      WHERE created_at BETWEEN :s AND :e $salesWhere
    ");

    $stmt->execute(array_merge([':s'=>$sToday,':e'=>$eToday], $params));
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt->execute(array_merge([':s'=>$sMTD,':e'=>$eMTD], $params));
    $mtd = $stmt->fetch(PDO::FETCH_ASSOC);

      // ===============================
      // OPERATING EXPENSES (MTD)
      // ===============================
      $expStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount),0)
        FROM expenses
        WHERE expense_date BETWEEN :s AND :e
      ");
      $expStmt->execute([
        ':s' => $sMTD,
        ':e' => $eMTD
      ]);
      $operatingExpenses = (float)$expStmt->fetchColumn();


   $grossProfit = (float)$mtd['total']; // temporary (no COGS yet)
$netProfit   = $grossProfit - $operatingExpenses;

echo json_encode([
  'success'=>true,

  'today_total' => (float)$today['total'],
  'today_orders'=> (int)$today['orders'],

  'mtd_total'   => (float)$mtd['total'],
  'mtd_orders'  => (int)$mtd['orders'],

  'operating_expenses' => $operatingExpenses,
  'gross_profit'       => $grossProfit,
  'net_profit'         => $netProfit,

  'products' => $roleId == 1 ? (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() : 0,
  'customers'=> $roleId == 1 ? (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() : 0,
  'suppliers'=> $roleId == 1 ? (int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn() : 0,
  'low_stock'=> (int)$pdo->query("
    SELECT COUNT(*) FROM (
      SELECT p.id,
        COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='in'),0)
      - COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='out'),0) AS on_hand,
        p.stock_alert_threshold
      FROM products p
    ) t
    WHERE t.stock_alert_threshold IS NOT NULL AND t.on_hand <= t.stock_alert_threshold
  ")->fetchColumn(),

  'receivables'=>0,
  'payables'=>0
]);
exit;

    break;
  }

  /* ---------- SALES TREND ---------- */
case 'sales_trend': {

  if ($roleId != 1) {
    echo json_encode(['success'=>true,'rows'=>[]]);
    exit;
  }

  [$s,$e] = daterange(13);

  // Fetch actual sales
  $stmt = $pdo->prepare("
    SELECT DATE(created_at) d, SUM(total_amount) total
    FROM sales
    WHERE created_at BETWEEN :s AND :e
    GROUP BY DATE(created_at)
  ");
  $stmt->execute([':s'=>$s,':e'=>$e]);
  $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // d => total

  // Build full 14-day range
  $rows = [];
  for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $rows[] = [
      'd'     => $date,
      'total' => (float)($data[$date] ?? 0)
    ];
  }

  echo json_encode(['success'=>true,'rows'=>$rows]);
  exit;
}


  /* ---------- SALES BY CATEGORY ---------- */
  case 'sales_by_category': {

    if ($roleId != 1) {
      echo json_encode(['success'=>true,'rows'=>[]]);
      exit;
    }

    [$s,$e] = mtd();
    $stmt = $pdo->prepare("
      SELECT c.name, SUM(si.quantity * si.unit_price) revenue
      FROM sale_items si
      JOIN products p ON p.id=si.product_id
      JOIN categories c ON c.id=p.category_id
      JOIN sales s ON s.id=si.sale_id
      WHERE s.created_at BETWEEN :s AND :e
      GROUP BY c.id
      ORDER BY revenue DESC
    ");
    $stmt->execute([':s'=>$s,':e'=>$e]);
    echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;
  }

  /* ---------- TOP PRODUCTS ---------- */
  case 'top_products': {

    if ($roleId != 1) {
      echo json_encode(['success'=>true,'rows'=>[]]);
      exit;
    }

    [$s,$e] = mtd();
    $stmt = $pdo->prepare("
      SELECT p.name, SUM(si.quantity) qty, SUM(si.quantity * si.unit_price) revenue
      FROM sale_items si
      JOIN products p ON p.id=si.product_id
      JOIN sales s ON s.id=si.sale_id
      WHERE s.created_at BETWEEN :s AND :e
      GROUP BY p.id
      ORDER BY revenue DESC
      LIMIT 10
    ");
    $stmt->execute([':s'=>$s,':e'=>$e]);
    echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;
  }

/* ---------- SALES VS EXPENSES ---------- */
  case 'sales_vs_expenses': {

  if ($roleId != 1) {
    echo json_encode(['success'=>true,'rows'=>[]]);
    exit;
  }

  [$s,$e] = mtd();

  // Sales per day
  $sales = $pdo->prepare("
    SELECT DATE(created_at) d, SUM(total_amount) total
    FROM sales
    WHERE created_at BETWEEN :s AND :e
    GROUP BY DATE(created_at)
  ");
  $sales->execute([':s'=>$s,':e'=>$e]);
  $salesData = $sales->fetchAll(PDO::FETCH_KEY_PAIR);

  // Expenses per day
  $exp = $pdo->prepare("
    SELECT expense_date d, SUM(amount) total
    FROM expenses
    WHERE expense_date BETWEEN :s AND :e
    GROUP BY expense_date
  ");
  $exp->execute([':s'=>$s,':e'=>$e]);
  $expData = $exp->fetchAll(PDO::FETCH_KEY_PAIR);

  // 🔥 BUILD FULL MTD RANGE
  $days = [];
  $start = new DateTime(date('Y-m-01'));
  $end   = new DateTime();

  while ($start <= $end) {
    $d = $start->format('Y-m-d');
    $salesVal = (float)($salesData[$d] ?? 0);
    $expVal   = (float)($expData[$d] ?? 0);

    $days[] = [
      'date'     => $d,
      'sales'    => $salesVal,
      'expenses' => $expVal,
      'profit'   => $salesVal - $expVal
    ];

    $start->modify('+1 day');
  }

  echo json_encode(['success'=>true,'rows'=>$days]);
  exit;
}

/* ---------- PAYMENT METHODS ---------- */
case 'payment_methods': {

  if ($roleId != 1) {
    echo json_encode(['success'=>true,'rows'=>[]]);
    exit;
  }

  [$s,$e] = mtd();

  $stmt = $pdo->prepare("
    SELECT payment_type, SUM(total_amount) total
    FROM sales
    WHERE created_at BETWEEN :s AND :e
    GROUP BY payment_type
  ");
  $stmt->execute([':s'=>$s,':e'=>$e]);

  echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
  exit;
}

/* ---------- PRODUCT MARGIN ---------- */
case 'product_margin': {

  if ($roleId != 1) {
    echo json_encode(['success'=>true,'rows'=>[]]);
    exit;
  }

  [$s,$e] = mtd();

  $stmt = $pdo->prepare("
    SELECT p.name,
      SUM(si.quantity * si.unit_price) revenue,
      SUM(si.cogs_total) cogs,
      SUM(si.quantity * si.unit_price) - SUM(si.cogs_total) profit
    FROM sale_items si
    JOIN products p ON p.id=si.product_id
    JOIN sales s ON s.id=si.sale_id
    WHERE s.created_at BETWEEN :s AND :e
    GROUP BY p.id
    ORDER BY profit DESC
    LIMIT 10
  ");
  $stmt->execute([':s'=>$s,':e'=>$e]);

  echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
  exit;
}

  /* ---------- LOW STOCK ---------- */
  case 'low_stock': {

    $rows = $pdo->query("
      SELECT p.sku, p.name,
        COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='in'),0)
      - COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='out'),0) AS on_hand,
        p.stock_alert_threshold
      FROM products p
      WHERE p.stock_alert_threshold IS NOT NULL
      ORDER BY on_hand ASC
      LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'rows'=>$rows]);
    break;
  }

  /* ---------- RECENT SALES ---------- */
  case 'recent_sales': {

    $sql = "
      SELECT id, total_amount, payment_type, created_at
      FROM sales
      WHERE 1=1 $salesWhere
      ORDER BY created_at DESC
      LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;
  }

  default:
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}

} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
