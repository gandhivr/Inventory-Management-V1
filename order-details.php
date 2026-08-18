<?php
// Start PHP session to access user authentication and maintain state across requests
// This enables access to $_SESSION variables set during login process
// Essential for determining user permissions and order access rights
session_start();

// Include database connection file containing PDO configuration
// PDO (PHP Data Objects) provides secure database connectivity with prepared statements
// This file typically contains database credentials and connection setup
require_once 'config/database.php';

// Check if user is logged in
// Basic authentication check - all users (admin, buyer, supplier) must be logged in
// Unlike previous files, this doesn't require admin role since all user types can view orders
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users to login page
    header('Location: login.php');
    exit(); // Stop script execution after redirect
}

// Extract and validate order ID from URL parameter
// intval() safely converts string input to integer, returning 0 for invalid input
// This prevents SQL injection and handles non-numeric input gracefully
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get current user's role and ID for access control decisions
// These variables determine what order information the user can access
$user_role = $_SESSION['role']; // admin, buyer, or supplier
$user_id = $_SESSION['user_id']; // Current user's database ID

// Validate that we have a legitimate positive order ID
// Zero or negative values indicate missing, invalid, or malicious order ID parameter
if ($order_id <= 0) {
    // Redirect to homepage if invalid order ID provided
    header('Location: index.php');
    exit();
}

