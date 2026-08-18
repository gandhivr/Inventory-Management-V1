<?php
// ===================================================================
// SUPPLIER ORDERS CSV EXPORT
// ===================================================================
// Purpose: Export supplier's orders to CSV format for Excel/Sheets
// Features: UTF-8 encoding, date filtering, order details with items
// Security: Session validation, role checking, prepared statements

// Start session to access user authentication data
session_start();

// Include database connection file
require_once 'config/database.php';

// ===================================================================
// AUTHENTICATION CHECK
// ===================================================================
// Verify user is logged in and has supplier role
// Prevents unauthorized access to supplier data
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    // Redirect to login if not authenticated or not a supplier
    header('Location: login.php');
    exit(); // Stop script execution
}

// Get supplier's user ID from session
$user_id = $_SESSION['user_id'];

try {
    // ===================================================================
    // DATABASE QUERY: Fetch all orders containing supplier's products
    // ===================================================================
    // Uses JOIN to connect orders with order items and products
    // Filters by supplier_id to show only this supplier's orders
    // Groups items by order for better display
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.id, o.total_amount, o.status, o.created_at, u.username as buyer_name,
               GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ', Price: ₹', oi.price, ')') SEPARATOR '; ') as items
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    // Execute query with supplier's user ID as parameter
    $stmt->execute([$user_id]);
    // Fetch all results as associative array
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===================================================================
    // CSV FILE GENERATION
    // ===================================================================
    
    // Set HTTP headers to trigger file download
    // Content-Type tells browser this is a CSV file
    header('Content-Type: text/csv; charset=utf-8');
    // Content-Disposition triggers download with filename including current date
    header('Content-Disposition: attachment; filename=my_orders_' . date('Y-m-d') . '.csv');
    
    // Create output stream that writes directly to browser
    // php://output is a special stream that sends data to the output buffer
    $output = fopen('php://output', 'w');
    
    // Add BOM (Byte Order Mark) for Excel UTF-8 support
    // This ensures special characters display correctly in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header row with column names
    // fputcsv() automatically handles escaping and formatting
    fputcsv($output, ['Order ID', 'Buyer Name', 'My Items', 'Order Total (₹)', 'Status', 'Order Date']);
    
    // ===================================================================
    // DATA ROWS: Loop through orders and write to CSV
    // ===================================================================
    foreach ($orders as $order) {
        // Write each order as a CSV row
        // number_format() adds thousands separator and 2 decimal places
        // ucfirst() capitalizes first letter of status
        // date() formats timestamp to readable date
        fputcsv($output, [
            $order['id'],                                           // Order ID
            $order['buyer_name'],                                   // Buyer's username
            $order['items'],                                        // Concatenated item list
            number_format($order['total_amount'], 2),              // Formatted price
            ucfirst($order['status']),                             // Capitalized status
            date('Y-m-d H:i:s', strtotime($order['created_at']))  // Formatted date
        ]);
    }
    
    // Close the output stream
    fclose($output);
    // Exit to prevent any additional output
    exit();

} catch (PDOException $e) {
    // Catch database errors and display error message
    die('Error: ' . $e->getMessage());
}
?>
