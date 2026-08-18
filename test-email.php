<?php
/**
 * Email Testing Script
 * Use this to test your email configuration before going live
 * 
 * IMPORTANT: Delete or restrict access to this file in production!
 */

require_once 'config/database.php';
require_once 'config/email.php';

// Security: Only allow access from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access denied. This script can only be run from localhost.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .alert { margin-top: 20px; }
        .config-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">📧 Email Configuration Test</h1>
        
        <div class="warning">
            <strong>⚠️ Security Warning:</strong> This is a test script. Delete or restrict access in production!
        </div>

        <div class="config-info">
            <h5>Current Email Configuration:</h5>
            <ul>
                <li><strong>SMTP Host:</strong> <?php echo SMTP_HOST; ?></li>
                <li><strong>SMTP Port:</strong> <?php echo SMTP_PORT; ?></li>
                <li><strong>From Email:</strong> <?php echo SMTP_FROM_EMAIL; ?></li>
                <li><strong>From Name:</strong> <?php echo SMTP_FROM_NAME; ?></li>
            </ul>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            $test_type = $_POST['test_type'];
            
            if (!$test_email) {
                echo '<div class="alert alert-danger">Invalid email address!</div>';
            } else {
                echo '<div class="alert alert-info">Sending test email to: ' . htmlspecialchars($test_email) . '</div>';
                
                $success = false;
                
                switch ($test_type) {
                    case 'simple':
                        // Simple test email
                        $subject = "Test Email from Inventory Management System";
                        $message = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2>Test Email</h2>
                            <p>This is a test email from your Inventory Management System.</p>
                            <p>If you received this email, your email configuration is working correctly!</p>
                            <p><strong>Sent at:</strong> " . date('F j, Y g:i A') . "</p>
                        </body>
                        </html>
                        ";
                        $success = sendEmail($test_email, $subject, $message);
                        break;
                        
                    case 'order':
                        // Test order notification
                        $order_details = [
                            'buyer_name' => 'Test Buyer',
                            'buyer_email' => $test_email,
                            'total_amount' => 1500.00,
                            'items' => [
                                ['name' => 'Test Product 1', 'quantity' => 2, 'price' => 500.00],
                                ['name' => 'Test Product 2', 'quantity' => 1, 'price' => 500.00]
                            ]
                        ];
                        $success = sendOrderConfirmationToBuyer(999, $order_details);
                        break;
                        
                    case 'admin':
                        // Test admin notification
                        $order_details = [
                            'buyer_name' => 'Test Buyer',
                            'buyer_email' => 'buyer@example.com',
                            'total_amount' => 1500.00,
                            'items' => [
                                ['name' => 'Test Product 1', 'quantity' => 2, 'price' => 500.00],
                                ['name' => 'Test Product 2', 'quantity' => 1, 'price' => 500.00]
                            ]
                        ];
                        
                        // Temporarily override admin email for testing
                        $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
                        $stmt->execute();
                        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($admin) {
                            $subject = "Test Admin Notification - Order #999";
                            $message = "
                            <html>
                            <body style='font-family: Arial, sans-serif;'>
                                <h2>Test Admin Notification</h2>
                                <p>This is a test of the admin order notification email.</p>
                                <p><strong>Order ID:</strong> #999</p>
                                <p><strong>Buyer:</strong> Test Buyer</p>
                                <p><strong>Total:</strong> ₹1,500.00</p>
                            </body>
                            </html>
                            ";
                            $success = sendEmail($test_email, $subject, $message);
                        }
                        break;
                }
                
                if ($success) {
                    echo '<div class="alert alert-success">
                        <strong>✅ Success!</strong> Test email sent successfully. Check your inbox (and spam folder).
                    </div>';
                } else {
                    echo '<div class="alert alert-danger">
                        <strong>❌ Failed!</strong> Could not send email. Check your configuration and server logs.
                    </div>';
                }
            }
        }
        ?>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="test_email" class="form-label">Test Email Address:</label>
                <input type="email" class="form-control" id="test_email" name="test_email" required 
                       placeholder="your-email@example.com">
                <small class="form-text text-muted">Enter the email address where you want to receive the test email</small>
            </div>

            <div class="mb-3">
                <label for="test_type" class="form-label">Test Type:</label>
                <select class="form-control" id="test_type" name="test_type" required>
                    <option value="simple">Simple Test Email</option>
                    <option value="order">Order Confirmation (Buyer)</option>
                    <option value="admin">Admin Notification</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Send Test Email</button>
        </form>

        <div class="mt-5">
            <h5>Troubleshooting Tips:</h5>
            <ul>
                <li>Check your spam/junk folder</li>
                <li>Verify email credentials in <code>config/email.php</code></li>
                <li>Ensure your server allows outbound email</li>
                <li>Check PHP error logs for detailed error messages</li>
                <li>For Gmail, use App Passwords (not your regular password)</li>
            </ul>
        </div>

        <div class="mt-4">
            <h5>Database Email Status:</h5>
            <?php
            try {
                // Check admin email
                $stmt = $pdo->query("SELECT COUNT(*) as count, 
                    SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as with_email 
                    FROM users WHERE role = 'admin'");
                $admin_stats = $stmt->fetch();
                
                // Check supplier emails
                $stmt = $pdo->query("SELECT COUNT(*) as count, 
                    SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as with_email 
                    FROM users WHERE role = 'supplier'");
                $supplier_stats = $stmt->fetch();
                
                // Check buyer emails
                $stmt = $pdo->query("SELECT COUNT(*) as count, 
                    SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as with_email 
                    FROM users WHERE role = 'buyer'");
                $buyer_stats = $stmt->fetch();
                
                echo '<table class="table table-sm">';
                echo '<tr><th>Role</th><th>Total Users</th><th>With Email</th><th>Status</th></tr>';
                
                $roles = [
                    'Admin' => $admin_stats,
                    'Supplier' => $supplier_stats,
                    'Buyer' => $buyer_stats
                ];
                
                foreach ($roles as $role => $stats) {
                    $status = $stats['with_email'] == $stats['count'] ? 
                        '<span class="badge bg-success">✓ All Set</span>' : 
                        '<span class="badge bg-warning">⚠ Missing Emails</span>';
                    echo "<tr>
                        <td>{$role}</td>
                        <td>{$stats['count']}</td>
                        <td>{$stats['with_email']}</td>
                        <td>{$status}</td>
                    </tr>";
                }
                echo '</table>';
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error checking database: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
