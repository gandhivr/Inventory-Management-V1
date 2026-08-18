<?php
/**
 * SMTP Email Configuration for Production (InfinityFree, etc.)
 * This version uses SMTP instead of PHP mail() function
 * 
 * SETUP INSTRUCTIONS:
 * 1. Update the email credentials below
 * 2. Rename this file to email.php (backup the old one first)
 * 3. Install PHPMailer or use this simple SMTP implementation
 */

// Email configuration - UPDATE THESE!
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'vrajgandhi06@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'xagbrnmdobquhcix'); // Gmail App Password (NOT your regular password)
define('SMTP_FROM_EMAIL', 'vrajgandhi06@gmail.com');
define('SMTP_FROM_NAME', 'Inventory Management System');

/**
 * Send email using SMTP
 * This works on InfinityFree and other hosts that block PHP mail()
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Check if we're on localhost - log instead of sending
    if (isset($_SERVER['SERVER_NAME']) && 
        ($_SERVER['SERVER_NAME'] === 'localhost' || 
         (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] === '127.0.0.1'))) {
        
        // Create logs directory if it doesn't exist
        $log_dir = __DIR__ . '/../logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        // Log email to file with clean text content
        $log_file = $log_dir . '/emails.log';
        
        // Remove style tags and their content
        $clean_message = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $message);
        // Remove all HTML tags
        $clean_message = strip_tags($clean_message);
        // Remove extra whitespace and blank lines
        $clean_message = preg_replace('/\n\s*\n\s*\n/', "\n\n", $clean_message);
        $clean_message = trim($clean_message);
        
        $log_content = "\n" . str_repeat("=", 80) . "\n";
        $log_content .= "📧 EMAIL NOTIFICATION\n";
        $log_content .= str_repeat("=", 80) . "\n";
        $log_content .= "Sent at: " . date('F j, Y g:i A') . "\n";
        $log_content .= "To: " . $to . "\n";
        $log_content .= "Subject: " . $subject . "\n";
        $log_content .= str_repeat("-", 80) . "\n\n";
        $log_content .= $clean_message . "\n\n";
        $log_content .= str_repeat("=", 80) . "\n";
        
        file_put_contents($log_file, $log_content, FILE_APPEND);
        
        return true;
    }
    
    // For production - send actual email via SMTP
    try {
        // Create email headers
        $email_headers = "MIME-Version: 1.0\r\n";
        $email_headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $email_headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $email_headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        
        // Try to send using PHP mail() first (works on some hosts)
        $sent = @mail($to, $subject, $message, $email_headers);
        
        if ($sent) {
            return true;
        }
        
        // If mail() fails, try SMTP socket connection
        return sendViaSMTP($to, $subject, $message);
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email via SMTP socket connection
 * Fallback method when mail() doesn't work
 */
