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

// Ternary operators with isset() for parameter extraction
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';

// HTTP headers for CSV file download
header('Content-Type: text/csv');
// String concatenation with . operator for dynamic filename
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');

// fopen() function creates file handle for output stream
$output = fopen('php://output', 'w');

// Try-catch block for exception handling
try {
    // Switch statement for different report types
    switch ($report_type) {
        case 'sales':
            // fputcsv() function writes array as CSV row
            fputcsv($output, ['Sales Report - ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, ['Date', 'Orders Count', 'Daily Revenue']);
            
            // PDO prepare() method with SQL DATE() function
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
            // execute() method with array parameter binding
            $stmt->execute([$start_date, $end_date]);
            
            // while loop with fetch() method for row iteration
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['date'],
                    $row['orders_count'],
                    // String concatenation with number_format() function
                    '₹' . number_format($row['daily_revenue'], 2)
                ]);
            }
            break;

        case 'products':
            fputcsv($output, ['Top Products Report - ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, ['Product Name', 'Unit Price', 'Total Sold', 'Revenue', 'Order Count']);
            
            // Query with INNER JOINs and DISTINCT keyword
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
                // Array with formatted currency values
                fputcsv($output, [
                    $row['name'],
                    '₹' . number_format($row['price'], 2),
                    $row['total_sold'],
                    '₹' . number_format($row['revenue'], 2),
                    $row['order_count']
                ]);
            }
            break;

        case 'suppliers':
            // Top Suppliers CSV
            fputcsv($output, ['Top Suppliers Report - ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, ['Supplier Name', 'Email', 'Products Count', 'Items Sold', 'Total Revenue']);
            
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
                fputcsv($output, [
                    $row['supplier_name'],
                    $row['email'],
                    $row['products_count'],
                    $row['total_items_sold'],
                    '₹' . number_format($row['total_revenue'], 2)
                ]);
            }
            break;

        case 'buyers':
            // Top Buyers CSV
            fputcsv($output, ['Top Buyers Report - ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, ['Buyer Name', 'Email', 'Orders Count', 'Total Spent', 'Average Order Value']);
            
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
                fputcsv($output, [
                    $row['buyer_name'],
                    $row['email'],
                    $row['orders_count'],
                    '₹' . number_format($row['total_spent'], 2),
                    '₹' . number_format($row['avg_order_value'], 2)
                ]);
            }
            break;

        case 'orders':
            fputcsv($output, ['Orders Report - ' . $start_date . ' to ' . $end_date]);
            fputcsv($output, ['Order ID', 'Customer', 'Date', 'Status', 'Total Amount', 'Items Count']);
            
            // Query with LEFT JOIN for optional relationships
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
                fputcsv($output, [
                    // String concatenation with # prefix
                    '#' . $row['id'],
                    $row['customer_name'],
                    // date() and strtotime() functions for formatting
                    date('Y-m-d H:i', strtotime($row['created_at'])),
                    // ucfirst() capitalizes first letter
                    ucfirst($row['status']),
                    '₹' . number_format($row['total_amount'], 2),
                    $row['items_count']
                ]);
            }
            break;

        default:
            // Default case in switch statement
            fputcsv($output, ['Summary Report - ' . $start_date . ' to ' . $end_date]);
            // Empty array creates blank CSV row
            fputcsv($output, []);
            
            // Variable assignment from database query
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
            // fetch() method returns single row as associative array
            $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Multiple fputcsv() calls with array access
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Orders', $summary['total_orders']]);
            fputcsv($output, ['Total Revenue', '₹' . number_format($summary['total_revenue'], 2)]);
            fputcsv($output, ['Average Order Value', '₹' . number_format($summary['avg_order_value'], 2)]);
            fputcsv($output, ['Unique Customers', $summary['unique_customers']]);
            fputcsv($output, ['Total Items Sold', $summary['total_items_sold']]);
            
            fputcsv($output, []);
            fputcsv($output, ['Order Status Distribution']);
            fputcsv($output, ['Status', 'Count', 'Revenue']);
            
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
                fputcsv($output, [
                    ucfirst($row['status']),
                    $row['count'],
                    '₹' . number_format($row['revenue'], 2)
                ]);
            }
            break;
    }

// Catch PDOException class for database errors
} catch (PDOException $e) {
    // getMessage() method gets exception error message
    fputcsv($output, ['Error generating report: ' . $e->getMessage()]);
}

// fclose() function closes file handle
fclose($output);
// exit() stops script execution
exit();
?>