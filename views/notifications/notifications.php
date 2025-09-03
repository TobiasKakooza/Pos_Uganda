<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
require_once('../../config/db.php');

// Fetch all notifications
$stmt = $pdo->query("
    SELECT n.id, n.message, n.created_at, p.name AS product_name 
    FROM notifications n
    JOIN products p ON n.product_id = p.id
    ORDER BY n.created_at DESC
");
$notifications = $stmt->fetchAll();
?>

<div class="container">
    <h2>🔔 All Notifications</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Message</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?= date('Y-m-d H:i', strtotime($n['created_at'])) ?></td>
                    <td><?= htmlspecialchars($n['product_name']) ?></td>
                    <td><?= htmlspecialchars($n['message']) ?></td>
                    <td><?= $n['is_read'] ? '✅ Read' : '🕓 Unread' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('../../includes/footer.php'); ?>
<script>
    // Example route: mark as read
$markStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$markStmt->execute([$id]);

    </script>