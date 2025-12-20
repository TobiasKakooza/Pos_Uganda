<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  echo json_encode(['success'=>false]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT message, created_at
  FROM notifications
  WHERE id = ?
");
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$n) {
  echo json_encode(['success'=>false]);
  exit;
}

/* Extract sale number from message: #63 */
preg_match('/#(\d+)/', $n['message'], $m);
$saleId = (int)($m[1] ?? 0);

if (!$saleId) {
  echo json_encode(['success'=>true,'note'=>'No sale linked']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    u.name AS cashier,
    GROUP_CONCAT(p.name SEPARATOR ', ') AS products,
    SUM(si.unit_price * si.quantity) AS total
  FROM sales s
  JOIN users u ON u.id = s.user_id
  JOIN sale_items si ON si.sale_id = s.id
  JOIN products p ON p.id = si.product_id
  WHERE s.id = ?
  GROUP BY s.id
");

$stmt->execute([$saleId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  'success'  => true,
  'cashier'  => $row['cashier'] ?? 'System',
  'product'  => $row['products'] ?? '—',
  'price'    => $row['total'] ?? 0
]);
