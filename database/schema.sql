-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 04:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meals_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('order','menu','user','system','login','logout') NOT NULL,
  `activity_action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `activity_action`, `description`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'system', 'login', 'Admin user logged in', 'user', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 22:53:00'),
(2, 1, 'menu', 'created', 'Added new menu item: Jollof Rice', 'menu_item', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 22:53:00'),
(3, 1, 'order', 'created', 'New order placed for Ugali & Sukuma', 'order', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 22:53:00'),
(4, 1, 'user', 'created', 'New customer registered: Barrack Oluoch', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 22:53:00'),
(5, 1, 'user', 'updated', 'Updated profile information', 'user', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 23:24:08'),
(6, 1, 'user', 'updated', 'Updated profile information', 'user', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 23:24:38'),
(7, 1, 'user', 'updated', 'Updated profile information', 'user', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 23:25:43'),
(8, 1, 'user', 'updated', 'Updated profile information', 'user', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 23:25:47'),
(9, 1, 'system', 'updated', 'Updated 8 system settings', 'setting', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 23:40:22'),
(10, 1, 'menu', 'updated', 'Updated menu item: Caesar Salad', 'menu_item', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 00:09:59'),
(11, 1, 'menu', 'updated', 'Updated menu item: Caesar Salad', 'menu_item', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 00:10:16'),
(12, 1, 'menu', 'updated', 'Updated menu item: Caesar Salad', 'menu_item', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-04 00:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `ambassadors`
--

CREATE TABLE `ambassadors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `experience` enum('none','some_sales','experienced','influencer') DEFAULT 'none',
  `motivation` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','converted_to_order','abandoned') DEFAULT 'active',
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `session_id`, `created_at`, `updated_at`, `status`, `items`, `total`) VALUES
(1, 3, 'k7oh81j39ph5ui7j565mvajmm2', '2025-10-02 23:07:07', '2025-10-03 00:27:02', 'active', '[{\"menu_item_id\":3,\"quantity\":8,\"price\":\"13000.00\"},{\"menu_item_id\":2,\"quantity\":1,\"price\":\"3000.00\"}]', 107000.00),
(2, 1, 't3j342ug7j0unr3r204jd5ma66', '2025-10-03 19:20:26', '2025-10-03 20:01:49', 'active', '[{\"menu_item_id\":1,\"quantity\":4200463004,\"price\":\"3\"},{\"menu_item_id\":2,\"quantity\":21001,\"price\":\"1\"}]', 99999999.99);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Breakfast', 'Morning meals to start your day right', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(2, 'Lunch', 'Hearty meals for the middle of the day', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(3, 'Dinner', 'Delicious evening meals', NULL, 1, '2025-10-02 21:14:40', '2025-10-03 20:57:19'),
(4, 'Appetizers', 'Tasty starters and snacks', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(5, 'Desserts', 'Sweet treats to finish your meal', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(6, 'Beverages', 'Refreshing drinks and beverages', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(7, 'Specials', 'Chef\'s special dishes of the day', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `catering_requests`
--

CREATE TABLE `catering_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'test feedback', '2025-10-04 01:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_available` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Whether the menu item is featured (1) or not (0)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `image`, `category_id`, `created_at`, `is_available`, `updated_at`, `status`, `is_featured`) VALUES
(1, 'pizaa', 'qqqqqqqqqqqqqqqqqqqqqqqqqq wwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww eeeeeeeeeeeeeeeeeeeeeeeeeeeeee rrrrrrrrrrrrrrrrrrrrrrrrrrrrrr tttttttttttttttttttttttttttt yyyyyyyyyyyyyyyyyyyyy', 1000.00, '68def819a7ba9.png', 5, '2025-10-02 21:22:21', 1, '2025-10-03 21:41:43', 'inactive', 1),
(2, 'Ugali', 'Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk', 3000.00, '68def828e8309.jpeg', 4, '2025-10-02 21:32:11', 1, '2025-10-03 21:41:38', 'inactive', 1),
(3, 'Chips', 'dsd asdbj nm sd asl saslf', 13000.00, '68def84c7df37.png', 4, '2025-10-02 21:45:56', 1, '2025-10-03 21:37:49', 'inactive', 0),
(5, 'Grilled Chicken', 'Succulent chicken breast marinated in aromatic herbs, garlic, and olive oil for 24 hours, then grilled to perfection. Served with a side of roasted vegetables and our signature herb butter. This dish offers a perfect balance of juicy tenderness and smoky flavor, making it a favorite among health-conscious customers who appreciate quality protein without compromising on taste.', 1800.00, '68def819a7ba9.png', 1, '2025-10-03 21:40:31', 1, '2025-10-03 22:41:02', 'active', 1),
(6, 'Caesar Salad', 'Crisp romaine lettuce hearts tossed in our homemade Caesar dressing made with fresh garlic, anchovies, parmesan cheese, and a hint of lemon zest. Topped with crunchy herb croutons, shaved parmesan, and perfectly poached eggs. This refreshing salad combines traditional flavors with modern presentation, offering a delightful mix of textures from crispy greens to creamy dressing.', 1200.00, '68def828e8309.jpeg', 7, '2025-10-03 21:40:31', 1, '2025-10-04 00:15:28', 'active', 1),
(7, 'Chocolate Cake', 'Rich, decadent chocolate cake made with premium Belgian dark chocolate and layered with smooth chocolate ganache. Each slice reveals a moist, fudgy interior that melts in your mouth, finished with a glossy chocolate glaze and edible gold dust. Perfect for chocolate lovers seeking an indulgent dessert experience that satisfies even the most discerning sweet tooth.', 800.00, '68def84c7df37.png', 3, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 1),
(8, 'Fresh Orange Juice', 'Freshly squeezed orange juice made from Valencia oranges picked at peak ripeness. Each glass contains the juice of 4-5 carefully selected oranges, providing natural sweetness without any added sugars or preservatives. This vibrant, citrusy beverage is rich in vitamin C and offers a refreshing burst of sunshine in every sip, perfect for starting your day or pairing with any meal.', 600.00, '68def819a7ba9.png', 6, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 0),
(9, 'Beef Stew', 'Tender beef chunks slow-cooked for hours in a rich, flavorful broth with carrots, potatoes, onions, and aromatic herbs. Our traditional recipe uses premium grass-fed beef that becomes fork-tender after simmering, absorbing all the savory flavors of the vegetables and spices. Served piping hot with crusty bread for dipping, this hearty stew is the ultimate comfort food for chilly evenings.', 2200.00, '68def828e8309.jpeg', 1, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('pending','processing','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Addins Meals on Wheels', 'general', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(2, 'site_email', 'info@addinsmeals.com', 'general', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(3, 'site_phone', '+1234567890', 'contact', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(4, 'site_address', '123 Food Street, Nairobi', 'contact', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(5, 'delivery_fee', '200', 'delivery', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(6, 'min_order_amount', '500', 'delivery', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(7, 'opening_hours', 'Mon-Fri: 8:00 AM - 10:00 PM', 'general', '2025-10-02 21:08:33', '2025-10-02 21:08:33'),
(8, 'closing_days', 'Sunday', 'general', '2025-10-02 21:08:33', '2025-10-02 21:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `phone`, `updated_at`) VALUES
(1, 'Barrack Oluoch', 'admin@example.com', '$2y$10$QCCK2/Uy4mS8HppcZpCDK.t5IgtV2lR6OY17oE8okIaB/XAuyQZ1G', 'admin', '2025-10-02 19:30:07', 'active', '0722334257', '2025-10-03 23:24:38'),
(3, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '$2y$10$6hUfanOC7/ES4Jr7Bniqq.PA2UJLgsyIymwBxT0lQicjWR1hXldLm', '', '2025-10-02 20:17:06', 'active', '0722334257', '2025-10-02 20:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_type` (`activity_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `entity_type` (`entity_type`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catering_requests`
--
ALTER TABLE `catering_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section` (`section`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_items_category` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_menu_item` (`user_id`,`menu_item_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `catering_requests`
--
ALTER TABLE `catering_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
