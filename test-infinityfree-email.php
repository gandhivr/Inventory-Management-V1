<?php
/**
 * Simple InfinityFree Email Test
 * This uses PHP mail() which works on InfinityFree
 */

$test_email = 'vrajgandhi06@gmail.com'; // Change this to your email

$to = $test_email;
$subject = 'Test Email from ProStock - ' . date('Y-m-d H:i:s');
$from = 'noreply@prostock.free.nf';
$from_name = 'ProStock Inventory System';

$message = '
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
    <div class="container">
        <div class="header">
            <h1>✅ Email Test Successful!</h1>
        </div>
        <div class="content">
            <h2>Congratulations!</h2>
            <p>Your InfinityFree email system is working correctly.</p>
            <p><strong>Sent at:</strong> ' . date('F j, Y g:i A') . '</p>
            <p><strong>From:</strong> ' . $from . '</p>
            <p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>
            <p>Your order notification emails will now work properly!</p>
        </div>
    </div>
</body>
</html>
';

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: $from_name <$from>\r\n";
$headers .= "Reply-To: $test_email\r\n";

$additional_params = "-f$from";

$result = @mail($to, $subject, $message, $headers, $additional_params);

?>
<!DOCTYPE html>
<html>
<head>
    <title>InfinityFree Email Test</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { background: #d1fae5; color: #065f46; padding: 20px; border-radius: 5px; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 5px; border-left: 4px solid #ef4444; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 5px; margin: 20px 0; }
        h1 { margin-top: 0; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 InfinityFree Email Test</h1>
        
        <?php if ($result): ?>
            <div class="success">
                <h2>✅ Email Sent Successfully!</h2>
                <p><strong>Test email sent to:</strong> <?php echo htmlspecialchars($test_email); ?></p>
                <p><strong>Time:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                <p><strong>From:</strong> <?php echo htmlspecialchars($from); ?></p>
            </div>
            
            <div class="info">
                <h3>What to do next:</h3>
                <ul>
                    <li>Check your inbox at <strong><?php echo htmlspecialchars($test_email); ?></strong></li>
                    <li><strong>Check your SPAM folder</strong> - InfinityFree emails often go to spam initially</li>
                    <li>If you received the email, your system is working!</li>
                    <li>Upload <code>config/email-infinityfree.php</code> and rename it to <code>config/email.php</code></li>
                    <li>Delete this test file for security</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error">
                <h2>❌ Email Failed to Send</h2>
                <p>The mail() function returned false. This could mean:</p>
                <ul>
                    <li>PHP mail() is disabled on your server</li>
                    <li>Server configuration issue</li>
                    <li>Invalid email format</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>Important Notes for InfinityFree:</h3>
            <ul>
                <li>InfinityFree blocks external SMTP (Gmail, etc.)</li>
                <li>You must use PHP mail() function instead</li>
                <li>Emails from free hosting often go to spam</li>
                <li>Use your domain email (noreply@prostock.free.nf) as sender</li>
                <li>Set Reply-To header to your Gmail for replies</li>
            </ul>
        </div>
    </div>
</body>
</html>
