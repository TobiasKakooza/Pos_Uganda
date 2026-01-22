<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? [];
$userId = $user['id'] ?? 0;
$roleId = $user['role_id'] ?? 0;

/* Resolve role name */
$roleName = match ($roleId) {
    1 => 'admin',
    2 => 'cashier',
    3 => 'inventory',
    default => null
};

/* Fetch unread notifications for THIS user / role */
$stmt = $pdo->prepare("
    SELECT id, message, link, created_at
    FROM notifications
    WHERE is_read = 0
      AND (target_role = :role OR user_id = :uid)
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([
    ':role' => $roleName,
    ':uid'  => $userId
]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);

/* Profile image fallback */
$avatar = $user['avatar'] ?? '/POS_UG/assets/images/avatar-default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Toby POS</title>

<link rel="stylesheet" href="/POS_UG/assets/css/style.css">
<link rel="stylesheet" href="/POS_UG/assets/css/inventory.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/POS_UG/assets/js/main.js" defer></script>
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>


<style>
/* =====================================================
   DARK HEADER – NOTIFICATIONS + PROFILE
===================================================== */
/* :root {
  --bg-header: #020617;
  --bg-panel: #0f172a;
  --bg-hover: #1e293b;
  --border: #1f2a44;
  --text-main: #e5e7eb;
  --text-muted: #94a3b8;
  --danger: #ef4444;
} */

/* ===== HEADER ===== */
header {
  position: fixed;
  inset: 0 0 auto 0;
  height: 64px;
   background: var(--bg-header);
   border-bottom: 1px solid var(--border);
   color: var(--text-main);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  z-index: 1200;
  box-shadow: 0 10px 30px rgba(0,0,0,.6);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 17px;
  font-weight: 600;
}

/* ===== ACTIONS ===== */
.header-actions {
  display: flex;
  align-items: center;
  gap: 18px;
}

/* ===== NOTIFICATIONS ===== */
.notif-wrapper { position: relative; }

.notif-bell {
  background: transparent;
  border: none;
  font-size: 22px;
  cursor: pointer;
  color: var(--text-main);
  padding: 6px;
  border-radius: 10px;
}

.notif-bell:hover {
  background: var(--bg-hover);
}

.notif-dot {
  position: absolute;
  top: -4px;
  right: -6px;
  min-width: 18px;
  height: 18px;
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  border-radius: 999px;
  display: grid;
  place-items: center;
  box-shadow: 0 0 10px rgba(239,68,68,.9);
}

#notifModal {
  display: flex;
  align-items: center;
  justify-content: center;
}

.hidden {
  display: none !important;
}


/* ===== DROPDOWNS ===== */
.dropdown {
  position: absolute;
  right: 0;
  top: 48px;
  width: 280px;
  background: linear-gradient(180deg, #0f172a, #020617);
  border-radius: 14px;
  border: 1px solid var(--border);
  box-shadow: 0 30px 70px rgba(0,0,0,.75);
  overflow: hidden;
  z-index: 2000;
}

.dropdown h4 {
  margin: 0;
  padding: 14px 16px;
  font-size: 14px;
  border-bottom: 1px solid var(--border);
}

.dropdown a {
  display: flex;
  gap: 10px;
  padding: 12px 16px;
  font-size: 13px;
  color: var(--text-main);
  text-decoration: none;
}

.dropdown a:hover {
  background: var(--bg-hover);
}

.hidden { display: none; }

/* ===== PROFILE ===== */
.profile-wrapper { position: relative; }

.profile-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-main);
}

.profile-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border);
}

.profile-name {
  font-size: 13px;
  color: var(--text-muted);
}

/* ===== MAIN OFFSET ===== */
main {
  margin-left: 220px;
  margin-top: 0px;
  padding: 24px;
  min-height: calc(100vh - 64px);
  background: var(--bg-app);
}



.app-logo {
  width: 36px;
  height: 36px;
  border-radius: 8px;
}

