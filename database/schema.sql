-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 02:09 PM
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
  `activity_type` enum('order','menu','user','system','login','logout','message') NOT NULL,
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
(12, 1, 'menu', 'updated', 'Updated menu item: Caesar Salad', 'menu_item', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-04 00:15:28'),
(13, 1, '', 'updated', 'Updated feedback status to in_review', 'feedback', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 03:30:19'),
(14, 1, '', 'updated', 'Updated feedback status to in_review', 'feedback', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 03:31:17'),
(15, 1, '', 'updated', 'Added response to feedback', 'feedback', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 03:32:03'),
(16, 1, '', 'created', 'Created new blog post: Building the Future of Food Ordering: The Addins Meals on Wheels Platform  Author', 'blog_post', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 03:48:09'),
(17, 1, '', 'created', 'Created new blog post: 🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'blog_post', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 03:53:53'),
(18, 1, '', 'created', 'Created new blog post: Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:02:12'),
(19, 1, 'system', 'updated', 'Updated 8 system settings', 'setting', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:04:39'),
(20, 1, '', 'updated', 'Updated blog post: Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:14:09'),
(21, 1, '', 'updated', 'Updated blog post: Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-04 04:23:03'),
(22, 1, '', 'updated', 'Updated blog post: 🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'blog_post', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-04 04:23:29'),
(23, 1, '', 'updated', 'Updated blog post: Building the Future of Food Ordering: The Addins Meals on Wheels Platform  Author', 'blog_post', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-04 04:23:45'),
(24, 1, '', 'updated', 'Updated blog post: Test Image Post', 'blog_post', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:29:21'),
(25, 1, '', 'updated', 'Updated blog post: Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:29:57'),
(26, 1, '', 'updated', 'Updated blog post: Building the Future of Food Ordering: The Addins Meals on Wheels Platform  Author', 'blog_post', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:30:19'),
(27, 1, '', 'updated', 'Updated blog post: 🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'blog_post', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:30:35'),
(28, 1, '', 'updated', 'Updated blog post: Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:32:56'),
(29, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:38:48'),
(30, 1, '', 'created', 'Created new blog post: 🥂 Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:40:31'),
(31, 1, '', 'created', 'Created new blog post: 🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'blog_post', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:42:47'),
(32, 1, '', 'updated', 'Updated blog post: 🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'blog_post', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:50:11'),
(33, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:50:20'),
(34, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:50:27'),
(35, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:50:38'),
(36, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:50:44'),
(37, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:51:57'),
(38, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:52:01'),
(39, 1, '', 'created', 'Created new blog post: 🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'blog_post', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:52:43'),
(40, 1, '', 'updated', 'Updated blog post: 🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'blog_post', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:53:39'),
(41, 1, '', 'updated', 'Updated blog post: 🥂 Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:53:57'),
(42, 1, '', 'updated', 'Updated blog post: 🥂 Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:54:23'),
(43, 1, '', 'updated', 'Updated blog post: Building the Future of Food Ordering: The Addins Meals on Wheels Platform  Author', 'blog_post', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:54:39'),
(44, 1, '', 'updated', 'Updated blog post: 🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'blog_post', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:54:56'),
(45, 1, '', 'updated', 'Updated blog post: 🥂 Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'blog_post', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:55:18'),
(46, 1, '', 'updated', 'Updated blog post: 🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'blog_post', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 05:07:55'),
(47, 1, '', 'updated', 'Changed blog post status to draft', 'blog_post', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 05:08:29'),
(48, 1, '', 'updated', 'Changed blog post status to published', 'blog_post', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 05:08:35'),
(49, 1, 'menu', 'created', 'Added new menu item: Jollof Rice', 'menu_item', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:20:54'),
(50, 1, 'menu', 'updated', 'Updated menu item: Jollof Rice', 'menu_item', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:22:21'),
(51, 1, '', 'created', 'Created new blog post: Testing Blog', 'blog_post', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:30:11'),
(52, 1, '', 'updated', 'Updated blog post: Testing Blog', 'blog_post', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:33:55'),
(53, 1, '', 'deleted', 'Deleted blog post', 'blog_post', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:34:04'),
(54, 1, '', 'updated', 'Added response to feedback', 'feedback', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:36:24'),
(55, 1, '', 'updated', 'Updated feedback status to in_review', 'feedback', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 16:37:02'),
(56, 1, '', 'updated', 'Updated customer message status to resolved', 'customer_message', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 19:50:20'),
(57, 1, '', 'updated_status', 'Updated application #0 status to approved', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 17:09:04'),
(58, 1, '', 'updated', 'Updated ambassador application: Barrack Oluoch', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 17:52:07'),
(59, 1, '', 'updated_status', 'Updated application #0 status to approved', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 17:56:48'),
(60, 1, '', 'deleted', 'Deleted ambassador application', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 18:07:51'),
(61, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:22:28'),
(62, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:23:22'),
(63, 1, '', 'activity', 'User \'Eng Teddy\' (ID: 4) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:28:11'),
(64, 1, '', 'activity', 'User \'Eng Teddy\' (ID: 4) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:28:24'),
(65, 1, '', 'activity', 'User \'John Driver\' (ID: 6) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:36:14'),
(66, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:41:02'),
(67, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:41:03'),
(68, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:41:32'),
(69, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:42:21'),
(70, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:45:01'),
(71, 1, '', 'activity', 'User \'John Driver\' (ID: 6) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:58:02'),
(72, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:58:11'),
(73, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:58:57'),
(74, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:03'),
(75, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:04'),
(76, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:04'),
(77, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:04'),
(78, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:04'),
(79, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:04'),
(80, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 22:59:08'),
(81, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 23:02:53'),
(82, 1, '', 'activity', 'User \'John Driver\' (ID: 6) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 23:07:58'),
(83, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 23:08:09'),
(84, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 15:12:50'),
(85, 1, '', 'activity', 'Order \'ORD-20251006-562A\' (ID: 23) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 16:50:54'),
(86, 1, '', 'activity', 'Order \'\' (ID: 3) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 16:51:19'),
(87, 1, '', 'activity', 'Order \'ORD-20251005-24DC\' (ID: 20) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 16:51:36'),
(88, 1, '', 'activity', 'Menu item \'Beef Stew\' (ID: 9) status changed to \'inactive\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:49:10'),
(89, 1, '', 'activity', 'Menu item \'Jollof Rice\' (ID: 11) status changed to \'active\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:49:21'),
(90, 1, '', 'activity', 'Menu item \'Jollof Rice\' (ID: 11) status changed to \'inactive\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:49:27'),
(91, 1, '', 'activity', 'Menu item \'Chips\' (ID: 3) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:49:45'),
(92, 1, '', 'activity', 'Menu item \'pizaa\' (ID: 1) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:49:55'),
(93, 1, '', 'activity', 'Menu item \'Grilled Chicken\' (ID: 5) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:50:13'),
(94, 1, '', 'activity', 'Menu item \'Ugali\' (ID: 2) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 17:59:26'),
(95, 1, '', 'updated_status', 'Updated application #0 status to approved', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:02:46'),
(96, 1, '', 'updated', 'Updated ambassador application: Sarah', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:02:55'),
(97, 1, '', 'activity', 'Menu item \'Grilled Chicken 1\' (ID: 5) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:07:03'),
(98, 1, '', 'activity', 'Menu item \'Chocolate Cake\' (ID: 7) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:07:22'),
(99, 1, '', 'activity', 'Menu item \'Caesar Salad\' (ID: 6) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:07:33'),
(100, 1, '', 'activity', 'Menu item \'Fresh Orange Juice\' (ID: 8) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:07:52'),
(101, 1, '', 'activity', 'User \'Eng Teddy\' (ID: 4) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 18:08:15'),
(102, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:46:06'),
(103, 6, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:46:32'),
(104, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:53:31'),
(105, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:53:49'),
(106, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:53:51'),
(107, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:54:03'),
(108, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:55:12'),
(109, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:55:31'),
(110, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:55:58'),
(111, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:56:05'),
(112, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:57:53'),
(113, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 17:58:25'),
(114, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:13'),
(115, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:20'),
(116, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:27'),
(117, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:35'),
(118, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:45'),
(119, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:46'),
(120, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:51'),
(121, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:53'),
(122, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:03:59'),
(123, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:04:11'),
(124, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:05:00'),
(125, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:05:02'),
(126, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:05:04'),
(127, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:06:48'),
(128, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:06:55'),
(129, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:06:57'),
(130, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:07:22'),
(131, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:07:26'),
(132, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:07:28'),
(133, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:07:36'),
(134, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:07:55'),
(135, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:09:49'),
(136, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:15:00'),
(137, 6, '', 'dashboard_view', 'Delivery person accessed dashboard', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:17:52'),
(138, 6, '', 'help_view', 'Delivery person accessed help page', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:18:37'),
(139, 1, '', 'activity', 'New user \'Sharon Ambassador\' (ID: 7) added.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:20:47'),
(140, 7, 'user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 18:21:06'),
(141, 1, '', 'updated', 'Updated ambassador application: Sarah Njeri (ID: 0)', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 19:36:34'),
(142, 1, '', 'updated', 'Updated ambassador application: Sarah n (ID: 0)', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 19:37:51'),
(143, 1, '', 'activity', 'New menu item \'Golden Flower Cookies\' (ID: 12) added.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:52:13'),
(144, 1, '', 'activity', 'Menu item \'Golden Flower Cookies\' (ID: 12) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:53:13'),
(145, 1, '', 'activity', 'New menu item \'Confetti Celebration Muffins\' (ID: 13) added.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:54:58'),
(146, 1, '', 'activity', 'Menu item \'Jollof Rice\' (ID: 11) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:56:37'),
(147, 1, '', 'activity', 'Menu item \'Grilled Chicken 1\' (ID: 5) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:56:45'),
(148, 1, '', 'activity', 'Menu item \'Caesar Salad\' (ID: 6) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:56:53'),
(149, 1, '', 'activity', 'Menu item \'Chocolate Cake\' (ID: 7) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:00'),
(150, 1, '', 'activity', 'Menu item \'Beef Stew\' (ID: 9) status changed to \'active\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:15'),
(151, 1, '', 'activity', 'Menu item \'Fresh Orange Juice\' (ID: 8) status changed to \'inactive\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:22'),
(152, 1, '', 'activity', 'Menu item \'Beef Stew\' (ID: 9) status changed to \'inactive\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:30'),
(153, 1, '', 'activity', 'Menu item \'Ugali\' (ID: 2) status changed to \'inactive\'.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:43'),
(154, 1, '', 'activity', 'Menu item \'Ugali\' (ID: 2) deleted.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 16:57:57'),
(155, 1, '', 'activity', 'Order #1 details updated (status: processing).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:02:25'),
(156, 1, '', 'activity', 'Order #46 details updated (status: processing).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:02:53'),
(157, 1, '', 'updated', 'Updated ambassador application: Sarah n (ID: 0)', 'ambassador', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 17:04:38'),
(158, 1, '', 'activity', 'Customer review deleted (ID: 5).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 19:16:25'),
(159, 1, '', 'activity', 'Customer review deleted (ID: 1).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 19:16:32'),
(160, 1, '', 'activity', 'Customer review approved (ID: 6).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 19:16:43'),
(161, 1, '', 'activity', 'Menu item \'Fresh Orange Juice\' (ID: 8) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 05:55:17'),
(162, 1, '', 'activity', 'Menu item \'Confetti Celebration Muffins\' (ID: 13) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:18:01'),
(163, 1, '', 'activity', 'Menu item \'Confetti Celebration Muffins\' (ID: 13) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:31:32'),
(164, 1, '', 'activity', 'Menu item \'Golden Flower Cookies\' (ID: 12) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:32:01'),
(165, 1, '', 'activity', 'Menu item \'Fresh Orange Juice\' (ID: 8) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:32:41'),
(166, 1, '', 'activity', 'Customer review approved (ID: 3).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:57:07'),
(167, 1, '', 'activity', 'Customer review approved (ID: 2).', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 07:58:03'),
(168, 1, '', 'activity', 'Menu item \'Fresh Orange Juice\' (ID: 8) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 08:04:57'),
(169, 1, '', 'activity', 'User \'Dr.  Barrack Test\' (ID: 9) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 08:05:44'),
(170, 1, '', 'activity', 'User \'Dr.  Barrack Test\' (ID: 9) updated.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 08:10:15');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_front` varchar(255) DEFAULT NULL COMMENT 'Path to front ID card image',
  `id_back` varchar(255) DEFAULT NULL COMMENT 'Path to back ID card image'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambassadors`
--

INSERT INTO `ambassadors` (`id`, `name`, `email`, `phone`, `social_media`, `experience`, `motivation`, `message`, `application_date`, `status`, `created_at`, `id_front`, `id_back`) VALUES
(0, 'Sarah n', 'sara@gmail.com', '0711111111', '', 'experienced', 'abcd e', '', '2025-10-07 18:02:14', 'pending', '2025-10-07 18:02:14', 'uploads/ambassadors/1759860134_front_68e555a671fea.png', 'uploads/ambassadors/1759860134_back_68e555a672199.png');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `content`, `author`, `status`, `image`, `created_at`, `views`) VALUES
(1, 'Building the Future of Food Ordering: The Addins Meals on Wheels Platform  Author', 'At Bonnie Computer Hub, we believe in empowering communities through technology. Our latest project, Addins Meals on Wheels, is more than just a food delivery service — it’s a complete digital platform designed to transform how people access meals, catering, and entrepreneurial opportunities.\r\n\r\nWhy Addins Meals on Wheels?\r\n\r\nIn today’s fast-moving world, convenience and accessibility are essential. Customers want to order meals with a few clicks, event organizers need quick catering solutions, and young entrepreneurs need opportunities to grow. The Addins Meals on Wheels platform answers all three needs in one place.\r\n\r\nCore Features\r\n\r\nHere’s what makes this project unique:\r\n\r\nEasy Online Ordering: Customers can browse menus, filter by categories, and place real-time orders.\r\n\r\nCatering Services: Event planners can book customized packages, request quotes, and explore past events in the gallery.\r\n\r\nAmbassadors Program: Students and young entrepreneurs can join as Addins Ambassadors, gaining skills and contributing to community impact.\r\n\r\nContent &amp;amp;amp;amp; Updates: From recipes to company news, the blog and CMS keep users informed and engaged.', 'Bonnie Computer Hub Team', 'published', 'catering2_1.png', '2025-10-04 03:48:09', 0),
(2, '🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'At Addins Meals on Wheels, we’re not just building a food platform — we’re building futures. One of the most exciting parts of our project is the Ambassadors Program, designed to empower young entrepreneurs, especially students, with real-world opportunities to grow, learn, and earn.\r\n\r\nWhat is the Addins Ambassadors Program?\r\n\r\nThe Ambassadors Program is a community-driven initiative where passionate individuals represent Addins Meals on Wheels in their schools, universities, and neighborhoods. Ambassadors don’t just promote the platform — they become part of a network that combines entrepreneurship, leadership, and technology.\r\n\r\nWhy It Matters\r\n\r\nIn a world where youth unemployment is high, we believe in giving young people the tools to succeed. The program provides:\r\n\r\nSkill Development: Training in digital marketing, customer service, and community engagement.\r\n\r\nFinancial Opportunities: Earn rewards and commissions by connecting people to Addins Meals on Wheels.\r\n\r\nNetworking: Join a community of like-minded young leaders and entrepreneurs.\r\n\r\nReal-World Experience: Gain practical exposure in sales, event planning, and brand building.\r\n\r\nHow the Platform Supports Ambassadors\r\n\r\nThe web platform comes with a dedicated Ambassador dashboard where participants can:\r\n\r\nApply online and track application status\r\n\r\nAccess training resources and program updates\r\n\r\nMonitor performance and engagement\r\n\r\nConnect with coordinators and fellow ambassadors\r\n\r\nOur Vision\r\n\r\nThe Ambassadors Program goes beyond business. It’s about inspiring youth to take charge, build confidence, and discover their potential. By combining technology with opportunity, we’re creating a ripple effect that impacts both communities and the individuals within them.\r\n\r\nJoin Us\r\n\r\nIf you’re a student, young professional, or simply passionate about making a difference in your community, the Addins Ambassadors Program is your chance to shine.\r\n\r\nTogether, we can serve meals, inspire change, and empower through technology.', 'Bonnie Computer Hub Team', 'published', '68def819a7ba9_1.png', '2025-10-04 03:53:53', 3),
(4, 'Test Image Post', 'This is a test post with an image', 'Test Author', 'published', 'cupcakes2_1.jpeg', '2025-10-04 04:28:47', 2),
(5, '🥂 Catering Made Simple: Addins Meals on Wheels for Every Occasion', 'Planning an event is exciting — but when it comes to food, it can also be stressful. That’s why Addins Meals on Wheels brings you a seamless catering solution, designed to take the hassle out of event planning while keeping your guests delighted.\r\n\r\nWhy Choose Addins Catering?\r\n\r\nWhether you’re hosting a corporate meeting, a birthday celebration, or a wedding, food is at the heart of the experience. With Addins Catering, you get:\r\n\r\nFlexible Packages: From small gatherings to large-scale events, we tailor menus to fit your needs.\r\n\r\nCustom Quotes: Request personalized pricing based on your guest count, menu preferences, and special requirements.\r\n\r\nDelicious Variety: Our curated menu includes fresh meals, baked goods, and beverages to suit all tastes.\r\n\r\nProfessional Service: From booking to delivery, our team ensures everything runs smoothly.\r\n\r\nFeatures in the Platform\r\n\r\nOur web platform makes catering easy and transparent:\r\n\r\nOnline Catering Request Form – Submit details of your event in minutes\r\n\r\nCustom Quote Generation – Get pricing fast, directly in your inbox\r\n\r\nService Comparisons – Browse different packages and find the best fit\r\n\r\nEvent Portfolio – Explore photos and stories from past events for inspiration\r\n\r\nBrand Identity in Action\r\n\r\nEvery catering experience reflects the Addins Meals on Wheels brand values — quality, trust, and excellence. With our Deep Red (#C1272D) passion, Gold (#D4AF37) excellence, and Dark Green (#2E5E3A) freshness, the service is not just about food — it’s about creating memories.\r\n\r\nWho We Serve\r\n\r\nCorporate Events: Meetings, launches, office parties\r\n\r\nPrivate Gatherings: Birthdays, anniversaries, family celebrations\r\n\r\nCommunity Functions: Fundraisers, school events, church gatherings\r\n\r\nConclusion\r\n\r\nWith Addins Catering, you don’t just book a service — you book peace of mind. We handle the food so you can focus on enjoying the event.\r\n\r\n👉 Ready to make your next event unforgettable? Visit our Catering Services page and let’s start planning together.', 'Bonnie Computer Hub Team', 'published', '68deef5bb67c4_1.jpg', '2025-10-04 04:40:31', 3),
(6, '🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'When hunger strikes, you don’t want to waste time scrolling endlessly through apps that feel too complicated or detached. At Addins Meals on Wheels, we believe ordering food should be simple, reliable, and joyful.\r\n\r\nWhat Makes Addins Different?\r\n\r\nUnlike generic delivery platforms, Addins is built for you, by people who care about your experience.\r\nHere’s what sets us apart:\r\n\r\nCurated Menu Categories – Pizza, Cookies, Cupcakes, and Catering, all crafted with love.\r\n\r\nReal-Time Order Tracking – Know exactly when your meal will arrive.\r\n\r\nTransparent Pricing – No hidden costs, just fresh food at fair prices.\r\n\r\nMultiple Payment Options – Pay securely via M-Pesa, PayPal, or cash on delivery.\r\n\r\nDesigned for Speed &amp;amp;amp;amp; Convenience\r\n\r\nOur platform is designed with a mobile-first approach so you can browse and order easily from your phone. With just a few clicks, your food is on its way.\r\n\r\nSmart Cart – Add items in real-time, see your total instantly.\r\n\r\nGuest Checkout – No need to sign up if you’re in a hurry.\r\n\r\nPersonalized Experience – Create an account to save your favorites and view past orders.\r\n\r\nMore Than Just Food Delivery\r\n\r\nFood is about connection. That’s why Addins integrates features that let you:\r\n\r\nRead &amp;amp;amp;amp; Leave Reviews – Share your experience and learn from others.\r\n\r\nAccess Nutritional Info – Know what’s in your food before you order.\r\n\r\nJoin Our Community – Follow our blog for recipes, health tips, and ambassador stories.\r\n\r\nBrand Promise in Every Meal\r\n\r\nOur meals carry the Deep Red (#C1272D) of passion, the Gold (#D4AF37) of excellence, and the Warm Cream (#F5E6D3) of care. When your food arrives, it’s not just a delivery — it’s a promise kept.\r\n\r\nConclusion\r\n\r\nWith Addins Meals on Wheels, you’re not just placing an order — you’re joining a movement that values quality, trust, and community.\r\n\r\n👉 Explore the Menu Page today and let’s bring fresh meals straight to your table.', 'Bonnie Computer Hub Team', 'published', 'catering_1.jpeg', '2025-10-04 04:42:47', 8),
(13, '🍕 Fresh, Fast, and Local: The Addins Meals on Wheels Ordering Experience', 'When hunger strikes, you don’t want to waste time scrolling endlessly through apps that feel too complicated or detached. At Addins Meals on Wheels, we believe ordering food should be simple, reliable, and joyful.\r\n\r\nWhat Makes Addins Different?\r\n\r\nUnlike generic delivery platforms, Addins is built for you, by people who care about your experience.\r\nHere’s what sets us apart:\r\n\r\nCurated Menu Categories – Pizza, Cookies, Cupcakes, and Catering, all crafted with love.\r\n\r\nReal-Time Order Tracking – Know exactly when your meal will arrive.\r\n\r\nTransparent Pricing – No hidden costs, just fresh food at fair prices.\r\n\r\nMultiple Payment Options – Pay securely via M-Pesa, PayPal, or cash on delivery.\r\n\r\nDesigned for Speed &amp; Convenience\r\n\r\nOur platform is designed with a mobile-first approach so you can browse and order easily from your phone. With just a few clicks, your food is on its way.\r\n\r\nSmart Cart – Add items in real-time, see your total instantly.\r\n\r\nGuest Checkout – No need to sign up if you’re in a hurry.\r\n\r\nPersonalized Experience – Create an account to save your favorites and view past orders.\r\n\r\nMore Than Just Food Delivery\r\n\r\nFood is about connection. That’s why Addins integrates features that let you:\r\n\r\nRead &amp; Leave Reviews – Share your experience and learn from others.\r\n\r\nAccess Nutritional Info – Know what’s in your food before you order.\r\n\r\nJoin Our Community – Follow our blog for recipes, health tips, and ambassador stories.\r\n\r\nBrand Promise in Every Meal\r\n\r\nOur meals carry the Deep Red (#C1272D) of passion, the Gold (#D4AF37) of excellence, and the Warm Cream (#F5E6D3) of care. When your food arrives, it’s not just a delivery — it’s a promise kept.\r\n\r\nConclusion\r\n\r\nWith Addins Meals on Wheels, you’re not just placing an order — you’re joining a movement that values quality, trust, and community.\r\n\r\n👉 Explore the Menu Page today and let’s bring fresh meals straight to your table.', 'Bonnie Computer Hub Team', 'published', 'freshfoods_1_1.png', '2025-10-04 04:52:43', 6);

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
(2, 1, 't3j342ug7j0unr3r204jd5ma66', '2025-10-03 19:20:26', '2025-10-04 18:56:32', 'active', '[{\"menu_item_id\":6,\"quantity\":1200,\"price\":1}]', 1200.00),
(3, 4, 'rb12e86fdmmcacecjahuj4k72o', '2025-10-04 05:33:14', '2025-10-04 19:03:26', 'active', '[]', 0.00),
(5, 1, 'en6jvsqikjs6r88hnhpu4scnbe', '2025-10-05 04:15:20', '2025-10-05 16:16:05', 'active', '[]', 0.00),
(6, 1, '187tko730nuus8ujglq15qaqf2', '2025-10-05 18:14:00', '2025-10-05 19:48:06', 'active', '[{\"menu_item_id\":6,\"quantity\":1200,\"price\":2},{\"menu_item_id\":9,\"quantity\":2200,\"price\":1},{\"menu_item_id\":1,\"quantity\":297,\"price\":1000}]', 301600.00),
(7, NULL, 'q686711m3p7n7fle8fmmt0oudt', '2025-10-05 18:48:04', '2025-10-05 18:48:04', 'active', '[]', 0.00),
(8, 4, 'f60ho97o8vdmkfgdd0fpbbt5hl', '2025-10-06 16:04:12', '2025-10-06 18:25:43', 'active', NULL, 0.00),
(9, 1, '962e4treuq9mgn66unjr5e9l23', '2025-10-06 16:27:01', '2025-10-06 18:16:57', 'active', NULL, 0.00),
(10, 6, 'ag46ug86ued6mjfp1m8o5iu0fe', '2025-10-06 20:37:28', '2025-10-06 21:39:32', 'active', NULL, 0.00),
(11, 1, '1uju8bnu0j3iqj8jh8ctkt12uk', '2025-10-06 20:38:10', '2025-10-07 15:17:37', 'active', NULL, 0.00),
(12, NULL, '7jo8a1up8vrkuv65bc9qhqc1q3', '2025-10-06 22:36:48', '2025-10-06 22:36:48', 'active', NULL, 0.00),
(13, 1, '0futvgqfp9u8r6tlil8fdaqk91', '2025-10-09 16:28:53', '2025-10-09 17:07:37', 'active', NULL, 0.00),
(14, 8, '836dp8ddbcre3njorme4lfm1v0', '2025-10-09 17:23:02', '2025-10-10 08:39:57', 'active', NULL, 0.00),
(15, 1, 'hp0s9ikn01ep4q02ec9n85oa0k', '2025-10-10 10:12:32', '2025-10-10 11:43:39', 'active', NULL, 0.00),
(16, 9, '3ctpiv1ib4ob1vufu6jnnacfhm', '2025-10-11 17:58:25', '2025-10-11 18:57:23', 'active', NULL, 0.00),
(17, 9, '0vi97u3lahr74iab6lh5m3529t', '2025-10-11 18:49:07', '2025-10-16 08:06:23', 'active', NULL, 0.00),
(18, 9, 'ovs8j5rfqt0sib1aqjagnpcui7', '2025-10-16 10:29:43', '2025-10-16 10:31:55', 'active', NULL, 0.00),
(19, 8, '9q3sbj5fk9tgt6b2nk9a8m2mil', '2025-10-16 10:48:49', '2025-10-16 10:57:07', 'active', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `menu_item_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(4, 8, 9, 1, 2200.00, '2025-10-06 18:25:30', '2025-10-06 18:25:30'),
(5, 8, 6, 2, 1200.00, '2025-10-06 18:25:38', '2025-10-06 18:25:43'),
(6, 11, 8, 1, 600.00, '2025-10-07 15:17:37', '2025-10-07 15:17:37'),
(23, 15, 13, 1, 80.00, '2025-10-10 11:43:39', '2025-10-10 11:43:39'),
(25, 16, 12, 39, 50.00, '2025-10-11 18:45:34', '2025-10-11 18:57:23'),
(29, 17, 13, 1, 80.00, '2025-10-16 05:11:12', '2025-10-16 05:11:12'),
(30, 17, 12, 1, 50.00, '2025-10-16 05:30:10', '2025-10-16 05:30:10'),
(31, 17, 8, 1, 600.00, '2025-10-16 08:06:23', '2025-10-16 08:06:23'),
(32, 18, 8, 1, 600.00, '2025-10-16 10:31:55', '2025-10-16 10:31:55'),
(33, 19, 12, 1, 50.00, '2025-10-16 10:57:07', '2025-10-16 10:57:07');

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
(2, 'Lunch ', 'Hearty meals for the middle of the day', NULL, 1, '2025-10-02 21:14:40', '2025-10-05 16:05:33'),
(3, 'Dinner', 'Delicious evening meals', NULL, 1, '2025-10-02 21:14:40', '2025-10-03 20:57:19'),
(4, 'Appetizers', 'Tasty starters and snacks', NULL, 1, '2025-10-02 21:14:40', '2025-10-09 16:53:34'),
(6, 'Beverages', 'Refreshing drinks and beverages', NULL, 1, '2025-10-02 21:14:40', '2025-10-16 05:47:25'),
(7, 'Specials', 'Chef\'s special dishes of the day', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(10, 'Dessert', 'a comforting afternoon tea snack, or a thoughtful gift', NULL, 1, '2025-10-09 16:53:01', '2025-10-09 16:53:01');

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

--
-- Dumping data for table `catering_requests`
--

INSERT INTO `catering_requests` (`id`, `name`, `email`, `phone`, `event_date`, `message`, `created_at`) VALUES
(1, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', '2025-11-07', 'abcd test', '2025-10-04 03:34:56'),
(2, 'Miss Cheza', 'cheza@gmail.com', '0711111111', '2025-10-04', 'another 1', '2025-10-04 07:06:11');

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
-- Table structure for table `customer_messages`
--

CREATE TABLE `customer_messages` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied','resolved') DEFAULT 'unread',
  `response` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_messages`
--

INSERT INTO `customer_messages` (`id`, `customer_id`, `customer_name`, `customer_email`, `subject`, `message`, `status`, `response`, `admin_id`, `created_at`, `updated_at`) VALUES
(1, NULL, 'John Doe', 'john@example.com', 'Order Inquiry', 'Hi, I placed an order #12345 yesterday but haven\'t received a confirmation email. Can you please check the status?', 'resolved', NULL, NULL, '2025-10-04 05:30:58', '2025-10-04 06:11:13'),
(2, NULL, 'Jane Smith', 'jane@example.com', 'Delivery Issue', 'My order was supposed to be delivered 2 hours ago but I haven\'t received it yet. Please help!', 'read', NULL, NULL, '2025-10-04 05:30:58', '2025-10-04 05:30:58'),
(3, NULL, 'Mike Johnson', 'mike@example.com', 'Menu Question', 'Do you have any vegetarian options available? I couldn\'t find them on the menu.', 'resolved', NULL, NULL, '2025-10-04 05:30:58', '2025-10-04 06:11:03'),
(4, NULL, 'Sarah Wilson', 'sarah@example.com', 'Special Request', 'I need to place a large catering order for 50 people next week. Can you provide a quote?', 'resolved', NULL, NULL, '2025-10-04 05:30:58', '2025-10-04 05:30:58'),
(5, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'hi', 'read', NULL, NULL, '2025-10-04 05:47:13', '2025-10-04 06:41:44'),
(6, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'hi', 'read', NULL, NULL, '2025-10-04 05:50:21', '2025-10-04 06:41:44'),
(7, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'hello', 'read', NULL, NULL, '2025-10-04 05:51:44', '2025-10-04 06:41:44'),
(8, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'how are you', 'read', NULL, NULL, '2025-10-04 06:04:23', '2025-10-04 06:41:44'),
(9, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'good you', 'read', NULL, NULL, '2025-10-04 06:05:02', '2025-10-04 06:41:44'),
(10, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'very nice', 'read', NULL, NULL, '2025-10-04 06:06:25', '2025-10-04 06:41:44'),
(11, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'whats you problem', 'read', NULL, NULL, '2025-10-04 06:06:53', '2025-10-04 06:41:44'),
(12, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'talk to me', 'read', NULL, NULL, '2025-10-04 06:09:19', '2025-10-04 06:41:44'),
(13, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'nothing at all i was just wasting time here', 'read', NULL, NULL, '2025-10-04 06:09:50', '2025-10-04 06:41:44'),
(14, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'sasa', 'read', NULL, NULL, '2025-10-04 06:30:32', '2025-10-04 06:41:44'),
(15, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'poa', 'resolved', NULL, NULL, '2025-10-04 06:31:11', '2025-10-04 19:50:20'),
(16, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'oky', 'read', NULL, NULL, '2025-10-04 06:32:28', '2025-10-04 06:41:44'),
(17, NULL, 'Miss Cheza', 'Miss Cheza@example.com', 'Chat Message', 'Morning', 'read', NULL, NULL, '2025-10-04 06:45:29', '2025-10-04 06:46:28'),
(18, NULL, 'Barrack Oluoch', 'Miss Cheza@example.com', 'Chat Message', 'morning', 'read', NULL, NULL, '2025-10-04 06:45:53', '2025-10-04 06:46:28'),
(19, NULL, 'Barrack Oluoch', 'Miss Cheza@example.com', 'Chat Message', 'how can i help you', 'unread', NULL, NULL, '2025-10-04 06:49:06', '2025-10-04 06:49:06'),
(20, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'Sasa', 'read', NULL, NULL, '2025-10-04 16:46:11', '2025-10-06 22:07:06'),
(21, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'Poa', 'read', NULL, NULL, '2025-10-04 16:47:32', '2025-10-06 22:07:06'),
(22, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'The system is working', 'read', NULL, NULL, '2025-10-04 16:47:53', '2025-10-06 22:07:06'),
(23, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'Yeah', 'read', NULL, NULL, '2025-10-04 16:48:11', '2025-10-06 22:07:06'),
(24, NULL, 'Barrack', 'Barrack@example.com', 'Chat Message', 'hello', 'read', NULL, NULL, '2025-10-16 10:57:30', '2025-10-16 11:00:05'),
(25, NULL, 'Barrack Oluoch', 'Barrack@example.com', 'Chat Message', 'yes', 'read', NULL, NULL, '2025-10-16 10:59:07', '2025-10-16 11:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `customer_reviews`
--

CREATE TABLE `customer_reviews` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_title` varchar(100) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `catering_event_type` varchar(100) DEFAULT NULL,
  `occasion_details` text DEFAULT NULL,
  `review_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_reviews`
--

INSERT INTO `customer_reviews` (`id`, `customer_name`, `customer_email`, `customer_title`, `rating`, `review_text`, `service_type`, `catering_event_type`, `occasion_details`, `review_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Sarah Smith', 'sarah@example.com', 'Event Coordinator', 5, 'The catering service for our event was outstanding. Professional staff and amazing food quality.', 'Catering', 'Corporate Event', '150 Guests', '2024-01-20', 'approved', '2025-10-09 19:14:16', '2025-10-09 19:14:16'),
(3, 'Mike Johnson', 'mike@example.com', 'Wedding Planner', 4, 'Great cookies and cupcakes! Fresh and tasty. Will definitely order again.', 'Catering', 'Wedding Catering', 'Multiple Events', '2024-01-25', 'approved', '2025-10-09 19:14:16', '2025-10-09 19:14:16'),
(4, 'Emily Davis', 'emily@example.com', 'Private Event Host', 5, 'Exceptional service and food quality. The attention to detail is impressive.', 'Catering', 'Family Events', 'Multiple Occasions', '2024-02-01', 'approved', '2025-10-09 19:14:16', '2025-10-09 19:14:16'),
(6, 'Barrack', 'barrackbarry2023@gmail.com', NULL, 4, 'qwerty', 'Catering', NULL, NULL, '2025-10-09', 'approved', '2025-10-09 19:15:59', '2025-10-09 19:16:43'),
(7, 'Still_Barrack', 'barrackbarry2023@gmail.com', NULL, 5, 'Like the services', 'Catering', NULL, NULL, '2025-10-16', 'pending', '2025-10-16 11:21:06', '2025-10-16 11:21:06'),
(8, 'Sarah Johnson', 'sarah.johnson@techcorp.com', 'Event Coordinator', 5, 'Addins Meals on Wheels catered our company\'s annual gala, and the food was absolutely exceptional. Our guests couldn\'t stop raving about the delicious dishes and professional service.', 'Catering', 'Corporate Event', '150 Guests', '2024-12-15', 'approved', '2025-10-16 15:21:00', '2025-10-16 15:21:00'),
(9, 'Michael Chen', 'michael@weddingplanners.com', 'Wedding Planner', 5, 'Working with Addins for wedding catering has been incredible. They understand the importance of presentation and timing, making every wedding reception memorable.', 'Catering', 'Wedding Catering', 'Multiple Events', '2024-12-10', 'approved', '2025-10-16 15:21:00', '2025-10-16 15:21:00'),
(10, 'Emily Rodriguez', 'emily.rodriguez@email.com', 'Private Event Host', 5, 'From my daughter\'s graduation party to our family reunion, Addins has never disappointed. The food is always fresh, beautifully presented, and absolutely delicious.', 'Catering', 'Family Events', 'Multiple Occasions', '2024-12-05', 'approved', '2025-10-16 15:21:00', '2025-10-16 15:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','in_review','resolved','closed') DEFAULT 'new',
  `response` text DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `message`, `status`, `response`, `response_date`, `created_at`) VALUES
(1, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'test feedback', 'resolved', 'will check', '2025-10-04 03:32:03', '2025-10-04 01:05:34'),
(2, 'Teddy Oluoch', 'teddy@gmail.com', 'Check the pages', 'resolved', 'Check this', '2025-10-04 16:36:24', '2025-10-04 16:34:54'),
(3, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'Another test', 'in_review', NULL, NULL, '2025-10-04 16:36:52'),
(4, 'Barrack Oluoch 123', 'oluochbarrackonyango@gmail.com', 'nmn', 'new', NULL, NULL, '2025-10-04 17:59:35'),
(5, 'Barrack Oluoch 123', 'oluochbarrackonyango@gmail.com', 'nmn', 'new', NULL, NULL, '2025-10-04 18:00:04'),
(6, 'Barrack Oluoch 123', 'oluochbarrackonyango@gmail.com', 'nmn', 'new', NULL, NULL, '2025-10-04 18:00:56'),
(7, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'anothertyui', 'new', NULL, NULL, '2025-10-04 18:14:17'),
(8, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'wertyuio', 'new', NULL, NULL, '2025-10-04 18:14:57'),
(9, 'ertyu', 'advb@gmail.com', 'qwertyuiop[', 'new', NULL, NULL, '2025-10-04 18:18:32'),
(10, 'ertyu', 'advb@gmail.com', 'qwertyuiop[', 'new', NULL, NULL, '2025-10-04 18:18:47'),
(11, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'qwertyuio', 'new', NULL, NULL, '2025-10-04 18:18:54'),
(12, 'Barrack Oluoch 123', 'oluochbarrackonyango@gmail.com', '1234567890', 'new', NULL, NULL, '2025-10-04 18:19:23'),
(13, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'q345tf', 'new', NULL, NULL, '2025-10-04 18:20:05'),
(14, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '23456789876543', 'new', NULL, NULL, '2025-10-04 18:20:17'),
(15, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '547', 'new', NULL, NULL, '2025-10-04 18:21:29'),
(16, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'fyytrte832t4ui', 'new', NULL, NULL, '2025-10-04 18:21:56'),
(17, 'Prof Barry', 'barrackbarry2023@gmail.com', 'wonderful', 'new', NULL, NULL, '2025-10-16 11:18:16');

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
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Whether the menu item is featured (1) or not (0)',
  `ingredients` text DEFAULT NULL COMMENT 'Detailed list of ingredients',
  `allergens` text DEFAULT NULL COMMENT 'Allergen information',
  `nutrition_info` text DEFAULT NULL COMMENT 'Nutritional information'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `image`, `category_id`, `created_at`, `is_available`, `updated_at`, `status`, `is_featured`, `ingredients`, `allergens`, `nutrition_info`) VALUES
(8, 'Fresh Orange Juice', 'Freshly squeezed orange juice made from Valencia oranges picked at peak ripeness. Each glass contains the juice of 4-5 carefully selected oranges, providing natural sweetness without any added sugars or preservatives. This vibrant, citrusy beverage is rich in vitamin C and offers a refreshing burst of sunshine in every sip, perfect for starting your day or pairing with any meal.', 600.00, 'menu_68e556f8f0c67.png', 7, '2025-10-03 21:40:31', 1, '2025-10-16 08:04:56', 'active', 0, 'iiiiiiiiiii', 'jjjjjjjjjj', 'kkkkkkkkkkkk'),
(9, 'Beef Stew', 'Tender beef chunks slow-cooked for hours in a rich, flavorful broth with carrots, potatoes, onions, and aromatic herbs. Our traditional recipe uses premium grass-fed beef that becomes fork-tender after simmering, absorbing all the savory flavors of the vegetables and spices. Served piping hot with crusty bread for dipping, this hearty stew is the ultimate comfort food for chilly evenings.', 2200.00, '68def828e8309.jpeg', 1, '2025-10-03 21:40:31', 1, '2025-10-09 16:57:30', 'inactive', 1, NULL, NULL, NULL),
(12, 'Golden Flower Cookies', 'Golden Flower Cookies: A Delightful Shortbread Treat\r\nIndulge in our beautifully crafted Golden Flower Cookies. These are not just any biscuits—they are delicate, buttery shortbreads pressed with an elegant floral design, making them a perfect accompaniment to your meal or a delightful treat on their own.\r\n\r\nTaste Profile: Rich, classic butter flavor with a subtle sweetness that melts in your mouth.\r\n\r\nTexture: Wonderfully crisp and tender, embodying the perfect shortbread snap.\r\n\r\nPerfect For: Dessert after an Addins meal, a comforting afternoon tea snack, or a thoughtful gift.', 50.00, 'menu_68e7e83d614da.png', 10, '2025-10-09 16:52:13', 1, '2025-10-16 07:32:01', 'active', 1, 'tttttttt', 'aaaaaaaaaa', 'cccccc'),
(13, 'Confetti Celebration Muffins', 'Confetti Celebration Muffins: Every Day is a Party!\r\nBrighten your meal with these delightful and moist muffins, perfect for a quick breakfast, a school lunchbox, or a celebratory dessert. Baked to a beautiful golden brown and topped with a generous scattering of colorful, playful sprinkles, they are a favorite for all ages.\r\n\r\nTaste Profile: Light, fluffy, and perfectly sweet with a classic vanilla essence.\r\n\r\nTexture: Wonderfully tender and moist, making every bite a joy.\r\n\r\nReady-to-Eat: Comes individually cupped for easy serving and on-the-go enjoyment.\r\n\r\nAdd a little happy to your order—you deserve it!', 80.00, 'menu_68e7e8e2e8460.png', 1, '2025-10-09 16:54:58', 1, '2025-10-16 07:31:32', 'active', 0, 'poureder', 'nuts', 'Carloris');

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_payments`
--

CREATE TABLE `mpesa_payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `paybill_number` varchar(20) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mpesa_payments`
--

INSERT INTO `mpesa_payments` (`id`, `order_id`, `reference`, `paybill_number`, `account_number`, `amount`, `phone_number`, `transaction_code`, `status`, `created_at`, `updated_at`) VALUES
(1, 73, 'MPESA-73-1760085282', '116519', '007160', 280.00, NULL, NULL, 'pending', '2025-10-10 08:34:42', '2025-10-10 08:34:42'),
(2, 74, 'MPESA-74-1760085348', '116519', '007160', 280.00, NULL, NULL, 'pending', '2025-10-10 08:35:48', '2025-10-10 08:35:48'),
(3, 75, 'MPESA-75-1760085597', '116519', '007160', 280.00, NULL, NULL, 'pending', '2025-10-10 08:39:57', '2025-10-10 08:39:57'),
(4, 78, 'MPESA-78-1760091505', '116519', '007160', 250.00, NULL, NULL, 'pending', '2025-10-10 10:18:25', '2025-10-10 10:18:25'),
(5, 80, 'MPESA-80-1760095731', '116519', '007160', 250.00, NULL, NULL, 'pending', '2025-10-10 11:28:51', '2025-10-10 11:28:51'),
(6, 81, 'MPESA-81-1760096316', '116519', '007160', 280.00, NULL, NULL, 'pending', '2025-10-10 11:38:36', '2025-10-10 11:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `template` varchar(100) DEFAULT 'default',
  `status` enum('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `total_recipients` int(11) DEFAULT 0,
  `sent_count` int(11) DEFAULT 0,
  `delivered_count` int(11) DEFAULT 0,
  `opened_count` int(11) DEFAULT 0,
  `clicked_count` int(11) DEFAULT 0,
  `bounced_count` int(11) DEFAULT 0,
  `unsubscribed_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_campaigns`
--

INSERT INTO `newsletter_campaigns` (`id`, `subject`, `content`, `template`, `status`, `scheduled_at`, `sent_at`, `total_recipients`, `sent_count`, `delivered_count`, `opened_count`, `clicked_count`, `bounced_count`, `unsubscribed_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ertyui', 'tyui', NULL, 'sent', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-09 19:36:27', '2025-10-10 05:50:10'),
(2, 'ertyui j', 'wertyui', NULL, 'draft', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-09 19:37:20', '2025-10-10 04:08:54'),
(3, 'Offer', 'ert yu scn  jslj n', NULL, 'sent', NULL, '2025-10-10 06:30:57', 1, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 03:43:13', '2025-10-10 06:30:59'),
(4, 'Offer', 'temp jho', NULL, 'sent', NULL, '2025-10-10 07:59:39', 2, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 03:58:50', '2025-10-10 07:59:44'),
(5, 'Offer', 'qwerty', NULL, 'sent', NULL, '2025-10-10 07:30:16', 1, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 03:59:09', '2025-10-10 07:30:22'),
(6, 'Offer test', 'wer', NULL, 'sending', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 04:05:27', '2025-10-10 04:05:27'),
(7, 'Offer test', 'wer', NULL, 'sent', NULL, '2025-10-10 07:53:16', 1, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 04:06:28', '2025-10-10 07:53:20'),
(8, 'Offer test', 'Failed to load newsletter data.', '1', 'sending', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 06:14:28', '2025-10-10 06:14:28'),
(9, 'Offer test', 'Failed to load newsletter data.', '1', 'sent', NULL, '2025-10-10 06:16:52', 1, 0, 0, 0, 0, 0, 0, 1, '2025-10-10 06:16:52', '2025-10-10 06:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscriptions`
--

CREATE TABLE `newsletter_subscriptions` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscription_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unsubscribe_token` varchar(64) DEFAULT NULL,
  `last_email_sent` timestamp NULL DEFAULT NULL,
  `total_emails_received` int(11) DEFAULT 0,
  `emails_opened` int(11) DEFAULT 0,
  `emails_clicked` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscriptions`
--

INSERT INTO `newsletter_subscriptions` (`id`, `email`, `subscription_date`, `is_active`, `unsubscribed_at`, `created_at`, `updated_at`, `unsubscribe_token`, `last_email_sent`, `total_emails_received`, `emails_opened`, `emails_clicked`) VALUES
(1, 'barrackbarry2023@gmail.com', '2025-10-09', 1, NULL, '2025-10-09 19:21:05', '2025-10-09 19:26:53', '591d7670926ab3550b248ac677670fa692e7d68e1a9a02e449490bd261d55a6e', NULL, 0, 0, 0),
(2, 'barrackbarry202223@gmail.com', '2025-10-09', 0, '2025-10-09 19:27:50', '2025-10-09 19:27:09', '2025-10-09 19:27:50', NULL, NULL, 0, 0, 0),
(3, 'oluochbarrackonyango@gmail.com', '2025-10-10', 1, NULL, '2025-10-10 07:58:52', '2025-10-10 07:58:52', NULL, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_templates`
--

CREATE TABLE `newsletter_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `html_template` text NOT NULL,
  `text_template` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_templates`
--

INSERT INTO `newsletter_templates` (`id`, `name`, `description`, `html_template`, `text_template`, `thumbnail`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Template', 'Clean and professional newsletter template with Addins branding', '\n    <!DOCTYPE html>\n    <html>\n    <head>\n        <meta charset=\'utf-8\'>\n        <meta name=\'viewport\' content=\'width=device-width, initial-scale=1\'>\n        <title>{{SUBJECT}}</title>\n        <style>\n            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }\n            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n            .header { text-align: center; border-bottom: 3px solid #C1272D; padding-bottom: 20px; margin-bottom: 30px; }\n            .logo { font-size: 24px; font-weight: bold; color: #C1272D; margin-bottom: 10px; }\n            .content { margin: 20px 0; }\n            .cta-button { display: inline-block; background-color: #C1272D; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }\n            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }\n            .unsubscribe { color: #C1272D; text-decoration: none; }\n        </style>\n    </head>\n    <body>\n        <div class=\'container\'>\n            <div class=\'header\'>\n                <div class=\'logo\'>Addins Meals on Wheels</div>\n                <h1>{{SUBJECT}}</h1>\n            </div>\n            <div class=\'content\'>\n                {{CONTENT}}\n            </div>\n            <div class=\'footer\'>\n                <p>You received this email because you subscribed to our newsletter.</p>\n                <p><a href=\'{{UNSUBSCRIBE_URL}}\' class=\'unsubscribe\'>Unsubscribe</a> | <a href=\'{{WEBSITE_URL}}\'>Visit Website</a></p>\n                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>\n            </div>\n        </div>\n    </body>\n    </html>\n    ', NULL, NULL, 1, '2025-10-09 19:26:53', '2025-10-09 19:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash_on_delivery',
  `payment_status` enum('pending','confirmed','failed','refunded') NOT NULL DEFAULT 'pending',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `delivery_instructions`, `subtotal`, `delivery_fee`, `total`, `payment_method`, `payment_status`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD-20251004-D2B3', 'Barrack Oluoch', 'admin@example.com', '0722334257', 'Kibera', '', 99999999.99, 0.00, 99999999.99, 'mpesa', 'confirmed', 'processing', '2025-10-04 18:54:44', '2025-10-09 17:02:25'),
(2, 4, 'ORD-20251004-4123', 'Eng Teddy', 'engteddy@gmail.com', '0711111111', 'nairobi', '', 641200.00, 0.00, 641200.00, 'mpesa', 'confirmed', 'delivered', '2025-10-04 19:03:26', '2025-10-07 15:22:18'),
(46, 1, '', '', '', '', 'Samin', '', 0.00, 0.00, 0.00, 'cash_on_delivery', 'pending', 'processing', '2025-10-07 17:46:44', '2025-10-09 17:02:53'),
(57, 8, 'ORD-20251009-8F2B', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '+254722334257', 'Kenya', '', 420.00, 200.00, 620.00, 'mpesa', 'confirmed', 'processing', '2025-10-09 17:25:36', '2025-10-09 17:25:36'),
(69, 9, 'ORD-20251010-4241', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '0722853859', 'Kenya', '', 50.00, 200.00, 250.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:17:19', '2025-10-10 08:17:19'),
(70, 9, 'ORD-20251010-1227', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '+254722334257', 'Kenya', '', 1330.00, 200.00, 1530.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:19:27', '2025-10-10 08:19:27'),
(71, 9, 'ORD-20251010-231F', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '+254722334257', 'Kenya', '', 250.00, 200.00, 450.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:28:14', '2025-10-10 08:28:15'),
(72, 9, 'ORD-20251010-505A', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '0722853859', 'Kenya', '', 50.00, 200.00, 250.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:31:08', '2025-10-10 08:31:08'),
(73, 9, 'ORD-20251010-00B3', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '+254722334257', 'Kenya', '', 80.00, 200.00, 280.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:34:42', '2025-10-10 08:34:42'),
(74, 9, 'ORD-20251010-6AE7', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '0722853859', 'Kenya', '', 80.00, 200.00, 280.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:35:48', '2025-10-10 08:35:48'),
(75, 9, 'ORD-20251010-5BBB', 'Dr. Barrack', 'barrackbarry2023@gmail.com', '0722853859', 'Kenya', '', 80.00, 200.00, 280.00, 'mpesa', '', 'pending_payment', '2025-10-10 08:39:57', '2025-10-10 08:39:57'),
(77, 1, 'ORD-20251010-EC77', 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', 'Kibera', '', 50.00, 200.00, 250.00, 'cash_on_delivery', 'pending', 'pending', '2025-10-10 10:17:55', '2025-10-10 10:17:55'),
(78, 1, 'ORD-20251010-A1F0', 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', 'Kibera', '', 50.00, 200.00, 250.00, 'mpesa', '', 'pending_payment', '2025-10-10 10:18:25', '2025-10-10 10:18:25'),
(79, 1, 'ORD-20251010-04D4', 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', 'Kibera', '', 50.00, 200.00, 250.00, 'cash_on_delivery', 'pending', 'pending', '2025-10-10 11:25:04', '2025-10-10 11:25:04'),
(80, 1, 'ORD-20251010-62B6', 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', 'Kibera', '', 50.00, 200.00, 250.00, 'mpesa', '', 'pending_payment', '2025-10-10 11:28:51', '2025-10-10 11:28:51'),
(81, 1, 'ORD-20251010-2014', 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', 'Kibera', '', 80.00, 200.00, 280.00, 'mpesa', '', 'pending_payment', '2025-10-10 11:38:35', '2025-10-10 11:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `quantity`, `price`, `total`, `created_at`) VALUES
(5, 1, 9, 'Beef Stew', 2200, 2200.00, 4840000.00, '2025-10-04 18:54:44'),
(14, 57, 12, 'Golden Flower Cookies', 2, 50.00, 100.00, '2025-10-09 17:25:36'),
(15, 57, 13, 'Confetti Celebration Muffins', 4, 80.00, 320.00, '2025-10-09 17:25:36'),
(16, 69, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 08:17:19'),
(17, 70, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 08:19:27'),
(18, 70, 13, 'Confetti Celebration Muffins', 16, 80.00, 1280.00, '2025-10-10 08:19:27'),
(19, 71, 12, 'Golden Flower Cookies', 5, 50.00, 250.00, '2025-10-10 08:28:14'),
(20, 72, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 08:31:08'),
(21, 73, 13, 'Confetti Celebration Muffins', 1, 80.00, 80.00, '2025-10-10 08:34:42'),
(22, 74, 13, 'Confetti Celebration Muffins', 1, 80.00, 80.00, '2025-10-10 08:35:48'),
(23, 75, 13, 'Confetti Celebration Muffins', 1, 80.00, 80.00, '2025-10-10 08:39:57'),
(24, 77, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 10:17:55'),
(25, 78, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 10:18:25'),
(26, 79, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 11:25:05'),
(27, 80, 12, 'Golden Flower Cookies', 1, 50.00, 50.00, '2025-10-10 11:28:51'),
(28, 81, 13, 'Confetti Celebration Muffins', 1, 80.00, 80.00, '2025-10-10 11:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `review_text` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_response` text DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `menu_item_id`, `user_id`, `customer_name`, `customer_email`, `rating`, `review_text`, `status`, `created_at`, `updated_at`, `admin_response`, `response_date`) VALUES
(1, 12, 9, '', '', 4, 'fghj', 'pending', '2025-10-16 07:55:26', '2025-10-16 07:55:26', NULL, NULL),
(2, 12, 9, '', '', 4, 'fghj', 'approved', '2025-10-16 07:56:22', '2025-10-16 07:58:03', NULL, NULL),
(3, 12, 9, '', '', 3, 'wonderfull', 'approved', '2025-10-16 07:56:40', '2025-10-16 07:57:06', NULL, NULL);

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
(4, 'site_address', '123 Food Street, Nakuru', 'contact', '2025-10-02 21:08:33', '2025-10-04 04:04:39'),
(5, 'delivery_fee', '300', 'delivery', '2025-10-02 21:08:33', '2025-10-04 04:04:39'),
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
  `role` enum('customer','admin','driver','delivery','ambassador') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID of the ambassador who referred this user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `phone`, `updated_at`, `created_by`) VALUES
(1, 'Barrack Oluoch', 'admin@example.com', '$2y$10$QCCK2/Uy4mS8HppcZpCDK.t5IgtV2lR6OY17oE8okIaB/XAuyQZ1G', 'admin', '2025-10-02 19:30:07', 'active', '0722334257', '2025-10-03 23:24:38', NULL),
(4, 'Eng Teddy', 'engteddy@gmail.com', '$2y$10$RJfwJPQtFjujKBqeOtx0EeCJRqCPAR42wBBm00YdAWYR9kpsCdqiq', 'customer', '2025-10-04 05:34:40', 'active', '0711111111', '2025-10-06 22:28:24', NULL),
(6, 'John Driver', 'driver@addinsmeals.com', '$2y$10$Yhnq5hNHAAYi5MjGqwqqR.oX9F5QOmURWL73TfcY7a47/hzbXPxCO', 'driver', '2025-10-06 21:22:08', 'active', '0712345678', '2025-10-06 22:58:02', NULL),
(7, 'Sharon Ambassador', 'ambassador@gmail.com', '$2y$10$RuQW.eRd8FponkuUyjv00.T89vXWapTIUejZU6ay1Eih3xRGER97a', 'ambassador', '2025-10-08 18:20:47', 'active', NULL, '2025-10-08 18:20:47', NULL),
(8, 'Barrack', 'barrackbarry2023@gmail.com', '$2y$10$cWZoY1uYheVWQDwywBnlz.G22Lc.WofhmheO4sXDjophPv.KukKsy', '', '2025-10-09 17:24:04', 'active', '+254722334257', '2025-10-09 17:24:04', NULL),
(9, 'Dr.  Barrack Test', 'oluochbarrackonyango@gmail.com', '$2y$10$yJGQJyQ3xkcoLMNZipNaauhlf98QePcvQkjAItYgAKvbCe56Udq7q', 'customer', '2025-10-10 07:58:07', 'active', '0734511918', '2025-10-16 08:10:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_reads`
--

CREATE TABLE `user_activity_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `menu_item_id`, `created_at`) VALUES
(5, 9, 12, '2025-10-16 07:35:27');

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`);

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
-- Indexes for table `customer_messages`
--
ALTER TABLE `customer_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_customer_email` (`customer_email`);

--
-- Indexes for table `customer_reviews`
--
ALTER TABLE `customer_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_items_category` (`category_id`);

--
-- Indexes for table `mpesa_payments`
--
ALTER TABLE `mpesa_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_reference` (`reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unsubscribe_token` (`unsubscribe_token`);

--
-- Indexes for table `newsletter_templates`
--
ALTER TABLE `newsletter_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_payment_status_status` (`payment_status`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_item_id` (`menu_item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_created_by` (`created_by`);

--
-- Indexes for table `user_activity_reads`
--
ALTER TABLE `user_activity_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_activity` (`user_id`,`activity_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_id` (`activity_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `catering_requests`
--
ALTER TABLE `catering_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_messages`
--
ALTER TABLE `customer_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `customer_reviews`
--
ALTER TABLE `customer_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `mpesa_payments`
--
ALTER TABLE `mpesa_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `newsletter_templates`
--
ALTER TABLE `newsletter_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_activity_reads`
--
ALTER TABLE `user_activity_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `mpesa_payments`
--
ALTER TABLE `mpesa_payments`
  ADD CONSTRAINT `mpesa_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_reads`
--
ALTER TABLE `user_activity_reads`
  ADD CONSTRAINT `user_activity_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_activity_reads_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `activity_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `password_resets`
--
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `used` (`used`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
