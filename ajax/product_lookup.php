<?php
require_once('../config/db.php');


$search = $_GET['q'] ?? '';

$stmt = $pdo->prepare("
    SELECT p.id, p.name,
        IFNULL(SUM(CASE WHEN i.type = 'in' THEN i.quantity ELSE -i.quantity END), 0) AS stock
    FROM products p
    LEFT JOIN inventories i ON p.id = i.product_id
    WHERE p.name LIKE ?
    GROUP BY p.id
    ORDER BY p.name
    LIMIT 50
");
$stmt->execute(["%$search%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return as Select2 format
echo json_encode([
    "results" => array_map(function ($prod) {
        return [
            "id" => $prod['id'],
            "text" => $prod['name'] . " (Stock: " . $prod['stock'] . ")"
        ];
    }, $results)
]);
