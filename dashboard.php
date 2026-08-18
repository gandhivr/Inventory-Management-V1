<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get statistics
try {
    // Count users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Count products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();

    // Count orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();

    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'");
    $total_revenue = $stmt->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $user_stats = [];
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/professional-admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-crown"></i> Admin Command Center</h1>
                    <p>Complete system oversight with advanced analytics</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>
            </div>
        </div>     
   <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-grid">
                <a href="product-list.php" class="action-card blue">
                    <div class="action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <h3>Product List</h3>
                </a>
                <a href="manage-users.php" class="action-card cyan">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Manage Users</h3>
                </a>
                <a href="admin-orders.php" class="action-card green">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>All Orders</h3>
                </a>
                <a href="reports.php" class="action-card purple">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reports</h3>
                </a>
                <a href="analytics.php" class="action-card orange">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics</h3>
                </a>
            </div>
        </div>

        <!-- Management Sections -->
        <div class="management-grid">
            <!-- User Management -->
            <div class="management-card blue-card">
                <div class="card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="card-content">
                    <h3>User Management</h3>
                    <p>Manage user accounts, roles, and permissions</p>
                    <div class="card-stats">
                        <span>Total Users: <strong><?php echo array_sum($user_stats); ?></strong></span>
                    </div>
                    <div class="card-actions">
                        <a href="manage-users.php" class="btn-action"><i class="fas fa-users"></i> Manage Users</a>
                        <a href="manage-users.php#addUser" class="btn-action secondary" onclick="setTimeout(function(){ document.getElementById('addUserModal') && new bootstrap.Modal(document.getElementById('addUserModal')).show(); }, 500);"><i class="fas fa-user-plus"></i> Add New User</a>
                    </div>
                </div>
            </div>

            <!-- Product Management -->
            <div class="management-card green-card">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="card-content">
                    <h3>Product Management</h3>
                    <p>Add, edit, and organize your product inventory</p>
                    <div class="card-stats">
                        <span>Total Products: <strong><?php echo $total_products; ?></strong></span>
                    </div>
                    <div class="card-actions">
                        <a href="manage-products.php" class="btn-action"><i class="fas fa-boxes"></i> Manage Products</a>
                        <a href="product-list.php" class="btn-action secondary"><i class="fas fa-list"></i> Product List</a>
                        <a href="add-product.php" class="btn-action secondary"><i class="fas fa-plus"></i> Add Product</a>
                    </div>
                </div>
            </div>

            <!-- Order Management -->
            <div class="management-card orange-card">
                <div class="card-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="card-content">
                    <h3>Order Management</h3>
                    <p>Track and manage customer orders and transactions</p>
                    <div class="card-stats">
                        <span>Total Orders: <strong><?php echo $total_orders; ?></strong></span>
                        <span>Revenue: <strong>₹<?php echo number_format($total_revenue, 2); ?></strong></span>
                    </div>
                    <div class="card-actions">
                        <a href="admin-orders.php" class="btn-action"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports & Analytics -->
        <div class="analytics-section">
            <div class="analytics-card purple-card">
                <div class="card-icon">
                    <i class="fas fa-chart-area"></i>
                </div>
                <div class="card-content">
                    <h3>Reports & Analytics</h3>
                    <p>View detailed reports and business analytics</p>
                    <div class="card-actions">
                        <a href="reports.php" class="btn-action"><i class="fas fa-chart-bar"></i> View Reports</a>
                        <a href="analytics.php" class="btn-action secondary"><i class="fas fa-chart-line"></i> Analytics</a>
                    </div>
                </div>
            </div>

            <div class="analytics-card cyan-card">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="card-content">
                    <h3>System Settings</h3>
                    <p>Configure system settings and preferences</p>
                    <div class="card-actions">
                        <a href="manage-users.php" class="btn-action"><i class="fas fa-cog"></i> Settings</a>
                    </div>
                </div>
            </div>

            <div class="analytics-card yellow-card">
                <div class="card-icon">
                    <i class="fas fa-list-alt"></i>
                </div>
                <div class="card-content">
                    <h3>PRODUCT LIST</h3>
                    <p>View and manage all products in inventory</p>
                    <div class="card-actions">
                        <a href="product-list.php" class="btn-action"><i class="fas fa-list"></i> View Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>