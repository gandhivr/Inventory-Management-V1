# Setup Email in 3 Minutes ⚡

## Your emails aren't sending because you need to configure Gmail SMTP!

### Step 1: Get Gmail App Password (2 minutes)

1. **Go to:** https://myaccount.google.com/security
2. **Enable "2-Step Verification"** (if not already on)
3. **Search for:** "App passwords"
4. **Click:** "App passwords"
5. **Select:** "Mail" and your device
6. **Copy the 16-character password** (looks like: `abcd efgh ijkl mnop`)

### Step 2: Update config/email.php (1 minute)

Open `config/email.php` and update **line 13**:

```php
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // Paste your app password here (remove spaces)
```

**Current settings:**
- Email: `vrajgandhi06@gmail.com` ✅ (already set)
- Password: `your-app-password-here` ❌ (YOU NEED TO UPDATE THIS!)

### Step 3: Test It!

1. Make a test purchase on your site
2. Check your email inbox (buyer, admin, supplier)
3. Check spam folder if not in inbox

## That's It! 🎉

Once you update the password, emails will be sent automatically to:
- ✅ Buyer (order confirmation)
- ✅ Admin (new order notification)  
- ✅ Supplier (product order notification)

## Troubleshooting

### Emails still not sending?

1. **Check spam folder** - Gmail might filter them
2. **Verify app password** - Make sure you copied it correctly (no spaces)
3. **Check error log** - Look in `logs/email_errors.log`
4. **Test Gmail login** - Make sure 2-Step Verification is enabled

### Can't find App Passwords?

- You MUST enable 2-Step Verification first
- Then search "App passwords" in Google Account settings
- If still not found, your account might not support it (try different Google account)

### Alternative: Use Different Email Service

If Gmail doesn't work, you can use:
- **SendGrid** (100 free emails/day)
- **Mailgun** (Free tier available)
- **Amazon SES** (Very cheap)

## Current Status

✅ Email code is installed and working
✅ Email address is configured (`vrajgandhi06@gmail.com`)
❌ **App password is NOT configured** ← YOU NEED TO FIX THIS!

## What Happens Now

**On localhost:**
- Emails are logged to `logs/emails.log` ✅

**On your online site:**
- Emails will try to send via Gmail SMTP
- **Will FAIL until you add the app password** ❌
- After adding password: Will send to real email addresses ✅

---

**Just update that ONE line with your Gmail app password and you're done!** 🚀
