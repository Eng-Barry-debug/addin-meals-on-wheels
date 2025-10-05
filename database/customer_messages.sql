-- Create customer_messages table for support system
CREATE TABLE IF NOT EXISTS `customer_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied','resolved') DEFAULT 'unread',
  `response` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_customer_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add some sample data for testing
INSERT INTO `customer_messages` (`customer_name`, `customer_email`, `subject`, `message`, `status`) VALUES
('John Doe', 'john@example.com', 'Order Inquiry', 'Hi, I placed an order #12345 yesterday but haven\'t received a confirmation email. Can you please check the status?', 'unread'),
('Jane Smith', 'jane@example.com', 'Delivery Issue', 'My order was supposed to be delivered 2 hours ago but I haven\'t received it yet. Please help!', 'read'),
('Mike Johnson', 'mike@example.com', 'Menu Question', 'Do you have any vegetarian options available? I couldn\'t find them on the menu.', 'replied'),
('Sarah Wilson', 'sarah@example.com', 'Special Request', 'I need to place a large catering order for 50 people next week. Can you provide a quote?', 'resolved');

-- Update view count for blog posts (add if not exists)
ALTER TABLE `blog_posts` ADD COLUMN IF NOT EXISTS `views` int(11) DEFAULT 0;
