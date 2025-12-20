<?php
// login.php MUST be standalone
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect
if (isset($_SESSION['user'])) {
    header("Location: /POS_UG/views/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Toby POS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ===============================
   GLOBAL RESET
================================ */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont;
  background: radial-gradient(circle at top, #0f172a, #020617);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e5e7eb;
}

/* ===============================
   LOGIN CONTAINER
================================ */
.login-wrapper {
  width: 100%;
  max-width: 420px;
  padding: 24px;
}

.login-card {
  background: linear-gradient(180deg, #0f172a, #020617);
  border-radius: 16px;
  padding: 36px 32px;
  box-shadow: 0 40px 80px rgba(0,0,0,.7);
  border: 1px solid #1f2a44;
}

/* ===============================
   LOGO
================================ */
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
}

.brand img {
  width: 48px;
  height: 48px;
  border-radius: 12px;
}

.brand span {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: .4px;
}

/* ===============================
   TITLE
================================ */
.login-title {
  text-align: center;
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 6px;
}

.login-subtitle {
  text-align: center;
  font-size: 14px;
  color: #94a3b8;
  margin-bottom: 28px;
}

/* ===============================
   FORM
================================ */
.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 13px;
  margin-bottom: 6px;
  color: #cbd5f5;
}

.form-group input {
  width: 100%;
  padding: 14px 14px;
  border-radius: 10px;
  border: 1px solid #1f2a44;
  background: #020617;
  color: #e5e7eb;
  font-size: 14px;
  transition: border .2s, box-shadow .2s;
}

.form-group input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,.25);
}

/* ===============================
   BUTTON
================================ */
.login-btn {
  width: 100%;
  padding: 14px;
  margin-top: 10px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #1e40af);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .15s ease, box-shadow .15s ease;
}

.login-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 30px rgba(37,99,235,.45);
}

/* ===============================
   FOOTER
================================ */
.login-footer {
  text-align: center;
  margin-top: 22px;
  font-size: 12px;
  color: #64748b;
}

/* ===============================
   ERROR MESSAGE
================================ */
.error-box {
  background: rgba(239,68,68,.15);
  border: 1px solid #ef4444;
  color: #fecaca;
  padding: 12px;
  border-radius: 10px;
  font-size: 13px;
  margin-bottom: 16px;
  text-align: center;
}
</style>
</head>

<body>

<div class="login-wrapper">
  <div class="login-card">

    <div class="brand">
      <img src="/POS_UG/assets/images/logo-pos.jpg" alt="Toby POS">
      <span>Toby POS</span>
    </div>

    <h2 class="login-title">Welcome Back</h2>
    <p class="login-subtitle">Sign in to continue to your dashboard</p>

    <?php if (!empty($_SESSION['login_error'])): ?>
      <div class="error-box">
        <?= htmlspecialchars($_SESSION['login_error']) ?>
      </div>
      <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>

    <form method="POST" action="/POS_UG/controllers/authController.php">

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" name="login" class="login-btn">
        Sign In
      </button>

    </form>

    <div class="login-footer">
      © <?= date('Y') ?> Toby POS · Secure Access
    </div>

  </div>
</div>

</body>
</html>
