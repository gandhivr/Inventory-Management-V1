<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get orders for supplier's products
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.id, o.total_amount, o.status, o.created_at, u.username as buyer_name,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ' × $', oi.price, ')') SEPARATOR '<br>') as supplier_items
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get supplier statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total_orders,
               SUM(oi.price * oi.quantity) as total_revenue,
               SUM(oi.quantity) as total_items_sold
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $orders = [];
    $stats = ['total_orders' => 0, 'total_revenue' => 0, 'total_items_sold' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Supplier Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- This links your PHP file to the external CSS file -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="supplier-dashboard.php">Dashboard</a>
                <a href="add-product.php">Add Product</a>
                <a href="product-list.php">My Products</a>
                <span class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Supplier)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <h2 style="margin: 0;"><i class="fas fa-shopping-cart"></i> Orders for My Products</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="supplier-export-orders-csv.php" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a href="supplier-export-orders-pdf.php" target="_blank" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card orders">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p>Total Orders</p>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>₹<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>

                <div class="stat-card products">
                    <div class="stat-icon items">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3><?php echo $stats['total_items_sold']; ?></h3>
                    <p>Items Sold</p>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="dashboard-card" style="text-align: center;">
                    <div style="font-size: 4rem; color: var(--cyan); margin-bottom: 1rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3><i class="fas fa-exclamation-circle"></i> No Orders Found</h3>
                    <p>No one has ordered your products yet.</p>
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add More Products
                    </a>
                </div>
            <?php else: ?>
                <div class="dashboard-card">
                    <h3><i class="fas fa-list-alt"></i> Order Details</h3>
                    <div class="table-wrapper">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> Order ID</th>
                                    <th><i class="fas fa-user"></i> Buyer</th>
                                    <th><i class="fas fa-boxes"></i> My Items in Order</th>
                                    <th><i class="fas fa-dollar-sign"></i> Order Total</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                    <th><i class="fas fa-calendar-alt"></i> Order Date</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td>
                                        <div>
                                            <?php echo $order['supplier_items']; ?>
                                        </div>
                                    </td>
                                    <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_icon = '';
                                        switch(strtolower($order['status'])) {
                                            case 'completed':
                                                $status_class = 'status-completed';
                                                $status_icon = 'fas fa-check-circle';
                                                break;
                                            case 'pending':
                                                $status_class = 'status-pending';
                                                $status_icon = 'fas fa-clock';
                                                break;
                                            case 'processing':
                                                $status_class = 'status-processing';
                                                $status_icon = 'fas fa-spinner';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'status-cancelled';
                                                $status_icon = 'fas fa-times-circle';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                                $status_icon = 'fas fa-question-circle';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
