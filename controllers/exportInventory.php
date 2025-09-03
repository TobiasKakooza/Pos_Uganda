<?php
require '../config/db.php';

$stmt = $pdo->prepare("
    SELECT i.id, i.quantity, i.type, i.note, i.stock_after, i.created_at, 
           p.name AS product_name 
    FROM inventories i 
    JOIN products p ON i.product_id = p.id 
    ORDER BY i.created_at DESC
");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
