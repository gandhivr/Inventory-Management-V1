<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock_quantity
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

} catch (PDOException $e) {
    $cart_items = [];
    $total_amount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Inventory Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/image-lightbox.css">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
    <style>
        /* Fix for cart form elements */
        .quantity-controls {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #667eea !important;
            border-radius: 12px !important;
            padding: 0.75rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            margin-bottom: 1rem !important;
        }
        
        .quantity-input {
            width: 70px !important;
            text-align: center !important;
            border: none !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            color: #2d3748 !important;
            background: transparent !important;
        }
        
        .quantity-input:focus {
            outline: none !important;
        }
        
        .remove-btn {
            background: linear-gradient(135deg, #e53e3e, #c53030) !important;
            color: white !important;
            border: none !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
        }
        
        .remove-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3) !important;
            color: white !important;
        }
        
        .item-total {
            font-size: 1.2rem !important;
            font-weight: 800 !important;
            color: #2d3748 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .item-actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 1rem !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="container">
                <h1>Inventory Management System</h1>
                <nav>
                    <a href="index.php">Home</a>
                    <a href="buyer-dashboard.php">Dashboard</a>
                    <a href="product-list.php">Browse Products</a>
                    <a href="buyer-orders.php">My Orders</a>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                    <a href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: var(--gray-900); font-family: 'Poppins', sans-serif;">🛒 My Shopping Cart</h2>
                <a href="product-list.php" class="btn">Continue Shopping</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="welcome">
                    <h3>🛍️ Your Cart is Empty</h3>
                    <p>Discover amazing products and add them to your cart!</p>
                    <div style="margin-top: 2rem;">
                        <a href="product-list.php" class="btn btn-success">Browse Products</a>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 8px; padding: 1rem;">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <?php if ($item['image']): ?>
                                    <div class="product-image-container" style="width: 80px; height: 80px; cursor: pointer; position: relative; border-radius: 4px; overflow: hidden;">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #666; font-size: 12px;">No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Price: <span class="price">₹<?php echo number_format($item['price'], 2); ?></span></p>
                                    <p>Available Stock: <?php echo $item['stock_quantity']; ?></p>
                                </div>
                            </div>

                            <div class="item-actions">
                                <form action="update-cart.php" method="POST" class="quantity-controls">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <label style="color: #2d3748; font-weight: 600;">Qty:</label>
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input">
                                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Update</button>
                                </form>

                                <div style="text-align: center;">
                                    <p class="item-total">Subtotal: ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    <a href="remove-from-cart.php?id=<?php echo $item['cart_id']; ?>" class="remove-btn" onclick="return confirm('Remove this item from cart?')">Remove</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-total">
                    <h3 style="color: var(--gray-900); font-size: 1.5rem; margin-bottom: 1rem;">💰 Cart Total: <span style="color: var(--primary-color);">₹<?php echo number_format($total_amount, 2); ?></span></h3>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="product-list.php" class="btn btn-secondary">🛍️ Continue Shopping</a>
                        <a href="checkout.php" class="btn btn-success">🚀 Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="js/script.js"></script>
    <script src="js/image-lightbox.js"></script>
</body>
</html>