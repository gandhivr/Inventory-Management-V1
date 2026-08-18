<?php
// Quick check for product ID 17 (Reebok Classic Leather)
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$product_id = 17; // The product from your screenshot

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Product #17 Debug Info</h1>";
    echo "<pre>";
    
    if ($product) {
        echo "Product Name: " . $product['name'] . "\n";
        echo "Image Path in DB: " . ($product['image'] ?: 'NULL/EMPTY') . "\n\n";
        
        if ($product['image']) {
            echo "--- File System Checks ---\n";
            echo "Path: " . $product['image'] . "\n";
            echo "File Exists: " . (file_exists($product['image']) ? 'YES ✓' : 'NO ✗') . "\n";
            
            if (file_exists($product['image'])) {
                echo "File Size: " . filesize($product['image']) . " bytes\n";
                echo "MIME Type: " . mime_content_type($product['image']) . "\n";
                echo "Real Path: " . realpath($product['image']) . "\n";
                echo "Is Readable: " . (is_readable($product['image']) ? 'YES ✓' : 'NO ✗') . "\n";
                
                echo "\n--- Image Preview ---\n";
                echo "</pre>";
                echo "<img src='{$product['image']}' style='max-width: 300px; border: 2px solid green;' onerror=\"this.style.display='none'; alert('Image failed to load!');\">";
                echo "<pre>\n";
            } else {
                echo "\n❌ FILE NOT FOUND!\n";
                echo "The database has a path but the file doesn't exist.\n";
                
                // Check if uploads directory exists
                echo "\n--- Directory Check ---\n";
                echo "uploads/ exists: " . (is_dir('uploads') ? 'YES' : 'NO') . "\n";
                echo "uploads/products/ exists: " . (is_dir('uploads/products') ? 'YES' : 'NO') . "\n";
                
                if (is_dir('uploads/products')) {
                    $files = glob('uploads/products/*');
                    echo "Files in uploads/products/: " . count($files) . "\n";
                    if (count($files) > 0) {
                        echo "\nFiles found:\n";
                        foreach (array_slice($files, 0, 5) as $file) {
                            echo "  - " . basename($file) . "\n";
                        }
                    }
                }
            }
        } else {
            echo "\n❌ NO IMAGE PATH IN DATABASE!\n";
            echo "The product has no image path stored.\n";
        }
        
        echo "\n--- Full Product Data ---\n";
        print_r($product);
        
    } else {
        echo "Product #17 not found in database!\n";
    }
    
    echo "</pre>";
    
    echo "<hr>";
    echo "<a href='product-list.php'>← Back to Products</a> | ";
    echo "<a href='manage-products.php'>Manage Products</a>";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
