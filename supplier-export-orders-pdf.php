<?php
// ===================================================================
// SUPPLIER ORDERS PDF EXPORT
// ===================================================================
// Purpose: Generate professional PDF report of supplier's orders
// Features: Summary statistics, order details, print-optimized layout
// Output: HTML that triggers browser's print-to-PDF functionality

// Start session for user authentication
session_start();

// Include database connection
require_once 'config/database.php';

// ===================================================================
// AUTHENTICATION: Verify supplier access
// ===================================================================
// Only authenticated suppliers can generate their order reports
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    // Redirect non-suppliers to login
    header('Location: login.php');
    exit();
}

// Store supplier information for report header
$user_id = $_SESSION['user_id'];           // Supplier's database ID
$supplier_name = $_SESSION['username'];     // Supplier's name for display

try {
    // ===================================================================
    // DATABASE QUERY: Fetch orders with detailed item information
    // ===================================================================
    // Complex query that:
    // 1. Joins orders with order_items, products, and users tables
    // 2. Groups items by order using GROUP_CONCAT
    // 3. Filters by supplier_id to show only this supplier's orders
    // 4. Formats item details with quantity and price
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.id, o.total_amount, o.status, o.created_at, u.username as buyer_name,
               GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ', Price: ₹', oi.price, ')') SEPARATOR '<br>') as items
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    // Execute query with supplier ID parameter
    $stmt->execute([$user_id]);
    // Fetch all orders as associative array
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===================================================================
    // CALCULATE SUMMARY STATISTICS
    // ===================================================================
    // These metrics appear in the report header
    
    // Count total number of orders
    $total_orders = count($orders);
    
    // Sum all order amounts using array_column to extract total_amount values
    // array_column() creates array of just the 'total_amount' values
    // array_sum() adds them all together
    $total_revenue = array_sum(array_column($orders, 'total_amount'));

} catch (PDOException $e) {
    // Handle database errors
    die('Error: ' . $e->getMessage());
}

// ===================================================================
// HTML GENERATION: Start output buffering
// ===================================================================
// ob_start() captures all output instead of sending to browser
// This allows us to manipulate the HTML before sending
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #10b981;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item h3 {
            margin: 0;
            color: #10b981;
            font-size: 24px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #10b981;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Orders Report</h1>
        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($supplier_name); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo $total_orders; ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></h3>
                <p>Average Order Value</p>
            </div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">No orders found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Buyer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                        <td style="font-size: 11px;"><?php echo $order['items']; ?></td>
                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p>Inventory Management System - Orders Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
<?php
// ===================================================================
// CAPTURE BUFFERED OUTPUT
// ===================================================================
// ob_get_clean() retrieves all buffered output and clears the buffer
// This gives us the complete HTML as a string
$html = ob_get_clean();

// ===================================================================
// OUTPUT PDF-READY HTML
// ===================================================================
// Set content type to HTML (browser will render it)
header('Content-Type: text/html; charset=utf-8');

// Output the generated HTML
echo $html;

// Automatically trigger browser's print dialog
// User can then choose "Save as PDF" or print directly
// This approach works without external PDF libraries
echo '<script>window.print();</script>';
?>
