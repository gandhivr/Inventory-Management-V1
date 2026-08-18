<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supplier' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    
    // Handle supplier ID - if admin, use selected supplier, if supplier, use their own ID
    if ($_SESSION['role'] === 'admin') {
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        if ($supplier_id <= 0) {
            header('Location: add-product.php?error=Please select a supplier');
            exit();
        }
    } else {
        $supplier_id = $_SESSION['user_id'];
    }

    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0) {
        header('Location: add-product.php?error=Please fill all fields with valid values');
        exit();
    }
     // Handle image upload
    $image_path = null;
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
                $image_path = $upload_path;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_quantity, image, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock_quantity, $image_path, $supplier_id]);

        header('Location: add-product.php?success=Product added successfully');
        exit();

    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Product insertion error: " . $e->getMessage());
         
        // Check for specific error types
        if ($e->getCode() == '23000') {
            header('Location: add-product.php?error=Database constraint error. Please check your data.');
        } elseif (strpos($e->getMessage(), 'products') !== false) {
            header('Location: add-product.php?error=Products table error. Please run setup.php');
        } else {
            header('Location: add-product.php?error=Database error: ' . urlencode($e->getMessage()));
        }
        exit();
    }
}

// If we reach here, it's a GET request - show the form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Inventory Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/add-product.css">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
    <style>
        /* ===================================================================
           MODERN ADD PRODUCT PAGE DESIGN
           ===================================================================
           Professional dark theme with gradient backgrounds and smooth animations */
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
            background-attachment: fixed !important;
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
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
                linear-gradient(rgba(16, 185, 129, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 185, 129, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header Styling */
        header {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 3px solid #10b981 !important;
            padding: 1.5rem 0 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
        }
        
        header .container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        header h1 {
            color: #f8fafc !important;
            font-size: 1.75rem !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            display: flex !important;
            align-items: center !important;
            margin: 0 !important;
        }
        
        header h1::before {
            content: "📦" !important;
            margin-right: 0.75rem !important;
            font-size: 1.5rem !important;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.4)) !important;
        }
        
        /* Navigation Styling */
        nav {
            display: flex !important;
            gap: 1rem !important;
            align-items: center !important;
            flex-wrap: wrap !important;
        }
        
        nav a {
            color: rgba(255, 255, 255, 0.9) !important;
            text-decoration: none !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(10px) !important;
        }
        
        nav a:hover {
            color: white !important;
            background: rgba(16, 185, 129, 0.2) !important;
            transform: translateY(-1px) !important;
            border-color: rgba(16, 185, 129, 0.4) !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2) !important;
        }
        
        nav span {
            background: rgba(16, 185, 129, 0.2) !important;
            color: white !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            border: 1px solid rgba(16, 185, 129, 0.4) !important;
            backdrop-filter: blur(10px) !important;
            font-size: 0.875rem !important;
        }
        
        /* Main Content */
        main {
            padding: 3rem 0 !important;
            min-height: calc(100vh - 120px) !important;
        }
        
        /* Form Container */
        .form-container {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            padding: 3rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 30px rgba(16, 185, 129, 0.1) !important;
            max-width: 800px !important;
            margin: 0 auto !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .form-container::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 6px !important;
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%) !important;
            border-radius: 1.5rem 1.5rem 0 0 !important;
        }
        
        .form-container h2 {
            color: #f8fafc !important;
            font-size: 2.25rem !important;
            font-weight: 800 !important;
            text-align: center !important;
            margin-bottom: 2rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 1rem !important;
        }
        
        .form-container h2::before {
            content: "➕" !important;
            font-size: 2rem !important;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.4)) !important;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem !important;
            border-radius: 0.75rem !important;
            margin-bottom: 2rem !important;
            font-weight: 500 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }
        
        .alert-error::before {
            content: "❌" !important;
            font-size: 1.25rem !important;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1) !important;
            color: #6ee7b7 !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
        }
        
        .alert-success::before {
            content: "✅" !important;
            font-size: 1.25rem !important;
        }
        
        /* Form Styling */
        .product-form {
            display: flex !important;
            flex-direction: column !important;
            gap: 2rem !important;
        }
        
        .form-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }
        
        .form-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
        }
        
        label {
            color: #f8fafc !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        
        label::before {
            content: "📝" !important;
            font-size: 0.875rem !important;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            background: rgba(51, 65, 85, 0.8) !important;
            border: 2px solid rgba(16, 185, 129, 0.3) !important;
            color: #f8fafc !important;
            border-radius: 0.75rem !important;
            padding: 1rem 1.25rem !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(10px) !important;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none !important;
            border-color: #10b981 !important;
            background: rgba(51, 65, 85, 0.9) !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1), 0 0 20px rgba(16, 185, 129, 0.2) !important;
            transform: translateY(-2px) !important;
        }
        
        textarea {
            resize: vertical !important;
            min-height: 120px !important;
        }
        
        /* File Input Styling */
        input[type="file"] {
            background: rgba(51, 65, 85, 0.8) !important;
            border: 2px dashed rgba(16, 185, 129, 0.3) !important;
            color: #f8fafc !important;
            border-radius: 0.75rem !important;
            padding: 1.5rem !important;
            font-size: 1rem !important;
            text-align: center !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
        }
        
        input[type="file"]:hover {
            border-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.1) !important;
        }
        
        small {
            color: #94a3b8 !important;
            font-size: 0.875rem !important;
            font-style: italic !important;
        }
        
        /* Button Styling */
        .form-actions {
            display: flex !important;
            gap: 1rem !important;
            justify-content: center !important;
            margin-top: 2rem !important;
        }
        
        .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.75rem !important;
            padding: 1rem 2rem !important;
            border-radius: 0.75rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            text-decoration: none !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            min-width: 160px !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3) !important;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 12px -2px rgba(16, 185, 129, 0.4) !important;
            color: white !important;
        }
        
        .btn-secondary {
            background: rgba(107, 114, 128, 0.8) !important;
            color: white !important;
            border: 2px solid rgba(107, 114, 128, 0.3) !important;
        }
        
        .btn-secondary:hover {
            background: rgba(75, 85, 99, 0.9) !important;
            transform: translateY(-2px) !important;
            color: white !important;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem !important;
                padding: 2rem !important;
            }
            
            .form-row {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .form-actions {
                flex-direction: column !important;
            }
            
            header .container {
                flex-direction: column !important;
                gap: 1rem !important;
                text-align: center !important;
            }
            
            nav {
                justify-content: center !important;
            }
        }
        
        /* Animation */
        .form-container {
            animation: slideInUp 0.6s ease-out !important;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            animation: fadeIn 0.8s ease-out !important;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="container">
                <h1>Inventory Management System</h1>
                <nav>
                    <a href="index.php">Home</a>
                    <?php if ($_SESSION['role'] === 'supplier'): ?>
                        <a href="supplier-dashboard.php">Dashboard</a>
                        <a href="product-list.php">My Products</a>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin-dashboard.php">Dashboard</a>
                        <a href="manage-products.php">Manage Products</a>
                    <?php endif; ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                    <a href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="form-container">
                <h2>Add New Product</h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="add-product.php" method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₹) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div class="form-group">
                            <label for="supplier_id">Supplier *</label>
                            <select id="supplier_id" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php
                                // Fetch suppliers for admin
                                $stmt = $pdo->query("SELECT id, username, name FROM users WHERE role = 'supplier' AND status = 'active'");
                                $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($suppliers as $supplier):
                                ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo htmlspecialchars($supplier['name'] . ' (' . $supplier['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.svg,.tiff,.tif,image/*">
                        <small>Supported formats: JPG, JPEG, PNG, GIF, WebP, BMP, SVG, TIFF. Max size: 2MB</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'manage-products.php' : 'product-list.php'; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>