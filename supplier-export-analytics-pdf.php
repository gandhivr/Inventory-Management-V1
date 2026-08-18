<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$supplier_id = $_SESSION['user_id'];
$supplier_name = $_SESSION['username'];

// Get date range from request or default to last 90 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-90 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+1 day'));

try {
    // Get product performance data
    $product_performance = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END), 0) as total_sold,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END), 0) as total_revenue,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN oi.order_id END) as order_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        WHERE p.supplier_id = ? AND p.is_deleted = 0
        GROUP BY p.id
        ORDER BY total_revenue DESC
        LIMIT 20
    ");
    $product_performance->execute([$start_date, $end_date, $supplier_id]);
    $products = $product_performance->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly sales data
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
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthly_sales->execute([$supplier_id]);
    $monthly_data = $monthly_sales->fetchAll(PDO::FETCH_ASSOC);

    // Get summary statistics
    $summary_stats = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0) as total_products,
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0 AND stock_quantity > 0) as in_stock_products,
            (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND is_deleted = 0 AND stock_quantity = 0) as out_of_stock_products,
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
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
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
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
            color: #667eea;
            font-size: 28px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 13px;
        }
        .section-title {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin: 30px 0 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
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
            font-size: 11px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Analytics Report</h1>
        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($supplier_name); ?></p>
        <p><strong>Period:</strong> <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="summary">
        <h3 style="margin: 0 0 15px 0; color: #667eea;">📈 Performance Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo number_format($summary['total_products']); ?></h3>
                <p>Total Products</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['total_orders']); ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['total_items_sold']); ?></h3>
                <p>Items Sold</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['in_stock_products']); ?></h3>
                <p>In Stock</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['out_of_stock_products']); ?></h3>
                <p>Out of Stock</p>
            </div>
            <div class="summary-item">
                <h3><?php echo number_format($summary['completed_orders']); ?></h3>
                <p>Completed Orders</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($summary['avg_order_value'], 2); ?></h3>
                <p>Avg Order Value</p>
            </div>
        </div>
    </div>

    <h2 class="section-title">🏆 Top Product Performance</h2>
    <?php if (empty($products)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">No product data available for selected period.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $index => $product): ?>
                    <tr <?php echo $index < 3 ? 'class="highlight"' : ''; ?>>
                        <td><strong><?php echo $index + 1; ?></strong></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>₹<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock_quantity']; ?></td>
                        <td><strong><?php echo number_format($product['total_sold']); ?></strong></td>
                        <td><strong>₹<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                        <td><?php echo $product['order_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="page-break"></div>

    <h2 class="section-title">📅 Monthly Sales Trend</h2>
    <?php if (empty($monthly_data)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">No monthly sales data available.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Orders</th>
                    <th>Completed Orders</th>
                    <th>Items Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_monthly_revenue = 0;
                $total_monthly_orders = 0;
                foreach ($monthly_data as $month): 
                    $total_monthly_revenue += $month['revenue'];
                    $total_monthly_orders += $month['orders'];
                ?>
                    <tr>
                        <td><strong><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></strong></td>
                        <td><?php echo number_format($month['orders']); ?></td>
                        <td><?php echo number_format($month['completed_orders']); ?></td>
                        <td><?php echo number_format($month['items_sold']); ?></td>
                        <td><strong>₹<?php echo number_format($month['revenue'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background: #f0f4ff; font-weight: bold;">
                    <td>TOTAL</td>
                    <td><?php echo number_format($total_monthly_orders); ?></td>
                    <td>-</td>
                    <td>-</td>
                    <td>₹<?php echo number_format($total_monthly_revenue, 2); ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p>Inventory Management System - Supplier Analytics Report</p>
        <p>This is a computer-generated document. No signature required.</p>
        <p>For questions or concerns, please contact your system administrator.</p>
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