.app-name {
  font-size: 15px;
  font-weight: 600;
  letter-spacing: .3px;
  color: var(--text-main);
  opacity: .95;
}
.notif-bell i {
  width: 20px;
  height: 20px;
  stroke-width: 2;
}
.profile-btn i {
  width: 16px;
  height: 16px;
  opacity: .6;
}
.dropdown a i {
  width: 16px;
  height: 16px;
  opacity: .75;
}

.danger-link {
  color: #ef4444 !important;
}
.header-actions {
  margin-left: auto;
}
/* 🌞 LIGHT MODE */
body[data-theme="light"] {
  --bg-header: #ffffff;
  --bg-hover: #f1f5f9;
  --border: #e5e7eb;
  --text-main: #0f172a;
  --text-muted: #64748b;
}

/* 🌙 DARK MODE */
body[data-theme="dark"] {
  --bg-header: #020617;
  --bg-hover: #1e293b;
  --border: #1e293b;
  --text-main: #e5e7eb;
  --text-muted: #94a3b8;
}

</style>
</head>

<body data-theme="light">


<header>

  <div class="header-title">
  <img src="/POS_UG/assets/images/logo-pos.jpg" class="app-logo">
  <span class="app-name">Toby POS</span>
</div>

<button id="themeToggle" class="btn ghost">
  <i data-lucide="moon"></i>
</button>

  <div class="header-actions">

    <!-- 🔔 Notifications -->
<div class="notif-wrapper">
  <button id="notifBell" class="notif-bell">
    <i data-lucide="bell"></i>
    <?php if ($notifCount): ?>
      <span class="notif-dot"><?= $notifCount ?></span>
    <?php endif; ?>
  </button>

  <div id="notifDropdown" class="dropdown hidden">
    <h4>Notifications</h4>

    <?php if (!$notifications): ?>
      <div style="padding:16px;color:#94a3b8;text-align:center">
        No new notifications
      </div>
    <?php endif; ?>

    <?php foreach ($notifications as $n): ?>
      <a href="#"
   class="notif-item"
   data-id="<?= $n['id'] ?>"
   data-message="<?= htmlspecialchars($n['message'], ENT_QUOTES) ?>"
   data-time="<?= date('Y-m-d H:i', strtotime($n['created_at'])) ?>">
  <?= htmlspecialchars($n['message']) ?>
  <small style="color:#64748b">
    <?= date('H:i d M', strtotime($n['created_at'])) ?>
  </small>
</a>

    <?php endforeach; ?>

    <a href="/POS_UG/views/notifications/notifications.php">
      View all →
    </a>
  </div>
</div>


    <!-- 👤 PROFILE -->
    <div class="profile-wrapper">
     <button id="profileBtn" class="profile-btn">
  <img src="<?= $avatar ?>" class="profile-avatar">
  <span class="profile-name"><?= htmlspecialchars($user['name']) ?></span>
  <i data-lucide="chevron-down"></i>
</button>


      <div id="profileDropdown" class="dropdown hidden">
<a href="/POS_UG/views/users/profile.php">
  <i data-lucide="user"></i> My Profile
</a>
<a href="/POS_UG/controllers/logout.php" class="danger-link">
  <i data-lucide="log-out"></i> Logout
</a>

      </div>
    </div>

  </div>
</header>


<!-- Notification View Modal -->
<div id="notifModal" class="hidden" style="
  position:fixed; inset:0;
  background:rgba(0,0,0,.6);
  z-index:3000;
">

  <div style="
    background:#0f172a; color:#e5e7eb;
    width:460px; border-radius:14px;
    padding:22px;
    box-shadow:0 30px 80px rgba(0,0,0,.9);
  ">
    <h3 style="margin-top:0">🔔 Notification Details</h3>

    <p id="notifModalMsg" style="margin:12px 0;font-size:15px"></p>

    <div id="notifExtraDetails"
         style="font-size:13px;color:#94a3b8;margin-bottom:12px">
      <!-- populated dynamically -->
    </div>

    <small id="notifModalTime" style="color:#64748b"></small>

    <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end">
      <button id="notifMarkRead" class="btn btn-primary">
        Mark as Read
      </button>
      <button id="notifClose" class="btn">
        Close
      </button>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/navbar.php'; ?>

