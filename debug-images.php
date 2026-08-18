<?php
// Debug script to check image paths and file existence
session_start();
require_once 'config/database.php';

// Check if user is logged in (allow all roles)
if (!isset($_SESSION['user_id'])) {
    die("Please login first. <a href='login.php'>Go to Login</a>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Debug Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        .product-debug {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin: 5px 0;
        }
        .status.success {
            background: #10b981;
            color: white;
        }
        .status.error {
            background: #ef4444;
            color: white;
        }
        .status.warning {
            background: #f59e0b;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Image Path Debug Tool</h1>
    
    <div class="info-box">
        <strong>Current Working Directory:</strong> <code><?php echo getcwd(); ?></code><br>
        <strong>Script Path:</strong> <code><?php echo __FILE__; ?></code><br>
        <strong>Document Root:</strong> <code><?php echo $_SERVER['DOCUMENT_ROOT']; ?></code>
    </div>

    <?php
    try {
        // Fetch all products with images
        $stmt = $pdo->query("SELECT id, name, image FROM products WHERE is_deleted = 0 ORDER BY id DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Products Image Analysis (" . count($products) . " products)</h2>";
        
        foreach ($products as $product) {
            echo "<div class='product-debug'>";
            echo "<h3>Product ID: {$product['id']} - {$product['name']}</h3>";
            
            echo "<table>";
            echo "<tr><th>Check</th><th>Status</th><th>Details</th></tr>";
            
            // Check 1: Image path in database
            echo "<tr>";
            echo "<td><strong>Database Path</strong></td>";
            if (!empty($product['image'])) {
                echo "<td><span class='status success'>✓ EXISTS</span></td>";
                echo "<td><code>" . htmlspecialchars($product['image']) . "</code></td>";
            } else {
                echo "<td><span class='status error'>✗ EMPTY</span></td>";
                echo "<td>No image path in database</td>";
            }
            echo "</tr>";
            
            // Check 2: File exists on server
            if (!empty($product['image'])) {
                echo "<tr>";
                echo "<td><strong>File Exists</strong></td>";
                if (file_exists($product['image'])) {
                    echo "<td><span class='status success'>✓ FOUND</span></td>";
                    echo "<td>File exists at: <code>" . realpath($product['image']) . "</code></td>";
                } else {
                    echo "<td><span class='status error'>✗ NOT FOUND</span></td>";
                    echo "<td>File does not exist at specified path</td>";
                }
                echo "</tr>";
                
                // Check 3: File is readable
                echo "<tr>";
                echo "<td><strong>File Readable</strong></td>";
                if (file_exists($product['image']) && is_readable($product['image'])) {
                    echo "<td><span class='status success'>✓ READABLE</span></td>";
                    echo "<td>File permissions OK</td>";
                } else {
                    echo "<td><span class='status error'>✗ NOT READABLE</span></td>";
                    echo "<td>Check file permissions</td>";
                }
                echo "</tr>";
                
                // Check 4: File size
                if (file_exists($product['image'])) {
                    echo "<tr>";
                    echo "<td><strong>File Size</strong></td>";
                    $size = filesize($product['image']);
                    echo "<td><span class='status success'>✓</span></td>";
                    echo "<td>" . number_format($size) . " bytes (" . number_format($size/1024, 2) . " KB)</td>";
                    echo "</tr>";
                    
                    // Check 5: MIME type
                    echo "<tr>";
                    echo "<td><strong>MIME Type</strong></td>";
                    $mime = mime_content_type($product['image']);
                    $validMimeTypes = [
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                        'image/webp', 'image/bmp', 'image/x-windows-bmp', 'image/svg+xml',
                        'image/tiff', 'image/x-tiff'
                    ];
                    if (in_array($mime, $validMimeTypes)) {
                        echo "<td><span class='status success'>✓ VALID</span></td>";
                    } else {
                        echo "<td><span class='status warning'>⚠ UNUSUAL</span></td>";
                    }
                    echo "<td><code>$mime</code></td>";
                    echo "</tr>";
                    
                    // Check 6: File extension
                    echo "<tr>";
                    echo "<td><strong>File Extension</strong></td>";
                    $ext = strtolower(pathinfo($product['image'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        echo "<td><span class='status success'>✓ VALID</span></td>";
                    } else {
                        echo "<td><span class='status warning'>⚠ UNUSUAL</span></td>";
                    }
                    echo "<td><code>.$ext</code></td>";
                    echo "</tr>";
                    
                    // Show image preview
                    echo "<tr>";
                    echo "<td><strong>Preview</strong></td>";
                    echo "<td colspan='2'>";
                    echo "<img src='{$product['image']}' class='image-preview' alt='Preview' onerror=\"this.style.display='none'; this.parentNode.innerHTML+='<span class=status error>✗ FAILED TO LOAD</span>';\">";
                    echo "</td>";
                    echo "</tr>";
                }
            }
            
            echo "</table>";
            echo "</div>";
        }
        
        // Check uploads directory
        echo "<h2>📁 Uploads Directory Check</h2>";
        echo "<div class='product-debug'>";
        
        $upload_dir = 'uploads/products/';
        echo "<table>";
        echo "<tr><th>Check</th><th>Status</th><th>Details</th></tr>";
        
        echo "<tr>";
        echo "<td><strong>Directory Exists</strong></td>";
        if (is_dir($upload_dir)) {
            echo "<td><span class='status success'>✓ EXISTS</span></td>";
            echo "<td><code>" . realpath($upload_dir) . "</code></td>";
        } else {
            echo "<td><span class='status error'>✗ NOT FOUND</span></td>";
            echo "<td>Directory does not exist</td>";
        }
        echo "</tr>";
        
        if (is_dir($upload_dir)) {
            echo "<tr>";
            echo "<td><strong>Directory Writable</strong></td>";
            if (is_writable($upload_dir)) {
                echo "<td><span class='status success'>✓ WRITABLE</span></td>";
                echo "<td>Can upload files</td>";
            } else {
                echo "<td><span class='status error'>✗ NOT WRITABLE</span></td>";
                echo "<td>Cannot upload files - check permissions</td>";
            }
            echo "</tr>";
            
            // List files in directory
            $files = glob($upload_dir . '*');
            echo "<tr>";
            echo "<td><strong>Files in Directory</strong></td>";
            echo "<td><span class='status success'>" . count($files) . " files</span></td>";
            echo "<td>";
            if (count($files) > 0) {
                echo "<ul style='margin: 0; padding-left: 20px;'>";
                foreach (array_slice($files, 0, 10) as $file) {
                    echo "<li><code>" . basename($file) . "</code> (" . number_format(filesize($file)/1024, 2) . " KB)</li>";
                }
                if (count($files) > 10) {
                    echo "<li><em>... and " . (count($files) - 10) . " more files</em></li>";
                }
                echo "</ul>";
            } else {
                echo "No files found";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='product-debug'>";
        echo "<span class='status error'>Database Error</span>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 8px;">
        <h3>Quick Actions</h3>
        <a href="manage-products.php" style="display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">← Back to Manage Products</a>
        <a href="test-jpeg-upload.php" style="display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 4px;">Test JPEG Upload</a>
    </div>
</body>
</html>
