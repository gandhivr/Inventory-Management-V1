<?php
// Start session to access user authentication and role information
// This enables access to $_SESSION variables set during login process
// Essential for maintaining user state and permissions across HTTP requests
session_start();

// Include database connection file to access PDO object
// PDO (PHP Data Objects) provides secure database connectivity with prepared statements
// This file typically contains database credentials and connection configuration
require_once 'config/database.php';

// Check if user is logged in and has admin privileges
// This is critical security check - only admin users should be able to manage products
// Prevents unauthorized users from accessing product management functionality
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to login page
    // header() function sends HTTP redirect response to browser
    header("Location: login.php");
    // exit() stops script execution immediately to prevent further processing
    exit();
}

// Handle Soft Delete Product (marks as deleted but keeps data)
// Soft delete preserves data for potential recovery and maintains referential integrity
// This approach is preferred for business applications where data recovery might be needed
//The line if (isset($_GET['soft_delete'])) checks if a specific URL parameter named soft_
// delete exists in the current request.
if (isset($_GET['soft_delete'])) {
    // Get product ID from URL parameter for soft deletion
    $id = $_GET['soft_delete'];
    try {
        // Update the product to mark it as deleted instead of removing it
        // This preserves data for potential recovery and maintains referential integrity
        // is_deleted = 1 flags the record as deleted, deleted_at stores timestamp
        // WHERE clause ensures we only soft-delete active products (is_deleted = 0)
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$id]);
        //Before: Product record with is_deleted = 0 and deleted_at = NULL
        //After: Same record with is_deleted = 1 and deleted_at = '2025-10-04 04:43:00'

        //The rowCount() method returns the number of rows affected by the last DELETE,
        //  INSERT, or UPDATE statement.
        // Check if any rows were affected (product existed and wasn't already deleted)
        // rowCount() returns number of affected rows - 0 means product not found or already deleted
        if ($stmt->rowCount() > 0) {
            // Set success message in session for display after redirect
            $_SESSION['message'] = "Product soft deleted successfully! (Can be restored)";
            $_SESSION['msg_type'] = "warning"; // Bootstrap alert class for styling
        } else {
            // Set error message if product wasn't found or already soft-deleted
            $_SESSION['message'] = "Product not found or already deleted.";
            $_SESSION['msg_type'] = "danger"; // Bootstrap alert class for error styling
        }
    } catch (PDOException $e) {
        // Handle database errors gracefully with user-friendly message
        // PDOException catches all database-related errors (connection, query, constraint violations)
        $_SESSION['message'] = "Error soft deleting product: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    // Redirect back to product management page to show updated list
    header("Location: manage-products.php");
    exit(); // Stop further script execution after redirect
}

// Handle Hard Delete Product (permanently removes from database)
// Hard delete completely removes data and associated files - cannot be recovered
// Used when permanent deletion is required or for cleaning up soft-deleted items
if (isset($_GET['hard_delete'])) {
    // Get product ID from URL parameter for permanent deletion
    //This condition checks if the hard_delete parameter exists in the URL query string. 
    //$_GET is a predefined associative array that automatically collects all variables
    //  passed to the current script via the HTTP GET method
    $id = $_GET['hard_delete'];
    try {
        // Get the image path before permanently deleting the record
        // We need image path to delete the physical file from server
        // Must retrieve this BEFORE deleting database record
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(); // Fetch single record as associative array
        
        // Permanently delete the product record from database
        // This removes all trace of the product from the database
        // Cannot be undone without database backup restoration
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete the associated image file from server if it exists
        // This prevents orphaned files from accumulating on server
        // Check both database field exists AND physical file exists
        if ($product && $product['image'] && file_exists($product['image'])) {
            unlink($product['image']); // Remove file from filesystem permanently
        }
        
        // Set success message for permanent deletion
        $_SESSION['message'] = "Product permanently deleted!";
        $_SESSION['msg_type'] = "success"; // Bootstrap success alert class
    } catch (PDOException $e) {
        // Handle any database errors during hard deletion process
        $_SESSION['message'] = "Error permanently deleting product: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: manage-products.php");
    exit();
}

