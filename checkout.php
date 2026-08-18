<?php
session_start();
require_once 'config/database.php';
require_once 'config/email-sendgrid.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock_quantity
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        header('Location: cart.php?error=Your cart is empty');
        exit();
    }

    // Calculate total and verify stock
    $total_amount = 0;
    $stock_errors = [];
    
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $stock_errors[] = $item['name'] . ' (requested: ' . $item['quantity'] . ', available: ' . $item['stock_quantity'] . ')';
        }
        $total_amount += $item['price'] * $item['quantity'];
    }

    if (!empty($stock_errors)) {
        $error_message = 'Insufficient stock for: ' . implode(', ', $stock_errors);
        header('Location: cart.php?error=' . urlencode($error_message));
        exit();
    }

} catch (PDOException $e) {
    header('Location: cart.php?error=Error processing checkout');
    exit();
}

// Process checkout if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$user_id, $total_amount]);
        $order_id = $pdo->lastInsertId();

        // Create order items and update product stock
        foreach ($cart_items as $item) {
            // Add order item
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        // Send email notifications after successful order
        try {
            // Get buyer information
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Prepare order details for emails
            $order_details = [
                'buyer_name' => $buyer['name'],
                'buyer_email' => $buyer['email'],
                'total_amount' => $total_amount,
                'items' => []
            ];
            
            // Get detailed order items with supplier info
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name, p.supplier_id 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group items by supplier
            $supplier_items = [];
            foreach ($order_items_details as $item) {
                $order_details['items'][] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
                
                if (!isset($supplier_items[$item['supplier_id']])) {
                    $supplier_items[$item['supplier_id']] = [];
                }
                $supplier_items[$item['supplier_id']][] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            
            // Send email to buyer
            sendOrderConfirmationToBuyer($order_id, $order_details);
            
            // Send email to admin
            sendOrderNotificationToAdmin($order_id, $order_details);
            
            // Send email to each supplier
            foreach ($supplier_items as $supplier_id => $items) {
                sendOrderNotificationToSupplier($order_id, $supplier_id, $items, $order_details);
            }
            
        } catch (Exception $e) {
            // Log error but don't stop the order process
            error_log("Email notification error: " . $e->getMessage());
        }

        header('Location: order-success.php?order_id=' . $order_id);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $checkout_error = 'Checkout failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/unified-dashboard.css">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
    <style>
        /* CRITICAL TEXT VISIBILITY FIXES FOR CHECKOUT */
        
        /* Force ALL text to be visible */
        *, *::before, *::after {
            color: #f8fafc !important;
        }
        
        /* Main content styling */
        main {
            padding: 2rem 0 !important;
            color: #f8fafc !important;
        }
        
        /* Page title styling */
        h2 {
            color: #f8fafc !important;
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            text-align: center !important;
            margin-bottom: 0.5rem !important;
        }
        
        /* Subtitle styling */
        main p {
            color: #cbd5e1 !important;
            text-align: center !important;
            font-size: 1.1rem !important;
            margin-bottom: 2rem !important;
        }
        
        /* Card styling */
        .checkout-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
        }
        
        /* Card headers */
        .checkout-card h3 {
            color: #f8fafc !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            margin-bottom: 1.5rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        
        /* Order item styling */
        .order-item {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 1rem 0 !important;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2) !important;
        }
        
        .order-item h4 {
            color: #f8fafc !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.25rem !important;
        }
        
        .order-item p {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
            margin: 0 !important;
            text-align: left !important;
        }
        
        .order-item strong {
            color: #10b981 !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
        }
        
        /* Total styling */
        .total-section {
            padding: 1.5rem 0 !important;
            font-size: 1.3rem !important;
            font-weight: 700 !important;
            color: #10b981 !important;
            text-align: right !important;
            border-top: 2px solid rgba(16, 185, 129, 0.3) !important;
        }
        
        /* Payment card styling */
        .payment-card {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            padding: 2rem !important;
            position: sticky !important;
            top: 2rem !important;
        }
        
        .payment-card h3 {
            color: #f8fafc !important;
            font-size: 1.3rem !important;
            font-weight: 700 !important;
            margin-bottom: 1rem !important;
        }
        
        .payment-card p {
            color: #cbd5e1 !important;
            margin-bottom: 2rem !important;
            text-align: left !important;
        }
        
        /* Total highlight box */
        .total-highlight {
            background: rgba(16, 185, 129, 0.1) !important;
            padding: 1.5rem !important;
            border-radius: 0.75rem !important;
            margin-bottom: 2rem !important;
            border-left: 4px solid #10b981 !important;
        }
        
        .total-highlight span:first-child {
            font-weight: 600 !important;
            color: #cbd5e1 !important;
        }
        
        .total-highlight span:last-child {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #10b981 !important;
        }
        
        /* Button styling */
        .btn {
            font-weight: 600 !important;
            padding: 1rem 2rem !important;
            border-radius: 0.5rem !important;
            border: none !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            font-size: 1.1rem !important;
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
        
        /* Alert styling */
        .alert {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            border-radius: 0.75rem !important;
            padding: 1rem 1.5rem !important;
            margin-bottom: 2rem !important;
        }
        
        /* Grid layout */
        .checkout-grid {
            display: grid !important;
            grid-template-columns: 2fr 1fr !important;
            gap: 2rem !important;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr !important;
            }
            
            .payment-card {
                position: static !important;
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
                    <a href="buyer-dashboard.php">Dashboard</a>
                    <a href="cart.php">Back to Cart</a>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                    <a href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="text-center mb-4">
                <h2><i class="fas fa-shopping-cart"></i> Checkout</h2>
                <p>Review your order and complete your purchase</p>
            </div>

            <?php if (isset($checkout_error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($checkout_error); ?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <div class="checkout-card">
                    <h3>
                        <i class="fas fa-clipboard-list"></i> Order Summary
                    </h3>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div>
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div>
                                <strong>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-section">
                        <i class="fas fa-calculator"></i> Total: ₹<?php echo number_format($total_amount, 2); ?>
                    </div>
                </div>

                <div class="payment-card">
                    <h3>
                        <i class="fas fa-credit-card"></i> Complete Order
                    </h3>
                    <p>Review your order and click the button below to complete your purchase.</p>
                    
                    <div class="total-highlight">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Order Total:</span>
                            <span>₹<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                    </div>
                    
                    <form method="POST" style="margin-bottom: 1rem;">
                        <button type="submit" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-rocket"></i> Complete Order (₹<?php echo number_format($total_amount, 2); ?>)
                        </button>
                    </form>

                    <a href="cart.php" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>