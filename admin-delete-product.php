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
// Ternary operator with isset() and intval() for safe type conversion
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Comparison operator to validate positive integer
if ($product_id <= 0) {
    header('Location: manage-products.php');
    exit();
}
// Try-catch block for exception handling
try {
    // PDO prepare() method creates prepared statement with placeholder
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    // execute() method binds array value to placeholder
    $stmt->execute([$product_id]);
    // fetch() method returns single row as associative array
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Logical NOT operator to check if product exists
    if (!$product) {
        header('Location: manage-products.php?error=Product not found');
        exit();
    }

    // Second prepared statement for product deletion
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    // Logical AND operator with file_exists() function
    if ($product['image'] && file_exists($product['image'])) {
        // unlink() function deletes file from filesystem
        unlink($product['image']);
    }

    // HTTP redirect with URL parameter
    header('Location: manage-products.php?success=Product deleted successfully');
    exit();

// Catch PDOException class for database errors
} catch (PDOException $e) {
    // Optional: error_log() function writes to server error log
    // error_log("Product deletion failed for ID $product_id: " . $e->getMessage());
    
    header('Location: manage-products.php?error=Failed to delete product');
    exit();
}
?>

