<?php
// - SUPPLIERS: View and manage their own products (edit/delete)
// - BUYERS: Browse all products and add items to shopping cart
// - ADMINS: View all products with supplier information
// Features: Role-based queries, image handling, cart integration, CRUD operations
session_start();
require_once 'config/database.php';

// Allow guests to browse products, but track if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
if ($user_role === 'supplier') {
    // ===================================================================
    // SUPPLIER QUERY: Fetch only products owned by this supplier
    // ===================================================================
    // Suppliers should only see and manage their own products
    // This query filters products by supplier_id matching the logged-in user
    
    // prepare() creates a prepared statement with placeholder (?)
    // Prepared statements prevent SQL injection attacks
    $stmt = $pdo->prepare("
        SELECT * 
        FROM products 
        WHERE supplier_id = ?           -- Filter: Only this supplier's products
        ORDER BY created_at DESC        -- Sort: Newest products first
    ");
    // The ? placeholder is safely replaced with $user_id value
    $stmt->execute([$user_id]);
    
} else {
    // ===================================================================
    // BUYER/ADMIN QUERY: Fetch all products with supplier information
    // ===================================================================
    // Buyers need to see all available products for shopping
    // Admins need to see all products for system oversight
    // This query includes supplier name via LEFT JOIN for display purposes
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,                        -- All product columns (id, name, price, etc.)
            u.username as supplier_name -- Supplier's username for display
        FROM products p 
        
        -- LEFT JOIN: Include supplier information
        -- LEFT JOIN ensures products appear even if supplier is deleted
        -- This maintains data integrity and prevents broken displays
        LEFT JOIN users u ON p.supplier_id = u.id 
        
        ORDER BY p.created_at DESC      -- Sort: Newest products first
    ");
    $stmt->execute();
}

// ===================================================================
// FETCH RESULTS: Retrieve all products as associative array
// ===================================================================
// fetchAll(PDO::FETCH_ASSOC) retrieves all query results
// FETCH_ASSOC returns array with column names as keys (easier to work with)
// Example: $product['name'], $product['price'], etc.
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- ===================================================================
     HTML DOCUMENT START
     ===================================================================
     This section contains the HTML structure and styling for the product list page
     Features: Responsive design, role-based UI, image handling, form validation -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- CHARACTER ENCODING: UTF-8 for international character support -->
    <meta charset="UTF-8">
    
    <!-- RESPONSIVE DESIGN: Viewport settings for mobile compatibility -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- PAGE TITLE: Displayed in browser tab -->
    <title>Products - Inventory Management System</title>
    
    <!-- EXTERNAL STYLESHEETS: Font Awesome icons and custom CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css?v=2">
    <link rel="stylesheet" href="css/unified-dashboard.css?v=2">
    <link rel="stylesheet" href="css/product-image-fix.css?v=2">
    <link rel="stylesheet" href="css/image-lightbox.css">
    <link rel="stylesheet" href="css/product-list-fix.css?v=1">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
    
    <!-- ===================================================================
         CUSTOM STYLES: Page-specific CSS for product display
         ===================================================================
         Includes: Product cards, image containers, buttons, responsive grid -->
    <style>
        /* Product Image Styling */
        .product-image-container {
            width: 100% !important;
            height: 150px !important;
            max-height: 150px !important;
            margin-bottom: 1rem !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            position: relative !important;
            background: #ffffff !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .product-image {
            width: 100% !important;
            height: 100% !important;
            max-height: 150px !important;
            object-fit: cover !important;
            object-position: center !important;
            transition: transform 0.3s ease !important;
        }
        
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #10b981;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .image-placeholder i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }
        
        .image-placeholder span {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Product Card Enhancements */
        .product-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            border-radius: 1rem !important;
            padding: 1.5rem !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }
        
        .product-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.1), 0 10px 10px -5px rgba(16, 185, 129, 0.04) !important;
            border-color: rgba(16, 185, 129, 0.4) !important;
        }
        
        .product-card h3 {
            color: #f8fafc !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.75rem !important;
        }
        
        .product-card p {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            margin-bottom: 0.75rem !important;
        }
        
        .price {
            color: #10b981 !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .supplier-info {
            color: #94a3b8 !important;
            font-size: 0.85rem !important;
        }
        
        .supplier-name {
            color: #10b981 !important;
            font-weight: 600 !important;
        }
        
        /* Button Styling */
        .btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            border: none !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            transition: all 0.3s ease !important;
            font-size: 0.875rem !important;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4) !important;
            color: white !important;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4) !important;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
        
        /* Form Input Styling - Enhanced Visibility */
        input[type="number"] {
            background: rgba(16, 185, 129, 0.2) !important;
            border: 2px solid #10b981 !important;
            color: #ffffff !important;
            border-radius: 0.5rem !important;
            padding: 0.75rem !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            text-align: center !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3) !important;
        }
        
        input[type="number"]:focus {
            outline: none !important;
            background: rgba(16, 185, 129, 0.3) !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            text-shadow: 0 0 15px rgba(255, 255, 255, 1) !important;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.6) !important;
        }
        
        /* Out of Stock Styling */
        .out-of-stock {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            text-align: center !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }
        
        /* Page Title Styling */
        h2 {
            color: #f8fafc !important;
            font-size: 2rem !important;
            font-weight: 700 !important;
            margin-bottom: 2rem !important;
        }
        
        /* Product Grid */
        .product-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)) !important;
            gap: 2rem !important;
            margin-top: 2rem !important;
        }
        
        /* Welcome Section */
        .welcome {
            text-align: center !important;
            padding: 4rem 2rem !important;
            background: rgba(30, 41, 59, 0.95) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
        }
        
        .welcome h3 {
            color: #f8fafc !important;
            font-size: 1.75rem !important;
            margin-bottom: 1rem !important;
        }
        
        .welcome p {
            color: #cbd5e1 !important;
            font-size: 1.1rem !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ===================================================================
             HEADER SECTION: Navigation and branding
             ===================================================================
             Provides role-specific navigation menu and user information display -->
        <header>
            <div class="container">
                <!-- SYSTEM BRANDING: Main application title -->
                <h1>Inventory Management System</h1>
                
                <!-- ===================================================================
                     DYNAMIC NAVIGATION: Role-based menu system
                     ===================================================================
                     Navigation links change based on user role to show relevant options
                     This provides a personalized experience for each user type -->
                <nav>
                    <!-- HOME LINK: Available to all users -->
                    <a href="index.php">Home</a>
                    
                    <?php if ($is_logged_in): ?>
                        <?php 
                        // ===================================================================
                        // SUPPLIER NAVIGATION: Links for product management
                        // ===================================================================
                        if ($user_role === 'supplier'): ?>
                            <!-- Dashboard: Supplier's main control panel -->
                            <a href="supplier-dashboard.php">Dashboard</a>
                            <!-- Add Product: Create new product listings -->
                            <a href="add-product.php">Add Product</a>
                            
                        <?php 
                        // ===================================================================
                        // BUYER NAVIGATION: Links for shopping functionality
                        // ===================================================================
                        elseif ($user_role === 'buyer'): ?>
                            <!-- Dashboard: Buyer's order history and account info -->
                            <a href="buyer-dashboard.php">Dashboard</a>
                            <!-- Cart: View and manage shopping cart -->
                            <a href="cart.php">My Cart</a>
                            
                        <?php 
                        // ===================================================================
                        // ADMIN NAVIGATION: Links for system administration
                        // ===================================================================
                        elseif ($user_role === 'admin'): ?>
                            <!-- Dashboard: Admin's system overview and statistics -->
                            <a href="admin-dashboard.php">Dashboard</a>
                            <!-- Manage Products: Admin product oversight tools -->
                            <a href="manage-products.php">Manage Products</a>
                        <?php endif; ?>
                        
                        <!-- ===================================================================
                             USER GREETING: Display current user information
                             ===================================================================
                             Shows username and role for user awareness and context -->
                        <span>
                            Welcome, 
                            <!-- htmlspecialchars() prevents XSS attacks by escaping HTML -->
                            <?php echo htmlspecialchars($_SESSION['username']); ?> 
                            <!-- ucfirst() capitalizes first letter of role for proper display -->
                            (<?php echo ucfirst($user_role); ?>)
                        </span>
                        
                        <!-- LOGOUT LINK: Session termination -->
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <!-- GUEST NAVIGATION: Login/Register options -->
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main>
            <!-- ===================================================================
                 PAGE HEADER: Title and action buttons
                 ===================================================================
                 Displays role-specific page title and relevant action buttons -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <!-- DYNAMIC PAGE TITLE: Changes based on user role -->
                <h2 style="color: var(--gray-900); font-family: 'Poppins', sans-serif;">
                    <?php 
                    // ===================================================================
                    // ROLE-BASED TITLE: Personalized page heading
                    // ===================================================================
                    // Different titles help users understand their context
                    if ($user_role === 'supplier') {
                        // Suppliers see "My Products" - emphasizes ownership
                        echo '🏪 My Products';
                    } elseif ($user_role === 'buyer' || $user_role === 'guest') {
                        // Buyers and guests see "Browse Products" - emphasizes shopping
                        echo '🛍️ Browse Products';
                    } else {
                        // Admins see "All Products" - emphasizes oversight
                        echo '📦 All Products';
                    }
                    ?>
                </h2>
                
                <?php 
                // ===================================================================
                // SUPPLIER ACTION BUTTON: Quick access to add product
                // ===================================================================
                // Only suppliers can add products, so button only shows for them
                if ($user_role === 'supplier'): ?>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="supplier-export-products-csv.php" class="btn" style="background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="supplier-export-products-pdf.php" target="_blank" class="btn" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="add-product.php" class="btn btn-success">➕ Add New Product</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php 
            // ===================================================================
            // SUCCESS MESSAGE DISPLAY: Show operation confirmation
            // ===================================================================
            // Display success message if passed via URL parameter
            // Common after operations like: add, edit, delete product
            if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <!-- htmlspecialchars() prevents XSS attacks on message display -->
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php 
            // ===================================================================
            // EMPTY STATE HANDLING: Display when no products exist
            // ===================================================================
            // Check if products array is empty and show appropriate message
            if (empty($products)): ?>
                <!-- EMPTY STATE: Friendly message when no products are available -->
                <div class="welcome">
                    <!-- DYNAMIC HEADING: Different message based on user role -->
                    <h3>
                        <?php 
                        // Suppliers: Encouraging message to add first product
                        // Others: Informative message about no products available
                        echo $user_role === 'supplier' ? '🚀 Start Your Product Journey' : '🔍 No Products Available'; 
                        ?>
                    </h3>
                    
                    <!-- DYNAMIC DESCRIPTION: Role-specific guidance -->
                    <p>
                        <?php 
                        // Suppliers: Call to action to add products
                        // Others: Polite message to check back later
                        echo $user_role === 'supplier' ? 'Add your first product to start selling!' : 'Check back later for new products.'; 
                        ?>
                    </p>
                    
                    <?php 
                    // ===================================================================
                    // SUPPLIER CALL-TO-ACTION: Button to add first product
                    // ===================================================================
                    // Only show for suppliers to encourage product creation
                    if ($user_role === 'supplier'): ?>
                        <div style="margin-top: 2rem;">
                            <a href="add-product.php" class="btn btn-success">Add Your First Product</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- ===================================================================
                     PRODUCT GRID: Display all products in card layout
                     ===================================================================
                     This section shows products when they exist in the database -->
                <div class="product-grid">
                    <?php 
                    // ===================================================================
                    // PRODUCT LOOP: Iterate through all products and display cards
                    // ===================================================================
                    // foreach loop processes each product from database query
                    foreach ($products as $product): ?>
                        <!-- PRODUCT CARD: Individual product display container -->
                        <div class="product-card">
                            
                            <!-- ===================================================================
                                 PRODUCT IMAGE SECTION: Display product photo or placeholder
                                 ===================================================================
                                 Handles three scenarios: valid image, broken image, no image -->
                            <div class="product-image-container">
                                <?php 
                                // ===================================================================
                                // IMAGE VALIDATION: Check if image exists in database and filesystem
                                // ===================================================================
                                // Two-part check ensures image is both:
                                // 1. Stored in database ($product['image'] is not empty)
                                // 2. Actually exists on server (file_exists() returns true)
                                if ($product['image'] && file_exists($product['image'])): ?>
                                    
                                    <!-- VALID IMAGE: Display actual product photo -->
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-image">
                                    
                                <?php else: ?>
                                    <!-- NO IMAGE: Display placeholder when no image exists -->
                                    <div class="image-placeholder">
                                        <i class="fas fa-box"></i>
                                        <span>No Image</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ===================================================================
                                 PRODUCT INFORMATION: Display product details
                                 ===================================================================
                                 Shows name, description, price, and stock information -->
                            
                            <!-- PRODUCT NAME: Main product title -->
                            <!-- htmlspecialchars() prevents XSS attacks on product name -->
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <!-- PRODUCT DESCRIPTION: Truncated description with ellipsis -->
                            <p>
                                <?php 
                                // substr() extracts first 100 characters of description
                                // strlen() checks if description is longer than 100 characters
                                // Adds '...' if description was truncated for visual indication
                                echo htmlspecialchars(substr($product['description'], 0, 100)) . 
                                     (strlen($product['description']) > 100 ? '...' : ''); 
                                ?>
                            </p>
                            
                            <!-- PRODUCT PRICE: Formatted currency display -->
                            <div class="price">
                                ₹<?php 
                                // number_format() adds thousands separator and 2 decimal places
                                // Example: 1234.5 becomes ₹1,234.50
                                echo number_format($product['price'], 2); 
                                ?>
                            </div>
                            
                            <!-- STOCK QUANTITY: Available inventory count -->
                            <p><strong>Stock:</strong> <?php echo $product['stock_quantity']; ?></p>
                            
                            <?php 
                            // ===================================================================
                            // SUPPLIER INFORMATION: Show supplier name for buyers/admins
                            // ===================================================================
                            // Suppliers don't need to see their own name on their products
                            // Buyers and admins see who is selling each product
                            if ($user_role !== 'supplier'): ?>
                                <p class="supplier-info">
                                    <strong>Supplier:</strong> 
                                    <span class="supplier-name">
                                        <?php 
                                        // Null coalescing operator (??) provides fallback value
                                        // Shows 'Unknown' if supplier_name is null (deleted supplier)
                                        echo htmlspecialchars($product['supplier_name'] ?? 'Unknown'); 
                                        ?>
                                    </span>
                                </p>
                            <?php endif; ?>

                            <!-- ===================================================================
                                 ACTION BUTTONS: Role-specific product interactions
                                 ===================================================================
                                 Different buttons appear based on user role and product availability -->
                            <div style="margin-top: 1rem;">
                                <?php 
                                // ===================================================================
                                // SUPPLIER ACTIONS: Edit and Delete buttons
                                // ===================================================================
                                // Suppliers can manage their own products
                                if ($user_role === 'supplier'): ?>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        </div>
                                        <!-- EDIT BUTTON: Navigate to product update page -->
                                        <a href="update-product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            ✏️ Edit
                                        </a>
                                        
                                        <!-- DELETE BUTTON: Remove product with confirmation -->
                                        <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-danger" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;" 
                                           onclick="return confirm('Are you sure you want to delete this product?')">
                                            🗑️ Delete
                                        </a>
                                        <!-- onclick: JavaScript confirmation prevents accidental deletions -->
                                        <!-- return false cancels navigation if user clicks "Cancel" -->
                                    </div>
                                    
                                <?php 
                                // ===================================================================
                                // BUYER/GUEST ACTIONS: Add to Cart or Login Prompt
                                // ===================================================================
                                // Buyers can add products to cart if stock is available
                                // Guests are prompted to login first
                                elseif (($user_role === 'buyer' || $user_role === 'guest') && $product['stock_quantity'] > 0): ?>
                                    
                                    <?php if ($user_role === 'buyer'): ?>
                                        <!-- ADD TO CART FORM: Submit product and quantity to cart -->
                                        <form action="add-to-cart.php" 
                                              method="POST" 
                                              style="display: flex; gap: 0.5rem; align-items: center; margin-top: 1rem;">
                                            
                                            <!-- HIDDEN INPUT: Product ID for cart processing -->
                                            <!-- Hidden fields pass data without displaying to user -->
                                            <input type="hidden" 
                                                   name="product_id" 
                                                   value="<?php echo $product['id']; ?>">
                                            
                                            <!-- QUANTITY INPUT: Number selector for order quantity -->
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="1"                                    
                                                   min="1"                                      
                                                   max="<?php echo $product['stock_quantity']; ?>"
                                                   style="width: 80px;">
                                            <!-- min/max attributes prevent ordering more than available stock -->
                                            
                                            <!-- SUBMIT BUTTON: Add product to shopping cart -->
                                            <button type="submit" 
                                                    class="btn btn-success" 
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                🛒 Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- GUEST LOGIN PROMPT: Redirect to login to purchase -->
                                        <div style="margin-top: 1rem;">
                                            <a href="login.php?redirect=product-list.php" 
                                               class="btn btn-success" 
                                               style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 100%; text-align: center;">
                                                🔐 Login to Purchase
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php 
                                // ===================================================================
                                // OUT OF STOCK MESSAGE: Display when product unavailable
                                // ===================================================================
                                // Show buyers/guests when product cannot be purchased
                                elseif ($user_role === 'buyer' || $user_role === 'guest'): ?>
                                    <div class="out-of-stock">
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- End of product loop -->
                </div>
            <?php endif; ?>
            <!-- End  of products
 display -->
        </main>
    </div>
    <script src="js/image-lightbox.js"></script>
</body>
</html>
