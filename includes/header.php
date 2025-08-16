<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Toby POS</title>
  <link rel="stylesheet" href="../../assets/css/inventory.css">

  <link rel="stylesheet" href="/assets/css/style.css">

  <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script src="/POS_UG/assets/js/main.js" defer></script>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background-color: #f4f6f9;
      color: #333;
    }

    /* Header */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #1e88e5;
      color: white;
      padding: 0 24px;
      height: 64px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .header-title {
      font-size: 1.6rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-title img {
      width: 32px;
      height: 32px;
    }

    .header-user {
      font-size: 14px;
      font-weight: 400;
      opacity: 0.8;
    }

    /* Sidebar */
    nav {
      background-color: #0d47a1;
      width: 220px;
      position: fixed;
      top: 64px;
      left: 0;
      bottom: 0;
      overflow-y: auto;
      padding-top: 1rem;
      z-index: 999;
    }

    nav ul {
      list-style: none;
      padding: 0;
    }

    nav ul li {
      position: relative;
    }

    nav ul li a {
      display: block;
      padding: 12px 20px;
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    nav ul li a:hover,
    nav ul li a.active {
      background-color: #1565c0;
    }

    nav ul li ul {
      display: none;
      background-color: #1565c0;
    }

    nav ul li:hover > ul {
      display: block;
    }

    nav ul li ul li a {
      padding-left: 40px;
    }

    /* Content */
    .container, main {
      margin-left: 240px;
      margin-top: 80px;
      padding: 2rem;
      min-height: calc(100vh - 80px);
      background: white;
      border-radius: 8px;
      overflow-x: auto;
    }

    ul {
      list-style-type: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
      nav {
        position: relative;
        width: 100%;
        height: auto;
      }

      .container, main {
        margin-left: 0;
        margin-top: 100px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="header-title">
      <img src="/assets/images/logo-pos.jpg" alt="POS Logo"> <!-- optional icon -->
       POS System
    </div>
    <div class="header-user">
      Welcome, <?php echo $_SESSION['username'] ?? 'User'; ?>
    </div>
  </header>
