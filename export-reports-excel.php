<?php
// Start session to access user authentication data
session_start();
// Include database connection configuration
require_once 'config/database.php';

// Authentication check using session variables and conditional logic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // HTTP header redirect for unauthorized access
    header('Location: login.php');
    exit();
}

// Ternary operators for parameter validation with default values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';

// HTTP headers for Excel file download and browser cache control
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// HTML output with Excel-specific XML namespaces for proper formatting
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';

// Exception handling with try-catch block
try {
    // Switch statement for different report types
    switch ($report_type) {
        case 'sales':
            // String concatenation for dynamic HTML content
            echo '<h2>Sales Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            echo '<table border="1">';
            echo '<tr style="background-color: #4CAF50; color: white; font-weight: bold;">';
            echo '<th>Date</th><th>Orders Count</th><th>Daily Revenue</th>';
            echo '</tr>';
            
            // Prepared statement with parameterized query for security
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as orders_count,
                    SUM(total_amount) as daily_revenue
                FROM orders 
                WHERE created_at BETWEEN ? AND ? 
                AND status = 'completed'
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            // While loop with PDO fetch method for result iteration
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . $row['date'] . '</td>';
                echo '<td>' . $row['orders_count'] . '</td>';
                echo '<td>₹' . number_format($row['daily_revenue'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        case 'products':
            // HTML table generation for product analytics
            echo '<h2>Top Products Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            echo '<table border="1">';
            echo '<tr style="background-color: #2196F3; color: white; font-weight: bold;">';
            echo '<th>Product Name</th><th>Unit Price</th><th>Total Sold</th><th>Revenue</th><th>Order Count</th>';
            echo '</tr>';
            
            // Complex SQL query with JOIN operations and aggregate functions
            $stmt = $pdo->prepare("
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
            ");
            $stmt->execute([$start_date, $end_date]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                // XSS prevention using htmlspecialchars function
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>₹' . number_format($row['price'], 2) . '</td>';
                echo '<td>' . $row['total_sold'] . '</td>';
                echo '<td>₹' . number_format($row['revenue'], 2) . '</td>';
                echo '<td>' . $row['order_count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        case 'suppliers':
            // Supplier performance report generation
            echo '<h2>Top Suppliers Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            echo '<table border="1">';
            echo '<tr style="background-color: #FF9800; color: white; font-weight: bold;">';
            echo '<th>Supplier Name</th><th>Email</th><th>Products Count</th><th>Items Sold</th><th>Total Revenue</th>';
            echo '</tr>';
            
            // Multi-table JOIN with role-based filtering and aggregation
            $stmt = $pdo->prepare("
                SELECT 
                    u.username as supplier_name,
                    u.email,
                    COUNT(DISTINCT p.id) as products_count,
                    SUM(oi.quantity) as total_items_sold,
                    SUM(oi.quantity * oi.price) as total_revenue
                FROM users u
                JOIN products p ON u.id = p.supplier_id
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE u.role = 'supplier'
                AND o.created_at BETWEEN ? AND ?
                AND o.status = 'completed'
                GROUP BY u.id
                ORDER BY total_revenue DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['supplier_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . $row['products_count'] . '</td>';
                echo '<td>' . $row['total_items_sold'] . '</td>';
                echo '<td>₹' . number_format($row['total_revenue'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        case 'buyers':
            // Customer analytics with spending patterns
            echo '<h2>Top Buyers Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            echo '<table border="1">';
            echo '<tr style="background-color: #9C27B0; color: white; font-weight: bold;">';
            echo '<th>Buyer Name</th><th>Email</th><th>Orders Count</th><th>Total Spent</th><th>Average Order Value</th>';
            echo '</tr>';
            
            // Statistical analysis using COUNT, SUM, and AVG aggregate functions
            $stmt = $pdo->prepare("
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
            ");
            $stmt->execute([$start_date, $end_date]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['buyer_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . $row['orders_count'] . '</td>';
                echo '<td>₹' . number_format($row['total_spent'], 2) . '</td>';
                echo '<td>₹' . number_format($row['avg_order_value'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        case 'orders':
            // Comprehensive order listing with customer details
            echo '<h2>Orders Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            echo '<table border="1">';
            echo '<tr style="background-color: #607D8B; color: white; font-weight: bold;">';
            echo '<th>Order ID</th><th>Customer</th><th>Date</th><th>Status</th><th>Total Amount</th><th>Items Count</th>';
            echo '</tr>';
            
            // LEFT JOIN to include orders even without items, with GROUP BY aggregation
            $stmt = $pdo->prepare("
                SELECT 
                    o.id,
                    u.username as customer_name,
                    o.created_at,
                    o.status,
                    o.total_amount,
                    COUNT(oi.id) as items_count
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>#' . $row['id'] . '</td>';
                echo '<td>' . htmlspecialchars($row['customer_name']) . '</td>';
                echo '<td>' . date('Y-m-d H:i', strtotime($row['created_at'])) . '</td>';
                echo '<td>' . ucfirst($row['status']) . '</td>';
                echo '<td>₹' . number_format($row['total_amount'], 2) . '</td>';
                echo '<td>' . $row['items_count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        default:
            // Default case for comprehensive business summary
            echo '<h2>Summary Report - ' . $start_date . ' to ' . $end_date . '</h2>';
            
            // Business metrics calculation with multiple aggregate functions
            $summary_stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as avg_order_value,
                    COUNT(DISTINCT o.user_id) as unique_customers,
                    SUM(oi.quantity) as total_items_sold
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status = 'completed'
            ");
            $summary_stmt->execute([$start_date, $end_date]);
            // Associative array from database result
            $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<h3>Summary Statistics</h3>';
            echo '<table border="1">';
            echo '<tr style="background-color: #4CAF50; color: white; font-weight: bold;">';
            echo '<th>Metric</th><th>Value</th>';
            echo '</tr>';
            echo '<tr><td>Total Orders</td><td>' . number_format($summary['total_orders']) . '</td></tr>';
            echo '<tr><td>Total Revenue</td><td>₹' . number_format($summary['total_revenue'], 2) . '</td></tr>';
            echo '<tr><td>Average Order Value</td><td>₹' . number_format($summary['avg_order_value'], 2) . '</td></tr>';
            echo '<tr><td>Unique Customers</td><td>' . number_format($summary['unique_customers']) . '</td></tr>';
            echo '<tr><td>Total Items Sold</td><td>' . number_format($summary['total_items_sold']) . '</td></tr>';
            echo '</table>';
            
            echo '<br><h3>Order Status Distribution</h3>';
            echo '<table border="1">';
            echo '<tr style="background-color: #2196F3; color: white; font-weight: bold;">';
            echo '<th>Status</th><th>Count</th><th>Revenue</th>';
            echo '</tr>';
            
            // Status-based grouping for order distribution analysis
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
            
            while ($row = $status_stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . ucfirst($row['status']) . '</td>';
                echo '<td>' . $row['count'] . '</td>';
                echo '<td>₹' . number_format($row['revenue'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;
    }

} catch (PDOException $e) {
    echo '<h2 style="color: red;">Error generating report</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<br><br>';
echo '<p style="font-size: 12px; color: #666;">Generated on: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p style="font-size: 12px; color: #666;">Inventory Management System - Admin Reports</p>';
echo '</body></html>';

exit();
?>