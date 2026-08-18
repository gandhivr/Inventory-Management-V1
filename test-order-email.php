<?php
/**
 * Test Order Email Notifications
 * Simulates sending order emails to check if they work
 */

require_once 'config/database.php';
require_once 'config/email-sendgrid.php';

echo "<h2>Testing Order Email Notifications</h2>";

// Test data
$order_id = 999;
$order_details = [
    'buyer_name' => 'Umesh Gandhi',
    'buyer_email' => 'ukgandhi999@gmail.com',
    'total_amount' => 1500.00,
    'items' => [
        [
            'name' => 'Test Product 1',
            'quantity' => 2,
            'price' => 500.00
        ],
        [
            'name' => 'Test Product 2',
            'quantity' => 1,
            'price' => 500.00
        ]
    ]
];

echo "<h3>Test Configuration:</h3>";
echo "<ul>";
echo "<li><strong>Order ID:</strong> $order_id</li>";
echo "<li><strong>Buyer:</strong> {$order_details['buyer_name']}</li>";
echo "<li><strong>Buyer Email:</strong> {$order_details['buyer_email']}</li>";
echo "<li><strong>Total:</strong> ₹" . number_format($order_details['total_amount'], 2) . "</li>";
echo "</ul>";

echo "<hr>";

// Test 1: Send to Buyer
echo "<h3>Test 1: Sending Order Confirmation to Buyer</h3>";
echo "<p>Sending to: <strong>{$order_details['buyer_email']}</strong></p>";

$result1 = sendOrderConfirmationToBuyer($order_id, $order_details);

if ($result1) {
    echo "<div style='background: #10b981; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Buyer email sent successfully!";
    echo "</div>";
} else {
    echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Failed to send buyer email";
    echo "</div>";
}

echo "<hr>";

// Test 2: Send to Admin
echo "<h3>Test 2: Sending Order Notification to Admin</h3>";

try {
    $stmt = $pdo->prepare("SELECT email, name FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && !empty($admin['email'])) {
        echo "<p>Admin found: <strong>{$admin['name']}</strong> ({$admin['email']})</p>";
        
        $result2 = sendOrderNotificationToAdmin($order_id, $order_details);
        
        if ($result2) {
            echo "<div style='background: #10b981; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Admin email sent successfully!";
            echo "</div>";
        } else {
            echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Failed to send admin email";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f59e0b; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ No admin user found or admin has no email address";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";

// Test 3: Send to Supplier (John - ID 13)
echo "<h3>Test 3: Sending Order Notification to Supplier (John)</h3>";

$supplier_id = 13; // John's ID from your screenshot
$supplier_items = [
    [
        'name' => 'Test Product from John',
        'quantity' => 2,
        'price' => 750.00
    ]
];

try {
    $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ? AND role = 'supplier'");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($supplier && !empty($supplier['email'])) {
        echo "<p>Supplier found: <strong>{$supplier['name']}</strong> ({$supplier['email']})</p>";
        
        $result3 = sendOrderNotificationToSupplier($order_id, $supplier_id, $supplier_items, $order_details);
        
        if ($result3) {
            echo "<div style='background: #10b981; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Supplier email sent successfully!";
            echo "</div>";
        } else {
            echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Failed to send supplier email";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f59e0b; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ Supplier not found or has no email address";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>Check the email inboxes for:</p>";
echo "<ul>";
echo "<li>Buyer: {$order_details['buyer_email']}</li>";
if (isset($admin)) echo "<li>Admin: {$admin['email']}</li>";
if (isset($supplier)) echo "<li>Supplier: {$supplier['email']}</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Emails may take 10-60 seconds to arrive. Check spam folders!</p>";
?>
