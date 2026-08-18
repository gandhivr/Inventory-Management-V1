<?php
/**
 * Email Log Viewer - For Development/Testing Only
 * Shows emails that were "sent" on localhost
 */

// Security: Only allow access from localhost
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access denied. This script can only be run from localhost.');
}

$log_file = __DIR__ . '/logs/emails.log';
$emails_exist = file_exists($log_file);
$email_content = $emails_exist ? file_get_contents($log_file) : '';

// Clear logs if requested
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    if ($emails_exist) {
        unlink($log_file);
    }
    header('Location: view-emails.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .email-log { background: #f8f9fa; padding: 20px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; max-height: 600px; overflow-y: auto; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">📧 Email Log Viewer</h1>
        
        <div class="warning">
            <strong>⚠️ Development Tool:</strong> This shows emails logged on localhost. Delete this file in production!
        </div>

        <?php if ($emails_exist && !empty($email_content)): ?>
            <div class="info">
                <strong>ℹ️ Info:</strong> Emails are being logged to <code>logs/emails.log</code> instead of being sent (localhost mode).
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Logged Emails:</h5>
                <div>
                    <a href="view-emails.php" class="btn btn-sm btn-primary">Refresh</a>
                    <a href="view-emails.php?clear=1" class="btn btn-sm btn-danger" onclick="return confirm('Clear all email logs?')">Clear Logs</a>
                </div>
            </div>

            <div class="email-log"><?php 
                // Show newest emails first by reversing the content
                $emails = explode(str_repeat("=", 80), $email_content);
                $emails = array_filter($emails, function($email) {
                    return trim($email) !== '';
                });
                $emails = array_reverse($emails);
                
                foreach ($emails as $email) {
                    echo htmlspecialchars(str_repeat("=", 80) . "\n" . trim($email) . "\n");
                }
            ?></div>

        <?php else: ?>
            <div class="alert alert-info">
                <strong>No emails logged yet.</strong><br>
                Make a test purchase to see emails appear here.
            </div>

            <div class="mt-4">
                <h5>How to Test:</h5>
                <ol>
                    <li>Log in as a buyer</li>
                    <li>Add products to cart</li>
                    <li>Complete checkout</li>
                    <li>Come back here to see the logged emails</li>
                </ol>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <h5>Quick Links:</h5>
            <a href="test-email.php" class="btn btn-sm btn-secondary">Test Email Configuration</a>
            <a href="index.php" class="btn btn-sm btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>
