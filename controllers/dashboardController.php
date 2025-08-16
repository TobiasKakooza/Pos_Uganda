<?php
// controllers/dashboardController.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'kpis';

// Helpers
function daterange($daysBack = 13) {
  $from = date('Y-m-d', strtotime("-{$daysBack} days")) . ' 00:00:00';
  $to   = date('Y-m-d') . ' 23:59:59';
  return [$from, $to];
}
function mtd() {
  $from = date('Y-m-01') . ' 00:00:00';
  $to   = date('Y-m-d') . ' 23:59:59';
  return [$from, $to];
}
function today() {
  $from = date('Y-m-d') . ' 00:00:00';
  $to   = date('Y-m-d') . ' 23:59:59';
  return [$from, $to];
}

try {
  switch ($action) {
    /* ---------------- KPIs (cards) ---------------- */
    case 'kpis': {
      // Today
      [$sToday, $eToday] = today();
      $sqlT = "SELECT COALESCE(SUM(total_amount),0) total, COUNT(*) orders
               FROM sales WHERE created_at BETWEEN :s AND :e";
      $st = $pdo->prepare($sqlT);
      $st->execute([':s'=>$sToday, ':e'=>$eToday]);
      $today = $st->fetch(PDO::FETCH_ASSOC);

      // MTD
      [$sMTD, $eMTD] = mtd();
      $st = $pdo->prepare($sqlT);
      $st->execute([':s'=>$sMTD, ':e'=>$eMTD]);
      $mtd = $st->fetch(PDO::FETCH_ASSOC);

      // Counts
      $products  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
      $customers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
      $suppliers = (int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

      // Low-stock alerts count
      $rows = $pdo->query("
        SELECT COUNT(*) FROM (
          SELECT p.id,
            COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='in'),0)
          - COALESCE((SELECT SUM(quantity) FROM inventories i WHERE i.product_id=p.id AND i.type='out'),0) AS on_hand,
            p.stock_alert_threshold
          FROM products p
        ) t
        WHERE t.stock_alert_threshold IS NOT NULL AND t.on_hand <= t.stock_alert_threshold
      ")->fetchColumn();
      $lowStock = (int)$rows;

      // Receivables (customers) & Payables (AP open/partially paid)
      $receivables = (float)$pdo->query("SELECT COALESCE(SUM(outstanding_balance),0) FROM customers")->fetchColumn();
      $payables = 0.0;
      if ($pdo->query("SHOW TABLES LIKE 'ap_bills'")->fetchColumn()) {
        $payables = (float)$pdo->query("
          SELECT COALESCE(SUM(balance),0) FROM ap_bills WHERE status IN ('open','partially_paid')
        ")->fetchColumn();
      }

      echo json_encode([
        'success'=>true,
        'today_total'=>(float)$today['total'],
        'today_orders'=>(int)$today['orders'],
        'mtd_total'=>(float)$mtd['total'],
        'mtd_orders'=>(int)$mtd['orders'],
        'products'=>$products,
        'customers'=>$customers,
        'suppliers'=>$suppliers,
        'low_stock'=>$lowStock,
        'receivables'=>$receivables,
        'payables'=>$payables
      ]);
      break;
    }

    /* ------------- Sales trend (last 14 days) ------------- */
    case 'sales_trend': {
      [$s,$e] = daterange(13);
      $stmt = $pdo->prepare("
        SELECT DATE(created_at) d, COALESCE(SUM(total_amount),0) total, COUNT(*) orders
        FROM sales
        WHERE created_at BETWEEN :s AND :e
        GROUP BY DATE(created_at)
        ORDER BY d
      ");
      $stmt->execute([':s'=>$s, ':e'=>$e]);
      echo json_encode(['success'=>true, 'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    /* ------------- Sales by category (MTD) ------------- */
    case 'sales_by_category': {
      [$s,$e] = mtd();
      $stmt = $pdo->prepare("
        SELECT c.name, COALESCE(SUM(si.quantity * si.unit_price),0) revenue
        FROM sale_items si
        JOIN products p ON p.id=si.product_id
        JOIN categories c ON c.id=p.category_id
        JOIN sales s ON s.id=si.sale_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
      ");
      $stmt->execute([':s'=>$s, ':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    /* ------------- Top products (MTD) ------------- */
    case 'top_products': {
      [$s,$e] = mtd();
      $stmt = $pdo->prepare("
        SELECT p.name, SUM(si.quantity) qty, SUM(si.quantity * si.unit_price) revenue
        FROM sale_items si
        JOIN products p ON p.id=si.product_id
        JOIN sales s ON s.id=si.sale_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY p.id, p.name
        ORDER BY revenue DESC
        LIMIT 10
      ");
      $stmt->execute([':s'=>$s, ':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    /* ------------- Low stock list ------------- */
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

    /* ------------- Recent sales ------------- */
    case 'recent_sales': {
      $rows = $pdo->query("
        SELECT id, total_amount, payment_type, created_at
        FROM sales
        ORDER BY created_at DESC
        LIMIT 10
      ")->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success'=>true,'rows'=>$rows]);
      break;
    }

    default:
      echo json_encode(['success'=>false,'message'=>'Unknown action']);
  }

} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
