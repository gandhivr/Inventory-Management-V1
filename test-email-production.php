<?php
/**
 * Production Email Test Script
 * Upload this to your online server to test email functionality
 * 
 * SECURITY: Set a password below and delete this file after testing!
 */

// SET YOUR TEST PASSWORD HERE
define('TEST_PASSWORD', 'test123'); // Change this!

session_start();

// Check password
if (!isset($_SESSION['email_test_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === TEST_PASSWORD) {
            $_SESSION['email_test_auth'] = true;
        } else {
            $error = "Incorrect password!";
        }
    }
    
    if (!isset($_SESSION['email_test_auth'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Test - Authentication</title>
            <style>
                body { font-family: Arial; background: #f5f5f5; padding: 50px; }
                .login-box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
                button { width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
                button:hover { background: #059669; }
                .error { color: red; padding: 10px; background: #fee; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>🔒 Email Test Authentication</h2>
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter test password" required>
                    <button type="submit">Access Test</button>
                </form>
                <p style="color: #666; font-size: 14px; margin-top: 20px;">
                    Password is set in the PHP file (line 9)
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once 'config/database.php';
require_once 'config/email.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .config-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { padding: 12px 30px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #059669; }
        .logout { float: right; padding: 8px 15px; background: #ef4444; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .logout:hover { background: #dc2626; }
        h1 { margin-top: 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>📧 Production Email Test</h1>
        
        <div class="alert alert-warning">
            <strong>⚠️ Important:</strong> Delete this file after testing! It's a security risk to leave it on your server.
        </div>

        <div class="config-box">
            <h3>Current Configuration:</h3>
            <ul>
                <li><strong>SMTP Host:</strong> <?php echo defined('SMTP_HOST') ? SMTP_HOST : 'Not configured'; ?></li>
                <li><strong>SMTP Port:</strong> <?php echo defined('SMTP_PORT') ? SMTP_PORT : 'Not configured'; ?></li>
                <li><strong>Username:</strong> <?php echo defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not configured'; ?></li>
                <li><strong>From Email:</strong> <?php echo defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'Not configured'; ?></li>
                <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_NAME']; ?></li>
                <li><strong>Server IP:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?></li>
            </ul>
        </div>

        <?php
        // Handle logout
        if (isset($_GET['logout'])) {
            session_destroy();
            header('Location: test-email-production.php');
            exit;
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
            $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            
            if (!$test_email) {
                echo '<div class="alert alert-danger"><strong>❌ Error:</strong> Invalid email address!</div>';
            } else {
                echo '<div class="alert alert-info">📤 Attempting to send email to: ' . htmlspecialchars($test_email) . '</div>';
                
                // Create test email
                $subject = "Test Email from Inventory System - " . date('Y-m-d H:i:s');
                $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                        .info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                        .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>✅ Email Test Successful!</h1>
                        </div>
                        <div class='content'>
                            <h2>Congratulations!</h2>
                            <p>If you're reading this email, your SMTP configuration is working correctly.</p>
                            
                            <div class='info'>
                                <h3>Test Details:</h3>
                                <p><strong>Sent at:</strong> " . date('F j, Y g:i A') . "</p>
                                <p><strong>From:</strong> " . SMTP_FROM_EMAIL . "</p>
                                <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
                                <p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>
                            </div>
                            
                            <p>Your email system is now ready to send order notifications, confirmations, and other automated emails.</p>
                            
                            <p><strong>Next Steps:</strong></p>
                            <ul>
                                <li>Delete the test-email-production.php file from your server</li>
                                <li>Test placing an order to verify order emails work</li>
                                <li>Check spam folder if emails don't arrive</li>
                            </ul>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " Inventory Management System</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Try to send email
                try {
                    $result = sendEmail($test_email, $subject, $message);
                    
                    if ($result) {
                        echo '<div class="alert alert-success">
                            <strong>✅ SUCCESS!</strong> Email sent successfully!<br><br>
                            <strong>What to do next:</strong><br>
                            1. Check your inbox at <strong>' . htmlspecialchars($test_email) . '</strong><br>
                            2. Check your spam/junk folder if you don\'t see it<br>
                            3. If email arrived, your configuration is working!<br>
                            4. <strong>DELETE THIS FILE</strong> from your server for security
                        </div>';
                    } else {
                        echo '<div class="alert alert-danger">
                            <strong>❌ FAILED!</strong> Could not send email.<br><br>
                            <strong>Possible issues:</strong><br>
                            • SMTP credentials are incorrect<br>
                            • Gmail App Password is wrong<br>
                            • Server firewall blocking SMTP ports<br>
                            • SMTP host/port configuration incorrect<br><br>
                            Check your config/email-smtp.php file and verify all settings.
                        </div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">
                        <strong>❌ ERROR:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    </div>';
                }
            }
        }
        ?>

        <form method="POST" style="margin-top: 30px;">
            <div class="form-group">
                <label for="test_email">📧 Your Email Address:</label>
                <input type="email" id="test_email" name="test_email" 
                       placeholder="your-email@gmail.com" 
                       value="<?php echo defined('SMTP_USERNAME') ? SMTP_USERNAME : ''; ?>" 
                       required>
                <small style="color: #666;">Enter the email where you want to receive the test</small>
            </div>

            <button type="submit" name="send_test">Send Test Email</button>
        </form>

        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>📋 Troubleshooting Checklist:</h3>
            <ul>
                <li>✓ SMTP credentials are correct in config/email-smtp.php</li>
                <li>✓ Using Gmail App Password (not regular password)</li>
                <li>✓ Server allows outbound connections on port 587</li>
                <li>✓ PHP has socket functions enabled (fsockopen)</li>
                <li>✓ Check spam/junk folder for test emails</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
}
?>
