-- Update users table to change status enum from 'blocked' to 'inactive'
ALTER TABLE `users` 
MODIFY COLUMN `status` ENUM('active','inactive') DEFAULT 'active';

-- Update any existing 'blocked' users to 'inactive'
UPDATE `users` SET `status` = 'inactive' WHERE `status` = 'blocked';
