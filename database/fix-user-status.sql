-- Fix user status enum to support soft delete functionality
-- This adds 'inactive' status to the users table enum

-- Update the status column to include 'inactive' option
ALTER TABLE `users` 
MODIFY COLUMN `status` ENUM('active', 'blocked', 'inactive') DEFAULT 'active';

-- Update any existing 'blocked' users to 'inactive' for consistency
UPDATE `users` SET `status` = 'inactive' WHERE `status` = 'blocked';

-- Add index on status for better query performance
ALTER TABLE `users` ADD INDEX `idx_status` (`status`);

-- Add index on deleted_at for better query performance
ALTER TABLE `users` ADD INDEX `idx_deleted_at` (`deleted_at`);