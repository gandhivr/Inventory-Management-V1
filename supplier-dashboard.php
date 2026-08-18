<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get supplier statistics
try {
    // Count total products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
    $stmt->execute([$user_id]);
    $total_products = $stmt->fetchColumn();

    // Count low stock products (less than 10)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ? AND stock_quantity < 10");
    $stmt->execute([$user_id]);
    $low_stock_count = $stmt->fetchColumn();

    // Total revenue from orders
    $stmt = $pdo->prepare("
        SELECT SUM(oi.price * oi.quantity) 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // Count total orders for supplier's products
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_orders = $stmt->fetchColumn();

    // Recent products
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE supplier_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Low stock products
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE supplier_id = ? AND stock_quantity < 10 
        ORDER BY stock_quantity ASC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders for supplier's products
    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, o.created_at, u.username as buyer_name,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        JOIN users u ON o.user_id = u.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top selling products
    $stmt = $pdo->prepare("
        SELECT p.name, p.price, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as revenue
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        WHERE p.supplier_id = ?
        GROUP BY p.id
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_products = 0;
    $low_stock_count = 0;
    $total_revenue = 0;
    $total_orders = 0;
    $recent_products = [];
    $low_stock_products = [];
    $recent_orders = [];
    $top_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - Inventory Management System</title>
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="add-product.php">Add Product</a>
                <a href="product-list.php">My Products</a>
                <a href="supplier-orders.php">Orders</a>
                <a href="supplier-analytics.php">Analytics</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Supplier)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <h2>Supplier Dashboard</h2>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card products">
                    <div class="stat-icon products">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                    <a href="product-list.php" class="btn btn-primary">Manage Products</a>
                </div>

                <div class="stat-card orders">
                    <div class="stat-icon out-stock">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3><?php echo $low_stock_count; ?></h3>
                    <p>Low Stock Items</p>
                    <small>Items with less than 10 in stock</small>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>

                <div class="stat-card orders">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Orders</p>
                    <a href="supplier-orders.php" class="btn btn-primary">View Orders</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Products -->
                <div class="dashboard-card">
                    <h3>Recent Products</h3>
                    <?php if (empty($recent_products)): ?>
                        <p>No products found.</p>
                        <a href="add-product.php" class="btn">Add Your First Product</a>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($recent_products as $product): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-right: 1rem;">
                                        <?php else: ?>
                                            <div class="product-placeholder" style="width: 50px; height: 50px; background: rgba(0, 242, 254, 0.1); border-radius: 8px; margin-right: 1rem; display: flex; align-items: center; justify-content: center; color: var(--cyan);"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <p>₹<?php echo number_format($product['price'], 2); ?> - Stock: <?php echo $product['stock_quantity']; ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="update-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="product-list.php" class="btn btn-primary">View All Products</a>
                    <?php endif; ?>
                </div>

                <!-- Low Stock Alert -->
                <div class="dashboard-card alert-card">
                    <h3><i class="fas fa-exclamation-triangle text-danger"></i> Low Stock Alert</h3>
                    <?php if (empty($low_stock_products)): ?>
                        <p>All products have sufficient stock.</p>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; margin-right: 1rem;">
                                        <?php else: ?>
                                            <div class="product-placeholder" style="width: 40px; height: 40px; background: rgba(255, 0, 110, 0.1); border-radius: 8px; margin-right: 1rem; display: flex; align-items: center; justify-content: center; color: var(--pink);"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <p style="color: var(--pink) !important; font-weight: bold;">Only <?php echo $product['stock_quantity']; ?> left!</p>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="update-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger">Restock</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <h3><i class="fas fa-shopping-cart text-success"></i> Recent Orders</h3>
                    <?php if (empty($recent_orders)): ?>
                        <p>No orders found for your products.</p>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <strong>Order #<?php echo $order['id']; ?></strong>
                                        <p>Buyer: <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                                        <p><?php echo htmlspecialchars($order['products']); ?></p>
                                        <p><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="order-amount">
                                        <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="supplier-orders.php" class="btn btn-primary">View All Orders</a>
                    <?php endif; ?>
                </div>

                <!-- Top Selling Products -->
                <div class="dashboard-card">
                    <h3><i class="fas fa-trophy text-warning"></i> Top Selling Products</h3>
                    <?php if (empty($top_products)): ?>
                        <p>No sales data available.</p>
                    <?php else: ?>
                        <div class="scrollable-content">
                            <?php foreach ($top_products as $product): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <p>₹<?php echo number_format($product['price'], 2); ?> each</p>
                                    </div>
                                    <div class="product-stats">
                                        <strong><?php echo $product['total_sold']; ?> sold</strong>
                                        <p>₹<?php echo number_format($product['revenue'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt text-warning"></i> Quick Actions</h3>
                <div class="quick-actions-grid">
                    <a href="add-product.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                    <a href="product-list.php" class="btn btn-primary">
                        <i class="fas fa-boxes"></i> Manage Products
                    </a>
                    <a href="supplier-orders.php" class="btn btn-info">
                        <i class="fas fa-shopping-cart"></i> View Orders
                    </a>
                    <a href="supplier-analytics.php" class="btn btn-warning">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                </div>
            </div>

            <!-- Export Reports -->
            <div class="dashboard-card">
                <h3><i class="fas fa-download"></i> Export Reports</h3>
                <p style="margin-bottom: 1.5rem; color: #cbd5e1;">Download your data in CSV or PDF format</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="background: rgba(16, 185, 129, 0.1); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <h4 style="margin: 0 0 1rem 0; color: #f8fafc;"><i class="fas fa-boxes"></i> Products Report</h4>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="supplier-export-products-csv.php" class="btn btn-success" style="flex: 1; font-size: 0.875rem;">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="supplier-export-products-pdf.php" target="_blank" class="btn btn-primary" style="flex: 1; font-size: 0.875rem;">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                    <div style="background: rgba(59, 130, 246, 0.1); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid rgba(59, 130, 246, 0.3);">
                        <h4 style="margin: 0 0 1rem 0; color: #f8fafc;"><i class="fas fa-shopping-cart"></i> Orders Report</h4>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="supplier-export-orders-csv.php" class="btn btn-success" style="flex: 1; font-size: 0.875rem;">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="supplier-export-orders-pdf.php" target="_blank" class="btn btn-primary" style="flex: 1; font-size: 0.875rem;">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>