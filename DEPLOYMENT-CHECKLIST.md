# Deployment Checklist for InfinityFree

## Before Uploading

### ✅ Email Configuration

- [ ] Get Gmail App Password
- [ ] Backup `config/email.php`
- [ ] Rename `config/email-smtp.php` to `config/email.php`
- [ ] Update email credentials in `config/email.php`:
  - SMTP_USERNAME
  - SMTP_PASSWORD
  - SMTP_FROM_EMAIL

### ✅ Database Configuration

- [ ] Create MySQL database on InfinityFree
- [ ] Note database name, username, password
- [ ] Update `config/database.php` with InfinityFree credentials
- [ ] Import your database SQL file

### ✅ Security

- [ ] Delete or restrict these test files:
  - `test-email.php`
  - `view-emails.php`
  - `debug-images.php`
  - Any other test files

### ✅ File Permissions

- [ ] Ensure `uploads/` folder is writable (755 or 777)
- [ ] Ensure `logs/` folder is writable (755 or 777)

## After Uploading

### ✅ Test Basic Functionality

- [ ] Can you access the homepage?
- [ ] Can you log in as admin?
- [ ] Can you log in as supplier?
- [ ] Can you log in as buyer?

### ✅ Test Email System

- [ ] Make a test purchase
- [ ] Check buyer email inbox
- [ ] Check admin email inbox
- [ ] Check supplier email inbox
- [ ] Check spam folders

### ✅ Test Product Features

- [ ] Can supplier add products?
- [ ] Can buyer view products?
- [ ] Can buyer add to cart?
- [ ] Can buyer checkout?
- [ ] Do product images display?

### ✅ Test Admin Features

- [ ] Can admin view all orders?
- [ ] Can admin manage users?
- [ ] Can admin view reports?
- [ ] Can admin export data?

## Email Setup on InfinityFree

### What Works on InfinityFree:
✅ SMTP email sending (with proper configuration)
✅ Gmail SMTP (with app password)
✅ Third-party email services (SendGrid, Mailgun)

### What Doesn't Work:
❌ PHP mail() function (blocked by InfinityFree)
❌ Localhost email logging (only works locally)

## When Emails Will Be Sent

After proper SMTP configuration, emails will be sent automatically when:

1. **Buyer completes purchase** → 3 emails sent:
   - ✉️ Buyer receives order confirmation
   - ✉️ Admin receives new order notification
   - ✉️ Supplier(s) receive product order notification

2. **All emails are HTML formatted** with:
   - Order details
   - Product list
   - Total amount
   - Professional styling

## Common Issues & Solutions

### Issue: Emails not arriving
**Solution:** 
- Check spam folder
- Verify Gmail app password
- Try sending test email from InfinityFree cPanel

### Issue: Database connection error
**Solution:**
- Update database credentials in `config/database.php`
- Use InfinityFree database hostname (not localhost)

### Issue: Images not displaying
**Solution:**
- Check file permissions on `uploads/` folder
- Verify image paths in database
- Re-upload images if needed

### Issue: Session errors
**Solution:**
- Check PHP version compatibility
- Ensure session directory is writable

## InfinityFree Specific Notes

1. **Free hosting limitations:**
   - Limited bandwidth
   - Limited storage
   - Email sending limits (hourly)
   - No SSH access

2. **Database:**
   - Use provided MySQL hostname
   - Usually format: `sqlXXX.infinityfree.com`

3. **File Manager:**
   - Upload to `htdocs` folder
   - Set proper permissions

4. **Email limits:**
   - Free tier may have hourly limits
   - Consider premium hosting for high volume

## Recommended Next Steps

After successful deployment:

1. **Monitor email delivery** - Check if all emails arrive
2. **Test all features** - Go through complete user flow
3. **Set up backups** - Regular database and file backups
4. **Monitor performance** - Check page load times
5. **Consider upgrade** - If traffic increases, upgrade hosting

## Support Resources

- InfinityFree Forum: https://forum.infinityfree.net/
- InfinityFree Knowledge Base: https://infinityfree.net/support/
- Gmail SMTP Guide: See `INFINITYFREE-EMAIL-SETUP.md`
