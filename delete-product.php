<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: product-list.php');
    exit();
}

try {
    // Get product details to delete image file
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: product-list.php?error=Product not found');
        exit();
    }

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);

    // Delete image file if it exists
    if ($product['image'] && file_exists($product['image'])) {
        unlink($product['image']);
    }

    header('Location: product-list.php?success=Product deleted successfully');
    exit();

} catch (PDOException $e) {
    header('Location: product-list.php?error=Failed to delete product');
    exit();
}
?>