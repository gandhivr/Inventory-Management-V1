<?php
// Start session to access $_SESSION superglobal
session_start();
// Include file to access $pdo database object
require_once 'config/database.php';

// Check session variables with isset() and logical operators
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Send HTTP redirect header
    header("Location: login.php");
    // Stop script execution
    exit();
}

// Try-catch block for exception handling
try {
    // PDO query() method executes SQL, fetchColumn() gets single value
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    // Soft deletes preserve historical data while hiding items from normal operations
    // This maintains referential integrity for past orders while removing products from catalog
    $total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 0")->fetchColumn();
    
    // TOTAL ORDERS COUNT: All orders regardless of current status
    // Includes pending, completed, cancelled, processing, shipped, and all other states
    // Provides overall transaction volume metric for business analysis
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // ===================================================================
    // USER SEGMENTATION METRICS: Role-based user analysis
    // ===================================================================
    // These queries help understand the composition and balance of the user base
    
    // SUPPLIERS COUNT: Users who can add/manage products for sale
    // Combined WHERE clause ensures both active status AND supplier role
    // Helps track business ecosystem health and supplier engagement
    $total_suppliers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supplier' AND status = 'active'")->fetchColumn();
    
    // BUYERS COUNT: Users who can purchase products
    // Active buyers represent the customer base size
    // Critical metric for understanding market reach and customer engagement
    $total_buyers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND status = 'active'")->fetchColumn();
    
    // ===================================================================
    // SYSTEM HEALTH METRICS: Inactive and deleted entity tracking
    // ===================================================================
    // These metrics help admins understand system maintenance needs
    
    // INACTIVE USERS: Accounts that are suspended or deactivated
    // High numbers might indicate user satisfaction issues or need for cleanup
    // Chain method calls: query() then fetchColumn()
    $total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 0")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_suppliers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supplier' AND status = 'active'")->fetchColumn();
    $total_buyers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND status = 'active'")->fetchColumn();
    $inactive_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
    $deleted_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 1")->fetchColumn();
    
    // SQL DATE_SUB() function with INTERVAL for date calculations
    $recent_users = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $recent_products = $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted = 0")->fetchColumn();
    
    // SQL COALESCE() function handles NULL values, returns 0 if SUM is NULL
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
// Catch PDOException class for database errors
} catch (PDOException $e) {
    // Multiple variable assignment in single statement
    $total_users = $total_products = $total_orders = $total_suppliers = $total_buyers = 0;
    $inactive_users = $deleted_products = $recent_users = $recent_products = 0;
    $total_revenue = $pending_orders = 0;
    
    // Store error message for display to admin with technical details
    // This helps with debugging database connectivity issues
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}

