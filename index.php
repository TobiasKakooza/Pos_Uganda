<?php
// Landing page only – no app logic here
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Toby POS — Smart Point of Sale for Modern Retail</title>

    <meta name="description" content="Toby POS is a modern, fast, and reliable point of sale system built for retail shops, supermarkets, and growing businesses.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Styles -->
    <link rel="stylesheet" href="landing/style/landing.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<!-- ================= HEADER / NAV ================= -->
<header class="site-header">
    <nav class="nav container">
        <div class="brand">
            <span class="brand-name">Toby</span><span class="brand-accent">POS</span>
        </div>

        <!-- Desktop Nav -->
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#preview">Preview</a>
            <a href="#contact">Contact</a>
            <a href="/POS_UG/views/login.php" class="btn-outline">Login</a>
        </div>

        <!-- Mobile Toggle -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-nav" id="mobileNav">
        <a href="#features">Features</a>
        <a href="#preview">Preview</a>
        <a href="#contact">Contact</a>
        <a href="/POS_UG/views/login.php" class="btn-primary">Login</a>
    </div>
</header>

<!-- ================= HERO ================= -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-text">
           <h1>
    Smart Point of Sale<br>
    <span>Built for Real Businesses</span>
</h1>


            <p>
                Toby POS helps retailers manage walk-in sales, inventory,
                suppliers, and reports with speed, accuracy, and clarity.
                Designed for daily use — not complexity.
            </p>

            <div class="hero-actions">
                <a href="#preview" class="btn-primary">View Live Screens</a>
                <a href="#contact" class="btn-secondary">Request Demo</a>
            </div>
        </div>

        <div class="hero-visual">
            <img src="landing/images/dash1.png" alt="Toby POS Dashboard">
        </div>
    </div>
</section>

<!-- ================= FEATURES ================= -->
<section id="features" class="section">
    <div class="container">
        <h2 class="section-title">Why Choose Toby POS?</h2>
        <p class="section-subtitle">
            Everything you need to run a retail store — without unnecessary noise.
        </p>

        <div class="features-grid">
            <div class="feature-card">
                <h3>Fast Walk-In Sales</h3>
                <p>
                    Optimized cashier workflow for walk-in customers.
                    Create sales, print receipts, and move on instantly.
                </p>
            </div>

            <div class="feature-card">
                <h3>Inventory Management</h3>
                <p>
                    Track stock movements, low-stock alerts,
                    purchase receipts, and adjustments in real time.
                </p>
            </div>

            <div class="feature-card">
                <h3>Supplier & Expenses</h3>
                <p>
                    Manage suppliers, purchase orders, bills,
                    and daily business expenses from one system.
                </p>
            </div>

            <div class="feature-card">
                <h3>Reports & Insights</h3>
                <p>
                    Daily sales reports, stock valuation,
                    profit tracking, and performance visibility.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ================= PREVIEW ================= -->
<section id="preview" class="section section-dark">
    <div class="container">
        <h2 class="section-title">Application Preview</h2>
        <p class="section-subtitle">
            Clean interface. Practical design. Built for speed.
        </p>

  <div class="preview-grid">
    <img src="landing/images/dash1.png" alt="Dashboard Overview">
    <img src="landing/images/dash2.png" alt="Analytics Dashboard">
    <img src="landing/images/prod1.png" alt="Products Management">
    <img src="landing/images/terminal.png" alt="POS Terminal">
    <img src="landing/images/expense1.png" alt="Expenses Management">
    <img src="landing/images/expense2.png" alt="Expense Reports">
</div>


    </div>
</section>

<!-- ================= CTA ================= -->
<section id="contact" class="cta">
    <div class="container cta-box">
        <h2>Ready for a Better POS Experience?</h2>
        <p>
            Toby POS is built for developers, shop owners,
            and businesses looking for a serious, extendable solution.
        </p>

        <a href="mailto:youremail@example.com" class="btn-primary">
            Contact the Developer
        </a>
    </div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="footer">
    <div class="container footer-content">
        <p>© <?= $year ?> Toby POS. All rights reserved.</p>
        <p class="footer-note">
            A modern point of sale system designed for real-world retail.
        </p>
    </div>
</footer>

<script src="landing/js/landing.js"></script>

</body>
</html>
