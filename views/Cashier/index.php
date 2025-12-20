<?php
require_once __DIR__ . '/../../includes/auth.php';

/* =====================================================
   HARD SECURITY
===================================================== */
if (($_SESSION['role_id'] ?? null) !== 2) {
    http_response_code(403);
    exit('Access denied');
}

require_permission('sales_access');

$userId = $_SESSION['user_id'];

/* =====================================================
   AJAX MODE (CHART DATA + FILTERS)
===================================================== */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $action  = $_GET['action'] ?? '';
    $from    = $_GET['from'] ?? date('Y-m-d');
    $to      = $_GET['to']   ?? date('Y-m-d');
    $payment = $_GET['payment'] ?? 'all';

    $paymentSql = $payment !== 'all' ? "AND payment_type = :payment" : "";

    /* ---------- SALES BY HOUR (TODAY) ---------- */
    if ($action === 'hourly') {
        $stmt = $pdo->prepare("
          SELECT HOUR(created_at) h, SUM(total_amount) total
          FROM sales
          WHERE user_id = :uid
            AND DATE(created_at) = CURDATE()
            $paymentSql
          GROUP BY h
          ORDER BY h
        ");

        $params = ['uid' => $userId];
        if ($payment !== 'all') $params['payment'] = $payment;

        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $data   = [];
        $map    = [];

        foreach ($rows as $r) {
            $map[(int)$r['h']] = (float)$r['total'];
        }

        for ($i = 0; $i < 24; $i++) {
            $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $data[]   = $map[$i] ?? 0;
        }

        echo json_encode(['labels' => $labels, 'data' => $data]);
        exit;
    }

    /* ---------- SALES BY DAY (RANGE) ---------- */
    if ($action === 'daily') {
        $stmt = $pdo->prepare("
          SELECT DATE(created_at) d, SUM(total_amount) total
          FROM sales
          WHERE user_id = :uid
            AND DATE(created_at) BETWEEN :from AND :to
            $paymentSql
          GROUP BY d
          ORDER BY d
        ");

        $params = [
            'uid'  => $userId,
            'from' => $from,
            'to'   => $to
        ];
        if ($payment !== 'all') $params['payment'] = $payment;

        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'labels' => array_column($rows, 'd'),
            'data'   => array_map('floatval', array_column($rows, 'total'))
        ]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    exit;
}

/* =====================================================
   TODAY KPIs
===================================================== */
$stmt = $pdo->prepare("
  SELECT COUNT(*) txns, COALESCE(SUM(total_amount),0) total
  FROM sales
  WHERE user_id = ? AND DATE(created_at)=CURDATE()
");
$stmt->execute([$userId]);
$sales = $stmt->fetch(PDO::FETCH_ASSOC);

/* CASH DRAWER */
$cash = 0;
if ($pdo->query("SHOW TABLES LIKE 'cash_movements'")->fetchColumn()) {
    $stmt = $pdo->prepare("
      SELECT COALESCE(
        SUM(CASE WHEN type='in' THEN amount ELSE 0 END) -
        SUM(CASE WHEN type='out' THEN amount ELSE 0 END),0)
      FROM cash_movements WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $cash = (float)$stmt->fetchColumn();
}

/* RECENT SALES */
$stmt = $pdo->prepare("
  SELECT id, total_amount, payment_type, created_at
  FROM sales
  WHERE user_id = ?
  ORDER BY created_at DESC
  LIMIT 8
");
$stmt->execute([$userId]);
$recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<style>
.cashier-container{padding:24px}
.cashier-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:20px}
.cashier-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.06)}
.cashier-kpi small{color:#6b7280;font-size:13px}
.cashier-kpi h2{margin-top:10px;font-size:22px;color:#111827}
.cashier-actions{margin-top:28px}
.cashier-actions a{padding:14px 22px;border-radius:12px;background:#020617;color:#fff;text-decoration:none;font-weight:600;box-shadow:0 12px 30px rgba(0,0,0,.45)}
.cashier-actions a:hover{background:#111827}
.cashier-charts{margin-top:32px;display:grid;grid-template-columns:1fr 1fr;gap:18px}
.chart-card{background:#fff;border-radius:14px;padding:16px;border:1px solid #e5e7eb;box-shadow:0 10px 24px rgba(0,0,0,.06)}
.cashier-table{margin-top:32px;background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden}
.cashier-table th, .cashier-table td{padding:12px;border-bottom:1px solid #e5e7eb;font-size:14px}
.cashier-table th{background:#f9fafb;color:#6b7280;text-align:left}
.filters{margin-top:28px;display:flex;gap:12px;align-items:end}
.filters input,.filters select{padding:8px;border-radius:8px;border:1px solid #cbd5e1}
.filters button{padding:10px 16px;border-radius:10px;border:none;background:#020617;color:#fff;font-weight:600}
</style>

<main class="cashier-container">

<h1 class="dash-title">
  <i data-lucide="shopping-cart"></i>
  Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>
</h1>

<div class="cashier-kpis">
  <div class="cashier-kpi"><small>My Sales Today</small><h2>UGX <?= number_format($sales['total']) ?></h2></div>
  <div class="cashier-kpi"><small>Transactions</small><h2><?= $sales['txns'] ?></h2></div>
  <div class="cashier-kpi"><small>Cash in Drawer</small><h2>UGX <?= number_format($cash) ?></h2></div>
</div>

<div class="cashier-actions">
  <a href="/POS_UG/views/sales/terminal.php">Open POS Terminal</a>
</div>

<div class="filters">
  <div>
    <label>From</label><br>
    <input type="date" id="from" value="<?= date('Y-m-d',strtotime('-6 days')) ?>">
  </div>
  <div>
    <label>To</label><br>
    <input type="date" id="to" value="<?= date('Y-m-d') ?>">
  </div>
  <div>
    <label>Payment</label><br>
    <select id="payment">
      <option value="all">All</option>
      <option value="Cash">Cash</option>
      <option value="Card">Card</option>
      <option value="Mobile">Mobile</option>
    </select>
  </div>
  <button onclick="reloadCharts()">Apply</button>
</div>

<div class="cashier-charts">
  <div class="chart-card"><h3>Sales by Hour (Today)</h3><canvas id="salesByHour"></canvas></div>
  <div class="chart-card"><h3>Sales by Day</h3><canvas id="salesByDay"></canvas></div>
</div>

<div class="cashier-table">
<table>
<thead><tr><th>ID</th><th>Payment</th><th>Total</th><th>Date</th><th>Time</th></tr></thead>
<tbody>
<?php foreach ($recentSales as $s): ?>
<tr>
<td>#<?= $s['id'] ?></td>
<td><?= htmlspecialchars($s['payment_type']) ?></td>
<td>UGX <?= number_format($s['total_amount']) ?></td>
<td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
<td><?= date('H:i', strtotime($s['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let hourChart, dayChart;

function reloadCharts(){
  const from = document.getElementById('from').value;
  const to = document.getElementById('to').value;
  const payment = document.getElementById('payment').value;

  fetch(`index.php?ajax=1&action=hourly&payment=${payment}`)
    .then(r=>r.json()).then(r=>{
      hourChart?.destroy();
      hourChart=new Chart(salesByHour,{type:'line',data:{labels:r.labels,datasets:[{label:'Sales (UGX)',data:r.data}]}})
    });

  fetch(`index.php?ajax=1&action=daily&from=${from}&to=${to}&payment=${payment}`)
    .then(r=>r.json()).then(r=>{
      dayChart?.destroy();
      dayChart=new Chart(salesByDay,{type:'bar',data:{labels:r.labels,datasets:[{label:'Sales (UGX)',data:r.data}]}})
    });
}
reloadCharts();
lucide.createIcons();
</script>
