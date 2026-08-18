<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$supplier_id = $_SESSION['user_id'];

// Get date range from request or default to last 90 days (extended range)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-90 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+1 day')); // Include today

try {
    // Supplier's Product Performance - Fixed to show all orders, not just completed
    $product_performance = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END), 0) as total_sold,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END), 0) as total_revenue,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN oi.order_id END) as order_count,
            COUNT(DISTINCT oi.order_id) as total_orders,
            p.created_at
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE p.supplier_id = ? AND p.is_deleted = 0
        GROUP BY p.id
        ORDER BY total_revenue DESC
    ");
    $product_performance->execute([$supplier_id]);
    $products = $product_performance->fetchAll(PDO::FETCH_ASSOC);

    // Monthly Sales Trend - Fixed to show all orders with status breakdown
    $monthly_sales = $pdo->prepare("
        SELECT 
            DATE_FORMAT(o.created_at, '%Y-%m') as month,
            COUNT(DISTINCT o.id) as orders,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
            SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END) as items_sold,
            SUM(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END) as revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.supplier_id = ? 
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_sales->execute([$supplier_id]);
    $monthly_data = $monthly_sales->fetchAll(PDO::FETCH_ASSOC);

    // Summary Statistics - Fixed to show all products and all orders
    $summary_stats = $pdo->prepare("
        SELECT 
            -- Product counts (not affected by date range)
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0) as total_products,
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0 AND stock_quantity > 0) as in_stock_products,
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0 AND stock_quantity = 0) as out_of_stock_products,
            
            -- Order and sales data (filtered by date range but includes all statuses)
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END), 0) as total_items_sold,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price END), 0) as avg_order_value
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        WHERE p.supplier_id = ? AND p.is_deleted = 0
    ");
    $summary_stats->execute([$supplier_id, $supplier_id, $supplier_id, $start_date, $end_date, $supplier_id]);
    $summary = $summary_stats->fetch(PDO::FETCH_ASSOC);

    // Top Selling Products - Fixed to show all orders in date range
    $top_products = $pdo->prepare("
        SELECT 
            p.name,
            p.price,
            SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END) as quantity_sold,
            SUM(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END) as revenue,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN oi.order_id END) as order_count,
            COUNT(DISTINCT oi.order_id) as total_orders
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.supplier_id = ? 
        AND o.created_at BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY quantity_sold DESC
        LIMIT 10
    ");
    $top_products->execute([$supplier_id, $start_date, $end_date]);
    $top_selling = $top_products->fetchAll(PDO::FETCH_ASSOC);

    // Recent Orders
    $recent_orders = $pdo->prepare("
        SELECT DISTINCT
            o.id,
            o.created_at,
            o.status,
            o.total_amount,
            u.username as buyer_name,
            COUNT(oi.id) as item_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recent_orders->execute([$supplier_id]);
    $recent_orders_data = $recent_orders->fetchAll(PDO::FETCH_ASSOC);

    // Low Stock Alert
    $low_stock = $pdo->prepare("
        SELECT 
            id,
            name,
            stock_quantity,
            price
        FROM products 
        WHERE supplier_id = ? 
        AND is_deleted = 0
        AND stock_quantity <= 5
        ORDER BY stock_quantity ASC
    ");
    $low_stock->execute([$supplier_id]);
    $low_stock_products = $low_stock->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error loading analytics: " . $e->getMessage();
    $products = [];
    $monthly_data = [];
    $summary = [
        'total_products' => 0,
        'in_stock_products' => 0,
        'out_of_stock_products' => 0,
        'total_items_sold' => 0,
        'total_revenue' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0
    ];
    $top_selling = [];
    $recent_orders_data = [];
    $low_stock_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Analytics - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="supplier-dashboard.php">
                <i class="fas fa-chart-line"></i> <strong>Supplier Analytics</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="supplier-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="manage-products.php"><i class="fas fa-boxes"></i> Products</a></li>
                        <li><a class="dropdown-item" href="supplier-orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="analytics-header p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <a href="supplier-dashboard.php" class="back-to-dashboard" title="Back to Dashboard">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1><i class="fas fa-chart-bar text-primary"></i> Supplier Analytics</h1>
                    <p class="lead mb-0">Track your product performance and sales insights</p>
                </div>
                <div class="col-md-4">
                    <!-- Date Range Filter -->
                    <form method="GET" class="date-filter">
                        <div class="row g-2">
                            <div class="col-5">
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-5">
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card" data-tooltip="Total products in your inventory">
                    <div class="stat-icon products">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($summary['total_products']); ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card highlight" data-tooltip="Revenue generated in selected period">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card highlight" data-tooltip="Total orders received">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($summary['total_orders']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card" data-tooltip="Items sold in selected period">
                    <div class="stat-icon items">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($summary['total_items_sold']); ?></h3>
                        <p>Items Sold</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card" data-tooltip="Products currently in stock">
                    <div class="stat-icon stock">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($summary['in_stock_products']); ?></h3>
                        <p>In Stock</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card <?php echo $summary['out_of_stock_products'] > 0 ? 'alert-card' : ''; ?>" data-tooltip="Products out of stock">
                    <div class="stat-icon out-stock">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($summary['out_of_stock_products']); ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Sales Trend Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-chart-area"></i> Sales Trend (Last 12 Months)</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleChartType('salesChart')">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Product Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-pie-chart"></i> Stock Status</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="row mb-4">
            <!-- Top Selling Products -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-table">
                    <div class="table-header">
                        <h5><i class="fas fa-trophy"></i> Top Selling Products</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_selling as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rank-badge"><?php echo $index + 1; ?></div>
                                                <div class="ms-2">
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <br><small class="text-muted">₹<?php echo number_format($product['price'], 2); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo number_format($product['quantity_sold']); ?></strong></td>
                                        <td><strong>₹<?php echo number_format($product['revenue'], 2); ?></strong></td>
                                        <td><?php echo $product['order_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($top_selling)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No sales data available for selected period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-table">
                    <div class="table-header">
                        <h5><i class="fas fa-clock"></i> Recent Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Buyer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders_data as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $order['id']; ?></strong>
                                            <br><small class="text-muted"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'completed' ? 'success' : 
                                                     ($order['status'] === 'pending' ? 'warning' : 
                                                      ($order['status'] === 'processing' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_orders_data)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No recent orders</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock_products)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert-card low-stock-alert">
                    <div class="alert-header">
                        <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h5>
                    </div>
                    <div class="alert-content">
                        <div class="row">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="low-stock-item">
                                        <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="stock-level">Stock: <span class="text-danger"><?php echo $product['stock_quantity']; ?></span></p>
                                        <p class="price">Price: ₹<?php echo number_format($product['price'], 2); ?></p>
                                        <a href="update-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Export Options -->
        <div class="row">
            <div class="col-12">
                <div class="export-options p-3">
                    <h6><i class="fas fa-download"></i> Export Analytics</h6>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <a href="supplier-export-analytics-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="supplier-export-analytics-pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard-common.js"></script>
    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#6c757d';

        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($monthly_data, 'orders')); ?>,
                    borderColor: '#48bb78',
                    backgroundColor: 'rgba(72, 187, 120, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Stock Status Chart
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        const stockChart = new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Out of Stock'],
                datasets: [{
                    data: [
                        <?php echo $summary['in_stock_products']; ?>,
                        <?php echo $summary['out_of_stock_products']; ?>
                    ],
                    backgroundColor: [
                        '#48bb78',
                        '#f56565'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Utility Functions
        function toggleChartType(chartId) {
            const chart = window[chartId];
            if (chart) {
                const currentType = chart.config.type;
                const newType = currentType === 'line' ? 'bar' : 'line';
                
                chart.config.type = newType;
                chart.update();
                
                DashboardUtils.showNotification(`Chart type changed to ${newType}`, 'success');
            }
        }


    </script>
</body>
</html>