<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Email Test</h1>";
echo "<pre>";

// Check if mail function exists
echo "mail() function exists: " . (function_exists('mail') ? 'YES' : 'NO') . "\n\n";

if (!function_exists('mail')) {
    echo "ERROR: mail() function is disabled on this server!\n";
    echo "InfinityFree has disabled PHP mail() function.\n\n";
    echo "SOLUTION: You need to use a third-party email service like:\n";
    echo "- SendGrid (free tier available)\n";
    echo "- Mailgun (free tier available)\n";
    echo "- Elastic Email\n";
    exit;
}

// Try to send a simple email
$to = 'vrajgandhi06@gmail.com';
$subject = 'Test from ProStock';
$message = 'This is a test email from your ProStock system.';
$headers = 'From: noreply@prostock.free.nf';

echo "Attempting to send email...\n";
echo "To: $to\n";
echo "From: noreply@prostock.free.nf\n\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✅ SUCCESS: mail() returned TRUE\n";
    echo "Email was accepted by the server.\n";
    echo "Check your inbox and SPAM folder at $to\n";
} else {
    echo "❌ FAILED: mail() returned FALSE\n";
    echo "The server rejected the email.\n";
}

echo "\n";
echo "Server: " . $_SERVER['SERVER_NAME'] . "\n";
echo "PHP Version: " . phpversion() . "\n";

echo "</pre>";
?>