// Handle Restore Product (undo soft delete)
// Restore functionality allows recovery of soft-deleted products
// This is why soft delete is valuable - provides safety net for accidental deletions
//This condition checks if the restore parameter exists in the URL query string.
if (isset($_GET['restore'])) {
    // Get product ID from URL parameter for restoration
    $id = $_GET['restore'];
    try {
        // Restore a soft-deleted product by setting is_deleted back to 0
        // Also clear the deleted_at timestamp to fully restore the record
        // WHERE clause ensures we only restore actually soft-deleted products
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND is_deleted = 1");
        $stmt->execute([$id]);
        
        // Check if restoration was successful (product existed in deleted state)
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Product restored successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            // Product not found in soft-deleted state
            $_SESSION['message'] = "Product not found in deleted items.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        // Handle database errors during restoration process
        $_SESSION['message'] = "Error restoring product: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: manage-products.php");
    exit();
}

// Handle Update Product
// Comprehensive product update system with image upload handling
// Processes form data from edit modal and updates database record
//this condition checks update_product query has submited via postmethode
if (isset($_POST['update_product'])) {
    // Extract form data from POST request for product update
    $id = $_POST['id']; // Hidden field containing product ID
    $name = $_POST['name']; // Product name from form
    $description = $_POST['description']; // Product description
    $price = $_POST['price']; // Product price
    $stock_quantity = $_POST['stock_quantity']; // Current stock level
    $current_image = $_POST['current_image'] ?? ''; // Current image path (null coalescing for safety)
    
    // Handle image upload
    // Default to keeping current image - only change if new image uploaded
    $image_path = $current_image; // Keep current image by default
    
    // Check if new image was uploaded successfully
    // $_FILES array contains uploaded file information
    // UPLOAD_ERR_OK constant means upload completed without errors
    // UPLOAD_ERR_NO_FILE means no file was selected (which is OK - we keep current image)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Define upload directory for product images
        $upload_dir = 'uploads/products/';
        
        // Create upload directory if it doesn't exist
        // mkdir() creates directory with 755 permissions recursively
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true); // 0777 = read/write/execute for all, true = recursive
        }
        
        // Extract and validate file extension for security
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'tif']; // All common image types
        
        // Also validate MIME type for additional security
        $allowed_mime_types = [
            'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif',
            'image/webp', 'image/bmp', 'image/x-windows-bmp', 'image/svg+xml',
            'image/tiff', 'image/x-tiff'
        ];
        $file_mime_type = mime_content_type($_FILES['image']['tmp_name']);
        
        // Verify uploaded file has allowed extension AND MIME type
        if (in_array($file_extension, $allowed_types) && in_array($file_mime_type, $allowed_mime_types)) {
            // For JFIF files, keep the original extension for compatibility
            // JFIF is a JPEG variant, so we preserve the extension
            $new_filename = uniqid() . '.' . $file_extension; // uniqid() creates unique identifier
            $target_file = $upload_dir . $new_filename; // Full path for new file
            
            // Move uploaded file from temporary location to permanent location
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if it exists to prevent orphaned files
                if ($current_image && file_exists($current_image)) {
                    unlink($current_image); // Remove old image file
                }
                $image_path = $target_file; // Update image path to new file
            } else {
                $_SESSION['message'] = "Error uploading image file.";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPG, JPEG, JFIF, PNG, and GIF images are allowed. (Detected: " . $file_extension . " / " . $file_mime_type . ")";
            $_SESSION['msg_type'] = "danger";
        }
    }
    
    try {
        // Update product record in database with new information
        // Prepared statement prevents SQL injection attacks
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $stock_quantity, $image_path, $id]);
        
        // Success message with image info for debugging
        if ($image_path && $image_path !== $current_image) {
            $_SESSION['message'] = "Product updated successfully! Image saved to: " . $image_path;
        } else {
            $_SESSION['message'] = "Product updated successfully!";
        }
        $_SESSION['msg_type'] = "success";
    } catch (PDOException $e) {
        // Handle any database errors during update process
        $_SESSION['message'] = "Error updating product: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: manage-products.php");
    exit();
}

// Determine which products to show based on filter
// This creates toggle functionality between active and deleted products
// URL parameter 'show_deleted=1' switches to deleted view
$show_deleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1';

