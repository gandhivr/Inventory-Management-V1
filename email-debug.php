<?php
/**
 * Email Debug Script - Shows detailed error messages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Debug Test</h1>";
echo "<pre>";

// Check if config files exist
echo "=== Checking Files ===\n";
echo "email-smtp.php exists: " . (file_exists('config/email-smtp.php') ? 'YES' : 'NO') . "\n\n";

// Load config - define constants manually to avoid database connection
if (file_exists('config/email-smtp.php')) {
    $config_content = file_get_contents('config/email-smtp.php');
    
    // Extract SMTP settings
    preg_match("/define\('SMTP_HOST',\s*'([^']+)'\)/", $config_content, $host);
    preg_match("/define\('SMTP_PORT',\s*(\d+)\)/", $config_content, $port);
    preg_match("/define\('SMTP_USERNAME',\s*'([^']+)'\)/", $config_content, $username);
    preg_match("/define\('SMTP_PASSWORD',\s*'([^']+)'\)/", $config_content, $password);
    preg_match("/define\('SMTP_FROM_EMAIL',\s*'([^']+)'\)/", $config_content, $from);
    
    define('SMTP_HOST', $host[1] ?? 'smtp.gmail.com');
    define('SMTP_PORT', $port[1] ?? 587);
    define('SMTP_USERNAME', $username[1] ?? '');
    define('SMTP_FROM_EMAIL', $from[1] ?? '');
    define('SMTP_PASSWORD', $password[1] ?? '');
} else {
    die("Config file not found!");
}

echo "=== Configuration ===\n";
echo "SMTP_HOST: " . SMTP_HOST . "\n";
echo "SMTP_PORT: " . SMTP_PORT . "\n";
echo "SMTP_USERNAME: " . SMTP_USERNAME . "\n";
echo "SMTP_PASSWORD: " . (strlen(SMTP_PASSWORD) > 0 ? str_repeat('*', strlen(SMTP_PASSWORD)) : 'EMPTY!') . "\n";
echo "SMTP_FROM_EMAIL: " . SMTP_FROM_EMAIL . "\n\n";

// Check PHP functions
echo "=== PHP Functions ===\n";
echo "fsockopen available: " . (function_exists('fsockopen') ? 'YES' : 'NO - REQUIRED!') . "\n";
echo "mail() available: " . (function_exists('mail') ? 'YES' : 'NO') . "\n";
echo "stream_socket_enable_crypto available: " . (function_exists('stream_socket_enable_crypto') ? 'YES' : 'NO - REQUIRED!') . "\n\n";

// Test SMTP connection
echo "=== Testing SMTP Connection ===\n";
$smtp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);

if (!$smtp) {
    echo "❌ FAILED to connect to SMTP server!\n";
    echo "Error: $errstr ($errno)\n";
    echo "\nPossible reasons:\n";
    echo "- Server firewall blocking port 587\n";
    echo "- fsockopen disabled by hosting provider\n";
    echo "- SMTP host is incorrect\n\n";
} else {
    echo "✅ Connected to SMTP server successfully!\n";
    $response = fgets($smtp, 515);
    echo "Server response: $response\n";
    
    // Try EHLO
    fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($smtp, 515);
    echo "EHLO response: $response\n";
    
    // Try STARTTLS
    echo "\nTesting STARTTLS...\n";
    fputs($smtp, "STARTTLS\r\n");
    $response = fgets($smtp, 515);
    echo "STARTTLS response: $response\n";
    
    if (strpos($response, '220') !== false) {
        echo "✅ STARTTLS supported\n";
        
        // Try to enable TLS
        $crypto = @stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($crypto) {
            echo "✅ TLS encryption enabled\n";
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
            echo "EHLO after TLS: $response\n";
            
            // Try AUTH LOGIN
            echo "\nTesting authentication...\n";
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            echo "AUTH LOGIN response: $response\n";
            
            if (strpos($response, '334') !== false) {
                // Send username
                fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
                $response = fgets($smtp, 515);
                echo "Username response: $response\n";
                
                // Send password
                fputs($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
                $response = fgets($smtp, 515);
                echo "Password response: $response\n";
                
                if (strpos($response, '235') !== false) {
                    echo "✅ Authentication SUCCESSFUL!\n";
                    echo "\n=== RESULT ===\n";
                    echo "Your SMTP configuration is CORRECT!\n";
                    echo "Emails should be working.\n";
                } else {
                    echo "❌ Authentication FAILED!\n";
                    echo "\nPossible reasons:\n";
                    echo "- Wrong Gmail app password\n";
                    echo "- App password expired or revoked\n";
                    echo "- Wrong email address\n";
                }
            }
        } else {
            echo "❌ Failed to enable TLS encryption\n";
            echo "Your server may not support TLS\n";
        }
    } else {
        echo "❌ STARTTLS not supported or failed\n";
    }
    
    fclose($smtp);
}

echo "\n=== Server Information ===\n";
echo "Server: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='test-email-production.php'>Go back to email test</a></li>";
echo "<li><a href='https://myaccount.google.com/apppasswords' target='_blank'>Generate new Gmail App Password</a></li>";
echo "<li><a href='https://myaccount.google.com/security' target='_blank'>Check Gmail Security Settings</a></li>";
echo "</ul>";
?>
