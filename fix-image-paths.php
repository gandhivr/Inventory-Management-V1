<?php
/**
 * Image Path Fixer for Online Hosting
 * 
 * This script helps fix image display issues when moving from localhost to online hosting
 * Run this ONCE after uploading your site to fix all image paths
 * 
 * SECURITY: Delete this file after running it!
 */

session_start();
require_once 'config/database.php';

// Security: Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Admin login required.');
}

$results = [];
$errors = [];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_paths'])) {
    try {
        // Get all products with images
        $stmt = $pdo->query("SELECT id, name, image, image_path FROM products WHERE image IS NOT NULL OR image_path IS NOT NULL");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $image_field = $product['image'] ?: $product['image_path'];
            
            if (empty($image_field)) {
                continue;
            }
            
            // Check if file exists
            $file_exists = file_exists($image_field);
            
            // Try to fix path if it doesn't exist
            if (!$file_exists) {
                // Try different path variations
                $possible_paths = [
                    $image_field,
                    'uploads/products/' . basename($image_field),
                    '../uploads/products/' . basename($image_field),
                    __DIR__ . '/' . $image_field,
                    __DIR__ . '/uploads/products/' . basename($image_field)
                ];
                
                $fixed_path = null;
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $fixed_path = 'uploads/products/' . basename($path);
                        break;
                    }
                }
                
                if ($fixed_path) {
                    // Update database with correct path
                    $update_stmt = $pdo->prepare("UPDATE products SET image = ?, image_path = ? WHERE id = ?");
                    $update_stmt->execute([$fixed_path, $fixed_path, $product['id']]);
                    
                    $results[] = "✅ Fixed: {$product['name']} - Updated path to: {$fixed_path}";
                } else {
                    $errors[] = "❌ Missing: {$product['name']} - File not found: {$image_field}";
                }
            } else {
                $results[] = "✓ OK: {$product['name']} - Image exists at: {$image_field}";
            }
        }
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Check upload directory permissions
$upload_dir = 'uploads/products/';
$dir_exists = is_dir($upload_dir);
$dir_writable = is_writable($upload_dir);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Image Paths</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .success { background: #d1e7dd; border-left: 4px solid #0f5132; padding: 15px; margin: 20px 0; }
        .error { background: #f8d7da; border-left: 4px solid #842029; padding: 15px; margin: 20px 0; }
        .info { background: #cfe2ff; border-left: 4px solid #084298; padding: 15px; margin: 20px 0; }
        .result-item { padding: 8px; margin: 5px 0; border-radius: 4px; font-family: monospace; font-size: 14px; }
        .result-success { background: #d1e7dd; }
        .result-error { background: #f8d7da; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; margin-left: 10px; }
        .status-ok { background: #198754; color: white; }
        .status-error { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔧 Image Path Fixer</h1>
        
        <div class="warning">
            <strong>⚠️ Important:</strong> This tool fixes image path issues after moving from localhost to online hosting.
            <br><strong>DELETE THIS FILE after using it!</strong>
        </div>

        <div class="info">
            <h5>System Status:</h5>
            <ul>
                <li>
                    Upload Directory: <code><?php echo $upload_dir; ?></code>
                    <?php if ($dir_exists): ?>
                        <span class="status-badge status-ok">EXISTS</span>
                    <?php else: ?>
                        <span class="status-badge status-error">NOT FOUND</span>
                    <?php endif; ?>
                </li>
                <li>
                    Directory Writable: 
                    <?php if ($dir_writable): ?>
                        <span class="status-badge status-ok">YES</span>
                    <?php else: ?>
                        <span class="status-badge status-error">NO - Set permissions to 755 or 777</span>
                    <?php endif; ?>
                </li>
                <li>Server: <?php echo $_SERVER['SERVER_NAME']; ?></li>
                <li>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
            </ul>
        </div>

        <?php if (!$dir_exists): ?>
            <div class="error">
                <strong>❌ Upload directory not found!</strong><br>
                Create the directory: <code>uploads/products/</code><br>
                Set permissions to 755 or 777
            </div>
        <?php endif; ?>

        <form method="POST" class="mb-4">
            <button type="submit" name="fix_paths" class="btn btn-primary btn-lg">
                <i class="fas fa-wrench"></i> Scan & Fix Image Paths
            </button>
        </form>

        <?php if (!empty($results) || !empty($errors)): ?>
            <div class="mt-4">
                <h4>Results:</h4>
                
                <?php if (!empty($results)): ?>
                    <div class="success">
                        <strong>✅ Processed <?php echo count($results); ?> products:</strong>
                    </div>
                    <?php foreach ($results as $result): ?>
                        <div class="result-item result-success"><?php echo htmlspecialchars($result); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="error mt-3">
                        <strong>❌ Errors (<?php echo count($errors); ?>):</strong>
                    </div>
                    <?php foreach ($errors as $error): ?>
                        <div class="result-item result-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                    
                    <div class="info mt-3">
                        <strong>How to fix missing images:</strong>
                        <ol>
                            <li>Make sure you uploaded the <code>uploads/products/</code> folder from localhost</li>
                            <li>Check file permissions (should be 644 for files, 755 for folders)</li>
                            <li>Verify image files exist in the uploads folder</li>
                            <li>Re-upload missing images through the product management page</li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-5">
            <h4>Manual Checks:</h4>
            <div class="info">
                <h5>1. Check if images were uploaded:</h5>
                <ul>
                    <li>Via FTP/File Manager, check if <code>uploads/products/</code> folder exists</li>
                    <li>Verify image files are inside this folder</li>
                    <li>Check file sizes (should not be 0 bytes)</li>
                </ul>
                
                <h5>2. Check file permissions:</h5>
                <ul>
                    <li>Folders: 755 or 777</li>
                    <li>Files: 644 or 666</li>
                    <li>Use your hosting control panel or FTP client to set permissions</li>
                </ul>
                
                <h5>3. Check database paths:</h5>
                <ul>
                    <li>Paths should be: <code>uploads/products/filename.jpg</code></li>
                    <li>NOT: <code>C:\xampp\htdocs\...</code> (localhost paths)</li>
                    <li>NOT: <code>/var/www/...</code> (absolute server paths)</li>
                </ul>
            </div>
        </div>

        <div class="mt-4">
            <a href="manage-products.php" class="btn btn-secondary">← Back to Manage Products</a>
            <a href="admin-dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="warning mt-4">
            <strong>🗑️ Remember:</strong> Delete this file (<code>fix-image-paths.php</code>) after fixing the images!
        </div>
    </div>
</body>
</html>
