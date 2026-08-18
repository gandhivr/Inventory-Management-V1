<?php
// Start session to access $_SESSION superglobal
session_start();
// Include file to access $pdo database object
require_once 'config/database.php';

// Check session variables with isset() and logical operators
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Send HTTP redirect header
    header('Location: login.php');
    // Stop script execution
    exit();
}

// Try-catch block for exception handling
try {
    // PDO query() method with SQL DATE_FORMAT() and aggregate functions
    $monthly_revenue = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND status = 'completed'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Complex query with multiple JOINs and COALESCE() function
    $category_performance = $pdo->query("
        SELECT 
            u.username as category,
            COUNT(DISTINCT p.id) as products_count,
            COALESCE(SUM(oi.quantity), 0) as items_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
        FROM users u
        LEFT JOIN products p ON u.id = p.supplier_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
        WHERE u.role = 'supplier'
        GROUP BY u.id, u.username
        HAVING revenue > 0
        ORDER BY revenue DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Query with DATE_SUB() function for date calculations
    $user_growth = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            role,
            COUNT(*) as new_users
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), role
        ORDER BY month ASC, role
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Query with SQL AVG() aggregate function
    $order_analytics = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            AVG(total_amount) as avg_amount,
            SUM(total_amount) as total_revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY status
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Query with INNER JOINs and DISTINCT keyword
    $top_products_revenue = $pdo->query("
        SELECT 
            p.name,
            SUM(oi.quantity * oi.price) as revenue,
            SUM(oi.quantity) as quantity_sold,
            COUNT(DISTINCT oi.order_id) as orders_count
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'completed'
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY p.id
        ORDER BY revenue DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Query with LEFT JOINs and COALESCE() for NULL handling
    $supplier_performance = $pdo->query("
        SELECT 
            u.username as supplier_name,
            COUNT(DISTINCT p.id) as products_count,
            COALESCE(SUM(oi.quantity), 0) as items_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
            COUNT(DISTINCT o.id) as orders_received
        FROM users u
        LEFT JOIN products p ON u.id = p.supplier_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
        WHERE u.role = 'supplier'
        GROUP BY u.id
        ORDER BY revenue DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Complex query with UNION ALL and CONCAT() function
    $recent_activity = $pdo->query("
        SELECT 
            'New User' as activity_type,
            username as description,
            created_at as activity_date,
            role as additional_info
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'New Order' as activity_type,
            CONCAT('Order #', id, ' - $', total_amount) as description,
            created_at as activity_date,
            status as additional_info
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL 
        
        SELECT 
            'New Product' as activity_type,
            name as description,
            created_at as activity_date,
            CONCAT('$', price) as additional_info
        FROM products 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND is_deleted = 0
        
        ORDER BY activity_date DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

// Catch PDOException class for database errors
} catch (PDOException $e) {
    // String concatenation with . operator
    $error_message = "Error loading analytics: " . $e->getMessage();
    // Assign empty arrays to variables
    $monthly_revenue = [];
    $category_performance = [];
    $user_growth = [];
    $order_analytics = [];
    $top_products_revenue = [];
    $supplier_performance = [];
    $recent_activity = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/professional-admin.css?v=<?php echo time(); ?>">
    <style>
        /* Professional Analytics Dashboard CSS */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            background-attachment: fixed !important;
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            min-height: 100vh !important;
        }
        
        /* Professional Grid Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container-fluid {
            position: relative;
            z-index: 1;
        }
        
        /* Navigation Styling */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .navbar-brand {
            color: #f8fafc !important;
            font-weight: 800 !important;
            font-size: 1.5rem !important;
        }
        
        .navbar-brand i {
            color: #06b6d4 !important;
            filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.4)) !important;
        }
        
        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500 !important;
        }
        
        .nav-link:hover {
            color: #06b6d4 !important;
        }
        
        /* Analytics Header */
        .analytics-header {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
            border: 1px solid rgba(6, 182, 212, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .analytics-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #06b6d4, #3b82f6, #8b5cf6);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .analytics-header h1 {
            color: #f8fafc !important;
            font-size: 2.25rem !important;
            font-weight: 800 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .analytics-header h1 i {
            color: #06b6d4 !important;
            filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.4)) !important;
        }
        
        .analytics-header .lead {
            color: #cbd5e1 !important;
            font-size: 1.125rem !important;
            font-weight: 500 !important;
        }
        
        .back-to-dashboard {
            color: #cbd5e1 !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            margin-bottom: 1rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.3s ease !important;
        }
        
        .back-to-dashboard:hover {
            color: #06b6d4 !important;
            background: rgba(6, 182, 212, 0.1) !important;
            transform: translateX(-4px) !important;
            text-decoration: none !important;
        }
        
        /* Professional Cards */
        .card, .analytics-card, .chart-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease !important;
            margin-bottom: 2rem !important;
        }
        
        .card:hover, .analytics-card:hover, .chart-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border-color: rgba(6, 182, 212, 0.2) !important;
        }
        
        .card-header, .chart-header {
            background: rgba(6, 182, 212, 0.1) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 1.5rem 2rem !important;
            border-radius: 1rem 1rem 0 0 !important;
        }
        
        .card-header h3, .card-header h4, .card-header h5, .chart-header h3, .chart-header h4 {
            color: #f8fafc !important;
            font-weight: 600 !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }
        
        .card-header i, .chart-header i {
            color: #06b6d4 !important;
            filter: drop-shadow(0 0 4px rgba(6, 182, 212, 0.4)) !important;
        }
        
        .card-body, .chart-body {
            padding: 2rem !important;
            color: #f8fafc !important;
        }
        
        /* Tables */
        .table {
            color: #f8fafc !important;
            background: transparent !important;
            margin-bottom: 0 !important;
        }
        
        .table th {
            color: #f8fafc !important;
            background: rgba(6, 182, 212, 0.1) !important;
            border-color: rgba(6, 182, 212, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-size: 0.875rem !important;
            padding: 1rem 0.75rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(6, 182, 212, 0.08) !important;
            color: #f8fafc !important;
            transform: translateX(2px) !important;
            transition: all 0.3s ease !important;
        }
        
        /* Buttons */
        .btn {
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            border: none !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        
        .btn-primary {
            background: #3b82f6 !important;
            color: white !important;
        }
        
        .btn-primary:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4) !important;
            color: white !important;
        }
        
        .btn-outline-primary {
            background: transparent !important;
            border: 2px solid #3b82f6 !important;
            color: #3b82f6 !important;
        }
        
        .btn-outline-primary:hover {
            background: #3b82f6 !important;
            color: white !important;
            transform: translateY(-1px) !important;
        }
        
        .btn-outline-success {
            background: transparent !important;
            border: 2px solid #10b981 !important;
            color: #10b981 !important;
        }
        
        .btn-outline-success:hover {
            background: #10b981 !important;
            color: white !important;
            transform: translateY(-1px) !important;
        }
        
        /* Statistics Cards */
        .stat-card, .kpi-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            text-align: center !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .stat-card::before, .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #06b6d4;
        }
        
        .stat-card:hover, .kpi-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 20px rgba(6, 182, 212, 0.4) !important;
        }
        
        .stat-card h3, .stat-card h4, .kpi-value {
            color: #f8fafc !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 2rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .stat-card p, .kpi-label {
            color: #cbd5e1 !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            margin: 0 !important;
        }
        
        /* Chart Containers */
        .chart-container {
            background: rgba(30, 41, 59, 0.95) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            margin-bottom: 2rem !important;
        }
        
        .chart-container canvas {
            background: rgba(255, 255, 255, 0.02) !important;
            border-radius: 0.5rem !important;
        }
        
        /* Activity Feed */
        .activity-item {
            background: rgba(6, 182, 212, 0.05) !important;
            padding: 1rem 1.5rem !important;
            border-radius: 0.75rem !important;
            margin-bottom: 1rem !important;
            border-left: 4px solid #06b6d4 !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .activity-item:hover::before {
            left: 100%;
        }
        
        .activity-item:hover {
            background: rgba(6, 182, 212, 0.15) !important;
            transform: translateX(8px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        
        .activity-item h6 {
            color: #f8fafc !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .activity-item p {
            color: #cbd5e1 !important;
            margin: 0 !important;
            font-size: 0.875rem !important;
        }
        
        .activity-item small {
            color: #94a3b8 !important;
            font-size: 0.75rem !important;
        }
        
        /* Status Badges */
        .badge {
            font-weight: 600 !important;
            padding: 0.4rem 0.8rem !important;
            border-radius: 0.375rem !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }
        
        .bg-success {
            background: #10b981 !important;
            color: white !important;
        }
        
        .bg-warning {
            background: #f59e0b !important;
            color: white !important;
        }
        
        .bg-danger {
            background: #ef4444 !important;
            color: white !important;
        }
        
        .bg-info {
            background: #06b6d4 !important;
            color: white !important;
        }
        
        /* Text Colors */
        .text-muted {
            color: #94a3b8 !important;
        }
        
        .text-success {
            color: #10b981 !important;
        }
        
        .text-primary {
            color: #06b6d4 !important;
        }
        
        .text-warning {
            color: #f59e0b !important;
        }
        
        .text-danger {
            color: #ef4444 !important;
        }
        
        /* Dropdown Menus */
        .dropdown-menu {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        
        .dropdown-item {
            color: #f8fafc !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.3s ease !important;
        }
        
        .dropdown-item:hover {
            background: rgba(6, 182, 212, 0.1) !important;
            color: #06b6d4 !important;
            transform: translateX(4px) !important;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .analytics-header {
                padding: 1.5rem !important;
            }
            
            .analytics-header h1 {
                font-size: 1.75rem !important;
            }
            
            .card-body, .chart-body {
                padding: 1.5rem !important;
            }
            
            .stat-card, .kpi-card {
                padding: 1.5rem !important;
            }
        }
        
        /* Animation for page load */
        .card, .stat-card, .kpi-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <i class="fas fa-analytics"></i> <strong>Analytics Dashboard</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <!-- htmlspecialchars() prevents XSS attacks -->
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
                    <a href="admin-dashboard.php" class="back-to-dashboard" title="Back to Dashboard">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1><i class="fas fa-chart-line text-primary"></i> Advanced Analytics</h1>
                    <p class="lead mb-0">Interactive charts and business intelligence insights</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="refreshCharts()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="admin-export-analytics-csv.php" class="btn btn-outline-success">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <a href="admin-export-analytics-pdf.php" target="_blank" class="btn btn-outline-info">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conditional statement with isset() function -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <!-- Revenue Trend Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-chart-area"></i> Revenue Trend (Last 12 Months)</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleChartType('revenueChart')">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-pie-chart"></i> Order Status</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <!-- Supplier Performance -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-chart-bar"></i> Supplier Performance</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- User Growth -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5><i class="fas fa-users"></i> User Growth</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="row mb-4">
            <!-- Top Products -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-table">
                    <div class="table-header">
                        <h5><i class="fas fa-trophy"></i> Top Products by Revenue</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Revenue</th>
                                    <th>Sold</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- foreach loop with index variable -->
                                <?php foreach ($top_products_revenue as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <!-- Arithmetic operation: $index + 1 -->
                                                <div class="rank-badge"><?php echo $index + 1; ?></div>
                                                <div class="ms-2">
                                                    <!-- Array access with square brackets -->
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- number_format() function with 2 decimal places -->
                                        <td><strong>₹<?php echo number_format($product['revenue'], 2); ?></strong></td>
                                        <td><?php echo number_format($product['quantity_sold']); ?></td>
                                        <td><?php echo $product['orders_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Supplier Performance -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-table">
                    <div class="table-header">
                        <h5><i class="fas fa-industry"></i> Supplier Performance</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Products</th>
                                    <th>Revenue</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supplier_performance as $index => $supplier): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rank-badge"><?php echo $index + 1; ?></div>
                                                <div class="ms-2">
                                                    <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $supplier['products_count']; ?></td>
                                        <td><strong>₹<?php echo number_format($supplier['revenue'], 2); ?></strong></td>
                                        <td><?php echo $supplier['orders_received']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="activity-feed">
                    <div class="activity-header">
                        <h5><i class="fas fa-clock"></i> Recent Activity (Last 7 Days)</h5>
                    </div>
                    <div class="activity-timeline">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon activity-<?php echo strtolower(str_replace(' ', '-', $activity['activity_type'])); ?>">
                                    <i class="fas fa-<?php 
                                        echo $activity['activity_type'] === 'New User' ? 'user-plus' : 
                                             ($activity['activity_type'] === 'New Order' ? 'shopping-cart' : 'box');
                                    ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo $activity['activity_type']; ?></div>
                                    <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <div class="activity-meta">
                                        <!-- strtotime() converts string to timestamp, date() formats output -->
                                        <span class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?></span>
                                        <span class="activity-info"><?php echo htmlspecialchars($activity['additional_info']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($monthly_revenue, 'revenue')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($monthly_revenue, 'orders')); ?>,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
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
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

        // Order Status Chart
        const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($order_analytics, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($order_analytics, 'count')); ?>,
                    backgroundColor: [
                        '#28a745',
                        '#ffc107', 
                        '#17a2b8',
                        '#dc3545',
                        '#6f42c1'
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

        // Supplier Performance Chart (renamed from category)
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($category_performance, 'category')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($category_performance, 'revenue')); ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    }
                }
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        
        // Process user growth data
        const userGrowthData = <?php echo json_encode($user_growth); ?>;
        const months = [...new Set(userGrowthData.map(item => item.month))];
        const roles = [...new Set(userGrowthData.map(item => item.role))];
        
        const datasets = roles.map((role, index) => {
            const colors = ['#28a745', '#007bff', '#ffc107'];
            return {
                label: role.charAt(0).toUpperCase() + role.slice(1),
                data: months.map(month => {
                    const item = userGrowthData.find(d => d.month === month && d.role === role);
                    return item ? item.new_users : 0;
                }),
                backgroundColor: colors[index] || '#6c757d',
                borderColor: colors[index] || '#6c757d',
                borderWidth: 1
            };
        });

        const userGrowthChart = new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'New Users'
                        }
                    }
                }
            }
        });

        // Utility Functions
        function refreshCharts() {
            // Show loading spinner
            const refreshBtn = document.querySelector('[onclick="refreshCharts()"]');
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<span class="loading-spinner"></span> Refreshing...';
            refreshBtn.disabled = true;
            
            // Simulate loading then reload
            setTimeout(() => {
                location.reload();
            }, 1000);
        }



        function toggleChartType(chartId) {
            const chart = window[chartId];
            if (chart) {
                const currentType = chart.config.type;
                const newType = currentType === 'line' ? 'bar' : 'line';
                
                chart.config.type = newType;
                chart.update();
                
                // Show notification
                showNotification(`Chart type changed to ${newType}`, 'success');
            }
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
            const cards = document.querySelectorAll('.chart-card, .analytics-table, .activity-feed');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>