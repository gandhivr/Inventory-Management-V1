<?php
// Debug script to check order status values
session_start();
require_once 'config/database.php';

try {
    $stmt = $pdo->query("
        SELECT id, user_id, total_amount, status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Order Status Debug</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Order ID</th><th>User ID</th><th>Amount</th><th>Status</th><th>Status Length</th><th>Status (Raw)</th><th>Created At</th></tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . $order['user_id'] . "</td>";
        echo "<td>₹" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>" . strlen($order['status']) . "</td>";
        echo "<td>" . var_export($order['status'], true) . "</td>";
        echo "<td>" . $order['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
