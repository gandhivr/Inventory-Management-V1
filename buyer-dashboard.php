<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get buyer statistics
try {
    // Count items in cart
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items_count = $stmt->fetchColumn();

    // Count total orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_orders = $stmt->fetchColumn();

    // Total spent
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_spent = $stmt->fetchColumn() ?: 0;

    // Recent orders
    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at,
               COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? 
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cart items with product details
    $stmt = $pdo->prepare("
        SELECT c.quantity, p.name, p.price, p.image
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $cart_preview = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Featured/New products
    $stmt = $pdo->query("
        SELECT p.*, u.username as supplier_name 
        FROM products p 
        LEFT JOIN users u ON p.supplier_id = u.id 
        WHERE p.stock_quantity > 0
        ORDER BY p.created_at DESC 
        LIMIT 6
    ");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $cart_items_count = 0;
    $total_orders = 0;
    $total_spent = 0;
    $recent_orders = [];
    $cart_preview = [];
    $featured_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - Inventory Management System</title>
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
    <link rel="stylesheet" href="css/image-lightbox.css">
    <style>
        /* CRITICAL FIX: Force input field visibility */
        input[type="number"] {
            background: rgba(0, 242, 254, 0.2) !important;
            border: 2px solid #00f2fe !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
            text-align: center !important;
            padding: 0.5rem !important;
            border-radius: 8px !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.3) !important;
        }
        
        input[type="number"]:focus {
            background: rgba(0, 242, 254, 0.3) !important;
            border-color: #00f2fe !important;
            color: #ffffff !important;
            text-shadow: 0 0 15px rgba(255, 255, 255, 1) !important;
            box-shadow: 0 0 25px rgba(0, 242, 254, 0.6) !important;
            outline: none !important;
        }
        
        .product-actions input[type="number"] {
            width: 80px !important;
            background: rgba(57, 255, 20, 0.2) !important;
            border: 2px solid #39ff14 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.9) !important;
        }
        
        .product-actions input[type="number"]:focus {
            background: rgba(57, 255, 20, 0.3) !important;
            border-color: #39ff14 !important;
            box-shadow: 0 0 25px rgba(57, 255, 20, 0.6) !important;
            text-shadow: 0 0 15px rgba(255, 255, 255, 1) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="product-list.php">Browse Products</a>
                <a href="cart.php">My Cart (<?php echo $cart_items_count; ?>)</a>
                <a href="buyer-orders.php">My Orders</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <h2>Buyer Dashboard</h2>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Cart Items</h3>
                    <div class="stat-value"><?php echo $cart_items_count; ?></div>
                    <a href="cart.php" class="btn btn-primary">View Cart</a>
                </div>

                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <a href="buyer-orders.php" class="btn btn-primary">View Orders</a>
                </div>

                <div class="stat-card">
                    <h3>Total Spent</h3>
                    <div class="stat-value">₹<?php echo number_format($total_spent, 2); ?></div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <h3>Recent Orders</h3>
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state">
                            <p>No orders found.</p>
                            <a href="product-list.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <strong>Order #<?php echo $order['id']; ?></strong>
                                        <p><?php echo $order['item_count']; ?> items - <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="order-amount">
                                        <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        <p>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="buyer-orders.php" class="btn btn-primary" style="margin-top: 1rem;">View All Orders</a>
                    <?php endif; ?>
                </div>

                <!-- Cart Preview -->
                <div class="dashboard-card">
                    <h3>Cart Preview</h3>
                    <?php if (empty($cart_preview)): ?>
                        <div class="empty-state">
                            <p>Your cart is empty.</p>
                            <a href="product-list.php" class="btn btn-primary">Browse Products</a>
                        </div>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($cart_preview as $item): ?>
                                <div class="cart-item">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Product">
                                    <?php else: ?>
                                        <div class="cart-item-placeholder">
                                            <span>No Image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="cart-item-info">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <p>Qty: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <a href="cart.php" class="btn btn-outline">View Full Cart</a>
                            <a href="checkout.php" class="btn btn-success">Checkout</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Featured Products -->
            <div class="dashboard-card">
                <h3>New Products</h3>
                <?php if (empty($featured_products)): ?>
                    <div class="empty-state">
                        <p>No products available.</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($featured_products as $product): ?>
                            <div class="product-card">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-card-placeholder">
                                        <span>No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-card-content">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></div>
                                    
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <form action="add-to-cart.php" method="POST" class="product-actions">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Add to Cart</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="out-of-stock">Out of Stock</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="product-list.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="quick-actions-grid">
                    <a href="product-list.php" class="btn btn-primary">Browse Products</a>
                    <a href="cart.php" class="btn btn-outline">View Cart</a>
                    <a href="buyer-orders.php" class="btn btn-outline">Order History</a>
                    <a href="buyer-profile.php" class="btn btn-outline">My Profile</a>
                </div>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
    <script src="js/image-lightbox.js"></script>
</body>
</html>