<?php
// Start session to access $_SESSION variables
session_start();
// Include database connection file to use $pdo object
require_once 'config/database.php';

// Check session variables exist and validate user role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supplier' && $_SESSION['role'] !== 'admin')) {
    // Send HTTP redirect header and stop execution
    header('Location: login.php');
    exit();
}

// Check $_SERVER superglobal for request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Extract data from $_POST superglobal and sanitize
    $name = trim($_POST['name']); // trim() removes whitespace
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']); // floatval() converts to float
    $stock_quantity = intval($_POST['stock_quantity']); // intval() converts to integer
    
    // Conditional logic based on session role value
    if ($_SESSION['role'] === 'admin') {
        // Ternary operator: condition ? value_if_true : value_if_false
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        // Conditional statement with comparison operator
        if ($supplier_id <= 0) {
            header('Location: add-product.php?error=Please select a supplier');
            exit();
        }
    } else {
        // Assign session variable to local variable
        $supplier_id = $_SESSION['user_id'];
    }

    // Use empty() function and logical operators (||) for validation
    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0) {
        header('Location: add-product.php?error=Please fill all fields with valid values');
        exit();
    }
    // Initialize variable with null value
    $image_path = null;
    // Check $_FILES superglobal and use logical AND (&&) operator
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        
        // Use is_dir() function to check directory existence
        if (!is_dir($upload_dir)) {
            // mkdir() function creates directory with permissions and recursive flag
            mkdir($upload_dir, 0755, true);
        }

        // Chain functions: pathinfo() gets file info, strtolower() converts case
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        // Define array of allowed values - supports all common image formats
        $allowed_extensions = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'tif'];
        
        // Also validate MIME type for additional security
        $allowed_mime_types = [
            'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif',
            'image/webp', 'image/bmp', 'image/x-windows-bmp', 'image/svg+xml',
            'image/tiff', 'image/x-tiff'
        ];
        $file_mime_type = mime_content_type($_FILES['image']['tmp_name']);

        // Use in_array() function to check if value exists in array
        if (in_array($file_extension, $allowed_extensions) && in_array($file_mime_type, $allowed_mime_types)) {
            // String concatenation with . operator
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            // move_uploaded_file() returns boolean, used in if condition
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            }
        }
    }

    // Try-catch block for exception handling
    try {
        // PDO prepare() method creates prepared statement with placeholders (?)
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_quantity, image, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
        // execute() method binds array values to placeholders in order
        $stmt->execute([$name, $description, $price, $stock_quantity, $image_path, $supplier_id]);

        header('Location: add-product.php?success=Product added successfully');
        exit();

    } catch (PDOException $e) {
        // error_log() writes to server error log
        error_log("Product insertion error: " . $e->getMessage());
        
        // Use getCode() method to check exception error code
        if ($e->getCode() == '23000') {
            header('Location: add-product.php?error=Database constraint error. Please check your data.');
        // strpos() searches for substring, !== checks for exact false
        } elseif (strpos($e->getMessage(), 'products') !== false) {
            header('Location: add-product.php?error=Products table error. Please run setup.php');
        } else {
            // urlencode() encodes string for URL parameters
            header('Location: add-product.php?error=Database error: ' . urlencode($e->getMessage()));
        }
        exit();
    }
} else {
    // Else clause handles all non-POST requests
    header('Location: add-product.php');
    exit();
}
?>