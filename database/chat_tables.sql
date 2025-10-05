-- Create chat_messages table for real-time chat system
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` varchar(50) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_type` enum('admin','customer','system') NOT NULL DEFAULT 'customer',
  `message` text NOT NULL,
  `message_type` enum('text','image','file') DEFAULT 'text',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create chat_conversations table
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `id` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` enum('active','closed','waiting') DEFAULT 'active',
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_message_at` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample conversation
INSERT INTO `chat_conversations` (`id`, `customer_name`, `customer_email`, `status`) VALUES
('conv_001', 'John Doe', 'john@example.com', 'active');

-- Insert sample messages
INSERT INTO `chat_messages` (`conversation_id`, `sender_name`, `sender_type`, `message`, `is_read`) VALUES
('conv_001', 'John Doe', 'customer', 'Hi, I need help with my order #12345', 1),
('conv_001', 'Support Team', 'admin', 'Hello John! I\'d be happy to help you with your order. Let me check the status for you.', 0),
('conv_001', 'John Doe', 'customer', 'Thank you! It was supposed to be delivered yesterday.', 0);

-- Update the customer_messages table to include conversation_id for linking
ALTER TABLE `customer_messages` ADD COLUMN IF NOT EXISTS `conversation_id` varchar(50) DEFAULT NULL;
ALTER TABLE `customer_messages` ADD KEY `idx_conversation_id` (`conversation_id`);

-- Link existing customer messages to conversations (optional)
-- UPDATE customer_messages SET conversation_id = 'conv_001' WHERE conversation_id IS NULL LIMIT 1;
