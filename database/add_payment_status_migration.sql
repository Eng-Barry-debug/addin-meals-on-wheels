-- Migration script to add payment_status to orders table
-- This script adds a payment_status column to track payment confirmation
-- Run this after backing up your database

ALTER TABLE `orders` ADD COLUMN `payment_status` ENUM('pending', 'confirmed', 'failed', 'refunded') NOT NULL DEFAULT 'pending' AFTER `payment_method`;

-- Update existing orders based on their current status and payment method
-- For M-Pesa orders that are 'processing' or 'delivered', mark payment as confirmed
UPDATE `orders` SET `payment_status` = 'confirmed' WHERE `payment_method` = 'mpesa' AND `status` IN ('processing', 'shipped', 'delivered');

-- For cash on delivery orders, keep as pending until admin confirms payment
-- (they will need to be manually confirmed by admin before delivery)

-- Add index for better query performance
ALTER TABLE `orders` ADD INDEX `idx_payment_status` (`payment_status`);
ALTER TABLE `orders` ADD INDEX `idx_payment_status_status` (`payment_status`, `status`);