function sendViaSMTP($to, $subject, $message) {
    try {
        // Connect to SMTP server
        $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
        
        if (!$smtp) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read server response
        $response = fgets($smtp, 515);
        
        // Send EHLO command
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($smtp, 515);
        
        // Start TLS if using port 587
        if (SMTP_PORT == 587) {
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
        }
        
        // Authenticate
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
        $response = fgets($smtp, 515);
        
        // Send email
        fputs($smtp, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        
        // Email headers and body
        $email_data = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $email_data .= "To: <" . $to . ">\r\n";
        $email_data .= "Subject: " . $subject . "\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $message . "\r\n";
        $email_data .= ".\r\n";
        
        fputs($smtp, $email_data);
        $response = fgets($smtp, 515);
        
        // Close connection
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order notification to admin
 */
function sendOrderNotificationToAdmin($order_id, $order_details) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin || empty($admin['email'])) {
            return false;
        }
        
        $subject = "New Order Received - Order #" . $order_id;
        
        $items_html = '';
        foreach ($order_details['items'] as $item) {
            $items_html .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($item['price'], 2) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($item['price'] * $item['quantity'], 2) . "</td>
                </tr>
            ";
        }
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .order-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #10b981; color: white; padding: 10px; text-align: left; }
                .total { font-size: 18px; font-weight: bold; color: #10b981; text-align: right; padding: 15px 0; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Order Received</h1>
                </div>
                <div class='content'>
                    <p>Hello Admin,</p>
                    <p>A new order has been placed in the system.</p>
                    
                    <div class='order-info'>
                        <h3>Order Details</h3>
                        <p><strong>Order ID:</strong> #{$order_id}</p>
                        <p><strong>Buyer:</strong> {$order_details['buyer_name']}</p>
                        <p><strong>Buyer Email:</strong> {$order_details['buyer_email']}</p>
                        <p><strong>Order Date:</strong> " . date('F j, Y g:i A') . "</p>
                    </div>
                    
                    <h3>Order Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$items_html}
                        </tbody>
                    </table>
                    
                    <div class='total'>
                        Total Amount: ₹" . number_format($order_details['total_amount'], 2) . "
                    </div>
                    
                    <p>Please log in to the admin dashboard to view more details and manage this order.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Inventory Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($admin['email'], $subject, $message);
        
    } catch (Exception $e) {
        error_log("Error sending admin notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order notification to supplier
 */
function sendOrderNotificationToSupplier($order_id, $supplier_id, $supplier_items, $order_details) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ? AND role = 'supplier'");
        $stmt->execute([$supplier_id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$supplier || empty($supplier['email'])) {
            return false;
        }
        
        $subject = "New Order for Your Products - Order #" . $order_id;
        
        $items_html = '';
        $supplier_total = 0;
        foreach ($supplier_items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $supplier_total += $subtotal;
            $items_html .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($item['price'], 2) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($subtotal, 2) . "</td>
                </tr>
            ";
        }
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .order-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #3b82f6; color: white; padding: 10px; text-align: left; }
                .total { font-size: 18px; font-weight: bold; color: #3b82f6; text-align: right; padding: 15px 0; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Order for Your Products</h1>
                </div>
                <div class='content'>
                    <p>Hello {$supplier['name']},</p>
                    <p>Great news! A customer has ordered your products.</p>
                    
                    <div class='order-info'>
                        <h3>Order Details</h3>
                        <p><strong>Order ID:</strong> #{$order_id}</p>
                        <p><strong>Buyer:</strong> {$order_details['buyer_name']}</p>
                        <p><strong>Order Date:</strong> " . date('F j, Y g:i A') . "</p>
                    </div>
                    
                    <h3>Your Products in This Order</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$items_html}
                        </tbody>
                    </table>
                    
                    <div class='total'>
                        Your Products Total: ₹" . number_format($supplier_total, 2) . "
                    </div>
                    
                    <p>Please log in to your supplier dashboard to view more details and prepare the products for shipment.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Inventory Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($supplier['email'], $subject, $message);
        
    } catch (Exception $e) {
        error_log("Error sending supplier notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order confirmation to buyer
 */
function sendOrderConfirmationToBuyer($order_id, $order_details) {
    if (empty($order_details['buyer_email'])) {
        return false;
    }
    
    $subject = "Order Confirmation - Order #" . $order_id;
    
    $items_html = '';
    foreach ($order_details['items'] as $item) {
        $items_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($item['price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($item['price'] * $item['quantity'], 2) . "</td>
            </tr>
        ";
    }
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .order-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background: #10b981; color: white; padding: 10px; text-align: left; }
            .total { font-size: 18px; font-weight: bold; color: #10b981; text-align: right; padding: 15px 0; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Thank You for Your Order!</h1>
            </div>
            <div class='content'>
                <p>Hello {$order_details['buyer_name']},</p>
                <p>Your order has been successfully placed and is being processed.</p>
                
                <div class='order-info'>
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #{$order_id}</p>
                    <p><strong>Order Date:</strong> " . date('F j, Y g:i A') . "</p>
                    <p><strong>Status:</strong> Completed</p>
                </div>
                
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                    </tbody>
                </table>
                
                <div class='total'>
                    Total Amount: ₹" . number_format($order_details['total_amount'], 2) . "
                </div>
                
                <p>You can track your order status by logging into your account.</p>
                <p>Thank you for shopping with us!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Inventory Management System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($order_details['buyer_email'], $subject, $message);
}
?>
