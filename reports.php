<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get date range from request or default to last 90 days (extended range)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-90 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+1 day')); // Include today

try {
    // Sales Report Data - Show all orders with status breakdown
    $sales_stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as daily_revenue,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $sales_stmt->execute([$start_date, $end_date]);
    $sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Products Report
    $products_stmt = $pdo->prepare("
        SELECT 
            p.name,
            p.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status = 'completed'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $products_stmt->execute([$start_date, $end_date]);
    $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Suppliers Report - Fixed to show all products, not just sold ones
    $suppliers_stmt = $pdo->prepare("
        SELECT 
            u.username as supplier_name,
            u.email,
            COUNT(DISTINCT p.id) as products_count,
            COALESCE(SUM(oi.quantity), 0) as total_items_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
        FROM users u
        JOIN products p ON u.id = p.supplier_id AND p.is_deleted = 0
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed' AND o.created_at BETWEEN ? AND ?
        WHERE u.role = 'supplier'
        GROUP BY u.id
        ORDER BY total_revenue DESC, products_count DESC
        LIMIT 10
    ");
    $suppliers_stmt->execute([$start_date, $end_date]);
    $top_suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Buyers Report
    $buyers_stmt = $pdo->prepare("
        SELECT 
            u.username as buyer_name,
            u.email,
            COUNT(DISTINCT o.id) as orders_count,
            SUM(o.total_amount) as total_spent,
            AVG(o.total_amount) as avg_order_value
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'buyer'
        AND o.created_at BETWEEN ? AND ?
        AND o.status = 'completed'
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $buyers_stmt->execute([$start_date, $end_date]);
    $top_buyers = $buyers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary Statistics - Fixed to show ALL orders, not just completed
    $summary_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_revenue,
            AVG(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE NULL END) as avg_order_value,
            COUNT(DISTINCT o.user_id) as unique_customers,
            SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END) as total_items_sold,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at BETWEEN ? AND ?
    ");
    $summary_stmt->execute([$start_date, $end_date]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Order Status Distribution
    $status_stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
        ORDER BY count DESC
    ");
    $status_stmt->execute([$start_date, $end_date]);
    $order_status = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error generating reports: " . $e->getMessage();
    $sales_data = [];
    $top_products = [];
    $top_suppliers = [];
    $top_buyers = [];
    $summary = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'unique_customers' => 0, 'total_items_sold' => 0];
    $order_status = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/professional-admin.css?v=<?php echo time(); ?>">
    <style>
        /* Force immediate styling for reports page */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            background-attachment: fixed !important;
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        }
        
        .navbar-custom {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .navbar-brand {
            color: #f8fafc !important;
            font-weight: 800 !important;
        }
        
        .navbar-brand i {
            color: #3b82f6 !important;
        }
        
        .reports-header {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .reports-header h1 {
            color: #f8fafc !important;
            font-size: 2.25rem !important;
            font-weight: 800 !important;
        }
        
        .reports-header h1 i {
            color: #3b82f6 !important;
        }
        
        /* Enhanced Statistics Cards */
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.95) 100%) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            padding: 2.5rem 2rem !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            text-align: center !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .stat-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 6px !important;
            background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%) !important;
            border-radius: 1.5rem 1.5rem 0 0 !important;
        }
        
        .stat-card::after {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            right: -50% !important;
            width: 100% !important;
            height: 100% !important;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%) !important;
            opacity: 0 !important;
            transition: opacity 0.4s ease !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02) !important;
            box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.25), 0 0 30px rgba(59, 130, 246, 0.3) !important;
            border-color: rgba(59, 130, 246, 0.6) !important;
        }
        
        .stat-card:hover::after {
            opacity: 0.3 !important;
        }
        
        .stat-card.highlight {
            border: 1px solid rgba(16, 185, 129, 0.4) !important;
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.15), 0 0 25px rgba(16, 185, 129, 0.2) !important;
        }
        
        .stat-card.highlight::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%) !important;
        }
        
        .stat-card.highlight:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25), 0 0 30px rgba(16, 185, 129, 0.3) !important;
            border-color: rgba(16, 185, 129, 0.6) !important;
        }
        
        .stat-card h3, .stat-number {
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 3rem !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            margin-bottom: 0.75rem !important;
            position: relative !important;
            z-index: 10 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        }
        
        .stat-card p {
            color: #94a3b8 !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            position: relative !important;
            z-index: 10 !important;
            margin: 0 !important;
        }
        
        /* Specific card type styling */
        .stat-card:nth-child(1) {
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        
        .stat-card:nth-child(1)::before {
            background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%) !important;
        }
        
        .stat-card:nth-child(1) .stat-number {
            color: #60a5fa !important;
        }
        
        .stat-card:nth-child(2) {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        
        .stat-card:nth-child(2)::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%) !important;
        }
        
        .stat-card:nth-child(2) .stat-number {
            color: #34d399 !important;
        }
        
        .stat-card:nth-child(3) {
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        
        .stat-card:nth-child(3)::before {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 50%, #b45309 100%) !important;
        }
        
        .stat-card:nth-child(3) .stat-number {
            color: #fbbf24 !important;
        }
        
        .stat-card:nth-child(4) {
            border-color: rgba(99, 102, 241, 0.3) !important;
        }
        
        .stat-card:nth-child(4)::before {
            background: linear-gradient(90deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%) !important;
        }
        
        .stat-card:nth-child(4) .stat-number {
            color: #818cf8 !important;
        }
        
        .stat-card:nth-child(5) {
            border-color: rgba(239, 68, 68, 0.3) !important;
        }
        
        .stat-card:nth-child(5)::before {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%) !important;
        }
        
        .stat-card:nth-child(5) .stat-number {
            color: #f87171 !important;
        }
        
        .data-table-container {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            margin-bottom: 2rem !important;
        }
        
        .data-table-header {
            background: rgba(59, 130, 246, 0.1) !important;
            padding: 1.5rem 2rem !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .data-table-header h4 {
            color: #f8fafc !important;
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            margin: 0 !important;
        }
        
        .data-table-header h4 i {
            color: #3b82f6 !important;
            margin-right: 0.75rem !important;
        }
        
        .table {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        .table th {
            color: #f8fafc !important;
            background: rgba(59, 130, 246, 0.05) !important;
            border-color: rgba(59, 130, 246, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.8rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
        }
        
        .text-muted {
            color: #94a3b8 !important;
        }
        
        .text-success {
            color: #10b981 !important;
        }
        
        .text-primary {
            color: #3b82f6 !important;
        }
        
        .badge {
            font-weight: 600 !important;
            padding: 0.4rem 0.8rem !important;
            border-radius: 0.375rem !important;
        }
        
        .bg-primary {
            background: #3b82f6 !important;
            color: white !important;
        }
        
        .bg-warning {
            background: #f59e0b !important;
            color: white !important;
        }
        
        .bg-info {
            background: #06b6d4 !important;
            color: white !important;
        }
        
        .bg-secondary {
            background: #6366f1 !important;
            color: white !important;
        }
        
        .export-buttons {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .export-btn {
            background: rgba(59, 130, 246, 0.1) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            color: #f8fafc !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            margin-right: 1rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .export-btn:hover {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: white !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <i class="fas fa-chart-bar"></i> <strong>Reports & Analytics</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="analytics.php"><i class="fas fa-analytics"></i> Analytics</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="reports-header p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <a href="admin-dashboard.php" class="back-to-dashboard" title="Back to Dashboard">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1><i class="fas fa-chart-line text-primary"></i> Business Reports</h1>
                    <p class="lead mb-0">Comprehensive business analytics and performance reports</p>
                </div>
                <div class="col-md-4">
                    <!-- Date Range Filter -->
                    <form method="GET" class="date-filter">
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-4">
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
                            </div>
                        </div>
                    </form>
                    <!-- Export Buttons -->
                    <div class="mt-2 d-flex gap-2">
                        <a href="admin-export-reports-pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="btn btn-sm btn-outline-info flex-fill">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=summary" class="btn btn-sm btn-outline-success flex-fill">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Enhanced Summary Statistics -->
        <div class="row mb-5" style="gap: 2rem; justify-content: center;">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-5">
                <div class="stat-card highlight" data-tooltip="Total number of orders in selected period">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number"><?php echo number_format($summary['total_orders']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-5">
                <div class="stat-card highlight" data-tooltip="Total revenue generated in selected period">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number">₹<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-5">
                <div class="stat-card" data-tooltip="Average value per order">
                    <div class="stat-icon avg">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number">₹<?php echo number_format($summary['avg_order_value'], 2); ?></h3>
                        <p>Avg Order Value</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-5">
                <div class="stat-card" data-tooltip="Number of unique customers">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number"><?php echo number_format($summary['unique_customers']); ?></h3>
                        <p>Unique Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-5">
                <div class="stat-card" data-tooltip="Total items sold across all orders">
                    <div class="stat-icon items">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number"><?php echo number_format($summary['total_items_sold']); ?></h3>
                        <p>Items Sold</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons mb-4">
            <h5 class="mb-3"><i class="fas fa-download"></i> Export Reports</h5>
            <a href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=summary" class="export-btn">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=summary" class="export-btn">
                <i class="fas fa-file-csv"></i> Export to CSV
            </a>
            <button onclick="window.print()" class="export-btn">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <!-- Reports Grid -->
        <div class="row">
            <!-- Sales Report -->
            <div class="col-lg-6 mb-4">
                <div class="data-table-container">
                    <div class="data-table-header">
                        <h4><i class="fas fa-chart-area"></i> Daily Sales Report</h4>
                        <small class="text-muted"><?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></small>
                    </div>
                    <div class="data-table-body">
                        <?php if (!empty($sales_data)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-calendar-day"></i> Date</th>
                                            <th><i class="fas fa-shopping-cart"></i> Orders</th>
                                            <th><i class="fas fa-dollar-sign"></i> Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($sales_data, 0, 10) as $day): ?>
                                            <tr>
                                                <td><strong><?php echo date('M j', strtotime($day['date'])); ?></strong></td>
                                                <td><span class="badge bg-primary"><?php echo $day['orders_count']; ?></span></td>
                                                <td><strong class="text-success">₹<?php echo number_format($day['daily_revenue'], 2); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No sales data available for the selected period.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-6 mb-4">
                <div class="data-table-container">
                    <div class="data-table-header">
                        <h4><i class="fas fa-trophy"></i> Top Selling Products</h4>
                        <small class="text-muted">Best performers</small>
                    </div>
                    <div class="data-table-body">
                        <?php if (!empty($top_products)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-box"></i> Product</th>
                                            <th><i class="fas fa-chart-bar"></i> Sold</th>
                                            <th><i class="fas fa-money-bill-wave"></i> Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <br><small class="text-muted">Unit: ₹<?php echo number_format($product['price'], 2); ?></small>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-warning"><?php echo $product['total_sold']; ?></span></td>
                                                <td><strong class="text-success">₹<?php echo number_format($product['revenue'], 2); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No product sales data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Suppliers -->
            <div class="col-lg-6 mb-4">
                <div class="data-table-container">
                    <div class="data-table-header">
                        <h4><i class="fas fa-industry"></i> Top Suppliers</h4>
                        <small class="text-muted">By revenue generated</small>
                    </div>
                    <div class="data-table-body">
                        <?php if (!empty($top_suppliers)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user-tie"></i> Supplier</th>
                                            <th><i class="fas fa-boxes"></i> Products</th>
                                            <th><i class="fas fa-chart-line"></i> Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_suppliers as $supplier): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                                        <br><small class="text-muted"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo $supplier['products_count']; ?></span></td>
                                                <td><strong class="text-success">₹<?php echo number_format($supplier['total_revenue'], 2); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No supplier data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Buyers -->
            <div class="col-lg-6 mb-4">
                <div class="data-table-container">
                    <div class="data-table-header">
                        <h4><i class="fas fa-shopping-bag"></i> Top Buyers</h4>
                        <small class="text-muted">Best customers</small>
                    </div>
                    <div class="data-table-body">
                        <?php if (!empty($top_buyers)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user"></i> Buyer</th>
                                            <th><i class="fas fa-shopping-cart"></i> Orders</th>
                                            <th><i class="fas fa-dollar-sign"></i> Total Spent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_buyers as $buyer): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($buyer['buyer_name']); ?></strong>
                                                        <br><small class="text-muted">Avg: ₹<?php echo number_format($buyer['avg_order_value'], 2); ?></small>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo $buyer['orders_count']; ?></span></td>
                                                <td><strong class="text-success">₹<?php echo number_format($buyer['total_spent'], 2); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No buyer data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <?php if (!empty($order_status)): ?>
        <div class="row">
            <div class="col-12">
                <div class="data-table-container">
                    <div class="data-table-header">
                        <h4><i class="fas fa-pie-chart"></i> Order Status Distribution</h4>
                        <small class="text-muted">Order breakdown by status</small>
                    </div>
                    <div class="data-table-body">
                        <div class="row">
                            <?php foreach ($order_status as $status): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="stat-card <?php echo strtolower($status['status']); ?>">
                                        <div class="stat-icon <?php echo strtolower($status['status']); ?>">
                                            <i class="fas fa-<?php echo $status['status'] == 'completed' ? 'check-circle' : ($status['status'] == 'pending' ? 'clock' : ($status['status'] == 'cancelled' ? 'times-circle' : 'info-circle')); ?>"></i>
                                        </div>
                                        <h3><?php echo $status['count']; ?></h3>
                                        <p><?php echo ucfirst($status['status']); ?></p>
                                        <small>₹<?php echo number_format($status['revenue'], 2); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enhanced Export Options -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="export-buttons">
                    <h5><i class="fas fa-download"></i> Export Reports</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3 text-primary">Quick Exports</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button class="export-btn" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                                <a href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=summary" 
                                   class="export-btn">
                                    <i class="fas fa-file-csv"></i> Summary CSV
                                </a>
                                <a href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=summary" 
                                   class="export-btn">
                                    <i class="fas fa-file-excel"></i> Summary Excel
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3 text-primary">Detailed Exports</h6>
                            <div class="dropdown">
                                <button class="export-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=sales">
                                        <i class="fas fa-chart-line"></i> Sales Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=products">
                                        <i class="fas fa-box"></i> Products Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=suppliers">
                                        <i class="fas fa-industry"></i> Suppliers Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=buyers">
                                        <i class="fas fa-users"></i> Buyers Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=orders">
                                        <i class="fas fa-shopping-cart"></i> Orders Report
                                    </a></li>
                                </ul>
                            </div>
                            <div class="dropdown ms-2">
                                <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=sales">
                                        <i class="fas fa-chart-line"></i> Sales Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=products">
                                        <i class="fas fa-box"></i> Products Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=suppliers">
                                        <i class="fas fa-industry"></i> Suppliers Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=buyers">
                                        <i class="fas fa-users"></i> Buyers Report
                                    </a></li>
                                    <li><a class="dropdown-item" href="export-reports-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=orders">
                                        <i class="fas fa-shopping-cart"></i> Orders Report
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard-common.js"></script>
    <script>
        function exportToCSV() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Generating CSV...';
            btn.disabled = true;
            
            // Simulate CSV generation
            setTimeout(() => {
                showNotification('CSV export would be generated here', 'info');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        }
        
        function exportToPDF() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Generating PDF...';
            btn.disabled = true;
            
            // Simulate PDF generation
            setTimeout(() => {
                showNotification('PDF export would be generated here', 'info');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close ms-2" onclick="this.parentElement.remove()"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Add fade-in animation to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .report-card, .export-options');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add tooltip functionality
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(element => {
                element.classList.add('tooltip-custom');
            });
        });
    </script>
</body>
</html>