<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0 || $quantity <= 0) {
        header('Location: product-list.php?error=Invalid product or quantity');
        exit();
    }

    try {
        // Check if product exists and has enough stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: product-list.php?error=Product not found');
            exit();
        }

        if ($product['stock_quantity'] < $quantity) {
            header('Location: product-list.php?error=Not enough stock available');
            exit();
        }

        // Check if item already exists in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // Update existing cart item
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($new_quantity > $product['stock_quantity']) {
                header('Location: product-list.php?error=Cannot add more items than available stock');
                exit();
            }

            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $cart_item['id']]);
        } else {
            // Add new cart item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        header('Location: cart.php?success=Item added to cart successfully');
        exit();

    } catch (PDOException $e) {
        header('Location: product-list.php?error=Failed to add item to cart');
        exit();
    }
} else {
    header('Location: product-list.php');
    exit();
}
?>