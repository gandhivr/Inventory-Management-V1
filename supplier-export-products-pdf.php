<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$supplier_name = $_SESSION['username'];

try {
    // Get all products for this supplier
    $stmt = $pdo->prepare("
        SELECT id, name, description, price, stock_quantity, created_at
        FROM products 
        WHERE supplier_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_products = count($products);
    $total_stock = array_sum(array_column($products, 'stock_quantity'));
    $total_value = 0;
    $low_stock_count = 0;
    
    foreach ($products as $product) {
        $total_value += $product['price'] * $product['stock_quantity'];
        if ($product['stock_quantity'] < 10) {
            $low_stock_count++;
        }
    }

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Generate HTML for PDF
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
            grid-template-columns: repeat(4, 1fr);
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
        .low-stock {
            color: #dc2626;
            font-weight: bold;
        }
        .good-stock {
            color: #10b981;
            font-weight: bold;
        }
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
        <h1>📦 Products Inventory Report</h1>
        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($supplier_name); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $total_stock; ?></h3>
                <p>Total Stock Units</p>
            </div>
            <div class="summary-item">
                <h3>₹<?php echo number_format($total_value, 2); ?></h3>
                <p>Inventory Value</p>
            </div>
            <div class="summary-item">
                <h3 style="color: <?php echo $low_stock_count > 0 ? '#dc2626' : '#10b981'; ?>">
                    <?php echo $low_stock_count; ?>
                </h3>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">No products found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Value</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong>#<?php echo $product['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td style="font-size: 11px;">
                            <?php 
                            $desc = htmlspecialchars($product['description']);
                            echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                            ?>
                        </td>
                        <td><strong>₹<?php echo number_format($product['price'], 2); ?></strong></td>
                        <td class="<?php echo $product['stock_quantity'] < 10 ? 'low-stock' : 'good-stock'; ?>">
                            <?php echo $product['stock_quantity']; ?>
                            <?php if ($product['stock_quantity'] < 10): ?>
                                ⚠️
                            <?php endif; ?>
                        </td>
                        <td>₹<?php echo number_format($product['price'] * $product['stock_quantity'], 2); ?></td>
                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p>Inventory Management System - Products Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Output as PDF using browser's print-to-PDF functionality
header('Content-Type: text/html; charset=utf-8');
echo $html;
echo '<script>window.print();</script>';
?>
