<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supplier' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $supplier_id = $_SESSION['user_id'];

    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0) {
        header("Location: update-product.php?id=$product_id&error=Please fill all fields with valid values");
        exit();
    }

    try {
        // Verify product belongs to current supplier
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND supplier_id = ?");
        $stmt->execute([$product_id, $supplier_id]);
        $current_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_product) {
            header('Location: product-list.php?error=Product not found');
            exit();
        }

        $image_path = $current_product['image']; // Keep current image by default

        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/products/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'tif'];
            
            // Validate MIME type for additional security
            $allowed_mime_types = [
                'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif',
                'image/webp', 'image/bmp', 'image/x-windows-bmp', 'image/svg+xml',
                'image/tiff', 'image/x-tiff'
            ];
            $file_mime_type = mime_content_type($_FILES['image']['tmp_name']);

            if (in_array($file_extension, $allowed_extensions) && in_array($file_mime_type, $allowed_mime_types)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists
                    if ($current_product['image'] && file_exists($current_product['image'])) {
                        unlink($current_product['image']);
                    }
                    $image_path = $upload_path;
                }
            }
        }

        // Update product
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ? AND supplier_id = ?");
        $stmt->execute([$name, $description, $price, $stock_quantity, $image_path, $product_id, $supplier_id]);

        header('Location: product-list.php?success=Product updated successfully');
        exit();

    } catch (PDOException $e) {
        header("Location: update-product.php?id=$product_id&error=Failed to update product. Please try again.");
        exit();
    }
} else {
    header('Location: product-list.php');
    exit();
}
?>