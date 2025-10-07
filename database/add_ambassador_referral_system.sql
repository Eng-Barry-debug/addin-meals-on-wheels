-- Add created_by column to users table for ambassador referral system
ALTER TABLE `users` ADD COLUMN `created_by` INT(11) DEFAULT NULL COMMENT 'ID of the ambassador who referred this user';

-- Add foreign key constraint (optional, but recommended)
ALTER TABLE `users` ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Create index for better performance
CREATE INDEX `idx_users_created_by` ON `users`(`created_by`);
