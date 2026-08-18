<?php
// Start PHP session to access user authentication and maintain login state
// This enables access to $_SESSION variables containing user ID, role, and other login data
// Essential for verifying buyer permissions and order ownership
session_start();

// Include database connection file containing PDO configuration
// PDO (PHP Data Objects) provides secure database access with prepared statement support
// This file contains database credentials and establishes the $pdo connection object
require_once 'config/database.php';

// Check if user is logged in and is a buyer
// This implements strict role-based access control for buyer-only functionality
// Unlike admin systems, this restricts access to buyers only, not all authenticated users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    // Redirect unauthorized users (non-buyers or unauthenticated) to login page
    // This prevents suppliers, admins, and anonymous users from accessing buyer order details
    header('Location: login.php');
    exit(); // Stop script execution immediately after redirect
}

// Extract and validate order ID from URL parameter
// intval() safely converts string input to integer, returning 0 for invalid/malicious input
// This prevents SQL injection attacks and handles non-numeric parameters gracefully
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Validate that we have a legitimate positive order ID
// Zero or negative values indicate missing, invalid, or potentially malicious order ID
// This validation prevents unnecessary database queries for invalid requests
if ($order_id <= 0) {
    // Redirect to homepage if invalid or missing order ID
    // Provides consistent user experience for invalid requests
    header('Location: index.php');
    exit();
}

// Get order details
// This section fetches comprehensive order information with security restrictions
// Ensures buyers can only access their own orders, not other buyers' orders
try {
    // Complex query joining multiple tables to get complete order information
    // LEFT JOIN ensures order shows even if some related data is missing
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    // Execute query with both order ID and current user ID for security
    // The user_id check ensures buyers can only see their own orders
    // This is critical security measure preventing unauthorized order access
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    // Fetch all matching records as associative array
    // fetchAll() returns array of arrays, each representing one order item
    // If order has multiple items, we get multiple rows with same order info
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any order data was found
    // empty() returns true if no records found or user lacks permission
    if (empty($order_data)) {
        // Order not found or doesn't belong to current buyer - redirect to homepage
        // This handles both non-existent orders and unauthorized access attempts
        header('Location: index.php');
        exit();
    }

    // Extract order information and items from query results
    // Since all rows contain same order info, use first row for order details
    $order = $order_data[0]; // Basic order information (date, total, status, etc.)
    
    // All rows contain order item information, so assign entire array
    // Each element represents one product/item within the order
    $order_items = $order_data; // Complete list of items in this order

} catch (PDOException $e) {
    // Handle any database errors gracefully without exposing technical details
    // PDOException catches connection failures, query errors, constraint violations
    // Redirect to safe page rather than displaying error details to user
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Inventory Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
    <style>
        /* Order Success Page Styling */
        .welcome {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            padding: 3rem 2rem !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            text-align: center !important;
            margin-bottom: 2rem !important;
        }
        
        .welcome h2 {
            color: #10b981 !important;
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            margin-bottom: 1rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.75rem !important;
        }
        
        .welcome p {
            color: #cbd5e1 !important;
            font-size: 1.1rem !important;
            margin-bottom: 2rem !important;
        }
        
        .order-info-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
            margin-top: 2rem !important;
            text-align: left !important;
        }
        
        .info-card {
            background: rgba(16, 185, 129, 0.1) !important;
            padding: 2rem !important;
            border-radius: 1rem !important;
            border-left: 4px solid #10b981 !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .info-card:nth-child(2) {
            background: rgba(59, 130, 246, 0.1) !important;
            border-left-color: #3b82f6 !important;
        }
        
        .info-card strong {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            display: block !important;
            margin-bottom: 0.5rem !important;
        }
        
        .info-card p {
            margin: 0 !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #f8fafc !important;
        }
        
        .order-details-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            padding: 2.5rem !important;
            border-radius: 1.5rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .order-details-card h3 {
            color: #f8fafc !important;
            margin-bottom: 2rem !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }
        
        .table {
            color: #f8fafc !important;
            background: transparent !important;
            width: 100% !important;
            margin-bottom: 2rem !important;
        }
        
        .table th {
            color: #f8fafc !important;
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: rgba(16, 185, 129, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.875rem !important;
            padding: 1rem 0.75rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        
        .table tfoot th {
            color: #10b981 !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            border-top: 2px solid rgba(16, 185, 129, 0.3) !important;
        }
        
        .btn {
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            border: none !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-size: 0.95rem !important;
            text-decoration: none !important;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.4) !important;
            color: white !important;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 15px rgba(107, 114, 128, 0.4) !important;
            color: white !important;
        }
        
        .btn:not(.btn-success):not(.btn-secondary) {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            color: white !important;
        }
        
        .btn:not(.btn-success):not(.btn-secondary):hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 15px rgba(59, 130, 246, 0.4) !important;
            color: white !important;
        }
        
        .action-buttons {
            text-align: center !important;
            margin-top: 2rem !important;
            display: flex !important;
            gap: 1rem !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
        }
        
        @media (max-width: 768px) {
            .order-info-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .welcome h2 {
                font-size: 2rem !important;
            }
            
            .action-buttons {
                flex-direction: column !important;
                align-items: center !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="container">
                <h1>Inventory Management System</h1>
                <button class="menu-toggle" onclick="toggleMenu()">☰ Menu</button>
                <nav id="mainNav">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                    <a href="index.php">🏠 Home</a>
                    <a href="buyer-dashboard.php">📊 Dashboard</a>
                    <a href="product-list.php">📦 Browse Products</a>
                    <a href="buyer-orders.php">📋 My Orders</a>
                    <a href="logout.php">🚪 Logout</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="welcome">
                <h2>
                    <i class="fas fa-check-circle"></i> Order Completed Successfully!
                </h2>
                <p>Thank you for your purchase. Your order has been processed and will be prepared for delivery.</p>
                <div class="order-info-grid">
                    <div class="info-card">
                        <strong>Order ID:</strong>
                        <p>#<?php echo $order_id; ?></p>
                    </div>
                    <div class="info-card">
                        <strong>Order Date:</strong>
                        <p><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="order-details-card">
                <h3>
                    <i class="fas fa-clipboard-list"></i> Order Details
                </h3>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total</th>
                            <th>₹<?php echo number_format($order['total_amount'], 2); ?></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="action-buttons">
                    <a href="product-list.php" class="btn btn-success">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <a href="buyer-orders.php" class="btn">
                        <i class="fas fa-list-alt"></i> View All Orders
                    </a>
                    <a href="buyer-dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
    <script>
        function toggleMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('menu-open');
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mainNav');
            const toggle = document.querySelector('.menu-toggle');
            
            if (nav && toggle && !nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('menu-open');
            }
        });
    </script>
</body>
</html>