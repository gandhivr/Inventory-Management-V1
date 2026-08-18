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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        
        // Delete order items first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Delete the order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Order #$order_id has been deleted successfully.";
        header('Location: admin-orders.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error deleting order: " . $e->getMessage();
    }
}

// Get order details for confirmation
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username as buyer_name 
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
    
    // Get order items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items_count = $stmt->fetchColumn();
    
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
    <title>Delete Order #<?php echo $order_id; ?> - Inventory Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Dark Theme for Delete Order */
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .delete-container {
            max-width: 600px;
            width: 100%;
            background: rgba(30, 41, 59, 0.9);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(100, 116, 139, 0.2);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .delete-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .delete-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .delete-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .delete-title {
            font-size: 2rem;
            font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 10px;
        }

        .delete-subtitle {
            color: #cbd5e1;
            font-size: 1.1rem;
        }

        .order-details {
            background: rgba(15, 23, 42, 0.6);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(100, 116, 139, 0.2);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #cbd5e1;
            font-weight: 600;
        }

        .detail-value {
            color: #f1f5f9;
            font-weight: 500;
        }

        .warning-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            color: #fca5a5;
            text-align: center;
            font-weight: 600;
        }

        .warning-message i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: block;
        }

        .form-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
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

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #f1f5f9;
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
            color: #f1f5f9;
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

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #ef4444;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .delete-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .delete-title {
                font-size: 1.5rem;
            }
            
            .delete-subtitle {
                font-size: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="delete-container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="delete-header">
            <div class="delete-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h1 class="delete-title">Delete Order</h1>
            <p class="delete-subtitle">Are you sure you want to delete this order?</p>
        </div>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value">#<?php echo $order_id; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value"><?php echo ucfirst($order['status']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Items Count:</span>
                <span class="detail-value"><?php echo $items_count; ?> items</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
        </div>

        <div class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Warning:</strong> This action cannot be undone. All order data and associated items will be permanently deleted.
        </div>

        <form method="POST" action="">
            <div class="form-actions">
                <a href="admin-orders.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Order
                </button>
            </div>
        </form>
    </div>
</body>
</html>