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
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN oi.order_id END) as order_count,
            p.created_at
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        WHERE p.supplier_id = ? AND p.is_deleted = 0
        GROUP BY p.id
        ORDER BY total_revenue DESC
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
        ORDER BY month ASC
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

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=analytics_report_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add report header
    fputcsv($output, ['Supplier Analytics Report']);
    fputcsv($output, ['Supplier', $supplier_name]);
    fputcsv($output, ['Date Range', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary Statistics Section
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Products', $summary['total_products']]);
    fputcsv($output, ['In Stock Products', $summary['in_stock_products']]);
    fputcsv($output, ['Out of Stock Products', $summary['out_of_stock_products']]);
    fputcsv($output, ['Total Orders', $summary['total_orders']]);
    fputcsv($output, ['Completed Orders', $summary['completed_orders']]);
    fputcsv($output, ['Total Items Sold', $summary['total_items_sold']]);
    fputcsv($output, ['Total Revenue (₹)', number_format($summary['total_revenue'], 2)]);
    fputcsv($output, ['Average Order Value (₹)', number_format($summary['avg_order_value'], 2)]);
    fputcsv($output, []);
    
    // Product Performance Section
    fputcsv($output, ['PRODUCT PERFORMANCE']);
    fputcsv($output, ['Product ID', 'Product Name', 'Price (₹)', 'Current Stock', 'Units Sold', 'Revenue (₹)', 'Orders', 'Created Date']);
    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'],
            $product['name'],
            number_format($product['price'], 2),
            $product['stock_quantity'],
            $product['total_sold'],
            number_format($product['total_revenue'], 2),
            $product['order_count'],
            date('Y-m-d', strtotime($product['created_at']))
        ]);
    }
    fputcsv($output, []);
    
    // Monthly Sales Trend Section
    fputcsv($output, ['MONTHLY SALES TREND']);
    fputcsv($output, ['Month', 'Total Orders', 'Completed Orders', 'Items Sold', 'Revenue (₹)']);
    foreach ($monthly_data as $month) {
        fputcsv($output, [
            $month['month'],
            $month['orders'],
            $month['completed_orders'],
            $month['items_sold'],
            number_format($month['revenue'], 2)
        ]);
    }
    
    fclose($output);
    exit();

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
