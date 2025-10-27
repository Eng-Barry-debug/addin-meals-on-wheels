<?php
// Create success_stories table for the admin management system
// This SQL script creates the success_stories table with all necessary fields

$sql = "
CREATE TABLE IF NOT EXISTS `success_stories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `story` text NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `success_stories`
  ADD CONSTRAINT `chk_rating` CHECK (`rating` >= 1 AND `rating` <= 5);
";

try {
    $pdo->exec($sql);
    echo "Success stories table created successfully!";
} catch (PDOException $e) {
    echo "Error creating success stories table: " . $e->getMessage();
}
?>
