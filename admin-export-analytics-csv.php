<?php
// Start session for authentication
session_start();

// Include database connection
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admins to login page
    header('Location: login.php');
    exit(); // Stop execution
}

try {
    // Aggregates completed orders by month
    // Shows revenue trends over time
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
        LIMIT 20
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
            (SELECT SUM(total_amount) FROM orders WHERE status = 'completed') as total_revenue
    ")->fetch(PDO::FETCH_ASSOC);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=admin_analytics_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add report header
    fputcsv($output, ['Admin Analytics Report']);
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Overall Statistics
    fputcsv($output, ['OVERALL STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Buyers', $overall_stats['total_buyers']]);
    fputcsv($output, ['Total Suppliers', $overall_stats['total_suppliers']]);
    fputcsv($output, ['Total Products', $overall_stats['total_products']]);
    fputcsv($output, ['Total Orders', $overall_stats['total_orders']]);
    fputcsv($output, ['Total Revenue (₹)', number_format($overall_stats['total_revenue'], 2)]);
    fputcsv($output, []);
    
    // Order Analytics by Status
    fputcsv($output, ['ORDER ANALYTICS BY STATUS (Last 3 Months)']);
    fputcsv($output, ['Status', 'Count', 'Average Amount (₹)', 'Total Revenue (₹)']);
    foreach ($order_analytics as $order) {
        fputcsv($output, [
            ucfirst($order['status']),
            $order['count'],
            number_format($order['avg_amount'], 2),
            number_format($order['total_revenue'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Monthly Revenue Trend
    fputcsv($output, ['MONTHLY REVENUE TREND (Last 12 Months)']);
    fputcsv($output, ['Month', 'Orders', 'Revenue (₹)']);
    foreach ($monthly_revenue as $month) {
        fputcsv($output, [
            $month['month'],
            $month['orders'],
            number_format($month['revenue'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Top Products
    fputcsv($output, ['TOP PRODUCTS BY REVENUE (Last 3 Months)']);
    fputcsv($output, ['Rank', 'Product Name', 'Revenue (₹)', 'Quantity Sold', 'Orders']);
    foreach ($top_products as $index => $product) {
        fputcsv($output, [
            $index + 1,
            $product['name'],
            number_format($product['revenue'], 2),
            $product['quantity_sold'],
            $product['orders_count']
        ]);
    }
    fputcsv($output, []);
    
    // Supplier Performance
    fputcsv($output, ['SUPPLIER PERFORMANCE']);
    fputcsv($output, ['Supplier Name', 'Products', 'Items Sold', 'Revenue (₹)', 'Orders Received']);
    foreach ($supplier_performance as $supplier) {
        fputcsv($output, [
            $supplier['supplier_name'],
            $supplier['products_count'],
            $supplier['items_sold'],
            number_format($supplier['revenue'], 2),
            $supplier['orders_received']
        ]);
    }
    
    fclose($output);
    exit();

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
