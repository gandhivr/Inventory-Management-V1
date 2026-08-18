<?php
/**
 * SendGrid Email Test
 * Test your SendGrid configuration
 */

// Simple password protection
$password = 'test123';
session_start();

if (!isset($_SESSION['auth']) && (!isset($_POST['pass']) || $_POST['pass'] !== $password)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>SendGrid Test - Login</title>
        <style>
            body { font-family: Arial; background: #f5f5f5; padding: 50px; }
            .box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>🔒 SendGrid Test</h2>
            <form method="POST">
                <input type="password" name="pass" placeholder="Password: test123" required>
                <button type="submit">Access</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION['auth'] = true;

// Load SendGrid config
require_once 'config/email-sendgrid.php';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
    
    if (!$test_email) {
        $error = "Invalid email address!";
    } elseif (SENDGRID_API_KEY === 'YOUR_SENDGRID_API_KEY_HERE') {
        $error = "Please update SENDGRID_API_KEY in config/email-sendgrid.php";
    } else {
        $subject = "SendGrid Test - " . date('Y-m-d H:i:s');
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 10px; }
                .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px; }
                .content { padding: 20px; background: white; margin-top: 20px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ SendGrid Working!</h1>
                </div>
                <div class='content'>
                    <h2>Success!</h2>
                    <p>Your SendGrid email system is working correctly.</p>
                    <p><strong>Sent at:</strong> " . date('F j, Y g:i A') . "</p>
                    <p><strong>From:</strong> " . SENDGRID_FROM_EMAIL . "</p>
                    <p><strong>Via:</strong> SendGrid API</p>
                    <p>Your order notification emails will now work on InfinityFree!</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $result = sendEmail($test_email, $subject, $message);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SendGrid Email Test</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { background: #d1fae5; color: #065f46; padding: 20px; border-radius: 5px; border-left: 4px solid #10b981; margin: 20px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 5px; border-left: 4px solid #ef4444; margin: 20px 0; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .config { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { padding: 12px 30px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #059669; }
        .logout { float: right; padding: 8px 15px; background: #ef4444; color: white; text-decoration: none; border-radius: 5px; }
        h1 { margin-top: 0; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>📧 SendGrid Email Test</h1>
        
        <?php if (isset($_GET['logout'])): session_destroy(); header('Location: test-sendgrid.php'); exit; endif; ?>
        
        <div class="info">
            <strong>ℹ️ About SendGrid:</strong> SendGrid is a free email service that works with InfinityFree. 
            It bypasses the hosting email restrictions and provides reliable email delivery.
        </div>
        
        <div class="config">
            <strong>Current Configuration:</strong><br>
            API Key: <?php echo (SENDGRID_API_KEY === 'YOUR_SENDGRID_API_KEY_HERE') ? '❌ NOT SET' : '✅ Configured'; ?><br>
            From Email: <?php echo SENDGRID_FROM_EMAIL; ?><br>
            From Name: <?php echo SENDGRID_FROM_NAME; ?>
        </div>
        
        <?php if (SENDGRID_API_KEY === 'YOUR_SENDGRID_API_KEY_HERE'): ?>
            <div class="warning">
                <strong>⚠️ Setup Required!</strong><br>
                Please follow the setup guide in <code>SENDGRID-SETUP-GUIDE.md</code> to configure your API key.
            </div>
        <?php endif; ?>
        
        <?php if ($result === true): ?>
            <div class="success">
                <h2>✅ Email Sent Successfully!</h2>
                <p><strong>Sent to:</strong> <?php echo htmlspecialchars($_POST['test_email']); ?></p>
                <p><strong>Time:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                <p><strong>Via:</strong> SendGrid API</p>
                <br>
                <strong>What to do next:</strong>
                <ul>
                    <li>Check your inbox (and spam folder!)</li>
                    <li>If email arrived, your setup is complete!</li>
                    <li>Rename <code>config/email-sendgrid.php</code> to <code>config/email.php</code></li>
                    <li>Test by placing an order on your site</li>
                    <li>Delete this test file for security</li>
                </ul>
            </div>
        <?php elseif ($result === false): ?>
            <div class="error">
                <h2>❌ Email Failed to Send</h2>
                <p>SendGrid API returned an error. Check:</p>
                <ul>
                    <li>API key is correct and has Mail Send permission</li>
                    <li>Sender email (<?php echo SENDGRID_FROM_EMAIL; ?>) is verified in SendGrid</li>
                    <li>Your SendGrid account is active</li>
                    <li>Check SendGrid Activity Feed for details</li>
                </ul>
            </div>
        <?php elseif ($error): ?>
            <div class="error">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="margin-top: 30px;">
            <h3>Send Test Email</h3>
            <input type="email" name="test_email" placeholder="your-email@gmail.com" 
                   value="<?php echo SENDGRID_FROM_EMAIL; ?>" required>
            <button type="submit" name="send_test">Send Test Email</button>
        </form>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>📋 Setup Checklist:</h3>
            <ul>
                <li>✓ Sign up at sendgrid.com (free)</li>
                <li>✓ Verify your sender email in SendGrid</li>
                <li>✓ Create API key with Mail Send permission</li>
                <li>✓ Update SENDGRID_API_KEY in config/email-sendgrid.php</li>
                <li>✓ Test email sending (this page)</li>
                <li>✓ Rename email-sendgrid.php to email.php</li>
            </ul>
            
            <p><strong>Need help?</strong> Read <code>SENDGRID-SETUP-GUIDE.md</code> for detailed instructions.</p>
        </div>
    </div>
</body>
</html>
