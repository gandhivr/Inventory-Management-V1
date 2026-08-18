-- =====================================================
-- CLEAR ALL DATABASE RECORDS - INVENTORY MANAGEMENT
-- =====================================================
-- WARNING: This script will permanently delete ALL user and product data
-- Make sure to backup your database before running this script
-- =====================================================

-- Disable foreign key checks temporarily to avoid constraint errors
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all data in the correct order (child tables first, then parent tables)

-- 1. Clear cart items (references users and products)
DELETE FROM cart;
ALTER TABLE cart AUTO_INCREMENT = 1;

-- 2. Clear order items (references orders and products)
DELETE FROM order_items;
ALTER TABLE order_items AUTO_INCREMENT = 1;

-- 3. Clear orders (references users)
DELETE FROM orders;
ALTER TABLE orders AUTO_INCREMENT = 1;

-- 4. Clear buyer profiles (references users)
DELETE FROM buyer_profile;
ALTER TABLE buyer_profile AUTO_INCREMENT = 1;

-- 5. Clear supplier profiles (references users)
DELETE FROM supplier_profile;
ALTER TABLE supplier_profile AUTO_INCREMENT = 1;

-- 6. Clear all products
DELETE FROM products;
ALTER TABLE products AUTO_INCREMENT = 1;

-- 7. Clear all users
DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;

-- Optional: Clear categories if you want to start fresh with categories too
-- Uncomment the lines below if you want to clear categories as well
-- DELETE FROM categories;
-- ALTER TABLE categories AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Run these queries after the script to verify all data is cleared:

-- Check remaining records in each table:
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'products' as table_name, COUNT(*) as record_count FROM products
UNION ALL
SELECT 'orders' as table_name, COUNT(*) as record_count FROM orders
UNION ALL
SELECT 'order_items' as table_name, COUNT(*) as record_count FROM order_items
UNION ALL
SELECT 'cart' as table_name, COUNT(*) as record_count FROM cart
UNION ALL
SELECT 'buyer_profile' as table_name, COUNT(*) as record_count FROM buyer_profile
UNION ALL
SELECT 'supplier_profile' as table_name, COUNT(*) as record_count FROM supplier_profile
UNION ALL
SELECT 'categories' as table_name, COUNT(*) as record_count FROM categories;

-- =====================================================
-- NOTES:
-- =====================================================
-- 1. This script preserves the table structure and only clears data
-- 2. Auto-increment counters are reset to 1 for all tables
-- 3. Categories table is preserved by default (uncomment to clear)
-- 4. Foreign key constraints are temporarily disabled during deletion
-- 5. All related data (profiles, orders, cart items) are cleared automatically
-- 6. The verification query at the end shows remaining record counts
-- =====================================================