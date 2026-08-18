<?php
/**
 * SendGrid Email Configuration
 * Works with InfinityFree and other free hosting providers
 * 
 * SETUP INSTRUCTIONS:
 * 1. Sign up at https://sendgrid.com (free - 100 emails/day)
 * 2. Create an API key: Settings > API Keys > Create API Key
 * 3. Copy the API key and paste it below
 * 4. Verify your sender email in SendGrid
 */

// SendGrid Configuration
define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY') ?: 'YOUR_SENDGRID_API_KEY_HERE'); // Get from SendGrid dashboard
define('SENDGRID_FROM_EMAIL', 'vrajgandhi06@gmail.com'); // Must be verified in SendGrid
define('SENDGRID_FROM_NAME', 'ProStock');
define('SENDGRID_REPLY_TO', 'vrajgandhi06@gmail.com');

/**
 * Send email using SendGrid API
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Send via SendGrid API (works on localhost and production)
    try {
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
            'reply_to' => [
                'email' => SENDGRID_REPLY_TO
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
        
        // Check response
        if ($http_code >= 200 && $http_code < 300) {
            error_log("SendGrid: Email sent successfully to $to");
            return true;
        } else {
            error_log("SendGrid Error: HTTP $http_code - $response - $curl_error");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("SendGrid Exception: " . $e->getMessage());
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
