<?php
/**
 * Force SendGrid Test - Bypasses localhost check
 * This will actually send an email via SendGrid API
 */

// SendGrid Configuration
define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY') ?: 'YOUR_SENDGRID_API_KEY_HERE');
define('SENDGRID_FROM_EMAIL', 'vrajgandhi06@gmail.com');
define('SENDGRID_FROM_NAME', 'ProStock');

echo "<h2>Testing SendGrid Email (Force Send)...</h2>";

$to = 'vrajgandhi06@gmail.com';
$subject = 'SendGrid Test - ProStock (Forced)';
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
            <p>This email was sent directly via SendGrid API.</p>
        </div>
    </div>
</body>
</html>
';

echo "<p>Sending test email to: <strong>$to</strong></p>";

// Prepare SendGrid API request
$data = [
    'personalizations' => [
        [
            'to' => [
                ['email' => $to]
            ],
            'subject' => $subject
        ]
    ],
    'from' => [
        'email' => SENDGRID_FROM_EMAIL,
        'name' => SENDGRID_FROM_NAME
    ],
    'content' => [
        [
            'type' => 'text/html',
            'value' => $message
        ]
    ]
];

$json_data = json_encode($data);

// Initialize cURL
$ch = curl_init('https://api.sendgrid.com/v3/mail/send');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . SENDGRID_API_KEY,
    'Content-Type: application/json'
]);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

echo "<hr>";
echo "<h3>SendGrid API Response:</h3>";
echo "<p><strong>HTTP Code:</strong> $http_code</p>";

if ($http_code >= 200 && $http_code < 300) {
    echo "<div style='background: #10b981; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Success!</h3>";
    echo "<p>Email sent successfully via SendGrid API!</p>";
    echo "<p>Check your inbox: <strong>$to</strong></p>";
    echo "<p><strong>Note:</strong> It may take 10-60 seconds to arrive. Check spam folder if you don't see it.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #ef4444; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    echo "<p><strong>Response:</strong> $response</p>";
    echo "<p><strong>cURL Error:</strong> $curl_error</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Debug Info:</h3>";
echo "<ul>";
echo "<li><strong>API Key:</strong> " . substr(SENDGRID_API_KEY, 0, 20) . "...</li>";
echo "<li><strong>From Email:</strong> " . SENDGRID_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . SENDGRID_FROM_NAME . "</li>";
echo "<li><strong>To Email:</strong> " . $to . "</li>";
echo "</ul>";
?>
