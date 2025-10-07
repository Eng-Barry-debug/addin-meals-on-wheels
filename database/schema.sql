-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 06:14 PM
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
(56, 1, '', 'updated', 'Updated customer message status to resolved', 'customer_message', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 19:50:20');

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
(0, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', '0722334257', '', 'none', 'abc testing', 'none', '2025-10-04 02:59:33', 'pending', '2025-10-04 02:59:33', NULL, NULL);

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
(2, '🌟 Empowering Young Entrepreneurs: The Addins Ambassadors Program', 'At Addins Meals on Wheels, we’re not just building a food platform — we’re building futures. One of the most exciting parts of our project is the Ambassadors Program, designed to empower young entrepreneurs, especially students, with real-world opportunities to grow, learn, and earn.\r\n\r\nWhat is the Addins Ambassadors Program?\r\n\r\nThe Ambassadors Program is a community-driven initiative where passionate individuals represent Addins Meals on Wheels in their schools, universities, and neighborhoods. Ambassadors don’t just promote the platform — they become part of a network that combines entrepreneurship, leadership, and technology.\r\n\r\nWhy It Matters\r\n\r\nIn a world where youth unemployment is high, we believe in giving young people the tools to succeed. The program provides:\r\n\r\nSkill Development: Training in digital marketing, customer service, and community engagement.\r\n\r\nFinancial Opportunities: Earn rewards and commissions by connecting people to Addins Meals on Wheels.\r\n\r\nNetworking: Join a community of like-minded young leaders and entrepreneurs.\r\n\r\nReal-World Experience: Gain practical exposure in sales, event planning, and brand building.\r\n\r\nHow the Platform Supports Ambassadors\r\n\r\nThe web platform comes with a dedicated Ambassador dashboard where participants can:\r\n\r\nApply online and track application status\r\n\r\nAccess training resources and program updates\r\n\r\nMonitor performance and engagement\r\n\r\nConnect with coordinators and fellow ambassadors\r\n\r\nOur Vision\r\n\r\nThe Ambassadors Program goes beyond business. It’s about inspiring youth to take charge, build confidence, and discover their potential. By combining technology with opportunity, we’re creating a ripple effect that impacts both communities and the individuals within them.\r\n\r\nJoin Us\r\n\r\nIf you’re a student, young professional, or simply passionate about making a difference in your community, the Addins Ambassadors Program is your chance to shine.\r\n\r\nTogether, we can serve meals, inspire change, and empower through technology.', 'Bonnie Computer Hub Team', 'published', '68def819a7ba9_1.png', '2025-10-04 03:53:53', 1),
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
(8, NULL, 'f60ho97o8vdmkfgdd0fpbbt5hl', '2025-10-06 16:04:12', '2025-10-06 16:04:12', 'active', NULL, 0.00);

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
(4, 'Appetizers 2.1', 'Tasty starters and snacks', NULL, 1, '2025-10-02 21:14:40', '2025-10-06 15:19:36'),
(5, 'Desserts', 'Sweet treats to finish your meal', NULL, 1, '2025-10-02 21:14:40', '2025-10-02 21:14:40'),
(6, 'Beverages 1', 'Refreshing drinks and beverages', NULL, 1, '2025-10-02 21:14:40', '2025-10-06 15:20:42'),
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
(20, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'Sasa', 'unread', NULL, NULL, '2025-10-04 16:46:11', '2025-10-04 16:46:11'),
(21, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'Poa', 'unread', NULL, NULL, '2025-10-04 16:47:32', '2025-10-04 16:47:32'),
(22, NULL, 'Eng Teddy', 'Eng Teddy@example.com', 'Chat Message', 'The system is working', 'unread', NULL, NULL, '2025-10-04 16:47:53', '2025-10-04 16:47:53'),
(23, NULL, 'Barrack Oluoch', 'Eng Teddy@example.com', 'Chat Message', 'Yeah', 'unread', NULL, NULL, '2025-10-04 16:48:11', '2025-10-04 16:48:11');

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
(16, 'Barrack Oluoch', 'oluochbarrackonyango@gmail.com', 'fyytrte832t4ui', 'new', NULL, NULL, '2025-10-04 18:21:56');

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
(2, 'Ugali', 'Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk Ugali aub abs  aks asf nb sdvf dfk', 3000.00, '68def828e8309.jpeg', 4, '2025-10-02 21:32:11', 1, '2025-10-06 15:20:05', 'active', 1),
(3, 'Chips', 'dsd asdbj nm sd asl saslf', 13000.00, '68def84c7df37.png', 4, '2025-10-02 21:45:56', 1, '2025-10-03 21:37:49', 'inactive', 0),
(5, 'Grilled Chicken', 'Succulent chicken breast marinated in aromatic herbs, garlic, and olive oil for 24 hours, then grilled to perfection. Served with a side of roasted vegetables and our signature herb butter. This dish offers a perfect balance of juicy tenderness and smoky flavor, making it a favorite among health-conscious customers who appreciate quality protein without compromising on taste.', 1800.00, '68def819a7ba9.png', 1, '2025-10-03 21:40:31', 1, '2025-10-03 22:41:02', 'active', 1),
(6, 'Caesar Salad', 'Crisp romaine lettuce hearts tossed in our homemade Caesar dressing made with fresh garlic, anchovies, parmesan cheese, and a hint of lemon zest. Topped with crunchy herb croutons, shaved parmesan, and perfectly poached eggs. This refreshing salad combines traditional flavors with modern presentation, offering a delightful mix of textures from crispy greens to creamy dressing.', 1200.00, '68def828e8309.jpeg', 7, '2025-10-03 21:40:31', 1, '2025-10-04 00:15:28', 'active', 1),
(7, 'Chocolate Cake', 'Rich, decadent chocolate cake made with premium Belgian dark chocolate and layered with smooth chocolate ganache. Each slice reveals a moist, fudgy interior that melts in your mouth, finished with a glossy chocolate glaze and edible gold dust. Perfect for chocolate lovers seeking an indulgent dessert experience that satisfies even the most discerning sweet tooth.', 800.00, '68def84c7df37.png', 3, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 1),
(8, 'Fresh Orange Juice', 'Freshly squeezed orange juice made from Valencia oranges picked at peak ripeness. Each glass contains the juice of 4-5 carefully selected oranges, providing natural sweetness without any added sugars or preservatives. This vibrant, citrusy beverage is rich in vitamin C and offers a refreshing burst of sunshine in every sip, perfect for starting your day or pairing with any meal.', 600.00, '68def819a7ba9.png', 6, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 0),
(9, 'Beef Stew', 'Tender beef chunks slow-cooked for hours in a rich, flavorful broth with carrots, potatoes, onions, and aromatic herbs. Our traditional recipe uses premium grass-fed beef that becomes fork-tender after simmering, absorbing all the savory flavors of the vegetables and spices. Served piping hot with crusty bread for dipping, this hearty stew is the ultimate comfort food for chilly evenings.', 2200.00, '68def828e8309.jpeg', 1, '2025-10-03 21:40:31', 1, '2025-10-03 21:40:31', 'active', 1),
(11, 'Jollof Rice', 'abc def gefh', 12000.00, '68e149bd9fba3.png', 4, '2025-10-04 16:20:54', 1, '2025-10-04 16:24:37', 'inactive', 0);

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
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `delivery_instructions`, `subtotal`, `delivery_fee`, `total`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD-20251004-D2B3', 'Barrack Oluoch', 'admin@example.com', '0722334257', 'Kibera', '', 99999999.99, 0.00, 99999999.99, 'mpesa', 'processing', '2025-10-04 18:54:44', '2025-10-05 17:38:20'),
(2, 4, 'ORD-20251004-4123', 'Eng Teddy', 'engteddy@gmail.com', '0711111111', 'nairobi', '', 641200.00, 0.00, 641200.00, 'mpesa', 'delivered', '2025-10-04 19:03:26', '2025-10-05 17:37:57'),
(3, 1, '', 'Barrack Oluoch', 'admin@example.com', '0722334257', 'nairobi', '', 0.00, 0.00, 0.00, 'mpesa', 'cancelled', '2025-10-05 16:40:57', '2025-10-05 17:26:33'),
(20, 1, 'ORD-20251005-24DC', 'javia', 'javia@gmail.com', '123456789', 'Kenya', 'None', 16114000.00, 0.00, 16114000.00, 'mpesa', 'processing', '2025-10-05 19:46:17', '2025-10-05 19:46:17');

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
(1, 1, 1, 'pizaa', 21001, 1000.00, 21001000.00, '2025-10-04 18:54:44'),
(2, 1, 2, 'Ugali', 12011201, 3000.00, 99999999.99, '2025-10-04 18:54:44'),
(3, 1, 6, 'Caesar Salad', 1201, 1200.00, 1441200.00, '2025-10-04 18:54:44'),
(4, 1, 7, 'Chocolate Cake', 1600, 800.00, 1280000.00, '2025-10-04 18:54:44'),
(5, 1, 9, 'Beef Stew', 2200, 2200.00, 4840000.00, '2025-10-04 18:54:44'),
(6, 2, 7, 'Chocolate Cake', 800, 800.00, 640000.00, '2025-10-04 19:03:26'),
(7, 2, 6, 'Caesar Salad', 1, 1200.00, 1200.00, '2025-10-04 19:03:26'),
(8, 20, 6, 'Caesar Salad', 1200, 1200.00, 1440000.00, '2025-10-05 19:46:17'),
(9, 20, 7, 'Chocolate Cake', 800, 800.00, 640000.00, '2025-10-05 19:46:17'),
(10, 20, 5, 'Grilled Chicken', 1800, 1800.00, 3240000.00, '2025-10-05 19:46:17'),
(11, 20, 2, 'Ugali', 3598, 3000.00, 10794000.00, '2025-10-05 19:46:17');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `phone`, `updated_at`) VALUES
(1, 'Barrack Oluoch', 'admin@example.com', '$2y$10$QCCK2/Uy4mS8HppcZpCDK.t5IgtV2lR6OY17oE8okIaB/XAuyQZ1G', 'admin', '2025-10-02 19:30:07', 'active', '0722334257', '2025-10-03 23:24:38'),
(4, 'Eng Teddy', 'engteddy@gmail.com', '$2y$10$RJfwJPQtFjujKBqeOtx0EeCJRqCPAR42wBBm00YdAWYR9kpsCdqiq', NULL, '2025-10-04 05:34:40', 'active', '0711111111', '2025-10-06 15:46:12');

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
(3, 1, 6, '2025-10-04 15:03:18');

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
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

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
