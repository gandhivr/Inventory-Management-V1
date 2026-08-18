# Fix: Manage Orders Link Issue

## Problem
The admin dashboard had a broken link to `manage-orders.php` which doesn't exist, causing a "Not Found" error when clicking "Review Orders" in the Pending Orders notification.

## Root Cause
The file is actually named `admin-orders.php`, but the link in the admin dashboard was pointing to `manage-orders.php`.

## Solution
Updated the link in `admin-dashboard.php` from:
```php
<a href="manage-orders.php" class="btn btn-sm btn-info mt-2">Review Orders</a>
```

To:
```php
<a href="admin-orders.php" class="btn btn-sm btn-info mt-2">Review Orders</a>
```

## File Updated
- **admin-dashboard.php** (line 512)

## Verification
✅ All other references to order management correctly use `admin-orders.php`
✅ No other broken links found
✅ File exists and is accessible

## Impact
- Pending Orders notification now works correctly
- Clicking "Review Orders" properly navigates to the orders management page
- No other functionality affected

## Related Files
- `admin-orders.php` - The actual orders management page
- `supplier-orders.php` - Supplier's orders page
- `buyer-orders.php` - Buyer's orders page

All order management pages are now properly linked and accessible.
