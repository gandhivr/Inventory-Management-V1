<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get buyer's orders with order items
try {
    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ' × $', oi.price, ')') SEPARATOR '<br>') as items
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Inventory Management System</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="buyer-dashboard.php">Dashboard</a>
                <a href="product-list.php">Browse Products</a>
                <a href="cart.php">My Cart</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <h2>My Orders</h2>

            <?php if (empty($orders)): ?>
                <div class="dashboard-card" style="text-align: center;">
                    <h3>No Orders Found</h3>
                    <p>You haven't placed any orders yet.</p>
                    <a href="product-list.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="dashboard-card">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td style="max-width: 300px;">
                                        <div style="font-size: 14px; line-height: 1.4;">
                                            <?php echo $order['items']; ?>
                                        </div>
                                    </td>
                                    <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($order['status']) {
                                            case 'completed':
                                                $status_class = 'status-completed';
                                                break;
                                            case 'pending':
                                                $status_class = 'status-pending';
                                                break;
                                            case 'processing':
                                                $status_class = 'status-processing';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'status-cancelled';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>