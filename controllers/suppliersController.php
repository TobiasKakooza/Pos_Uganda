<?php
// controllers/suppliersController.php
ini_set('display_errors',1); error_reporting(E_ALL);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function jbad($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }
function ok($p=[]){ echo json_encode(['success'=>true]+$p); exit; }

$userId = $_SESSION['user']['id'] ?? null;

/** ---------- list (for ajax table, optional search/paging) ---------- */
if ($action === 'list') {
  $q      = trim($_GET['q'] ?? '');
  $limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));

  $where = '';
  $args  = [];
  if ($q !== '') {
    $where = "WHERE (s.name LIKE :q OR s.phone LIKE :q OR s.email LIKE :q)";
    $args[':q'] = "%{$q}%";
  }

  $sql = "
    SELECT
      s.id, s.name, s.contact_person, s.phone, s.email,
      s.status, s.city, s.country,
      s.currency_code, s.current_balance, s.credit_limit,
      s.tax_id, s.payment_terms_id, pt.name AS terms_name, pt.days AS terms_days,
      s.created_at
    FROM suppliers s
    LEFT JOIN payment_terms pt ON pt.id = s.payment_terms_id
    $where
    ORDER BY s.name ASC
    LIMIT $limit OFFSET $offset
  ";
  $stmt = $pdo->prepare($sql); $stmt->execute($args);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $cnt  = $pdo->prepare("SELECT COUNT(*) FROM suppliers s $where");
  $cnt->execute($args);
  $total = (int)$cnt->fetchColumn();

  ok(['rows'=>$rows,'total'=>$total]);
}

/** ---------- payment terms (for select) ---------- */
if ($action === 'terms') {
  $rows = $pdo->query("SELECT id, name, days FROM payment_terms ORDER BY days")->fetchAll(PDO::FETCH_ASSOC);
  ok(['rows'=>$rows]);
}

/** ---------- get one ---------- */
if ($action === 'get') {
  $id = (int)($_GET['id'] ?? 0);
  if (!$id) jbad('Missing id');

  $s = $pdo->prepare("SELECT * FROM suppliers WHERE id=:id");
  $s->execute([':id'=>$id]);
  $row = $s->fetch(PDO::FETCH_ASSOC);
  if (!$row) jbad('Not found');

  ok(['supplier'=>$row]);
}

/** ---------- save (insert or update) ---------- */
if ($action === 'save') {
  $data = json_decode(file_get_contents('php://input'), true) ?? [];
  $id   = (int)($data['id'] ?? 0);

  $name = trim($data['name'] ?? '');
  if ($name === '') jbad('Name is required');

  // sanitize
  $fields = [
    'contact_person','phone','email','status','tax_id','address1','address2',
    'city','region','country','postal_code','currency_code','notes'
  ];
  $nums   = ['credit_limit','opening_balance','current_balance'];
  foreach ($nums as $k) $data[$k] = isset($data[$k]) ? (float)$data[$k] : 0;
  $termsId = !empty($data['payment_terms_id']) ? (int)$data['payment_terms_id'] : null;

  try {
    if ($id === 0) {
      // INSERT
      $sql = "INSERT INTO suppliers
        (name, contact_person, phone, email, status, tax_id, address1, address2,
         city, region, country, postal_code, currency_code, payment_terms_id,
         credit_limit, opening_balance, current_balance, notes, created_by, updated_by)
        VALUES
        (:name,:contact,:phone,:email,:status,:tax,:addr1,:addr2,
         :city,:region,:country,:postal,:cur,:terms,:climit,:openbal,:curbal,:notes,:cb,:ub)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':name'=>$name,
        ':contact'=>$data['contact_person'] ?? null,
        ':phone'=>$data['phone'] ?? null,
        ':email'=>$data['email'] ?? null,
        ':status'=>$data['status'] ?? 'active',
        ':tax'=>$data['tax_id'] ?? null,
        ':addr1'=>$data['address1'] ?? null,
        ':addr2'=>$data['address2'] ?? null,
        ':city'=>$data['city'] ?? null,
        ':region'=>$data['region'] ?? null,
        ':country'=>$data['country'] ?? null,
        ':postal'=>$data['postal_code'] ?? null,
        ':cur'=>$data['currency_code'] ?? 'UGX',
        ':terms'=>$termsId,
        ':climit'=>$data['credit_limit'] ?? 0,
        ':openbal'=>$data['opening_balance'] ?? 0,
        ':curbal'=>$data['current_balance'] ?? 0,
        ':notes'=>$data['notes'] ?? null,
        ':cb'=>$userId, ':ub'=>$userId
      ]);
      $newId = (int)$pdo->lastInsertId();

      // Opening balance → AP ledger (debit increases payable)
      if (!empty($data['opening_balance'])) {
        $ins = $pdo->prepare("
          INSERT INTO ap_ledger (supplier_id, txn_type, txn_id, txn_date, description, debit, credit, balance)
          VALUES (:sid,'opening',NULL,CURDATE(),'Opening balance',:debit,0,:debit)
        ");
        $ins->execute([':sid'=>$newId, ':debit'=>(float)$data['opening_balance']]);
      }

      ok(['id'=>$newId]);
    } else {
      // UPDATE
      $sql = "UPDATE suppliers SET
        name=:name, contact_person=:contact, phone=:phone, email=:email,
        status=:status, tax_id=:tax, address1=:addr1, address2=:addr2,
        city=:city, region=:region, country=:country, postal_code=:postal,
        currency_code=:cur, payment_terms_id=:terms, credit_limit=:climit,
        opening_balance=:openbal, current_balance=:curbal, notes=:notes, updated_by=:ub
        WHERE id=:id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':name'=>$name,
        ':contact'=>$data['contact_person'] ?? null,
        ':phone'=>$data['phone'] ?? null,
        ':email'=>$data['email'] ?? null,
        ':status'=>$data['status'] ?? 'active',
        ':tax'=>$data['tax_id'] ?? null,
        ':addr1'=>$data['address1'] ?? null,
        ':addr2'=>$data['address2'] ?? null,
        ':city'=>$data['city'] ?? null,
        ':region'=>$data['region'] ?? null,
        ':country'=>$data['country'] ?? null,
        ':postal'=>$data['postal_code'] ?? null,
        ':cur'=>$data['currency_code'] ?? 'UGX',
        ':terms'=>$termsId,
        ':climit'=>$data['credit_limit'] ?? 0,
        ':openbal'=>$data['opening_balance'] ?? 0,
        ':curbal'=>$data['current_balance'] ?? 0,
        ':notes'=>$data['notes'] ?? null,
        ':ub'=>$userId, ':id'=>$id
      ]);
      ok(['id'=>$id]);
    }
  } catch (Throwable $e) {
    jbad($e->getMessage());
  }
}

/** ---------- delete ---------- */
if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if (!$id) jbad('Missing id');
  $pdo->prepare("DELETE FROM suppliers WHERE id=:id")->execute([':id'=>$id]);
  ok();
}

jbad('Unknown action');
