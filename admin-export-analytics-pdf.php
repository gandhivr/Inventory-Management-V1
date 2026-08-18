<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['username'];

try {
    // Monthly Revenue
    $monthly_revenue = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND status = 'completed'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Top Products by Revenue
    $top_products = $pdo->query("
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
        LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Supplier Performance
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

    // Order Analytics by Status
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

    // Overall Statistics
    $overall_stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'buyer') as total_buyers,
            (SELECT COUNT(*) FROM users WHERE role = 'supplier') as total_suppliers,
            (SELECT COUNT(*) FROM products WHERE is_deleted = 0) as total_products,
            (SELECT COUNT(*) FROM orders) as total_orders,
            (SELECT SUM(total_amount) FROM orders WHERE status = 'completed') as total_revenue,
            (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders
    ")->fetch(PDO::FETCH_ASSOC);

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
            border-bottom: 3px solid #06b6d4;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #06b6d4;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #ecfeff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #06b6d4;
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
            color: #06b6d4;
            font-size: 24px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .section-title {
            color: #06b6d4;
            border-bottom: 2px solid #06b6d4;
            padding-bottom: 10px;
            margin: 30px 0 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #06b6d4;
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
        <h1>📊 System Analytics Report</h1>
        <p><strong>Administrator:</strong> <?php echo htmlspecialchars($admin_name); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="summary">
        <h3 style="margin: 0 0 15px 0; color: #06b6d4;">📈 System Overview</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['total_buyers']); ?></h3>
                <p>Total Buyers</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['total_suppliers']); ?></h3>
                <p>Total Suppliers</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['total_products']); ?></h3>
                <p>Total Products</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['total_orders']); ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($overall_stats['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['completed_orders']); ?></h3>
                <p>Completed Orders</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($overall_stats['pending_orders']); ?></h3>
                <p>Pending Orders</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo $overall_stats['total_orders'] > 0 ? number_format($overall_stats['total_revenue'] / $overall_stats['total_orders'], 2) : '0.00'; ?></h3>
                <p>Avg Order Value</p>
            </div>
        </div>
    </div>

    <h2 class="section-title">📦 Order Analytics by Status (Last 3 Months)</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Average Amount</th>
                <th>Total Revenue</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_status_orders = array_sum(array_column($order_analytics, 'count'));
            foreach ($order_analytics as $order): 
                $percentage = $total_status_orders > 0 ? ($order['count'] / $total_status_orders) * 100 : 0;
            ?>
                <tr>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                    <td><strong><?php echo number_format($order['count']); ?></strong></td>
                    <td>₹<?php echo number_format($order['avg_amount'], 2); ?></td>
                    <td><strong>₹<?php echo number_format($order['total_revenue'], 2); ?></strong></td>
                    <td><?php echo number_format($percentage, 1); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">🏆 Top Products by Revenue (Last 3 Months)</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product Name</th>
                <th>Revenue</th>
                <th>Quantity Sold</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_products as $index => $product): ?>
                <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                    <td><strong><?php echo $index + 1; ?></strong></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><strong>₹<?php echo number_format($product['revenue'], 2); ?></strong></td>
                    <td><?php echo number_format($product['quantity_sold']); ?></td>
                    <td><?php echo $product['orders_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2 class="section-title">👥 Supplier Performance</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Supplier Name</th>
                <th>Products</th>
                <th>Items Sold</th>
                <th>Revenue</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($supplier_performance as $index => $supplier): ?>
                <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                    <td><strong><?php echo $index + 1; ?></strong></td>
                    <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                    <td><?php echo number_format($supplier['products_count']); ?></td>
                    <td><?php echo number_format($supplier['items_sold']); ?></td>
                    <td><strong>₹<?php echo number_format($supplier['revenue'], 2); ?></strong></td>
                    <td><?php echo number_format($supplier['orders_received']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">📅 Monthly Revenue Trend (Last 12 Months)</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Orders</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_monthly_revenue = 0;
            $total_monthly_orders = 0;
            foreach ($monthly_revenue as $month): 
                $total_monthly_revenue += $month['revenue'];
                $total_monthly_orders += $month['orders'];
            ?>
                <tr>
                    <td><strong><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></strong></td>
                    <td><?php echo number_format($month['orders']); ?></td>
                    <td><strong>₹<?php echo number_format($month['revenue'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background: #ecfeff; font-weight: bold;">
                <td>TOTAL</td>
                <td><?php echo number_format($total_monthly_orders); ?></td>
                <td>₹<?php echo number_format($total_monthly_revenue, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Inventory Management System - Admin Analytics Report</p>
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
