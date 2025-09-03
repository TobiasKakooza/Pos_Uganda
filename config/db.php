<?php
$host = 'localhost:3307';
$db   = 'ugandan_pos_new';
$user = 'root';
$pass = '19941997'; // Replace with your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
 
} catch (\PDOException $e) {
    die('DB Connection Failed: ' . $e->getMessage());
}
