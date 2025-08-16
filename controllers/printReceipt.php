<?php
// --- no cache ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../includes/auth.php');

if (!isset($_GET['sale_id']) || !ctype_digit($_GET['sale_id'])) {
  exit('Invalid receipt.');
}
$sale_id = (int) $_GET['sale_id'];

// pull sale + cashier name
$saleStmt = $pdo->prepare("
  SELECT s.id, s.subtotal, s.discount_type, s.discount_value, s.discount_amount,
         s.tax_rate, s.tax_amount, s.paid_amount, s.change_amount,
         s.total_amount, s.payment_type, s.comment, s.customer_id,
         s.created_at, u.name AS cashier
  FROM sales s
  LEFT JOIN users u ON u.id = s.user_id
  WHERE s.id = ?
");
$saleStmt->execute([$sale_id]);
$sale = $saleStmt->fetch();
if (!$sale) exit('Sale not found.');

// items
$itemStmt = $pdo->prepare("
  SELECT si.product_id, si.quantity, si.unit_price, p.name
  FROM sale_items si
  JOIN products p ON p.id = si.product_id
  WHERE si.sale_id = ?
");
$itemStmt->execute([$sale_id]);
$items = $itemStmt->fetchAll();

// compute subtotal from items as fallback
$computedSubtotal = 0.0;
foreach ($items as $it) {
  $computedSubtotal += (float)$it['quantity'] * (float)$it['unit_price'];
}

// prefer GET -> DB -> computed
$subtotal      = isset($_GET['subtotal'])  ? (float)$_GET['subtotal']  : (isset($sale['subtotal']) ? (float)$sale['subtotal'] : $computedSubtotal);
$discountType  = $_GET['discountType']     ?? ($sale['discount_type'] ?? null);    // 'percent' | 'amount' | null
$discountValue = isset($_GET['discountValue']) ? (float)$_GET['discountValue'] : (isset($sale['discount_value']) ? (float)$sale['discount_value'] : 0);
$discountAmt   = isset($_GET['discountAmt'])   ? (float)$_GET['discountAmt']   : (isset($sale['discount_amount']) ? (float)$sale['discount_amount'] : 0);

$taxRate       = isset($_GET['taxRate'])   ? (float)$_GET['taxRate']   : (isset($sale['tax_rate']) ? (float)$sale['tax_rate'] : 0.0);
$taxAmount     = isset($_GET['taxAmount']) ? (float)$_GET['taxAmount'] : (isset($sale['tax_amount']) ? (float)$sale['tax_amount'] : 0.0);

$paid          = isset($_GET['paid'])      ? (float)$_GET['paid']      : (isset($sale['paid_amount']) ? (float)$sale['paid_amount'] : (float)$sale['total_amount']);
$change        = isset($_GET['change'])    ? (float)$_GET['change']    : (isset($sale['change_amount']) ? (float)$sale['change_amount'] : max(0.0, $paid - (float)$sale['total_amount']));

$comment       = trim((string)($sale['comment'] ?? ''));
$cashier       = $sale['cashier'] ?: '—';
$customerText  = $sale['customer_id'] ? ('#' . (int)$sale['customer_id']) : 'Walk-in';

$autoPrint = isset($_GET['autoprint']); // prints only if explicitly asked

function ugx($n){ return number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Receipt #<?= htmlspecialchars($sale_id) ?></title>
  <style>
    body { font-family: monospace; padding: 20px; color:#111; }
    h2 { text-align: center; margin: 8px 0 4px; }
    .header-logo { text-align:center; margin-bottom: 4px; }
    .header-logo img { max-width: 120px; height: auto; }
    .meta, .extra { margin: 6px 0 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 4px; text-align: left; border-bottom: 1px dotted #999; }
    th { font-weight: bold; }
    .summary { width: 100%; margin-top: 10px; }
    .summary td { padding: 4px 2px; }
    .summary .label { text-align: right; }
    .summary .value { text-align: right; width: 180px; }
    .center { text-align: center; margin-top: 16px; font-size: 13px; }
    .muted { color:#666; }
    @media print { @page { margin: 10mm; } .no-print { display:none; } }
  </style>
</head>
<body>
  <div class="header-logo">
    <img src="/POS_UG/assets/images/logo.PNG" alt="Company Logo">
  </div>

  <h2>🧾 TOBY POS RECEIPT</h2>

  <div class="meta">
    <strong>Sale ID:</strong> <?= htmlspecialchars($sale_id) ?><br>
    <strong>Date:</strong> <?= htmlspecialchars($sale['created_at']) ?><br>
    <strong>Payment:</strong> <?= htmlspecialchars($sale['payment_type']) ?><br>
    <strong>Cashier:</strong> <?= htmlspecialchars($cashier) ?><br>
    <strong>Customer:</strong> <?= htmlspecialchars($customerText) ?>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:50%;">Product</th>
        <th style="width:10%;">Qty</th>
        <th style="width:20%;">Price</th>
        <th style="width:20%;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): 
        $lineTotal = (float)$it['quantity'] * (float)$it['unit_price'];
      ?>
      <tr>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= (int)$it['quantity'] ?></td>
        <td><?= ugx($it['unit_price']) ?></td>
        <td><?= ugx($lineTotal) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="summary">
    <tr>
      <td class="label"><strong>Subtotal:</strong></td>
      <td class="value">UGX <?= ugx($subtotal) ?></td>
    </tr>

    <?php if ($discountAmt > 0): ?>
    <tr>
      <td class="label">
        <strong>Discount<?= $discountType === 'percent' ? ' ('.rtrim(rtrim(number_format($discountValue, 2),'0'),'.').'%)' : '' ?>:</strong>
      </td>
      <td class="value">− UGX <?= ugx($discountAmt) ?></td>
    </tr>
    <?php endif; ?>

    <tr>
      <td class="label"><strong>Tax (<?= rtrim(rtrim(number_format($taxRate, 2), '0'), '.') ?>%):</strong></td>
      <td class="value">UGX <?= ugx($taxAmount) ?></td>
    </tr>
    <tr>
      <td class="label"><strong>Total:</strong></td>
      <td class="value"><strong>UGX <?= ugx($sale['total_amount']) ?></strong></td>
    </tr>
    <tr>
      <td class="label">Amount Paid:</td>
      <td class="value">UGX <?= ugx($paid) ?></td>
    </tr>
    <tr>
      <td class="label">Change:</td>
      <td class="value">UGX <?= ugx($change) ?></td>
    </tr>
  </table>

  <?php if ($comment !== ''): ?>
    <div class="extra"><span class="muted">Note:</span> <?= nl2br(htmlspecialchars($comment)) ?></div>
  <?php endif; ?>

  <div class="center">Thank you for your purchase!</div>

  <?php if ($autoPrint): ?>
    <script>window.addEventListener('load', () => window.print());</script>
  <?php endif; ?>
</body>
</html>
