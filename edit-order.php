<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: admin-orders.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Update order status and total amount
        $status = $_POST['status'];
        $total_amount = floatval($_POST['total_amount']);
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, total_amount = ? WHERE id = ?");
        $stmt->execute([$status, $total_amount, $order_id]);
        
        // Update order items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item_id => $item_data) {
                $quantity = intval($item_data['quantity']);
                $price = floatval($item_data['price']);
                
                if ($quantity > 0) {
                    $stmt = $pdo->prepare("UPDATE order_items SET quantity = ?, price = ? WHERE id = ? AND order_id = ?");
                    $stmt->execute([$quantity, $price, $item_id, $order_id]);
                } else {
                    // Remove item if quantity is 0
                    $stmt = $pdo->prepare("DELETE FROM order_items WHERE id = ? AND order_id = ?");
                    $stmt->execute([$item_id, $order_id]);
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Order updated successfully!";
        header('Location: order-details.php?id=' . $order_id);
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username as buyer_name, u.email as buyer_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: admin-orders.php');
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image, u.username as supplier_name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        JOIN users u ON p.supplier_id = u.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    header('Location: admin-orders.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?php echo $order_id; ?> - Inventory Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Dark Theme for Edit Order */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #e2e8f0;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header */
        .header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            padding: 30px 40px;
            margin-bottom: 40px;
            border: 1px solid rgba(100, 116, 139, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #00d4ff, #ff00d4, #00ff88);
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #00d4ff, #ff00d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            background: linear-gradient(135deg, #64748b, #475569);
            color: #f1f5f9;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(100, 116, 139, 0.5);
            color: #f1f5f9;
            text-decoration: none;
        }

        /* Form Container */
        .form-container {
            background: rgba(30, 41, 59, 0.8);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(100, 116, 139, 0.2);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00d4ff, #0ea5e9);
        }

        /* Order Info Section */
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-card {
            background: rgba(15, 23, 42, 0.6);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid rgba(100, 116, 139, 0.3);
            backdrop-filter: blur(10px);
        }

        .info-card h3 {
            color: #f1f5f9;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(100, 116, 139, 0.2);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #cbd5e1;
            font-weight: 600;
        }

        .info-value {
            color: #f1f5f9;
            font-weight: 500;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            display: block;
            color: #f1f5f9;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(100, 116, 139, 0.3);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        .form-control option {
            background: #1e293b;
            color: #f1f5f9;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 40px;
        }

        .items-section h3 {
            color: #f1f5f9;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .items-table th {
            background: rgba(0, 212, 255, 0.1);
            color: #f1f5f9;
            padding: 20px 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .items-table td {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(100, 116, 139, 0.2);
            color: #cbd5e1;
        }

        .items-table tbody tr:hover {
            background: rgba(0, 212, 255, 0.05);
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Product Display */
        .product-display {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .product-placeholder {
            width: 50px;
            height: 50px;
            background: rgba(100, 116, 139, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .product-name {
            font-weight: 600;
            color: #f1f5f9;
        }

        /* Input Controls in Table */
        .table-input {
            width: 80px;
            padding: 8px 12px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.3);
            border-radius: 8px;
            color: #f1f5f9;
            text-align: center;
            font-weight: 600;
        }

        .table-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid #f59e0b;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid #10b981;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #ef4444;
        }

        .status-processing {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid #3b82f6;
        }

        /* Action Buttons */
        .form-actions {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0ea5e9);
            color: #0f172a;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.5);
            color: #0f172a;
            text-decoration: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
            color: #f1f5f9;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(100, 116, 139, 0.5);
            color: #f1f5f9;
            text-decoration: none;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #ef4444;
        }

        /* Animations */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .order-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-container {
                padding: 25px 20px;
            }
            
            .items-table {
                font-size: 0.9rem;
            }
            
            .items-table th,
            .items-table td {
                padding: 15px 10px;
            }
            
            .product-display {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #00d4ff, #0ea5e9);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header fade-in-up">
            <div class="header-content">
                <h1><i class="fas fa-edit"></i> Edit Order #<?php echo $order_id; ?></h1>
                <a href="order-details.php?id=<?php echo $order_id; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Order Details
                </a>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container fade-in-up">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Order Information -->
                <div class="order-info">
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                        <div class="info-row">
                            <span class="info-label">Order ID:</span>
                            <span class="info-value">#<?php echo $order_id; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Current Status:</span>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Customer Information</h3>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">User ID:</span>
                            <span class="info-value">#<?php echo $order['user_id']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Order Status and Total -->
                <div class="form-group">
                    <label for="status"><i class="fas fa-flag"></i> Order Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="pending" <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo ($order['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo ($order['status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                        <option value="completed" <?php echo ($order['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_amount"><i class="fas fa-dollar-sign"></i> Total Amount</label>
                    <input type="number" name="total_amount" id="total_amount" class="form-control" 
                           value="<?php echo $order['total_amount']; ?>" step="0.01" min="0" required>
                </div>

                <!-- Order Items -->
                <div class="items-section">
                    <h3><i class="fas fa-box"></i> Order Items</h3>
                    
                    <?php if (empty($order_items)): ?>
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                            <p>No items found in this order.</p>
                        </div>
                    <?php else: ?>
                        <table class="items-table">
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
                                $total = 0;
                                foreach ($order_items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="product-display">
                                                <?php if ($item['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                         alt="Product" class="product-image">
                                                <?php else: ?>
                                                    <div class="product-placeholder">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                        <td>
                                            <input type="number" name="items[<?php echo $item['id']; ?>][quantity]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="0" class="table-input" 
                                                   onchange="updateSubtotal(this, <?php echo $item['price']; ?>)">
                                        </td>
                                        <td>
                                            $<input type="number" name="items[<?php echo $item['id']; ?>][price]" 
                                                    value="<?php echo $item['price']; ?>" 
                                                    step="0.01" min="0" class="table-input"
                                                    onchange="updateSubtotal(this.parentNode.previousElementSibling.querySelector('input'), this.value)">
                                        </td>
                                        <td>
                                            <strong class="subtotal">₹<?php echo number_format($subtotal, 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="order-details.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateSubtotal(quantityInput, price) {
            const quantity = parseFloat(quantityInput.value) || 0;
            const unitPrice = parseFloat(price) || 0;
            const subtotal = quantity * unitPrice;
            
            const subtotalCell = quantityInput.closest('tr').querySelector('.subtotal');
            subtotalCell.textContent = '$' + subtotal.toFixed(2);
            
            // Update total amount
            updateTotalAmount();
        }
        
        function updateTotalAmount() {
            let total = 0;
            document.querySelectorAll('.subtotal').forEach(function(cell) {
                const amount = parseFloat(cell.textContent.replace('$', '')) || 0;
                total += amount;
            });
            
            document.getElementById('total_amount').value = total.toFixed(2);
        }
        
        // Add event listeners for real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('input[name*="[quantity]"]');
            const priceInputs = document.querySelectorAll('input[name*="[price]"]');
            
            quantityInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const priceInput = this.closest('tr').querySelector('input[name*="[price]"]');
                    updateSubtotal(this, priceInput.value);
                });
            });
            
            priceInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const quantityInput = this.closest('tr').querySelector('input[name*="[quantity]"]');
                    updateSubtotal(quantityInput, this.value);
                });
            });
        });
    </script>
</body>
</html>