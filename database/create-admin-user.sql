-- =====================================================
-- CREATE MAIN ADMIN USER
-- =====================================================
-- This script creates the main admin user after database cleanup
-- Username: admin
-- Password: admin123
-- =====================================================

-- Insert the main admin user with properly hashed password
INSERT INTO users (
    name, 
    username, 
    email, 
    password, 
    role, 
    status, 
    created_at
) VALUES (
    'Main Administrator',
    'admin',
    'admin@inventory.com',
    '$2y$10$YourHashedPasswordHere',  -- Will be replaced with correct hash
    'admin',
    'active',
    NOW()
);

-- =====================================================
-- VERIFICATION
-- =====================================================
-- Check if admin user was created successfully
SELECT 
    id,
    name,
    username,
    email,
    role,
    status,
    created_at
FROM users 
WHERE role = 'admin';

-- =====================================================
-- LOGIN CREDENTIALS
-- =====================================================
-- Username: admin
-- Password: admin123
-- Role: admin
-- Status: active
-- =====================================================