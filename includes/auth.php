<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/login.php');
    exit;
}