<main>

<script>
const notifBell = document.getElementById('notifBell');
const notifDropdown = document.getElementById('notifDropdown');
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById('profileDropdown');

notifBell?.addEventListener('click', e => {
  e.stopPropagation();
  notifDropdown.classList.toggle('hidden');
  profileDropdown.classList.add('hidden');
});

profileBtn?.addEventListener('click', e => {
  e.stopPropagation();
  profileDropdown.classList.toggle('hidden');
  notifDropdown.classList.add('hidden');
});

document.addEventListener('click', () => {
  notifDropdown.classList.add('hidden');
  profileDropdown.classList.add('hidden');
});
</script>
<script>
  lucide.createIcons();
</script>
<script>
(function () {
  const key = 'pos-theme';
  const body = document.body;
  const btn = document.getElementById('themeToggle');
  const icon = btn?.querySelector('i');

  const saved = localStorage.getItem(key) || 'light';
  body.dataset.theme = saved;
  updateIcon(saved);

  btn?.addEventListener('click', () => {
    const next = body.dataset.theme === 'dark' ? 'light' : 'dark';
    body.dataset.theme = next;
    localStorage.setItem(key, next);
    updateIcon(next);
  });

  function updateIcon(theme) {
    if (!icon) return;
    icon.setAttribute('data-lucide', theme === 'dark' ? 'sun' : 'moon');
    lucide.createIcons();
  }
})();
</script>


<script>
let activeNotifId = null;

/* ✅ EVENT DELEGATION — works on dashboard & notifications page */
document.addEventListener('click', function (e) {
  const item = e.target.closest('.notif-item');
  if (!item) return;

  e.preventDefault();

  activeNotifId = item.dataset.id;

  document.getElementById('notifModalMsg').textContent =
    item.dataset.message;

  document.getElementById('notifModalTime').textContent =
    'Created at: ' + item.dataset.time;

  document.getElementById('notifExtraDetails').innerHTML = 'Loading details…';

  fetch(`/POS_UG/controllers/notificationsDetails.php?id=${activeNotifId}`)
    .then(r => r.json())
    .then(d => {
      if (!d.success) {
        document.getElementById('notifExtraDetails').innerHTML = 'No details available';
        return;
      }

      document.getElementById('notifExtraDetails').innerHTML = `
        <div>👤 Cashier: <strong>${d.cashier}</strong></div>
        <div>🛒 Items: <strong>${d.product}</strong></div>
        <div>💰 Total: <strong>UGX ${Number(d.price).toLocaleString()}</strong></div>
      `;
    });

  document.getElementById('notifModal').classList.remove('hidden');
  document.getElementById('notifDropdown')?.classList.add('hidden');
});

/* ✅ Close modal */
document.getElementById('notifClose').onclick = () => {
  document.getElementById('notifModal').classList.add('hidden');
  activeNotifId = null;
  document.getElementById('notifExtraDetails').innerHTML = '';
};

/* ✅ Mark as read (NO redirect, UX-friendly) */
document.getElementById('notifMarkRead').onclick = () => {
  if (!activeNotifId) return;

  fetch(`/POS_UG/controllers/notificationActions.php?action=read&id=${activeNotifId}`)
    .then(r => r.json())
    .then(() => {
      document.getElementById('notifModal').classList.add('hidden');

      // Visually update items without reload
      document.querySelectorAll(`[data-id="${activeNotifId}"]`)
        .forEach(el => {
          const row = el.closest('tr');
          if (row) row.style.background = '';
        });

      // Remove bell badge if needed
      const dot = document.querySelector('.notif-dot');
      if (dot) dot.remove();

      activeNotifId = null;
    });
};
</script>
