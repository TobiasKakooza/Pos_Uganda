<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* ===============================
   1. BASIC LOGIN CHECK
================================ */
if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: /POS_UG/views/login.php');
    exit;
}

/* ===============================
   2. CURRENT USER HELPER
================================ */
function current_user(): array
{
    return [
        'id'      => (int) $_SESSION['user_id'],
        'name'    => $_SESSION['user_name'] ?? '',
        'role_id' => (int) $_SESSION['role_id']
    ];
}

/* ===============================
   3. PERMISSION CHECK (SINGLE SOURCE)
================================ */
function can(string $permissionCode): bool
{
    global $pdo;

    static $cache = [];

    $userId = (int) $_SESSION['user_id'];
    $roleId = (int) $_SESSION['role_id'];

    $cacheKey = $userId . ':' . $permissionCode;

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM permissions p
        LEFT JOIN user_permissions up
            ON up.permission_id = p.id
           AND up.user_id = :uid
        LEFT JOIN role_permissions rp
            ON rp.permission_id = p.id
           AND rp.role_id = :role
        WHERE p.code = :code
          AND COALESCE(up.allowed, rp.permission_id IS NOT NULL) = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':uid'  => $userId,
        ':role' => $roleId,
        ':code' => $permissionCode
    ]);

    return $cache[$cacheKey] = (bool) $stmt->fetchColumn();
}

/* ===============================
   4. HARD BLOCK HELPER
================================ */
function require_permission(string $permissionCode): void
{
    if (!can($permissionCode)) {
        http_response_code(403);
        echo "<h3 style='padding:2rem;color:red'>Access Denied</h3>";
        exit;
    }
}
