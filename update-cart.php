<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    if ($cart_id <= 0 || $quantity <= 0) {
        header('Location: cart.php?error=Invalid cart item or quantity');
        exit();
    }

    try {
        // Verify cart item belongs to current user and get product stock
        $stmt = $pdo->prepare("
            SELECT c.id, p.stock_quantity 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_id, $user_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart_item) {
            header('Location: cart.php?error=Cart item not found');
            exit();
        }

        if ($quantity > $cart_item['stock_quantity']) {
            header('Location: cart.php?error=Quantity exceeds available stock');
            exit();
        }

        // Update cart item quantity
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $cart_id]);

        header('Location: cart.php?success=Cart updated successfully');
        exit();

    } catch (PDOException $e) {
        header('Location: cart.php?error=Failed to update cart');
        exit();
    }
} else {
    header('Location: cart.php');
    exit();
}
?>