<?php
// ===================================================================
// SUPPLIER PRODUCTS CSV EXPORT
// ===================================================================
// Purpose: Export supplier's product inventory to CSV format
// Features: Product details, stock levels, pricing, creation dates
// Security: Session-based authentication, role verification

// Start session to access authentication data
session_start();

// Include database connection configuration
require_once 'config/database.php';

// ===================================================================
// SECURITY CHECK: Verify supplier authentication
// ===================================================================
// Ensure only logged-in suppliers can export their products
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    // Redirect unauthorized users to login page
    header('Location: login.php');
    exit(); // Stop further execution
}

// Store supplier's user ID for database queries
$user_id = $_SESSION['user_id'];

try {
    // ===================================================================
    // DATABASE QUERY: Fetch all products owned by this supplier
    // ===================================================================
    // Retrieves complete product information for export
    $stmt = $pdo->prepare("
        SELECT id, name, description, price, stock_quantity, created_at
        FROM products 
        WHERE supplier_id = ?
        ORDER BY created_at DESC
    ");
    // Execute prepared statement with supplier ID parameter
    // Prevents SQL injection by using parameterized query
    $stmt->execute([$user_id]);
    // Fetch all products as associative array for easy access
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===================================================================
    // CSV FILE HEADERS: Configure browser for file download
    // ===================================================================
    
    // Set MIME type for CSV file
    header('Content-Type: text/csv; charset=utf-8');
    // Trigger download with filename containing current date
    // Example: my_products_2024-11-24.csv
    header('Content-Disposition: attachment; filename=my_products_' . date('Y-m-d') . '.csv');
    
    // Open output stream that writes directly to browser
    // This is more memory-efficient than building entire file in memory
    $output = fopen('php://output', 'w');
    
    // Write BOM (Byte Order Mark) for Excel UTF-8 compatibility
    // Without this, special characters may not display correctly in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // ===================================================================
    // CSV HEADER ROW: Column names for the spreadsheet
    // ===================================================================
    fputcsv($output, ['Product ID', 'Product Name', 'Description', 'Price (₹)', 'Stock Quantity', 'Created Date']);
    
    // ===================================================================
    // DATA ROWS: Write each product to CSV
    // ===================================================================
    // Loop through all products and format data for export
    foreach ($products as $product) {
        // fputcsv() automatically handles:
        // - Escaping special characters
        // - Adding quotes where needed
        // - Proper CSV formatting
        fputcsv($output, [
            $product['id'],                                        // Unique product identifier
            $product['name'],                                      // Product name
            $product['description'],                               // Full description
            number_format($product['price'], 2),                  // Price with 2 decimals
            $product['stock_quantity'],                           // Current stock level
            date('Y-m-d H:i:s', strtotime($product['created_at'])) // Formatted creation date
        ]);
    }
    
    // Close the file stream
    fclose($output);
    // Exit to prevent any additional output that could corrupt the CSV
    exit();

} catch (PDOException $e) {
    // Handle database errors gracefully
    // In production, you might want to log this instead of displaying
    die('Error: ' . $e->getMessage());
}
?>
