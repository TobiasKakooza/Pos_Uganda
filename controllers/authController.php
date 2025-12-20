<?php
// authController.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT id, name, email, password, role_id, avatar
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role_id']   = $user['role_id'];
        $_SESSION['user']      = $user;

        /* 🔀 ROLE SWITCH */
        switch ((int)$user['role_id']) {

            case 1: // ADMIN
                header('Location: /POS_UG/views/dashboard.php');
                break;

            case 2: // CASHIER
                header('Location: /POS_UG/views/Cashier/index.php');
                break;

            case 3: // INVENTORY MANAGER
                header('Location: /POS_UG/views/Inventory/index.php');
                break;

            default:
                session_destroy();
                $_SESSION['login_error'] = 'Access not allowed';
                header('Location: /POS_UG/views/login.php');
        }
        exit;
    }

    $_SESSION['login_error'] = 'Invalid email or password';
    header('Location: /POS_UG/views/login.php');
    exit;
}
?>