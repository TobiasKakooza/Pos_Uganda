<?php
include('../../includes/auth.php');
include('../../includes/header.php');
include('../../includes/navbar.php');
require_once('../../config/db.php');

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll();
?>

<div class="container">
    <h2>📂 Category List</h2>
    <a href="add.php">➕ Add New Category</a><br><br>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Description</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $index => $cat): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= htmlspecialchars($cat['description']) ?></td>
                    <td><?= date('Y-m-d', strtotime($cat['created_at'])) ?></td>
                    <td>
                        <a href="edit.php?id=<?= $cat['id'] ?>">✏️ Edit</a>
                        |
                        <a href="../../controllers/categoryController.php?delete=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('../../includes/footer.php'); ?>
