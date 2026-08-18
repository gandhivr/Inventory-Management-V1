# SendGrid Email Setup Guide

## Why SendGrid?
InfinityFree blocks both SMTP and PHP mail() functions. SendGrid provides a free API that works perfectly with free hosting.

## Step-by-Step Setup

### 1. Create SendGrid Account (FREE)
1. Go to https://sendgrid.com
2. Click "Start for Free"
3. Sign up with your email (vrajgandhi06@gmail.com)
4. Verify your email address
5. Complete the signup process

**Free Plan:** 100 emails per day (perfect for your project!)

### 2. Verify Your Sender Email
1. Log in to SendGrid dashboard
2. Go to **Settings** > **Sender Authentication**
3. Click **Verify a Single Sender**
4. Enter your details:
   - From Name: `Inventory Management System`
   - From Email: `vrajgandhi06@gmail.com`
   - Reply To: `vrajgandhi06@gmail.com`
5. Check your email and click the verification link
6. Wait for approval (usually instant)

### 3. Create API Key
1. In SendGrid dashboard, go to **Settings** > **API Keys**
2. Click **Create API Key**
3. Name it: `ProStock Inventory`
4. Choose **Full Access** (or at minimum: Mail Send)
5. Click **Create & View**
6. **COPY THE API KEY** (you won't see it again!)
   - It looks like: `SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### 4. Update Your Configuration
1. Open `config/email-sendgrid.php`
2. Find line 13: `define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY_HERE');`
3. Replace `YOUR_SENDGRID_API_KEY_HERE` with your actual API key
4. Save the file

Example:
```php
define('SENDGRID_API_KEY', 'SG.abc123xyz789...');
define('SENDGRID_FROM_EMAIL', 'vrajgandhi06@gmail.com');
define('SENDGRID_FROM_NAME', 'Inventory Management System');
```

### 5. Activate SendGrid Email System
**Option A: Rename files (Recommended)**
1. Rename `config/email.php` to `config/email-old-backup.php`
2. Rename `config/email-sendgrid.php` to `config/email.php`

**Option B: Update require statement**
In files that use email (like `checkout.php`), change:
```php
require_once 'config/email.php';
```
to:
```php
require_once 'config/email-sendgrid.php';
```

### 6. Test Your Setup
1. Upload `test-sendgrid.php` to your server
2. Open: `https://prostock.free.nf/test-sendgrid.php`
3. Click "Send Test Email"
4. Check your inbox (and spam folder!)

### 7. Upload to Server
Upload these files to your InfinityFree server:
- `config/email-sendgrid.php` (rename to `email.php`)
- `test-sendgrid.php` (for testing)

## Troubleshooting

### Email not arriving?
1. **Check spam folder** - SendGrid emails sometimes go to spam initially
2. **Verify sender email** - Make sure you verified your email in SendGrid
3. **Check API key** - Make sure you copied the full API key
4. **Check SendGrid dashboard** - Go to Activity to see if emails were sent

### API Key Error?
- Make sure there are no extra spaces in the API key
- API key should start with `SG.`
- Make sure you gave it "Mail Send" permission

### Still not working?
1. Check SendGrid Activity Feed: https://app.sendgrid.com/email_activity
2. Look for error messages
3. Make sure your account is verified
4. Check if you've exceeded the 100 emails/day limit

## Important Notes

✅ **Free Plan Limits:**
- 100 emails per day
- Perfect for testing and small projects
- No credit card required

✅ **Benefits:**
- Works with InfinityFree
- Professional email delivery
- Email tracking and analytics
- Better deliverability than free hosting

✅ **Security:**
- Never share your API key
- Keep it in config file (not in public code)
- Delete test files after testing

## Next Steps

After setup:
1. Test with `test-sendgrid.php`
2. Place a test order to verify order emails work
3. Check all three email types:
   - Buyer confirmation
   - Admin notification
   - Supplier notification
4. Delete test files for security
5. Monitor SendGrid dashboard for email activity

## Support

- SendGrid Docs: https://docs.sendgrid.com
- SendGrid Support: https://support.sendgrid.com
- Free plan includes email support

---

**Your Configuration:**
- Email: vrajgandhi06@gmail.com
- Server: prostock.free.nf
- Daily Limit: 100 emails
- Cost: FREE
