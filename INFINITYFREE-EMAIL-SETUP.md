# Email Setup for InfinityFree Hosting

## Important: InfinityFree Email Limitations

InfinityFree **blocks PHP mail() function** for security reasons. You need to use SMTP to send emails.

## Quick Setup (3 Steps)

### Step 1: Get Gmail App Password

1. Go to: https://myaccount.google.com/security
2. Enable **"2-Step Verification"** (if not enabled)
3. Search for **"App passwords"**
4. Create app password for "Mail"
5. Copy the 16-character password (remove spaces)

### Step 2: Update Email Configuration

**Option A: Replace the email.php file**

1. Backup your current `config/email.php`
2. Rename `config/email-smtp.php` to `config/email.php`
3. Edit the new `config/email.php` and update lines 10-13:

```php
define('SMTP_USERNAME', 'your-email@gmail.com');     // Your Gmail
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop');      // App Password (remove spaces)
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');   // Your Gmail
```

### Step 3: Upload to InfinityFree

Upload your files to InfinityFree and test!

## Testing on InfinityFree

1. Make a test purchase on your live site
2. Check your email inbox (buyer, admin, supplier)
3. Check spam folder if not in inbox

## Alternative: Use SendGrid (Free Tier)

SendGrid offers 100 free emails per day:

1. Sign up at: https://sendgrid.com/
2. Get API key
3. Use SendGrid's PHP library
4. More reliable than Gmail for production

## Troubleshooting

### Emails Not Arriving?

1. **Check spam folder** - First place to look
2. **Verify Gmail credentials** - Make sure app password is correct
3. **Check InfinityFree limits** - Free hosting has email limits
4. **Try different email service** - SendGrid, Mailgun, etc.

### Gmail Blocking Emails?

- Use App Password (not regular password)
- Enable "Less secure app access" (not recommended)
- Consider using SendGrid instead

### InfinityFree Specific Issues

- InfinityFree may have hourly email limits
- Some SMTP ports might be blocked
- Consider upgrading to premium hosting for better email support

## Production Recommendations

For a real business website:

1. **Use a paid email service** (SendGrid, Mailgun, Amazon SES)
2. **Upgrade hosting** - Consider paid hosting with email support
3. **Set up SPF/DKIM records** - Prevent emails going to spam
4. **Monitor email delivery** - Track bounces and failures

## Current Setup Summary

- **Localhost**: Emails logged to `logs/emails.log` (for testing)
- **Production**: Emails sent via SMTP to real addresses
- **Automatic detection**: Code detects localhost vs production

## Need Help?

If emails still don't work on InfinityFree:

1. Check InfinityFree forum for email issues
2. Contact InfinityFree support
3. Consider using a third-party email service
4. Upgrade to premium hosting with email support
