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
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Comparison operator to validate positive integer
if ($order_id <= 0) {
    header('Location: admin-orders.php');
    exit();
}
// Try-catch block for exception handling
try {
    // PDO beginTransaction() method starts database transaction
    $pdo->beginTransaction();

    // PDO prepare() method creates prepared statement with placeholder
    $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
    // execute() method binds array value to placeholder
    $stmt->execute([$order_id]);

    // Second prepared statement for main order deletion
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);

    // PDO commit() method makes all changes permanent
    $pdo->commit();

    // HTTP redirect with URL parameter
    header('Location: admin-orders.php?success=Order deleted successfully');
    exit();

// Catch PDOException class for database errors
} catch (PDOException $e) {
    // PDO rollBack() method undoes all transaction changes
    $pdo->rollBack();
    
    // Optional: error_log() function writes to server error log
    // error_log("Order deletion failed for ID $order_id: " . $e->getMessage());
    
    header('Location: admin-orders.php?error=Failed to delete order');
    exit();
}
?>
