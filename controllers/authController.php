<?php
session_start();
require '../config/db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // ⚠️ Plain text comparison — DO NOT USE IN PRODUCTION
    if ($user && $user['password'] === $password) {
        $_SESSION['user'] = $user;
        header('Location: ../views/dashboard.php');
        exit;
    } else {
        echo "Invalid credentials.";
    }
}
