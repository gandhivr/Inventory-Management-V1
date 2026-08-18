<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$cart_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cart_id <= 0) {
    header('Location: cart.php');
    exit();
}

try {
    // Verify cart item belongs to current user
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        header('Location: cart.php?error=Cart item not found');
        exit();
    }

    // Delete cart item
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);

    header('Location: cart.php?success=Item removed from cart');
    exit();

} catch (PDOException $e) {
    header('Location: cart.php?error=Failed to remove item from cart');
    exit();
}
?>