<?php include('../config/db.php'); ?>
<?php include('../includes/header.php'); ?>

<style>
body {
  background: #f4f7fa;
  font-family: 'Segoe UI', sans-serif;
}

.login-container {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-box {
  background: #ffffff;
  border: 1px solid #ddd;
  padding: 40px;
  max-width: 400px;
  width: 100%;
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.05);
}

.login-box h2 {
  margin-bottom: 20px;
  color: #0d47a1;
  font-weight: bold;
  text-align: center;
}

.login-box input[type="email"],
.login-box input[type="password"] {
  width: 100%;
  padding: 12px 10px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 15px;
  transition: border-color 0.2s ease;
}

.login-box input:focus {
  border-color: #0d47a1;
  outline: none;
}

.login-box button[type="submit"] {
  width: 100%;
  padding: 12px;
  background-color: #0d47a1;
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.login-box button[type="submit"]:hover {
  background-color: #1565c0;
}
</style>

<div class="login-container">
  <form method="POST" action="../controllers/authController.php" class="login-box">
    <h2>Login to Toby POS</h2>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
  </form>
</div>

<?php include('../includes/footer.php'); ?>