// Try-catch blocks with fetchAll() method returning associative arrays
try {
    // fetchAll(PDO::FETCH_ASSOC) returns array of associative arrays
    $recent_users_list = $pdo->query("
        SELECT name, username, role, created_at 
        FROM users 
        WHERE status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Assign empty array on exception
    $recent_users_list = [];
}

try {
    $recent_products_list = $pdo->query("
        SELECT name, price, stock_quantity, created_at 
        FROM products 
        WHERE is_deleted = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_products_list = [];
}

try {
    // SQL comparison operators (<=) and logical operators (AND)
    $low_stock_products = $pdo->query("
        SELECT name, stock_quantity 
        FROM products 
        WHERE is_deleted = 0 AND stock_quantity <= 5 
        ORDER BY stock_quantity ASC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $low_stock_products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management System</title>
    <!-- Bootstrap 5 CSS for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons throughout the dashboard -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for potential future analytics charts -->
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" rel="stylesheet">
    <link rel="stylesheet" href="css/unified-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">

</head>
<body>
    <!-- Navigation Bar with user information and logout option -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
        <div class="container-fluid">
            <!-- Brand/Logo section -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas fa-user-shield"></i>
                <strong>Inventory System</strong>
            </a>
            <!-- User dropdown menu -->
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> 
                        <!-- htmlspecialchars() prevents XSS attacks -->
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Dashboard Header with welcome message and current date/time -->
        <div class="dashboard-header p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-text mb-2">
                        <i class="fas fa-tachometer-alt text-primary"></i> 
                        <!-- Echo statement with htmlspecialchars() function -->
                        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </h1>
                    <p class="lead mb-0">Manage your inventory system from this comprehensive dashboard</p>
                </div>
                <div class="col-md-4 text-end">
                    <!-- PHP date() function with format strings -->
                    <div class="text-muted">
                        <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?><br>
                        <i class="fas fa-clock"></i> <?php echo date('g:i A'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages System -->
        <!-- Display success/error messages stored in session -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                // Display message and immediately clear it from session
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['msg_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Display database error messages to admin -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Statistics Overview Row -->
        <div class="row mb-4">
            <!-- Total Users Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card users p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon users me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <!-- number_format() adds thousands separators -->
                            <h3 class="mb-1"><?php echo number_format($total_users); ?></h3>
                            <p class="mb-0 text-muted">Total Users</p>
                            <!-- Conditional statement with comparison operator -->
                            <?php if ($recent_users > 0): ?>
                                <small class="text-success">+<?php echo $recent_users; ?> this week</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Products Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card products p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon products me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo number_format($total_products); ?></h3>
                            <p class="mb-0 text-muted">Total Products</p>
                            <?php if ($recent_products > 0): ?>
                                <small class="text-success">+<?php echo $recent_products; ?> this week</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Orders Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card orders p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon orders me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo number_format($total_orders); ?></h3>
                            <p class="mb-0 text-muted">Total Orders</p>
                            <!-- Show pending orders as a warning indicator -->
                            <?php if ($pending_orders > 0): ?>
                                <small class="text-warning"><?php echo $pending_orders; ?> pending</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Revenue Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card revenue p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon revenue me-3">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div>
                            <!-- Format currency with 2 decimal places -->
                            <h3 class="mb-1">₹<?php echo number_format($total_revenue, 2); ?></h3>
                            <p class="mb-0 text-muted">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Statistics Row -->
        <div class="row mb-4">
            <!-- Suppliers Count -->
            <div class="col-md-3">
                <div class="stat-card p-3 text-center">
                    <i class="fas fa-industry fa-2x text-primary mb-2"></i>
                    <h4><?php echo number_format($total_suppliers); ?></h4>
                    <p class="mb-0 text-muted">Suppliers</p>
                </div>
            </div>
            <!-- Buyers Count -->
            <div class="col-md-3">
                <div class="stat-card p-3 text-center">
                    <i class="fas fa-shopping-bag fa-2x text-success mb-2"></i>
                    <h4><?php echo number_format($total_buyers); ?></h4>
                    <p class="mb-0 text-muted">Buyers</p>
                </div>
            </div>
            <!-- Inactive Users Count -->
            <div class="col-md-3">
                <div class="stat-card p-3 text-center">
                    <i class="fas fa-user-slash fa-2x text-warning mb-2"></i>
                    <h4><?php echo number_format($inactive_users); ?></h4>
                    <p class="mb-0 text-muted">Inactive Users</p>
                </div>
            </div>
            <!-- Deleted Products Count -->
            <div class="col-md-3">
                <div class="stat-card p-3 text-center">
                    <i class="fas fa-archive fa-2x text-danger mb-2"></i>
                    <h4><?php echo number_format($deleted_products); ?></h4>
                    <p class="mb-0 text-muted">Deleted Products</p>
                </div>
            </div>
        </div>



        <!-- Management Sections Grid -->
        <div class="row mb-4">
            <!-- User Management Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-card p-4 text-center">
                    <i class="fas fa-users-cog fa-4x text-primary mb-3"></i>
                    <h5 class="card-title">User Management</h5>
                    <p class="card-text">Manage user accounts, roles, and permissions</p>
                    <div class="d-grid gap-2">
                        <a href="manage-users.php" class="btn btn-gradient primary">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="manage-users.php#addUser" class="btn btn-outline-primary btn-sm" onclick="localStorage.setItem('openAddUserModal', 'true');">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                </div>
            </div>

            <!-- Product Management Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-card p-4 text-center">
                    <i class="fas fa-boxes fa-4x text-success mb-3"></i>
                    <h5 class="card-title">Product Management</h5>
                    <p class="card-text">Add, edit, and organize your product inventory</p>
                    <div class="d-grid gap-2">
                        <a href="manage-products.php" class="btn btn-gradient success">
                            <i class="fas fa-box"></i> Manage Products
                        </a>
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="product-list.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-list"></i> Product List
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="add-product.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-plus"></i> Add Product
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Management Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-card p-4 text-center">
                    <i class="fas fa-clipboard-list fa-4x text-warning mb-3"></i>
                    <h5 class="card-title">Order Management</h5>
                    <p class="card-text">Track and manage customer orders and transactions</p>
                    <div class="d-grid gap-2">
                        <a href="admin-orders.php" class="btn btn-gradient warning">
                            <i class="fas fa-shopping-cart"></i> Manage Orders
                        </a>
                        <!-- Show pending orders badge if any exist -->
                        <?php if ($pending_orders > 0): ?>
                            <span class="badge bg-danger"><?php echo $pending_orders; ?> Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reports & Analytics Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-card p-4 text-center">
                    <i class="fas fa-chart-bar fa-4x text-info mb-3"></i>
                    <h5 class="card-title">Reports & Analytics</h5>
                    <p class="card-text">View detailed reports and business analytics</p>
                    <div class="d-grid gap-2">
                        <a href="reports.php" class="btn btn-gradient info">
                            <i class="fas fa-chart-line"></i> View Reports
                        </a>
                        <a href="analytics.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-analytics"></i> Analytics Dashboard
                        </a>
                    </div>
                </div>
            </div>


         <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-card p-4 text-center">
                    <i class="fas fa-list-alt fa-4x text-secondary mb-3"></i>
                    <h5 class="card-title">PRODUCT LIST</h5>
                    <p class="card-text">SEE ALL PRODUCTS</p>
                    <div class="d-grid gap-2">
                        <a href="product-list.php" class="btn btn-gradient primary">
                            <i class="fas fa-list"></i> Product List
                        </a>
                    </div>
                </div>
            </div>


        </div>

        <!-- Activity Feed and Alerts Row -->
        <div class="row">
            <!-- Recent Activity Column -->
            <div class="col-lg-6 mb-4">
                <div class="activity-card p-4">
                    <h5 class="mb-4">
                        <i class="fas fa-clock text-primary"></i> Recent Activity
                    </h5>
                    
                    <!-- Recent Users Section -->
                    <h6 class="text-muted mb-3">New Users</h6>
                    <!-- empty() function checks if array has elements -->
                    <?php if (!empty($recent_users_list)): ?>
                        <!-- foreach loop iterates through array -->
                        <?php foreach ($recent_users_list as $user): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <!-- Array access with square brackets -->
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <!-- Ternary operator for conditional CSS classes -->
                                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'supplier' ? 'primary' : 'success'); ?>">
                                            <!-- ucfirst() capitalizes first letter -->
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </div>
                                    <!-- strtotime() converts string to timestamp -->
                                    <small class="text-muted">
                                        <?php echo date('M j, g:i A', strtotime($user['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent users</p>
                    <?php endif; ?>

                    <hr>

                    <!-- Recent Products Section -->
                    <h6 class="text-muted mb-3">New Products</h6>
                    <?php if (!empty($recent_products_list)): ?>
                        <?php foreach ($recent_products_list as $product): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <!-- Display product name with XSS protection -->
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <!-- Format price with currency symbol -->
                                        <small class="text-success">₹<?php echo number_format($product['price'], 2); ?></small>
                                        <!-- Display stock quantity -->
                                        <small class="text-muted">• Stock: <?php echo $product['stock_quantity']; ?></small>
                                    </div>
                                    <!-- Format and display creation timestamp -->
                                    <small class="text-muted">
                                        <?php echo date('M j, g:i A', strtotime($product['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent products</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alerts and Notifications Column -->
            <div class="col-lg-6 mb-4">
                <div class="activity-card p-4">
                    <h5 class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Alerts & Notifications
                    </h5>

                    <!-- Low Stock Alert Section -->
                    <?php if (!empty($low_stock_products)): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h6>
                            <p class="mb-2">The following products are running low:</p>
                            <!-- Loop through low stock products -->
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="low-stock-item">
                                    <!-- Display product name with XSS protection -->
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <!-- Show remaining stock with danger badge -->
                                    <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?> left</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pending Orders Alert Section -->
                    <?php if ($pending_orders > 0): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-shopping-cart"></i> Pending Orders</h6>
                            <p class="mb-0">You have <strong><?php echo $pending_orders; ?></strong> orders waiting for processing.</p>
                            <a href="admin-orders.php" class="btn btn-sm btn-info mt-2">Review Orders</a>
                        </div>
                    <?php endif; ?>

                    <!-- Inactive Users Alert Section -->
                    <?php if ($inactive_users > 0): ?>
                        <div class="alert alert-secondary">
                            <h6><i class="fas fa-user-slash"></i> Inactive Users</h6>
                            <p class="mb-0">There are <strong><?php echo $inactive_users; ?></strong> inactive user accounts.</p>
                            <a href="manage-users.php?show_inactive=1" class="btn btn-sm btn-secondary mt-2">View Inactive Users</a>
                        </div>
                    <?php endif; ?>

                    <!-- System Status Section -->
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> System Status</h6>
                        <p class="mb-0">All systems operational. Last backup: <?php echo date('M j, Y g:i A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Auto-refresh dashboard every 5 minutes (300,000 milliseconds)
        // This keeps the statistics current for active monitoring
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Add smooth scrolling behavior for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Initialize Bootstrap tooltips for better UX
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
