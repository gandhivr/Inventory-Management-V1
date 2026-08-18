# Quick Email Setup - 3 Steps

## Step 1: Configure Email Settings (2 minutes)

Open `config/email.php` and update these lines:

```php
define('SMTP_USERNAME', 'your-email@gmail.com');     // Line 10
define('SMTP_PASSWORD', 'your-app-password');        // Line 11
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');   // Line 12
```

## Step 2: Get Gmail App Password (3 minutes)

1. Go to: https://myaccount.google.com/security
2. Enable "2-Step Verification" (if not already enabled)
3. Search for "App passwords" 
4. Create new app password for "Mail"
5. Copy the 16-character password (remove spaces)
6. Paste it in `SMTP_PASSWORD` above

## Step 3: Test It! (1 minute)

1. Open in browser: `http://localhost/your-project/test-email.php`
2. Enter your email address
3. Click "Send Test Email"
4. Check your inbox (and spam folder)

## That's It! 🎉

When a buyer purchases items, emails will automatically be sent to:
- ✅ Buyer (order confirmation)
- ✅ Admin (new order notification)
- ✅ Supplier(s) (product order notification)

## Important Notes

- Make sure all users have email addresses in the database
- Delete `test-email.php` after testing (security)
- Check spam folder if emails don't arrive
- For production, consider using PHPMailer or email service (SendGrid, Mailgun)

## Need Help?

See `EMAIL-SETUP-GUIDE.md` for detailed instructions and troubleshooting.