// Fetch products based on current view (active or deleted)
// Conditional query execution based on current view mode
try {
    if ($show_deleted) {
        // Show only soft-deleted products
        // Include deleted_at timestamp for display in deleted products view
        // ORDER BY deleted_at DESC shows most recently deleted first
        $stmt = $pdo->query("SELECT id, name, description, price, stock_quantity, image, deleted_at FROM products WHERE is_deleted = 1 ORDER BY deleted_at DESC");
    } else {
        // Show only active (non-deleted) products
        // is_deleted = 0 filters out soft-deleted products
        // ORDER BY id DESC shows newest products first
        $stmt = $pdo->query("SELECT id, name, description, price, stock_quantity, image FROM products WHERE is_deleted = 0 ORDER BY id DESC");
    }
    // Fetch all matching products as associative array
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully - set empty array as fallback
    $products = [];
    $_SESSION['message'] = "Error fetching products: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Get counts for both active and deleted products for display
// These counts are displayed in navigation buttons to show available items
try {
    // Count active products for "View Active Products" button
    $active_count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 0")->fetchColumn();
    // Count soft-deleted products for "View Deleted Products" button
    $deleted_count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 1")->fetchColumn();
} catch (PDOException $e) {
    // Set counts to zero if database error occurs
    $active_count = 0;
    $deleted_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <!-- Bootstrap 5 CSS framework for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome icons library for UI icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Unified Dashboard CSS -->
    <link rel="stylesheet" href="css/professional-admin.css?v=<?php echo time(); ?>">
    <style>
        /* IMMEDIATE TEXT VISIBILITY FIX - OVERRIDE EVERYTHING */
        
        /* Force ALL elements to be visible */
        *, *::before, *::after {
            color: #f8fafc !important;
        }
        
        /* Force table elements specifically */
        table, table *, .table, .table * {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        /* Force table cells */
        td, th, .table td, .table th {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        /* Force all text content */
        p, span, div, strong, b, em, i, small {
            color: #f8fafc !important;
        }
        
        /* Override Bootstrap completely */
        .text-success { color: #10b981 !important; }
        .text-muted { color: #cbd5e1 !important; }
        .text-primary { color: #3b82f6 !important; }
        .text-secondary { color: #6b7280 !important; }
        .text-dark { color: #f8fafc !important; }
        .text-light { color: #f8fafc !important; }
        
        /* Professional Manage Products CSS */
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
            color: #10b981 !important;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.4)) !important;
        }
        
        /* Page Header */
        .page-header {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .page-header h1 {
            color: #f8fafc !important;
            font-size: 2.25rem !important;
            font-weight: 800 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .page-header h1 i {
            color: #10b981 !important;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.4)) !important;
        }
        
        /* Table Container */
        .table-container {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            margin-bottom: 2rem !important;
        }
        
        /* Table Styling */
        .table {
            color: #f8fafc !important;
            background: transparent !important;
            margin-bottom: 0 !important;
        }
        
        .table th {
            color: #f8fafc !important;
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: rgba(16, 185, 129, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-size: 0.875rem !important;
            padding: 1rem 0.75rem !important;
        }
        
        .table th i {
            color: #10b981 !important;
            margin-right: 0.5rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(16, 185, 129, 0.08) !important;
            color: #f8fafc !important;
            transform: translateX(2px) !important;
            transition: all 0.3s ease !important;
        }
        
        .table-hover tbody tr:hover td {
            color: #f8fafc !important;
        }
        
        /* Product Name and Description */
        .table td strong {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
        }
        
        .table td .text-muted {
            color: #cbd5e1 !important;
            font-size: 0.875rem !important;
        }
        
        /* Price Styling */
        .price {
            color: #10b981 !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        /* Stock Badges */
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
        
        /* Buttons */
        .btn {
            font-weight: 600 !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem !important;
            border: none !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.375rem !important;
            font-size: 0.875rem !important;
        }
        
        .btn-primary {
            background: #3b82f6 !important;
            color: white !important;
        }
        
        .btn-primary:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4) !important;
            color: white !important;
        }
        
        .btn-success {
            background: #10b981 !important;
            color: white !important;
        }
        
        .btn-success:hover {
            background: #059669 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4) !important;
            color: white !important;
        }
        
        .btn-warning {
            background: #f59e0b !important;
            color: white !important;
        }
        
        .btn-warning:hover {
            background: #d97706 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.4) !important;
            color: white !important;
        }
        
        .btn-danger {
            background: #ef4444 !important;
            color: white !important;
        }
        
        .btn-danger:hover {
            background: #dc2626 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4) !important;
            color: white !important;
        }
        
        /* FORCE TEXT VISIBILITY - CRITICAL OVERRIDES */
        
        /* Force all table text to be visible */
        .table td, .table th {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        /* Force product ID visibility */
        .table td:first-child {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
        }
        
        /* Force product name visibility */
        .table td:nth-child(3) {
            color: #f8fafc !important;
        }
        
        .table td:nth-child(3) strong {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        /* Force description visibility */
        .table td:nth-child(4) {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
        }
        
        /* Force supplier name visibility */
        .table td:nth-child(5) {
            color: #f8fafc !important;
            font-weight: 600 !important;
        }
        
        /* Force price visibility */
        .table td:nth-child(6) {
            color: #10b981 !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        /* Force stock visibility */
        .table td:nth-child(7) {
            color: #f8fafc !important;
            font-weight: 600 !important;
        }
        
        /* Force all text elements to be visible */
        .table * {
            color: #f8fafc !important;
        }
        
        .table .text-muted {
            color: #cbd5e1 !important;
        }
        
        .table .price, .table .amount {
            color: #10b981 !important;
        }
        
        /* Override any Bootstrap or other CSS that might be hiding text */
        .table tbody tr td {
            color: #f8fafc !important;
            font-size: 0.95rem !important;
        }
        
        .table tbody tr:hover td {
            color: #f8fafc !important;
        }
        
        /* Force visibility for specific content */
        .table td strong,
        .table td b {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        .table td small {
            color: #cbd5e1 !important;
        }
        
        /* Force table header visibility */
        .table thead th {
            color: #f8fafc !important;
            background: rgba(16, 185, 129, 0.1) !important;
            font-weight: 700 !important;
        }
        
        /* Override any conflicting styles */
        .table-responsive .table td {
            color: #f8fafc !important;
        }
        
        /* Force container background */
        .table-responsive {
            background: rgba(30, 41, 59, 0.95) !important;
            border-radius: 1rem !important;
            padding: 1rem !important;
        }
        
        /* Additional text visibility fixes */
        td[style*="color"] {
            color: #f8fafc !important;
        }
        
        /* Force visibility for any inline styled elements */
        [style*="color: #"] {
            color: #f8fafc !important;
        }
        
        /* Ensure no text is hidden */
        .table td:not(.badge):not(.btn) {
            color: #f8fafc !important;
        }
        
        /* Product name specific styling */
        .product-name {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        /* Description specific styling */
        .product-description {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
        }
        
        /* ID specific styling */
        .product-id {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-family: 'JetBrains Mono', monospace !important;
        }
        
        /* Supplier specific styling */
        .supplier-name {
            color: #f8fafc !important;
            font-weight: 600 !important;
        }
        
        /* BOOTSTRAP OVERRIDE - CRITICAL TEXT VISIBILITY FIXES */
        
        /* Override Bootstrap text colors */
        .table .text-success {
            color: #10b981 !important;
        }
        
        .table .text-muted {
            color: #cbd5e1 !important;
        }
        
        .table .fw-bold {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        /* Override table-dark class */
        .table-dark th {
            color: #f8fafc !important;
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: rgba(16, 185, 129, 0.2) !important;
        }
        
        /* Override table-striped */
        .table-striped > tbody > tr:nth-of-type(odd) > td {
            background: rgba(16, 185, 129, 0.03) !important;
            color: #f8fafc !important;
        }
        
        .table-striped > tbody > tr:nth-of-type(even) > td {
            background: transparent !important;
            color: #f8fafc !important;
        }
        
        /* Override table-hover */
        .table-hover > tbody > tr:hover > td {
            background: rgba(16, 185, 129, 0.08) !important;
            color: #f8fafc !important;
        }
        
        /* Force all table cell content to be visible */
        .table td * {
            color: inherit !important;
        }
        
        .table td strong {
            color: #f8fafc !important;
        }
        
        .table td span {
            color: inherit !important;
        }
        
        .table td span.text-success {
            color: #10b981 !important;
        }
        
        /* Specific fixes for product data */
        .table tbody tr td:nth-child(1) strong {
            color: #f8fafc !important; /* ID */
        }
        
        .table tbody tr td:nth-child(3) strong {
            color: #f8fafc !important; /* Product Name */
        }
        
        .table tbody tr td:nth-child(4) {
            color: #cbd5e1 !important; /* Description */
        }
        
        .table tbody tr td:nth-child(5) span {
            color: #10b981 !important; /* Price */
        }
        
        /* Badge overrides */
        .badge.bg-success {
            background: #10b981 !important;
            color: white !important;
        }
        
        .badge.bg-warning {
            background: #f59e0b !important;
            color: white !important;
        }
        
        .badge.bg-danger {
            background: #ef4444 !important;
            color: white !important;
        }
        
        /* Small text overrides */
        .table small {
            color: #94a3b8 !important;
        }
        
        /* Deleted row styling */
        .deleted-row td {
            color: #94a3b8 !important;
            opacity: 0.7 !important;
        }
        
        .deleted-row td strong {
            color: #94a3b8 !important;
        }
        
        /* Force visibility for any remaining hidden text */
        .table-container * {
            color: #f8fafc !important;
        }
        
        .table-container .text-success {
            color: #10b981 !important;
        }
        
        .table-container .text-muted {
            color: #cbd5e1 !important;
        }
        
        .table-container .text-warning {
            color: #f59e0b !important;
        }
        
        .table-container .text-danger {
            color: #ef4444 !important;
        }
        
        /* Additional Bootstrap class overrides */
        .table .fw-normal {
            color: #f8fafc !important;
        }
        
        .table .fw-light {
            color: #cbd5e1 !important;
        }
        
        .table .fw-semibold {
            color: #f8fafc !important;
        }
        
        /* Ensure no Bootstrap utility classes hide text */
        .table [class*="text-"] {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Custom CSS for product image display in table */
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(16, 185, 129, 0.3) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
        }
        
        .product-image:hover {
            transform: scale(1.05) !important;
            border-color: #10b981 !important;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4), 0 4px 6px -2px rgba(16, 185, 129, 0.05) !important;
        }
        
        .product-placeholder {
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1) !important;
            border: 2px solid rgba(16, 185, 129, 0.3) !important;
            border-radius: 8px;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #10b981 !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
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
            background: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
            transform: translateX(4px) !important;
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 0.75rem !important;
            border: none !important;
            padding: 1rem 1.5rem !important;
            margin-bottom: 1.5rem !important;
            font-weight: 500 !important;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
            border-left: 4px solid #10b981 !important;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
            border-left: 4px solid #ef4444 !important;
        }
        
        /* Search and Filter */
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #f8fafc !important;
            border-radius: 0.5rem !important;
            padding: 0.75rem 1rem !important;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.4) !important;
            color: #f8fafc !important;
        }
        
        .form-control::placeholder {
            color: #94a3b8 !important;
        }
        
        /* Pagination */
        .pagination .page-link {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
        }
        
        .pagination .page-link:hover {
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: #10b981 !important;
            color: #10b981 !important;
        }
        
        .pagination .page-item.active .page-link {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: white !important;
        }
        
        .product-image:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10;
            position: relative;
        }
        
        /* CSS for image preview in edit modal */
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Placeholder styling when no image is available */
        .no-image-placeholder {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }
        
        .no-image-placeholder:hover {
            background: linear-gradient(135deg, #dee2e6, #e9ecef);
            color: #495057;
        }
        
        .no-image-placeholder i {
            font-size: 1.5rem;
        }
        
        /* Styling for soft-deleted product rows */
        .deleted-row {
            background-color: #fff3cd; /* Light yellow background */
            opacity: 0.8; /* Slightly transparent to indicate deleted state */
        }
        
        /* Enhanced table styling */
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Product name styling */
        .product-name {
            font-weight: 600;
            color: #495057;
        }
        
        /* Price styling */
        .product-price {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Stock badge improvements */
        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
        }
        
        /* Action buttons styling */
        .btn-sm {
            padding: 0.375rem 0.5rem;
            margin: 0 2px;
        }
        
        /* Card enhancements */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        /* Image modal preview */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            cursor: pointer;
        }
        
        .image-modal img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }
        
        /* Modal Styling Fixes */
        .modal-content {
            background: rgba(30, 41, 59, 0.98) !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
            border-radius: 1rem !important;
            color: #f8fafc !important;
        }
        
        .modal-header {
            background: rgba(16, 185, 129, 0.1) !important;
            border-bottom: 1px solid rgba(16, 185, 129, 0.3) !important;
            color: #f8fafc !important;
        }
        
        .modal-title {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        .modal-body {
            color: #f8fafc !important;
        }
        
        .modal-body label {
            color: #f8fafc !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .modal-body .form-control {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #f8fafc !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .modal-body .form-control:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3) !important;
            color: #f8fafc !important;
        }
        
        .modal-body input[type="file"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            padding: 0.5rem !important;
            cursor: pointer !important;
        }
        
        .modal-body input[type="file"]::file-selector-button {
            background: #10b981 !important;
            color: white !important;
            border: none !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem !important;
            cursor: pointer !important;
            margin-right: 1rem !important;
            font-weight: 600 !important;
        }
        
        .modal-body input[type="file"]::file-selector-button:hover {
            background: #059669 !important;
        }
        
        .modal-body textarea {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #f8fafc !important;
        }
        
        .modal-body textarea:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3) !important;
            color: #f8fafc !important;
        }
        
        .modal-footer {
            background: rgba(16, 185, 129, 0.05) !important;
            border-top: 1px solid rgba(16, 185, 129, 0.3) !important;
        }
        
        .modal-body .card {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
        }
        
        .modal-body .card-header {
            background: rgba(16, 185, 129, 0.1) !important;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2) !important;
            color: #f8fafc !important;
        }
        
        .modal-body .card-body {
            color: #f8fafc !important;
        }
        
        .modal-body .text-muted {
            color: #cbd5e1 !important;
        }
        
        .modal-body .alert {
            background: rgba(59, 130, 246, 0.1) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            color: #3b82f6 !important;
        }
        
        .btn-close {
            filter: invert(1) !important;
            opacity: 0.8 !important;
        }
        
        .btn-close:hover {
            opacity: 1 !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <!-- Page title with icon for visual identification -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-box"></i> Manage Products</h2>
                <hr>
            </div>
        </div>

        <!-- Navigation -->
        <!-- Navigation buttons for main actions and view switching -->
        <div class="row mb-3">
            <div class="col-md-6">
                <!-- Back to admin dashboard link -->
                <a href="admin-dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <!-- Add new product button -->
                <a href="add-product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
            <div class="col-md-6 text-end">
                <!-- Toggle between active and deleted products view -->
                <!-- PHP conditional rendering based on current view state -->
                <?php if (!$show_deleted): ?>
                    <!-- Show "View Deleted Products" when currently showing active products -->
                    <a href="manage-products.php?show_deleted=1" class="btn btn-warning">
                        <i class="fas fa-trash"></i> View Deleted Products (<?php echo $deleted_count; ?>)
                    </a>
                <?php else: ?>
                    <!-- Show "View Active Products" when currently showing deleted products -->
                    <a href="manage-products.php" class="btn btn-success">
                        <i class="fas fa-box"></i> View Active Products (<?php echo $active_count; ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Messages -->
        <!-- Display success/error messages stored in session -->
        <?php if (isset($_SESSION['message'])): ?>
            <!-- Bootstrap alert component with dynamic styling based on message type -->
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                // Display message and immediately clear from session to prevent re-display
                echo $_SESSION['message']; 
                unset($_SESSION['message']); // Clear message
                unset($_SESSION['msg_type']); // Clear message type
                ?>
                <!-- Dismissible alert close button -->
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Products Table -->
        <!-- Main content area displaying product list in table format -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <!-- Card header with dynamic title based on current view -->
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <?php if ($show_deleted): ?>
                                <!-- Title for deleted products view -->
                                <i class="fas fa-trash text-warning"></i> Deleted Products (<?php echo count($products); ?> total)
                            <?php else: ?>
                                <!-- Title for active products view -->
                                <i class="fas fa-box text-success"></i> Active Products (<?php echo count($products); ?> total)
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Handle empty product list with appropriate messaging -->
                        <?php if (empty($products)): ?>
                            <div class="text-center py-4">
                                <?php if ($show_deleted): ?>
                                    <!-- Empty state for deleted products -->
                                    <i class="fas fa-trash fa-4x text-muted"></i>
                                    <h4 class="mt-3">No Deleted Products</h4>
                                    <p>All products are currently active.</p>
                                <?php else: ?>
                                    <!-- Empty state for active products -->
                                    <i class="fas fa-box-open fa-4x text-muted"></i>
                                    <h4 class="mt-3">No Products Found</h4>
                                    <p>Start by adding your first product.</p>
                                    <a href="add-product.php" class="btn btn-primary">Add Product</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Product table when products exist -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <!-- Table header with column titles -->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <!-- Conditional column for deletion timestamp -->
                                            <?php if ($show_deleted): ?>
                                                <th>Deleted At</th>
                                            <?php endif; ?>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loop through products array to display each product -->
                                        <?php foreach ($products as $product): ?>
                                            <!-- Apply special styling for deleted products -->
                                            <tr <?php echo $show_deleted ? 'class="deleted-row"' : ''; ?>>
                                                <!-- Product ID column -->
                                                <td><strong><?php echo $product['id']; ?></strong></td>
                                                <!-- Product image column with fallback for missing images -->
                                                <td>
                                                    <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                                        <!-- Display actual product image -->
                                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             class="product-image"
                                                             data-image-src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>"
                                                             data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                                             onclick="showImageModalSafe(this)"
                                                             title="Click to enlarge">
                                                    <?php else: ?>
                                                        <!-- Display placeholder when no image available -->
                                                        <div class="no-image-placeholder" title="No image available">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Product name with XSS protection -->
                                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                                <!-- Truncated description (first 50 characters) -->
                                                <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></td>
                                                <!-- Formatted price display -->
                                                <td><span class="text-success fw-bold">₹<?php echo number_format($product['price'], 2); ?></span></td>
                                                <!-- Stock status with colored badges -->
                                                <td>
                                                    <?php if ($product['stock_quantity'] > 0): ?>
                                                        <!-- In stock badge (green) -->
                                                        <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                                                    <?php else: ?>
                                                        <!-- Out of stock badge (red) -->
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Conditional deletion timestamp column -->
                                                <?php if ($show_deleted): ?>
                                                    <td>
                                                        <small class="text-muted">
                                                            <!-- Format deletion timestamp for display -->
                                                            <?php echo date('M j, Y g:i A', strtotime($product['deleted_at'])); ?>
                                                        </small>
                                                    </td>
                                                <?php endif; ?>
                                                <!-- Action buttons column with conditional display -->
                                                <td>
                                                    <?php if ($show_deleted): ?>
                                                        <!-- Actions for deleted products -->
                                                        <!-- Restore button -->
                                                        <a href="manage-products.php?restore=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           onclick="return confirm('Are you sure you want to restore this product?')"
                                                           title="Restore Product">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                        <!-- Permanent delete button -->
                                                        <a href="manage-products.php?hard_delete=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to PERMANENTLY delete this product? This action cannot be undone!')"
                                                           title="Permanently Delete">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Actions for active products -->
                                                        <!-- Edit button (triggers JavaScript modal) -->
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-product-id="<?php echo $product['id']; ?>"
                                                                data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                                                data-product-description="<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>"
                                                                data-product-price="<?php echo $product['price']; ?>"
                                                                data-product-stock="<?php echo $product['stock_quantity']; ?>"
                                                                data-product-image="<?php echo htmlspecialchars($product['image'] ?? '', ENT_QUOTES); ?>"
                                                                onclick="editProductSafe(this)"
                                                                title="Edit Product">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <!-- Soft delete button -->
                                                        <a href="manage-products.php?soft_delete=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-warning" 
                                                           onclick="return confirm('Are you sure you want to soft delete this product? It can be restored later.')"
                                                           title="Soft Delete (Recoverable)">
                                                            <i class="fas fa-archive"></i>
                                                        </a>
                                                        <!-- Hard delete button -->
                                                        <a href="manage-products.php?hard_delete=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to PERMANENTLY delete this product? This action cannot be undone!')"
                                                           title="Permanently Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal (same as original) -->
    <!-- Bootstrap modal for editing product information -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal header with title and close button -->
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- Form for product editing with file upload capability -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Hidden fields for product ID and current image path -->
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="row">
                            <!-- Left column - text inputs -->
                            <div class="col-md-6">
                                <!-- Product name input -->
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                
                                <!-- Product description textarea -->
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="edit_description" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <!-- Price input -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_price" class="form-label">Price (₹)</label>
                                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                                        </div>
                                    </div>
                                    <!-- Stock quantity input -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_stock" class="form-label">Stock Quantity</label>
                                            <input type="number" class="form-control" name="stock_quantity" id="edit_stock" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right column - image upload and preview -->
                            <div class="col-md-6">
                                <!-- Image preview container -->
                                <div id="image_preview" class="mb-3">
                                    <!-- Current image will be shown here via JavaScript -->
                                </div>
                                
                                <!-- Image file input -->
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label" style="color: #f8fafc !important; font-weight: 600;">Choose File</label>
                                    <input type="file" class="form-control" name="image" id="edit_image" accept=".jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.svg,.tiff,.tif,image/*" onchange="previewImage(this)" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
                                    <small class="text-muted" style="color: #cbd5e1 !important; display: block; margin-top: 0.5rem;">Leave empty to keep current image. Supported: JPG, PNG, GIF, WebP, BMP, SVG, TIFF. Max size: 2MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal footer with action buttons -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="update_product" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal for enlarged view -->
    <div id="imageModal" class="image-modal" onclick="hideImageModal()">
        <img id="modalImage" src="" alt="">
    </div>

    <!-- Bootstrap JavaScript bundle for modal and other interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // NEW SAFE FUNCTION: Reads data from HTML data attributes instead of inline parameters
        // This prevents JavaScript injection and handles special characters properly
        function editProductSafe(button) {
            // Get data from data attributes (automatically HTML-decoded by browser)
            const id = button.getAttribute('data-product-id');
            const name = button.getAttribute('data-product-name');
            const description = button.getAttribute('data-product-description');
            const price = button.getAttribute('data-product-price');
            const stock = button.getAttribute('data-product-stock');
            const imagePath = button.getAttribute('data-product-image');
            
            // Call the original function with safe data
            editProduct(id, name, description, price, stock, imagePath);
        }
        
        // JavaScript function to populate edit modal with product data
        // Called when edit button is clicked on any product row
        function editProduct(id, name, description, price, stock, imagePath) {
            // Populate hidden and visible form fields with current product data
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('current_image').value = imagePath || '';
            
            // Show current image in preview area
            const previewDiv = document.getElementById('image_preview');
            if (imagePath && imagePath.trim() !== '') {
                // Display current product image
                previewDiv.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <small class="text-muted">Current Image:</small>
                        </div>
                        <div class="card-body text-center">
                            <img src="${imagePath}" class="preview-image" alt="Current Product Image" onerror="this.style.display='none'; this.parentNode.innerHTML='<div class=text-danger>Image not found</div>'">
                        </div>
                    </div>
                `;
            } else {
                // Show message when no image is available
                previewDiv.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No image currently uploaded
                    </div>
                `;
            }
            
            // Show the modal using Bootstrap's JavaScript API
            var modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
        
        // JavaScript function to preview newly selected image before upload
        // Called when user selects new image file in edit modal
        function previewImage(input) {
            const previewDiv = document.getElementById('image_preview');
            
            // Check if file was selected and is accessible
            if (input.files && input.files[0]) {
                // Create FileReader to read selected image file
                const reader = new FileReader();
                
                // Define what happens when file is successfully read
                reader.onload = function(e) {
                    // Display preview of new image
                    previewDiv.innerHTML = `
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <small>New Image Preview:</small>
                            </div>
                            <div class="card-body text-center">
                                <img src="${e.target.result}" class="preview-image" alt="New Product Image">
                            </div>
                        </div>
                    `;
                }
                
                // Start reading the selected file as data URL for preview
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Safe wrapper for image modal - reads from data attributes
        function showImageModalSafe(element) {
            const imageSrc = element.getAttribute('data-image-src');
            const productName = element.getAttribute('data-product-name');
            showImageModal(imageSrc, productName);
        }
        
        // Function to show image in modal for enlarged view
        function showImageModal(imageSrc, productName) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            modalImage.src = imageSrc;
            modalImage.alt = productName;
            modal.style.display = 'block';
            
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }
        
        // Function to hide image modal
        function hideImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            
            // Restore body scrolling
            document.body.style.overflow = 'auto';
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideImageModal();
            }
        });
    </script>
</body>
</html>