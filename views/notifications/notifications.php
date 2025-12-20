<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';

/* =====================================================
   SECURITY
===================================================== */

$userId = $_SESSION['user_id'];
$roleId = $_SESSION['role_id'];

/* Role name resolver */
$roleName = match ($roleId) {
    1 => 'admin',
    2 => 'cashier',
    3 => 'inventory',
    default => null
};

/* =====================================================
   MARK AS READ (ONLY OWN NOTIFICATION)
===================================================== */
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
          AND (target_user_id = ? OR target_role = ?)
    ");
    $stmt->execute([
        (int)$_GET['read'],
        $userId,
        $roleName
    ]);

    header("Location: notifications.php");
    exit;
}

/* =====================================================
   FETCH NOTIFICATIONS (ROLE + USER SAFE)
===================================================== */

$where = [];
$args  = [];

/* Admin sees all */
if ($roleId === 1) {
    $where[] = "1=1";
}

/* Non-admins see ONLY targeted notifications */
else {
    $where[] = "(n.target_user_id = ? OR n.target_role = ?)";
    $args[]  = $userId;
    $args[]  = $roleName;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT 
        n.id,
        n.message,
        n.created_at,
        n.is_read,
        n.type,
        u.name AS actor
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    $whereSql
    ORDER BY n.created_at DESC
");

$stmt->execute($args);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container">
  <h2>🔔 Notifications</h2>

  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Triggered By</th>
        <th>Message</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>

    <tbody>
    <?php if (!$notifications): ?>
      <tr><td colspan="5">No notifications</td></tr>
    <?php endif; ?>

    <?php foreach ($notifications as $n): ?>
      <?php
        // 🔐 OPTION B: Normalize is_read safely
        $isRead = (int)($n['is_read'] ?? 0);
      ?>
      <tr style="<?= $isRead ? '' : 'background:#e3f2fd' ?>">
        <td><?= date('Y-m-d H:i', strtotime($n['created_at'])) ?></td>
        <td><?= htmlspecialchars($n['actor'] ?? 'System') ?></td>
        <td><?= htmlspecialchars($n['message']) ?></td>
        <td><?= $isRead ? '✅ Read' : '🕓 Unread' ?></td>
        <td style="white-space:nowrap">

  <!-- 🔍 VIEW (opens popup, does NOT navigate) -->
  <button
    class="btn btn-sm btn-info notif-item"
    data-id="<?= $n['id'] ?>"
    data-message="<?= htmlspecialchars($n['message'], ENT_QUOTES) ?>"
    data-time="<?= date('Y-m-d H:i', strtotime($n['created_at'])) ?>">
    View
  </button>

  <!-- ✅ MARK READ (explicit action only) -->
  <?php if (!$isRead): ?>
    <a href="?read=<?= $n['id'] ?>" class="btn btn-sm btn-primary">
      Mark read
    </a>
  <?php endif; ?>

</td>

      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
document.querySelectorAll('.notif-item').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();

    const id = btn.dataset.id;

    document.getElementById('notifModal').classList.remove('hidden');
    document.getElementById('notifModalMsg').textContent = btn.dataset.message;
    document.getElementById('notifModalTime').textContent =
      'Created at: ' + btn.dataset.time;

    fetch(`/POS_UG/controllers/notificationsDetails.php?id=${id}`)
      .then(r => r.json())
      .then(d => {
        if (!d.success) return;

        document.getElementById('notifExtraDetails').innerHTML = `
          <div>👤 Cashier: <strong>${d.cashier}</strong></div>
          <div>🛒 Items: <strong>${d.product}</strong></div>
          <div>💰 Total: <strong>UGX ${Number(d.price).toLocaleString()}</strong></div>
        `;
      });

    document.getElementById('notifMarkRead').onclick = () => {
      fetch(`/POS_UG/controllers/notificationActions.php?action=read&id=${id}`)
        .then(() => location.reload());
    };
  });
});
</script>
