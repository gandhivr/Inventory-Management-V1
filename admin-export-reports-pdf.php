<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['username'];

// Get date range from request or default to last 90 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-90 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+1 day'));

try {
    // Sales Report Data
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
        LIMIT 30
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
        LIMIT 15
    ");
    $products_stmt->execute([$start_date, $end_date]);
    $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Suppliers Report
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
        ORDER BY total_revenue DESC
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

    // Summary Statistics
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
    die('Error: ' . $e->getMessage());
}

// Generate HTML for PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
        }
        .summary-item h3 {
            margin: 0;
            color: #3b82f6;
            font-size: 24px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 11px;
        }
        .section-title {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
            margin: 30px 0 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #3b82f6;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .highlight {
            background: #fef3c7 !important;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .page-break {
            page-break-after: always;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Business Reports</h1>
        <p><strong>Administrator:</strong> <?php echo htmlspecialchars($admin_name); ?></p>
        <p><strong>Period:</strong> <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="summary">
        <h3 style="margin: 0 0 15px 0; color: #3b82f6;">📈 Executive Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo number_format($summary['total_orders']); ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($summary['avg_order_value'], 2); ?></h3>
                <p>Avg Order Value</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['unique_customers']); ?></h3>
                <p>Unique Customers</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['total_items_sold']); ?></h3>
                <p>Items Sold</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['completed_orders']); ?></h3>
                <p>Completed</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['pending_orders']); ?></h3>
                <p>Pending</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['cancelled_orders']); ?></h3>
                <p>Cancelled</p>
            </div>
        </div>
    </div>

    <h2 class="section-title">📦 Order Status Distribution</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Revenue</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_status_orders = array_sum(array_column($order_status, 'count'));
            foreach ($order_status as $status): 
                $percentage = $total_status_orders > 0 ? ($status['count'] / $total_status_orders) * 100 : 0;
            ?>
                <tr>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($status['status']); ?>">
                            <?php echo ucfirst($status['status']); ?>
                        </span>
                    </td>
                    <td><strong><?php echo number_format($status['count']); ?></strong></td>
                    <td>₹<?php echo number_format($status['revenue'], 2); ?></td>
                    <td><?php echo number_format($percentage, 1); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">🏆 Top Selling Products</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product Name</th>
                <th>Unit Price</th>
                <th>Quantity Sold</th>
                <th>Revenue</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_products as $index => $product): ?>
                <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                    <td><strong><?php echo $index + 1; ?></strong></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                    <td><strong><?php echo number_format($product['total_sold']); ?></strong></td>
                    <td><strong>₹<?php echo number_format($product['revenue'], 2); ?></strong></td>
                    <td><?php echo $product['order_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2 class="section-title">👥 Top Suppliers</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Supplier Name</th>
                <th>Email</th>
                <th>Products</th>
                <th>Items Sold</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_suppliers as $index => $supplier): ?>
                <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                    <td><strong><?php echo $index + 1; ?></strong></td>
                    <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                    <td style="font-size: 9px;"><?php echo htmlspecialchars($supplier['email']); ?></td>
                    <td><?php echo number_format($supplier['products_count']); ?></td>
                    <td><?php echo number_format($supplier['total_items_sold']); ?></td>
                    <td><strong>₹<?php echo number_format($supplier['total_revenue'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">🛍️ Top Buyers</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Buyer Name</th>
                <th>Email</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Avg Order</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_buyers as $index => $buyer): ?>
                <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                    <td><strong><?php echo $index + 1; ?></strong></td>
                    <td><?php echo htmlspecialchars($buyer['buyer_name']); ?></td>
                    <td style="font-size: 9px;"><?php echo htmlspecialchars($buyer['email']); ?></td>
                    <td><?php echo number_format($buyer['orders_count']); ?></td>
                    <td><strong>₹<?php echo number_format($buyer['total_spent'], 2); ?></strong></td>
                    <td>₹<?php echo number_format($buyer['avg_order_value'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">📅 Daily Sales Report (Last 30 Days)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Orders</th>
                <th>Completed</th>
                <th>Pending</th>
                <th>Cancelled</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales_data as $day): ?>
                <tr>
                    <td><strong><?php echo date('M j, Y', strtotime($day['date'])); ?></strong></td>
                    <td><?php echo $day['orders_count']; ?></td>
                    <td><?php echo $day['completed_count']; ?></td>
                    <td><?php echo $day['pending_count']; ?></td>
                    <td><?php echo $day['cancelled_count']; ?></td>
                    <td><strong>₹<?php echo number_format($day['daily_revenue'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Inventory Management System - Business Reports</p>
        <p>This is a computer-generated document. No signature required.</p>
        <p>Confidential - For internal use only</p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Output as PDF using browser's print-to-PDF functionality
header('Content-Type: text/html; charset=utf-8');
echo $html;
echo '<script>window.print();</script>';
?>
