<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
require_once('../../config/db.php');

// Get category ID from URL
if (!isset($_GET['id'])) {
    echo "Missing category ID.";
    exit;
}

$category_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    echo "Category not found.";
    exit;
}
?>

<div class="container">
    <h2>✏️ Edit Category</h2>

    <form action="../../controllers/categoryController.php" method="POST">
        <input type="hidden" name="id" value="<?= $category['id'] ?>">

        <label>Category Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"><?= htmlspecialchars($category['description']) ?></textarea><br><br>

        <button type="submit" name="update_category">💾 Update Category</button>
    </form>

    <p><a href="list.php">← Back to Category List</a></p>
</div>

<?php include('../../includes/footer.php'); ?>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="../../assets/js/main.js" defer></script>
