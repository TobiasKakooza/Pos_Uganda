<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$user = $_SESSION['user'];
$userId = $user['id'];

/* ---------------------------------------------------------
   FETCH USER (LATEST DATA)
--------------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.avatar, r.name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   AVATAR PATH
--------------------------------------------------------- */
$avatarPath = (!empty($profile['avatar']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $profile['avatar']))
    ? $profile['avatar']
    : '/POS_UG/uploads/avatars/default.png';

/* ---------------------------------------------------------
   UPDATE PROFILE INFO
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $userId]);

    $_SESSION['user']['name']  = $name;
    $_SESSION['user']['email'] = $email;

    $success = "Profile updated successfully";
}

/* ---------------------------------------------------------
   UPDATE PASSWORD
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {

    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);

        $success = "Password updated successfully";
    }
}

/* ---------------------------------------------------------
   AVATAR UPLOAD
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {

    if (!empty($_FILES['avatar']['name'])) {

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($_FILES['avatar']['type'], $allowed)) {
            $error = "Invalid image format";
        } else {

            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $fileName = "user_{$userId}." . $ext;
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/POS_UG/uploads/avatars/";
            $uploadPath = $uploadDir . $fileName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath);

            $dbPath = "/POS_UG/uploads/avatars/" . $fileName;

            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$dbPath, $userId]);

            $_SESSION['user']['avatar'] = $dbPath;

            header("Location: profile.php");
            exit;
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<style>
.profile-wrapper {
  max-width: 980px;
  margin: auto;
  background: #020617;
  border: 1px solid #1f2a44;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 30px 80px rgba(0,0,0,.7);
}

.profile-header {
  display: flex;
  gap: 30px;
  align-items: center;
}

.avatar-box {
  position: relative;
}

.avatar-box img {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #1e293b;
}

.avatar-box form {
  text-align: center;
  margin-top: 10px;
}

.profile-info {
  flex: 1;
}

.profile-info h2 {
  margin: 0;
}

.profile-info span {
  color: #94a3b8;
  font-size: 13px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
  margin-top: 24px;
}

.form-group label {
  font-size: 12px;
  color: #94a3b8;
}

.form-group input {
  width: 100%;
  padding: 10px;
  background: #0f172a;
  border: 1px solid #1f2a44;
  border-radius: 10px;
  color: #e5e7eb;
}

button {
  padding: 10px 16px;
  background: #3b82f6;
  border: none;
  color: #fff;
  border-radius: 10px;
  cursor: pointer;
}

button:hover {
  background: #2563eb;
}

.alert {
  margin-bottom: 14px;
  padding: 12px;
  border-radius: 10px;
  background: #022c22;
  color: #22c55e;
}
</style>

<div class="profile-wrapper">

  <?php if (!empty($success)): ?>
    <div class="alert"><?= $success ?></div>
  <?php endif; ?>

  <div class="profile-header">

    <div class="avatar-box">
      <img src="<?= $avatarPath ?>">
      <form method="post" enctype="multipart/form-data">
        <input type="file" name="avatar" required>
        <button name="upload_avatar">Upload Avatar</button>
      </form>
    </div>

    <div class="profile-info">
      <h2><?= htmlspecialchars($profile['name']) ?></h2>
      <span><?= htmlspecialchars($profile['role']) ?></span>
      <br>
      <span><?= htmlspecialchars($profile['email']) ?></span>
    </div>

  </div>

  <form method="post" class="form-grid">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>">
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>">
    </div>

    <button name="update_profile">Update Profile</button>
  </form>

  <form method="post" class="form-grid" style="margin-top:20px;">
    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="password">
    </div>
    <button name="update_password">Change Password</button>
  </form>

</div>

<?php include '../../includes/footer.php'; ?>