// Get order details with access control
// This section implements role-based access control for order viewing
// Different user types have different permissions for viewing order information
try {
    // Role-based query execution with different access levels
    if ($user_role === 'admin') {
        // Admin can view all orders
        // Admins have unrestricted access to all order information in the system
        // JOIN with users table to get buyer information for display
        $stmt = $pdo->prepare("
            SELECT o.*, u.username as buyer_name, u.email as buyer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]); // Only need order ID since admin can see everything
        
    } elseif ($user_role === 'buyer') {
        // Buyer can only view their own orders
        // Security measure: buyers cannot access other buyers' order details
        // Additional WHERE clause restricts access to orders belonging to current user
        $stmt = $pdo->prepare("
            SELECT o.*, u.username as buyer_name, u.email as buyer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]); // Both order ID and user ID for security
        
    } else {
        // Supplier can view orders containing their products
        // Suppliers can only see orders that include products they supply
        // DISTINCT prevents duplicate order records if multiple products from same supplier
        // JOIN with order_items and products tables to filter by supplier ownership
        //DISTINCT prevents duplicate rows if an order contains multiple products from the same supplier
        //o.* retrieves all order fields (id, total_amount, status, created_at, etc.)
        $stmt = $pdo->prepare("
            SELECT DISTINCT o.*, u.username as buyer_name, u.email as buyer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN products p ON oi.product_id = p.id 
            WHERE o.id = ? AND p.supplier_id = ?
        ");
        $stmt->execute([$order_id, $user_id]); // Filter by supplier's products only
    }
    
    // Fetch the order record as associative array
    // Will be false if no matching order found or user lacks permission
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if order was found and user has permission to view it
    if (!$order) {
        // Order not found or user lacks permission - redirect to homepage
        // This handles cases where order doesn't exist or access is denied
        header('Location: index.php');
        exit();
    }
    
    // Get order items with role-based filtering
    // After confirming order access, fetch the items within that order
    // Access control continues at item level for suppliers
    if ($user_role === 'supplier') {
        // Supplier sees only their products in the order
        // Even if order contains products from multiple suppliers,
        // each supplier only sees their own products for security/privacy
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image, u.username as supplier_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            JOIN users u ON p.supplier_id = u.id 
            WHERE oi.order_id = ? AND p.supplier_id = ?
        ");
        $stmt->execute([$order_id, $user_id]); // Filter items by supplier ownership
        
    } else {
        // Admin and buyer see all items in the order
        // Admins need full visibility for management purposes
        // Buyers need to see all items in their own orders
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image, u.username as supplier_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            JOIN users u ON p.supplier_id = u.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]); // No additional filtering needed
    }
    
    // Fetch all order items as associative array
    // This will contain different items based on user role and permissions
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle any database errors gracefully
    // Don't expose technical error details to users for security
    // Redirect to homepage rather than showing error details
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Inventory Management System</title>
    <style>
        /* Complete Order Details CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Order Management Color Palette */
            --primary-color: #2c8cfb;
            --secondary-color: #5cacfa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            /* Order Status Colors */
            --status-pending: #f59e0b;
            --status-completed: #10b981;
            --status-cancelled: #ef4444;
            --status-processing: #3b82f6;
            --status-shipped: #8b5cf6;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            /* Gradients */
            --primary-gradient: linear-gradient(135deg, #2c8cfb 0%, #5cacfa 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --order-gradient: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            
            /* Transitions */
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            font-size: 14px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
        }

        /* Header */
        header {
            background: var(--primary-gradient);
            padding: var(--space-lg) 0;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            color: var(--white);
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        header h1::before {
            content: "📦";
            margin-right: var(--space-sm);
        }

        nav {
            display: flex;
            gap: var(--space-sm);
            align-items: center;
            flex-wrap: wrap;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        nav a:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.15);
        }

        nav span {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 600;
        }

        /* Main Content */
        main {
            padding: var(--space-2xl) 0;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-2xl);
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
        }

        .page-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .page-header h2::before {
            content: "📋";
            font-size: 1.75rem;
        }

        /* Order Info Grid */
        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-xl);
            margin-bottom: var(--space-2xl);
        }

        .info-card {
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .info-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .info-card h3.order-info::before {
            content: "ℹ️";
        }

        .info-card h3.customer-info::before {
            content: "👤";
        }

        .info-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md) 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .info-detail:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-700);
        }

        .info-value {
            font-weight: 500;
            color: var(--gray-900);
        }

        /* Status Badge */
        .status-badge {
            padding: var(--space-xs) var(--space-md);
            border-radius: var(--radius-lg);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid var(--status-pending);
        }

        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid var(--status-completed);
        }

        .status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid var(--status-cancelled);
        }

        .status-badge.processing {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid var(--status-processing);
        }

        .status-badge.shipped {
            background: #e5e7eb;
            color: #374151;
            border: 1px solid var(--status-shipped);
        }

        /* Order Amount */
        .order-amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--success-color);
        }

        /* Order Items Card */
        .items-card {
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--space-xl);
            border-left: 4px solid var(--success-color);
        }

        .items-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .items-card h3::before {
            content: "📦";
        }

        /* Product Table */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--space-lg);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .product-table th,
        .product-table td {
            padding: var(--space-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .product-table th {
            background: var(--gray-100);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .product-table tbody tr {
            transition: background-color var(--transition-fast);
        }

        .product-table tbody tr:hover {
            background: var(--gray-50);
        }

        .product-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Product Display */
        .product-display {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .product-placeholder {
            width: 60px;
            height: 60px;
            background: var(--gray-200);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Table Footer */
        .table-footer {
            border-top: 2px solid var(--primary-color);
            background: var(--gray-50);
        }

        .table-footer th {
            background: var(--gray-50);
            font-weight: 700;
            color: var(--gray-900);
        }

        .total-amount {
            color: var(--success-color);
            font-size: 1.1rem;
            font-weight: 800;
        }

        /* Admin Actions */
        .admin-actions {
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--info-color);
        }

        .admin-actions h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .admin-actions h3::before {
            content: "⚙️";
        }

        .actions-row {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
            flex-wrap: wrap;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .status-form label {
            font-weight: 600;
            color: var(--gray-700);
        }

        .status-form select {
            padding: var(--space-sm) var(--space-md);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: border-color var(--transition-fast);
        }

        .status-form select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-lg);
            background: var(--primary-gradient);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            transition: all var(--transition-fast);
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        /* No Items State */
        .no-items {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--gray-500);
            font-style: italic;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--gray-300);
        }

        .no-items::before {
            content: "📦";
            font-size: 3rem;
            display: block;
            margin-bottom: var(--space-md);
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-md);
            }
            
            .order-info-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: var(--space-md);
                text-align: center;
            }
            
            .product-table {
                font-size: 0.75rem;
            }
            
            .product-table th,
            .product-table td {
                padding: var(--space-sm);
            }
            
            .product-display {
                flex-direction: column;
                text-align: center;
                gap: var(--space-sm);
            }
            
            .actions-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <?php if ($user_role === 'admin'): ?>
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="admin-orders.php">All Orders</a>
                <?php elseif ($user_role === 'buyer'): ?>
                    <a href="buyer-dashboard.php">Dashboard</a>
                    <a href="buyer-orders.php">My Orders</a>
                <?php else: ?>
                    <a href="supplier-dashboard.php">Dashboard</a>
                    <a href="supplier-orders.php">Orders</a>
                <?php endif; ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($user_role); ?>)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main class="fade-in">
            <!-- Page Header -->
            <div class="page-header">
                <h2>Order Details #<?php echo $order_id; ?></h2>
                <?php if ($user_role === 'admin'): ?>
                    <a href="admin-orders.php" class="btn">← Back to Orders</a>
                <?php elseif ($user_role === 'buyer'): ?>
                    <a href="buyer-orders.php" class="btn">← Back to My Orders</a>
                <?php else: ?>
                    <a href="supplier-orders.php" class="btn">← Back to Orders</a>
                <?php endif; ?>
            </div>

            <!-- Order Information Grid -->
            <div class="order-info-grid">
                <!-- Order Information -->
                <div class="info-card">
                    <h3 class="order-info">Order Information</h3>
                    <div class="info-detail">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value">#<?php echo $order_id; ?></span>
                    </div>
                    <div class="info-detail">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-detail">
                        <span class="info-label">Status:</span>
                        <span class="status-badge <?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="info-detail">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="info-card">
                    <h3 class="customer-info">Customer Information</h3>
                    <div class="info-detail">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                    </div>
                    <div class="info-detail">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                    </div>
                    <?php if ($user_role === 'supplier'): ?>
                        <div class="info-detail">
                            <span class="info-label">Note:</span>
                            <span class="info-value" style="font-style: italic; color: var(--info-color);">
                                This order contains your products
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="items-card">
                <h3>Order Items <?php echo ($user_role === 'supplier') ? '(Your Products Only)' : ''; ?></h3>
                
                <?php if (empty($order_items)): ?>
                    <div class="no-items">
                        <p>No items found in this order.</p>
                    </div>
                <?php else: ?>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-display">
                                            <?php if ($item['image']): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="Product" class="product-image">
                                            <?php else: ?>
                                                <div class="product-placeholder">📦</div>
                                            <?php endif; ?>
                                            <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($item_total, 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-footer">
                            <tr>
                                <th colspan="4">
                                    <?php echo ($user_role === 'supplier') ? 'Your Products Subtotal:' : 'Total:'; ?>
                                </th>
                                <th class="total-amount">₹<?php echo number_format($subtotal, 2); ?></th>
                            </tr>
                            <?php if ($user_role !== 'supplier'): ?>
                                <tr>
                                    <th colspan="4">Order Total:</th>
                                    <th class="total-amount">₹<?php echo number_format($order['total_amount'], 2); ?></th>
                                </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Admin Actions -->
            <?php if ($user_role === 'admin'): ?>
                <div class="admin-actions">
                    <h3>Admin Actions</h3>
                    <div class="actions-row">
                        <form method="POST" action="update-order-status.php" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <label for="status">Update Status:</label>
                            <select name="status" id="status">
                                <option value="pending" <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($order['status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="completed" <?php echo ($order['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="btn">Update Status</button>
                        </form>
                        
                        <a href="admin-delete-order.php?id=<?php echo $order_id; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                            🗑️ Delete Order
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
