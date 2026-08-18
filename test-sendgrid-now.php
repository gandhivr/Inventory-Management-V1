<?php
/**
 * Quick SendGrid Test
 * Tests if SendGrid API is working correctly
 */

require_once 'config/email-sendgrid.php';

echo "<h2>Testing SendGrid Email...</h2>";

// Test email
$test_email = 'vrajgandhi06@gmail.com'; // Your verified email
$subject = 'SendGrid Test - ProStock';
$message = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ SendGrid is Working!</h1>
        </div>
        <div class="content">
            <p>Congratulations!</p>
            <p>Your SendGrid email integration is working correctly.</p>
            <p><strong>From:</strong> ProStock</p>
            <p><strong>Time:</strong> ' . date('F j, Y g:i A') . '</p>
            <p>You can now send order notifications and other emails from your ProStock website.</p>
        </div>
    </div>
</body>
</html>
';

echo "<p>Sending test email to: <strong>$test_email</strong></p>";

$result = sendEmail($test_email, $subject, $message);

if ($result) {
    echo "<div style='background: #10b981; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Success!</h3>";
    echo "<p>Test email sent successfully via SendGrid!</p>";
    echo "<p>Check your inbox: <strong>$test_email</strong></p>";
    echo "<p>Note: It may take a few seconds to arrive. Check spam folder if you don't see it.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #ef4444; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Failed to send test email. Check your API key and sender verification.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Configuration:</h3>";
echo "<ul>";
echo "<li><strong>API Key:</strong> " . substr(SENDGRID_API_KEY, 0, 20) . "...</li>";
echo "<li><strong>From Email:</strong> " . SENDGRID_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . SENDGRID_FROM_NAME . "</li>";
echo "</ul>";

echo "<p><a href='checkout.php'>← Back to Checkout</a></p>";
?>
