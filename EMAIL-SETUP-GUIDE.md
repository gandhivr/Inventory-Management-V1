# Email Notification Setup Guide

## Overview
The system now sends automatic email notifications when a buyer purchases items:
- **Buyer** receives order confirmation
- **Admin** receives notification of new order
- **Supplier(s)** receive notifications for their products in the order

## Configuration Steps

### 1. Update Email Settings
Edit `config/email.php` and update these constants with your email credentials:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your email
define('SMTP_PASSWORD', 'your-app-password'); // Your app password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // Your email
define('SMTP_FROM_NAME', 'Inventory Management System');
```

### 2. Gmail Setup (If using Gmail)

#### Enable 2-Factor Authentication
1. Go to your Google Account settings
2. Navigate to Security
3. Enable 2-Step Verification

#### Generate App Password
1. Go to Google Account > Security > 2-Step Verification
2. Scroll down to "App passwords"
3. Select "Mail" and your device
4. Copy the 16-character password
5. Use this password in `SMTP_PASSWORD` (without spaces)

### 3. Alternative: Using PHP mail() Function
The current implementation uses PHP's built-in `mail()` function which works on most servers. If you need more reliable email delivery, consider:

#### Option A: Install PHPMailer (Recommended for Production)
```bash
composer require phpmailer/phpmailer
```

Then update the `sendEmail()` function in `config/email.php` to use PHPMailer.

#### Option B: Use a Third-Party Email Service
- SendGrid
- Mailgun
- Amazon SES
- Postmark

### 4. Test Email Functionality

#### Test on Localhost
For local testing, you can use:
- **Fake SMTP servers**: MailHog, Mailtrap
- **Gmail SMTP**: Follow Gmail setup above

#### Test on Production Server
Ensure your server has:
- PHP `mail()` function enabled
- Proper SMTP configuration
- Firewall allows outbound email (port 25, 587, or 465)

### 5. Verify Email Addresses

Make sure all users have valid email addresses in the database:

```sql
-- Check admin email
SELECT email FROM users WHERE role = 'admin';

-- Check supplier emails
SELECT id, name, email FROM users WHERE role = 'supplier';

-- Check buyer emails
SELECT id, name, email FROM users WHERE role = 'buyer';
```

Update missing emails:
```sql
UPDATE users SET email = 'admin@example.com' WHERE role = 'admin' AND id = 1;
```

## Email Templates

### Buyer Confirmation Email
- Order ID and date
- Complete list of items ordered
- Total amount
- Order status

### Admin Notification Email
- Order ID and date
- Buyer information (name and email)
- Complete list of items
- Total amount

### Supplier Notification Email
- Order ID and date
- Buyer name
- Only items from that supplier
- Subtotal for supplier's products

## Troubleshooting

### Emails Not Sending
1. Check PHP error logs: `error_log` in your PHP configuration
2. Verify email credentials are correct
3. Check spam/junk folders
4. Ensure server allows outbound email
5. Test with a simple PHP mail script

### Gmail "Less Secure Apps" Error
- Use App Passwords instead (see Gmail Setup above)
- Don't use your regular Gmail password

### Emails Going to Spam
- Configure SPF, DKIM, and DMARC records for your domain
- Use a verified sender email address
- Consider using a dedicated email service

## Testing the System

1. **Create a test order**:
   - Log in as a buyer
   - Add products to cart
   - Complete checkout

2. **Check email delivery**:
   - Buyer should receive confirmation
   - Admin should receive notification
   - Supplier(s) should receive notification

3. **Verify email content**:
   - All order details are correct
   - Links work properly
   - Formatting displays correctly

## Production Recommendations

1. **Use PHPMailer or similar library** for better reliability
2. **Implement email queue** for high-volume orders
3. **Add retry logic** for failed emails
4. **Log all email attempts** for debugging
5. **Use environment variables** for sensitive credentials
6. **Set up monitoring** for email delivery failures

## Security Notes

- Never commit email credentials to version control
- Use environment variables or secure config files
- Restrict access to `config/email.php`
- Use app-specific passwords, not main account passwords
- Regularly rotate email credentials

## Future Enhancements

- Add email templates for order status updates
- Send shipping notifications
- Add unsubscribe functionality
- Support multiple languages
- Add email preferences for users
- Implement email verification for new users
