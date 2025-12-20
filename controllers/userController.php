<?php
declare(strict_types=1);

require_once '../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* ======================================================
   HELPERS
====================================================== */
function json_response($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function post($key, $default = null) {
  return $_POST[$key] ?? $default;
}

/* ======================================================
   ROUTER
====================================================== */
switch ($action) {

  /* ======================================================
     LIST USERS
  ====================================================== */
  case 'list':
    $stmt = $pdo->query("
      SELECT 
        u.id,
        u.name,
        u.email,
        LOWER(REPLACE(r.name, ' ', '_')) AS role
      FROM users u
      JOIN roles r ON r.id = u.role_id
      ORDER BY u.id ASC
    ");

    json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
    break;

  /* ======================================================
     GET SINGLE USER
  ====================================================== */
  case 'get':
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
      json_response(['error' => 'Invalid user ID'], 400);
    }

    $stmt = $pdo->prepare("
      SELECT 
        u.id,
        u.name,
        u.email,
        LOWER(REPLACE(r.name, ' ', '_')) AS role
      FROM users u
      JOIN roles r ON r.id = u.role_id
      WHERE u.id = ?
      LIMIT 1
    ");
    $stmt->execute([$id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      json_response(['error' => 'User not found'], 404);
    }

    json_response($user);
    break;

  /* ======================================================
     CREATE / UPDATE USER
  ====================================================== */
  case 'save':
    $id       = (int) post('id', 0);
    $name     = trim(post('name'));
    $email    = trim(post('email'));
    $password = post('password');
    $roleId   = (int) post('role_id');

    if (!$name || !$email || !$roleId) {
      json_response(['error' => 'Missing required fields'], 400);
    }

    // Validate role exists
    $roleCheck = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
    $roleCheck->execute([$roleId]);
    if (!$roleCheck->fetch()) {
      json_response(['error' => 'Invalid role'], 400);
    }

    // ================= CREATE =================
    if ($id === 0) {

      // Prevent duplicate email
      $exists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
      $exists->execute([$email]);
      if ($exists->fetch()) {
        json_response(['error' => 'Email already exists'], 409);
      }

      $hashed = password_hash(
        $password ?: '123456',
        PASSWORD_DEFAULT
      );

      $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role_id)
        VALUES (?, ?, ?, ?)
      ");
      $stmt->execute([$name, $email, $hashed, $roleId]);

      json_response(['status' => 'created']);
    }

    // ================= UPDATE =================
    else {

      if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
          UPDATE users
          SET name = ?, email = ?, password = ?, role_id = ?
          WHERE id = ?
        ");
        $stmt->execute([$name, $email, $hashed, $roleId, $id]);
      } else {
        $stmt = $pdo->prepare("
          UPDATE users
          SET name = ?, email = ?, role_id = ?
          WHERE id = ?
        ");
        $stmt->execute([$name, $email, $roleId, $id]);
      }

      json_response(['status' => 'updated']);
    }
    break;

  /* ======================================================
     DELETE USER
  ====================================================== */
  case 'delete':
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
      json_response(['error' => 'Invalid user ID'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    json_response(['status' => 'deleted']);
    break;

  /* ======================================================
     GET USER PERMISSIONS
  ====================================================== */
 case 'permissions':
  $userId = (int)($_GET['user_id'] ?? 0);

  if (!$userId) {
    json_response(['error' => 'Invalid user ID'], 400);
  }

  $stmt = $pdo->prepare("
    SELECT 
      p.id,
      p.code,
      p.label,
      p.module,
      COALESCE(up.allowed, rp.permission_id IS NOT NULL) AS allowed
    FROM permissions p
    LEFT JOIN role_permissions rp
      ON rp.permission_id = p.id
     AND rp.role_id = (SELECT role_id FROM users WHERE id = ?)
    LEFT JOIN user_permissions up
      ON up.permission_id = p.id
     AND up.user_id = ?
    ORDER BY p.module, p.label
  ");
  $stmt->execute([$userId, $userId]);

  json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
  break;


  /* ======================================================
     SAVE USER PERMISSIONS (OVERRIDES)
  ====================================================== */
  case 'save_permissions':
    $userId = (int) post('user_id');
    $perms  = post('permissions', []);

    if (!$userId) {
      json_response(['error' => 'Invalid user ID'], 400);
    }

    $pdo->prepare("
      DELETE FROM user_permissions WHERE user_id = ?
    ")->execute([$userId]);

    if (!empty($perms)) {
      $stmt = $pdo->prepare("
        INSERT INTO user_permissions (user_id, permission_id, allowed)
        VALUES (?, ?, 1)
      ");

      foreach ($perms as $pid) {
        $stmt->execute([$userId, (int)$pid]);
      }
    }

    json_response(['status' => 'permissions_saved']);
    break;

  /* ======================================================
     FALLBACK
  ====================================================== */
  default:
    json_response(['error' => 'Invalid action'], 400);
}
