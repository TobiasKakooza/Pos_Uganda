<?php
// controllers/reportsController.php
ini_set('display_errors',1); error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

function maybe_csv(string $filename, array $header, array $rows) {
  if (isset($_GET['export']) && strtolower($_GET['export']) === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    if ($header) fputcsv($out, $header);
    foreach ($rows as $r) fputcsv($out, array_values($r));
    fclose($out);
    exit;
  }
}


$action = $_GET['action'] ?? '';

function dtRange() {
  $from = $_GET['from'] ?? date('Y-m-01');
  $to   = $_GET['to']   ?? date('Y-m-d');
  return [$from.' 00:00:00', $to.' 23:59:59'];
}

function calcEOD(PDO $pdo, string $date): array {
  $start = $date . ' 00:00:00';
  $end   = $date . ' 23:59:59';

  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS sales_count,
           COALESCE(SUM(subtotal),0)        AS subtotal,
           COALESCE(SUM(discount_amount),0) AS discount_total,
           COALESCE(SUM(tax_amount),0)      AS tax_total,
           COALESCE(SUM(total_amount),0)    AS total_sales
    FROM sales
    WHERE created_at BETWEEN :s AND :e
  ");
  $stmt->execute([':s'=>$start, ':e'=>$end]);
  $sales = $stmt->fetch(PDO::FETCH_ASSOC);

  $rows = $pdo->prepare("
    SELECT payment_type,
           COALESCE(SUM(total_amount),0)  AS total,
           COALESCE(SUM(paid_amount),0)   AS paid,
           COALESCE(SUM(change_amount),0) AS change_due
    FROM sales
    WHERE created_at BETWEEN :s AND :e
    GROUP BY payment_type
  ");
  $rows->execute([':s'=>$start, ':e'=>$end]);
  $payments = $rows->fetchAll(PDO::FETCH_ASSOC);

  $cm = $pdo->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN type='in'  THEN amount END),0) AS cash_in,
      COALESCE(SUM(CASE WHEN type='out' THEN amount END),0) AS cash_out
    FROM cash_movements
    WHERE created_at BETWEEN :s AND :e
  ");
  $cm->execute([':s'=>$start, ':e'=>$end]);
  $cashMoves = $cm->fetch(PDO::FETCH_ASSOC);

  $cp = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) AS credit_payments
    FROM credit_payments
    WHERE created_at BETWEEN :s AND :e
  ");
  $cp->execute([':s'=>$start, ':e'=>$end]);
  $creditPayments = (float)$cp->fetch(PDO::FETCH_ASSOC)['credit_payments'];

  $shift = $pdo->prepare("
    SELECT
      COALESCE((SELECT opening_balance FROM shifts
                WHERE DATE(opened_at)=:d ORDER BY opened_at ASC LIMIT 1), 0)   AS opening_balance,
      (SELECT closing_balance FROM shifts
       WHERE DATE(closed_at)=:d AND status='closed'
       ORDER BY closed_at DESC LIMIT 1)                                         AS closing_balance
  ");
  $shift->execute([':d'=>$date]);
  $shiftVals = $shift->fetch(PDO::FETCH_ASSOC);
  $opening = (float)$shiftVals['opening_balance'];
  $closing = isset($shiftVals['closing_balance']) ? (float)$shiftVals['closing_balance'] : null;

  $cashSalesStmt = $pdo->prepare("
    SELECT COALESCE(SUM(paid_amount - change_amount),0) AS cash_net
    FROM sales
    WHERE created_at BETWEEN :s AND :e AND payment_type='cash'
  ");
  $cashSalesStmt->execute([':s'=>$start, ':e'=>$end]);
  $cashNet = (float)$cashSalesStmt->fetch(PDO::FETCH_ASSOC)['cash_net'];

  $expectedDrawer = $opening + $cashNet + (float)$cashMoves['cash_in'] - (float)$cashMoves['cash_out'];

  return [
    'date'               => $date,
    'sales'              => $sales,
    'payments'           => $payments,
    'cash_movements'     => $cashMoves,
    'credit_payments'    => $creditPayments,
    'opening_balance'    => $opening,
    'closing_balance'    => $closing,
    'cash_from_sales_net'=> $cashNet,
    'expected_drawer'    => $expectedDrawer,
  ];
}

try {
  switch ($action) {

    /* ---------- End-of-day (EOD) ---------- */
    case 'end_of_day': {
      $date = $_GET['date'] ?? date('Y-m-d');
      $out  = calcEOD($pdo, $date);
      echo json_encode(['success'=>true] + $out);
      break;
    }

    case 'close_day': {
      $date    = $_POST['date'] ?? date('Y-m-d');
      $counted = isset($_POST['closing_balance']) ? (float)$_POST['closing_balance'] : null;

      $out = calcEOD($pdo, $date);

      $pdo->beginTransaction();
      $sql = "
        INSERT INTO eod_reports
          (report_date, opening_balance, sales_count, subtotal, discount_total, tax_total, total_sales,
           cash_from_sales_net, cash_in, cash_out, credit_payments, expected_drawer, closing_balance)
        VALUES
          (:d,:opening,:sales_count,:subtotal,:discount_total,:tax_total,:total_sales,
           :cash_net,:cash_in,:cash_out,:credit_payments,:expected_drawer,:closing_balance)
        ON DUPLICATE KEY UPDATE
          opening_balance=VALUES(opening_balance),
          sales_count=VALUES(sales_count),
          subtotal=VALUES(subtotal),
          discount_total=VALUES(discount_total),
          tax_total=VALUES(tax_total),
          total_sales=VALUES(total_sales),
          cash_from_sales_net=VALUES(cash_from_sales_net),
          cash_in=VALUES(cash_in),
          cash_out=VALUES(cash_out),
          credit_payments=VALUES(credit_payments),
          expected_drawer=VALUES(expected_drawer),
          closing_balance=VALUES(closing_balance)
      ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':d'               => $out['date'],
        ':opening'         => (float)$out['opening_balance'],
        ':sales_count'     => (int)$out['sales']['sales_count'],
        ':subtotal'        => (float)$out['sales']['subtotal'],
        ':discount_total'  => (float)$out['sales']['discount_total'],
        ':tax_total'       => (float)$out['sales']['tax_total'],
        ':total_sales'     => (float)$out['sales']['total_sales'],
        ':cash_net'        => (float)$out['cash_from_sales_net'],
        ':cash_in'         => (float)$out['cash_movements']['cash_in'],
        ':cash_out'        => (float)$out['cash_movements']['cash_out'],
        ':credit_payments' => (float)$out['credit_payments'],
        ':expected_drawer' => (float)$out['expected_drawer'],
        ':closing_balance' => $counted,
      ]);

      if ($counted !== null) {
        $upd = $pdo->prepare("
          UPDATE shifts
          SET closing_balance=:cb, status='closed', closed_at=NOW()
          WHERE DATE(opened_at)=:d AND status='open'
          ORDER BY opened_at ASC LIMIT 1
        ");
        $upd->execute([':cb'=>$counted, ':d'=>$date]);
      }

      $pdo->commit();
      echo json_encode(['success'=>true, 'message'=>'EOD saved'] + $out);
      break;
    }

    /* ---------- SALES ---------- */
    case 'sales_overview': {
      [$s,$e] = dtRange();
      $grp = $_GET['group'] ?? 'day'; // day|week|month
      $fmt = $grp==='month' ? '%Y-%m' : ($grp==='week' ? '%x-W%v' : '%Y-%m-%d');

      $sql = "
        SELECT DATE_FORMAT(created_at, :fmt) AS bucket,
               COUNT(*) AS sales_count,
               COALESCE(SUM(subtotal),0)        AS subtotal,
               COALESCE(SUM(discount_amount),0) AS discount_total,
               COALESCE(SUM(tax_amount),0)      AS tax_total,
               COALESCE(SUM(total_amount),0)    AS total_sales
        FROM sales
        WHERE created_at BETWEEN :s AND :e
        GROUP BY bucket
        ORDER BY bucket
      ";
      $stmt=$pdo->prepare($sql);
      $stmt->execute([':fmt'=>$fmt, ':s'=>$s, ':e'=>$e]);
      echo json_encode(['success'=>true, 'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'sales_by_product': {
      [$s,$e]=dtRange();
      $sql="
        SELECT p.id, p.name, SUM(si.quantity) qty, 
               SUM(si.quantity*si.unit_price) revenue
        FROM sale_items si
        JOIN products p ON p.id=si.product_id
        JOIN sales s  ON s.id=si.sale_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY p.id, p.name
        ORDER BY qty DESC
        LIMIT :lim OFFSET :off";
      $lim = (int)($_GET['limit'] ?? 50);
      $off = (int)($_GET['offset'] ?? 0);
      $stmt=$pdo->prepare($sql);
      $stmt->bindValue(':s',$s); $stmt->bindValue(':e',$e);
      $stmt->bindValue(':lim',$lim,PDO::PARAM_INT);
      $stmt->bindValue(':off',$off,PDO::PARAM_INT);
      $stmt->execute();
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'sales_by_category': {
      [$s,$e]=dtRange();
      $sql="
        SELECT c.id, c.name,
               SUM(si.quantity) qty,
               SUM(si.quantity*si.unit_price) revenue
        FROM sale_items si
        JOIN products p ON p.id=si.product_id
        JOIN categories c ON c.id=p.category_id
        JOIN sales s ON s.id=si.sale_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY c.id, c.name
        ORDER BY revenue DESC";
      $stmt=$pdo->prepare($sql); $stmt->execute([':s'=>$s,':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'sales_by_payment': {
      [$s,$e]=dtRange();
      $sql="
        SELECT payment_type,
               COUNT(*) sales_count,
               SUM(total_amount) total
        FROM sales
        WHERE created_at BETWEEN :s AND :e
        GROUP BY payment_type
        ORDER BY total DESC";
      $stmt=$pdo->prepare($sql); $stmt->execute([':s'=>$s,':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'sales_by_cashier': {
      [$s,$e]=dtRange();
      $sql="
        SELECT u.id, u.name,
               COUNT(*) sales_count,
               COALESCE(SUM(total_amount),0) total
        FROM sales s
        JOIN users u ON u.id=s.user_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY u.id, u.name
        ORDER BY total DESC";
      $stmt=$pdo->prepare($sql); $stmt->execute([':s'=>$s,':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    /* ---------- INVENTORY ---------- */
    case 'stock_levels': {
  $q     = trim($_GET['q'] ?? '');
  $catId = (int)($_GET['category_id'] ?? 0);
  $only  = $_GET['only'] ?? ''; // '', 'low', 'out'

  $sql = "
    SELECT p.id, p.name, p.sku, p.stock_alert_threshold,
           c.name AS category,
           COALESCE(SUM(CASE WHEN i.type='in'  THEN i.quantity ELSE 0 END),0)
         - COALESCE(SUM(CASE WHEN i.type='out' THEN i.quantity ELSE 0 END),0) AS on_hand
    FROM products p
    LEFT JOIN inventories i ON i.product_id=p.id
    LEFT JOIN categories  c ON c.id=p.category_id
    WHERE 1=1
  ";

  $params = [];
  if ($q !== '') {
    $sql .= " AND (p.name LIKE :q OR p.sku LIKE :q) ";
    $params[':q'] = "%{$q}%";
  }
  if ($catId) {
    $sql .= " AND p.category_id = :cid ";
    $params[':cid'] = $catId;
  }

  $sql .= "
    GROUP BY p.id, p.name, p.sku, p.stock_alert_threshold, c.name
    HAVING 1=1
  ";

  if ($only === 'out') {
    $sql .= " AND on_hand <= 0 ";
  } elseif ($only === 'low') {
    $sql .= " AND on_hand <= COALESCE(p.stock_alert_threshold, 0) ";
  }

  $sql .= " ORDER BY on_hand ASC, p.name ASC ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // CSV export if requested
  maybe_csv('stock_levels.csv', ['sku','name','category','on_hand','threshold'],
    array_map(fn($r)=>[
      $r['sku'],$r['name'],$r['category'],$r['on_hand'],$r['stock_alert_threshold']
    ], $data)
  );

  echo json_encode(['success'=>true,'rows'=>$data]);
  break;
}


    case 'stock_valuation': {
      $sql="
        WITH q AS (
          SELECT p.id,
                 COALESCE(SUM(CASE WHEN i.type='in'  THEN i.quantity END),0)
               - COALESCE(SUM(CASE WHEN i.type='out' THEN i.quantity END),0) AS on_hand,
                 p.avg_cost, p.last_cost, p.name, p.sku
          FROM products p
          LEFT JOIN inventories i ON i.product_id=p.id
          GROUP BY p.id, p.avg_cost, p.last_cost, p.name, p.sku
        )
        SELECT id, name, sku, on_hand,
               COALESCE(NULLIF(avg_cost,0), last_cost, 0) as cost_basis,
               on_hand * COALESCE(NULLIF(avg_cost,0), last_cost, 0) as value
        FROM q
        ORDER BY value DESC";
      $rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      $total = array_sum(array_column($rows,'value'));
      echo json_encode(['success'=>true,'rows'=>$rows,'total_value'=>$total]);
      break;
    }

    case 'reorder_alerts': {
      $sql="
        WITH stock AS (
          SELECT p.id,
                 COALESCE(SUM(CASE WHEN i.type='in'  THEN i.quantity END),0)
               - COALESCE(SUM(CASE WHEN i.type='out' THEN i.quantity END),0) AS on_hand
          FROM products p
          LEFT JOIN inventories i ON i.product_id=p.id
          GROUP BY p.id
        )
        SELECT p.id, p.name, p.sku, p.stock_alert_threshold, s.on_hand
        FROM products p
        JOIN stock s ON s.id=p.id
        WHERE p.stock_alert_threshold IS NOT NULL
          AND s.on_hand <= p.stock_alert_threshold
        ORDER BY s.on_hand ASC";
      $rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success'=>true,'rows'=>$rows]);
      break;
    }

    case 'expiry_report': {
      $days = (int)($_GET['days'] ?? 60);
      $sql="
        SELECT r.reference_no, p.name, ri.batch_no, ri.expiry_date, ri.qty
        FROM receipt_items ri
        JOIN receipts r ON r.id=ri.receipt_id
        JOIN products p ON p.id=ri.product_id
        WHERE ri.expiry_date IS NOT NULL
          AND ri.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :d DAY)
        ORDER BY ri.expiry_date ASC";
      $stmt=$pdo->prepare($sql); $stmt->execute([':d'=>$days]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    /* ---------- FINANCIAL ---------- */
    case 'profitability': {
      [$s,$e]=dtRange();
      $sql="
        SELECT
          COALESCE(SUM(si.quantity*si.unit_price),0) AS revenue,
          COALESCE(SUM(si.quantity * COALESCE(NULLIF(p.avg_cost,0), p.last_cost, 0)),0) AS cogs
        FROM sale_items si
        JOIN sales s ON s.id=si.sale_id
        JOIN products p ON p.id=si.product_id
        WHERE s.created_at BETWEEN :s AND :e";
      $stmt=$pdo->prepare($sql); $stmt->execute([':s'=>$s,':e'=>$e]);
      $r=$stmt->fetch(PDO::FETCH_ASSOC);
      $gross = (float)$r['revenue'] - (float)$r['cogs'];
      echo json_encode(['success'=>true] + $r + ['gross_profit'=>$gross]);
      break;
    }

    case 'tax_summary': {
      [$s,$e]=dtRange();
      $stmt=$pdo->prepare("
        SELECT DATE(created_at) as d, SUM(tax_amount) tax
        FROM sales
        WHERE created_at BETWEEN :s AND :e
        GROUP BY DATE(created_at)
        ORDER BY d");
      $stmt->execute([':s'=>$s,':e'=>$e]);
      $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success'=>true,'rows'=>$rows,
                        'total_tax'=>array_sum(array_map(fn($x)=>$x['tax'],$rows))]);
      break;
    }

    case 'discounts_returns': {
      [$s,$e]=dtRange();
      $q1=$pdo->prepare("SELECT SUM(discount_amount) disc FROM sales WHERE created_at BETWEEN :s AND :e");
      $q1->execute([':s'=>$s,':e'=>$e]);
      $disc=(float)$q1->fetchColumn();

      $q2=$pdo->prepare("SELECT COALESCE(SUM(ABS(quantity)),0) qty FROM inventories WHERE type='return' AND created_at BETWEEN :s AND :e");
      $q2->execute([':s'=>$s,':e'=>$e]);
      $retQty=(float)$q2->fetchColumn();

      echo json_encode(['success'=>true,'discount_total'=>$disc,'returns_qty'=>$retQty]);
      break;
    }

    /* ---------- CUSTOMERS ---------- */
    case 'top_customers': {
      [$s,$e]=dtRange();
      $stmt=$pdo->prepare("
        SELECT c.id,c.name, COALESCE(SUM(s.total_amount),0) total_spent, COUNT(*) orders
        FROM sales s
        JOIN customers c ON c.id=s.customer_id
        WHERE s.created_at BETWEEN :s AND :e
        GROUP BY c.id,c.name
        ORDER BY total_spent DESC
        LIMIT 50");
      $stmt->execute([':s'=>$s,':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'debtors': {
      $rows=$pdo->query("
        SELECT id,name,phone,email,outstanding_balance
        FROM customers
        WHERE outstanding_balance>0
        ORDER BY outstanding_balance DESC")->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success'=>true,'rows'=>$rows]);
      break;
    }

    /* ---------- STAFF ---------- */
    case 'shift_report': {
      [$s,$e]=dtRange();
      $stmt=$pdo->prepare("
        SELECT sh.id, u.name cashier, sh.opened_at, sh.closed_at, sh.opening_balance, sh.closing_balance,
               (SELECT COALESCE(SUM(total_amount),0)
                FROM sales WHERE user_id=sh.user_id AND created_at BETWEEN sh.opened_at AND COALESCE(sh.closed_at,NOW())) AS sales_total
        FROM shifts sh
        JOIN users u ON u.id=sh.user_id
        WHERE sh.opened_at BETWEEN :s AND :e
        ORDER BY sh.opened_at DESC");
      $stmt->execute([':s'=>$s,':e'=>$e]);
      echo json_encode(['success'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'cash_drawer': {
      [$s,$e]=dtRange();
      $rows=$pdo->prepare("
        SELECT report_date, opening_balance, total_sales, cash_from_sales_net, cash_in, cash_out,
               expected_drawer, closing_balance
        FROM eod_reports
        WHERE report_date BETWEEN :ds AND :de
        ORDER BY report_date DESC");
      $rows->execute([':ds'=>substr($s,0,10), ':de'=>substr($e,0,10)]);
      echo json_encode(['success'=>true,'rows'=>$rows->fetchAll(PDO::FETCH_ASSOC)]);
      break;
    }

    case 'sales_by_payment': {
        [$s,$e]=dtRange();
        $rows = $pdo->prepare("
          SELECT payment_type, COUNT(*) sales_count, SUM(total_amount) total
          FROM sales
          WHERE created_at BETWEEN :s AND :e
          GROUP BY payment_type
          ORDER BY total DESC
        ");
        $rows->execute([':s'=>$s,':e'=>$e]);
        $data = $rows->fetchAll(PDO::FETCH_ASSOC);
        maybe_csv('sales_by_payment.csv',['payment_type','sales_count','total'],$data);
        echo json_encode(['success'=>true,'rows'=>$data]);
        break;
      }
        case 'slow_movers': {
            [$s,$e]=dtRange();
            $lim = (int)($_GET['limit'] ?? 50);
            $stmt=$pdo->prepare("
              SELECT p.id, p.name, SUM(si.quantity) qty, SUM(si.quantity*si.unit_price) revenue
              FROM sale_items si
              JOIN products p ON p.id=si.product_id
              JOIN sales s  ON s.id=si.sale_id
              WHERE s.created_at BETWEEN :s AND :e
              GROUP BY p.id,p.name
              HAVING qty IS NOT NULL
              ORDER BY qty ASC, revenue ASC
              LIMIT :lim
            ");
            $stmt->bindValue(':s',$s); $stmt->bindValue(':e',$e);
            $stmt->bindValue(':lim',$lim,PDO::PARAM_INT);
            $stmt->execute();
            $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
            maybe_csv('slow_movers.csv',['name','qty','revenue'],$data);
            echo json_encode(['success'=>true,'rows'=>$data]);
            break;
          }
          case 'customer_history': {
            $cid = (int)($_GET['customer_id'] ?? 0);
            if (!$cid) { echo json_encode(['success'=>false,'message'=>'customer_id required']); break; }
            [$s,$e]=dtRange();
            $stmt=$pdo->prepare("
              SELECT s.id sale_id, s.created_at, s.total_amount,
                    SUM(si.quantity) items, SUM(si.quantity*si.unit_price) revenue
              FROM sales s
              LEFT JOIN sale_items si ON si.sale_id=s.id
              WHERE s.customer_id=:cid AND s.created_at BETWEEN :s AND :e
              GROUP BY s.id
              ORDER BY s.created_at DESC
            ");
            $stmt->execute([':cid'=>$cid,':s'=>$s,':e'=>$e]);
            $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
            maybe_csv("customer_{$cid}_history.csv",['sale_id','created_at','total_amount','items','revenue'],$data);
            echo json_encode(['success'=>true,'rows'=>$data]);
            break;
          }

        case 'void_cancel_report': {
          [$s,$e]=dtRange();
          $stmt=$pdo->prepare("
            SELECT status, COUNT(*) cnt, SUM(total_amount) total
            FROM sales
            WHERE created_at BETWEEN :s AND :e AND status IN ('void','cancelled','refunded')
            GROUP BY status
          ");
          $stmt->execute([':s'=>$s,':e'=>$e]);
          $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
          maybe_csv('void_cancel.csv',['status','count','total'],$data);
          echo json_encode(['success'=>true,'rows'=>$data]);
          break;
        }
        case 'categories': {
  $rows = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'rows'=>$rows]);
  break;
}


    default:
      echo json_encode(['success'=>false,'message'=>'Unknown action']);
  }
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
