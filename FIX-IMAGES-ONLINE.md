# Fix Images Not Showing on Online Hosting

## Problem
Product images show "No Image" placeholder on your online site but worked fine on localhost.

## Why This Happens
1. **Images not uploaded** - The `uploads/products/` folder wasn't uploaded to the server
2. **Wrong file permissions** - Server can't read the image files
3. **Incorrect paths in database** - Database has localhost paths instead of relative paths
4. **Case sensitivity** - Linux servers are case-sensitive (image.JPG ≠ image.jpg)

## Quick Fix (3 Steps)

### Step 1: Upload Images Folder

**Via FTP (FileZilla, etc.):**
1. Connect to your hosting via FTP
2. Navigate to `public_html` or `htdocs` folder
3. Upload the entire `uploads` folder from your localhost
4. Make sure the structure is: `public_html/uploads/products/`

**Via File Manager (cPanel/InfinityFree):**
1. Log into your hosting control panel
2. Open File Manager
3. Navigate to `public_html` or `htdocs`
4. Upload the `uploads` folder
5. Extract if it's a ZIP file

### Step 2: Set File Permissions

**For InfinityFree/cPanel:**
1. Right-click on `uploads` folder
2. Select "Change Permissions" or "CHMOD"
3. Set folder permissions to **755** or **777**
4. Check "Apply to subdirectories"
5. Set file permissions to **644** or **666**

**Permission Numbers:**
- **755** = Owner can read/write/execute, others can read/execute
- **777** = Everyone can read/write/execute (use if 755 doesn't work)
- **644** = Owner can read/write, others can read
- **666** = Everyone can read/write

### Step 3: Run the Fix Script

1. Upload `fix-image-paths.php` to your site root
2. Visit: `https://yoursite.com/fix-image-paths.php`
3. Log in as admin
4. Click "Scan & Fix Image Paths"
5. Review the results
6. **Delete the file after use!**

## Manual Verification

### Check if Images Exist
Visit in browser:
```
https://yoursite.com/uploads/products/
```

You should see a list of image files. If you get "403 Forbidden" or "404 Not Found", images weren't uploaded.

### Check Specific Image
If database shows: `uploads/products/abc123.jpg`

Try accessing:
```
https://yoursite.com/uploads/products/abc123.jpg
```

If image doesn't load:
- ✅ File exists? Check via FTP/File Manager
- ✅ Correct filename? Check spelling and case
- ✅ Correct permissions? Should be 644 or 666
- ✅ Correct path? Should be relative, not absolute

## Common Issues & Solutions

### Issue 1: "No Image" Placeholder
**Cause:** Images not uploaded or wrong path
**Solution:**
1. Upload `uploads/products/` folder
2. Run `fix-image-paths.php`
3. Check file permissions

### Issue 2: 403 Forbidden Error
**Cause:** Wrong folder permissions
**Solution:**
1. Set folder permissions to 755 or 777
2. Set file permissions to 644 or 666

### Issue 3: Some Images Work, Others Don't
**Cause:** Case sensitivity or missing files
**Solution:**
1. Check filename case (Linux is case-sensitive)
2. Re-upload missing images
3. Run fix script

### Issue 4: Images Work on Homepage but Not Admin Panel
**Cause:** Different path handling
**Solution:**
1. Ensure all paths are relative: `uploads/products/file.jpg`
2. NOT absolute: `/var/www/uploads/...` or `C:\xampp\...`
3. Run fix script to normalize paths

## InfinityFree Specific Notes

### File Upload Limits
- Max file size: Usually 10MB per file
- Upload via File Manager or FTP
- For large folders, upload as ZIP and extract

### Directory Structure
```
public_html/
├── index.php
├── config/
├── uploads/
│   └── products/
│       ├── image1.jpg
│       ├── image2.png
│       └── ...
└── ...
```

### .htaccess for Images (Optional)
Create `.htaccess` in `uploads/products/`:
```apache
# Allow image access
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Prevent directory listing
Options -Indexes
```

## Testing Checklist

After fixing, test these:

- [ ] Can you see product images on homepage?
- [ ] Can you see product images in product list?
- [ ] Can you see product images in admin panel?
- [ ] Can you see product images in cart?
- [ ] Can you see product images in orders?
- [ ] Can supplier upload new images?
- [ ] Can admin edit product images?

## Prevention for Future

### When Adding New Products
1. Always use relative paths
2. Upload images through the web interface
3. Don't manually edit database paths
4. Keep backups of uploads folder

### When Moving Sites
1. Always upload `uploads` folder
2. Set correct permissions immediately
3. Run fix script after upload
4. Test image display before going live

## Alternative: Re-upload All Images

If fix script doesn't work:

1. Log in as supplier/admin
2. Go to each product
3. Click "Edit"
4. Re-upload the image
5. Save

This ensures correct paths and permissions.

## Need More Help?

### Check These:
1. Browser console (F12) for 404 errors
2. Hosting error logs
3. File Manager to verify uploads exist
4. Database to check image paths

### Common Error Messages:
- **404 Not Found** = File doesn't exist or wrong path
- **403 Forbidden** = Permission issue
- **500 Internal Server Error** = Server configuration issue

### Contact Support If:
- Images still don't show after all fixes
- Can't set file permissions
- Can't access uploads folder
- Getting server errors

## Quick Reference

| Problem | Solution |
|---------|----------|
| No images show | Upload uploads folder + set permissions |
| Some images show | Run fix script + check missing files |
| 403 Forbidden | Set folder to 755/777, files to 644/666 |
| 404 Not Found | Check if files exist, verify paths |
| Works locally, not online | Upload uploads folder, run fix script |

## Files to Use

1. **fix-image-paths.php** - Automatic path fixer (delete after use)
2. **debug-images.php** - Check image status (already exists)
3. **manage-products.php** - Re-upload images manually

---

**Remember:** Always backup your database and files before making changes!
