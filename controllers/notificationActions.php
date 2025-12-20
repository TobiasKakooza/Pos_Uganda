<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$roleId = $_SESSION['role_id'] ?? 0;

$roleName = match ($roleId) {
    1 => 'admin',
    2 => 'cashier',
    3 => 'inventory',
    default => null
};

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if (!$id || $action !== 'read') {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ?
      AND (user_id = ? OR target_role = ? OR ? = 'admin')
");

$stmt->execute([$id, $userId, $roleName, $roleName]);

echo json_encode(['success' => true]);
