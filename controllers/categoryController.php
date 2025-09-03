<?php
session_start();
require '../config/db.php';

// ADD CATEGORY
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if ($name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $_SESSION['success'] = "✅ Category added successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Error adding category: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Category name is required.";
    }

    header('Location: ../views/products/categories/list.php');
    exit;
}

// UPDATE CATEGORY
if (isset($_POST['update_category'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if ($id && $name) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $_SESSION['success'] = "✅ Category updated successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Error updating category: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Missing category ID or name.";
    }

    header('Location: ../views/products/categories/list.php');
    exit;
}

// DELETE CATEGORY
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "🗑️ Category deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Cannot delete category: It might be linked to a product.";
    }

    header('Location: ../views/products/categories/list.php');
    exit;
}
