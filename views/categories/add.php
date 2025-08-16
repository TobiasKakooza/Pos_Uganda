<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
?>

<div class="container">
    <h2>➕ Add New Category</h2>

    <form action="../../controllers/categoryController.php" method="POST">
        <label>Category Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" placeholder="Optional..."></textarea><br><br>

        <button type="submit" name="add_category">✅ Save Category</button>
    </form>

    <p><a href="list.php">← Back to Category List</a></p>
</div>

<?php include('../../includes/footer.php'); ?>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="../../assets/js/main.js" defer></script>
