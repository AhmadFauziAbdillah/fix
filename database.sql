-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 07:16 PM
-- Server version: 10.4.16-MariaDB
-- PHP Version: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warranty_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `full_name`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$YViC0nKNvW.QpHyH0PY6cOI0IzrArzmkw.0EsviU2paWhu.iFnGNa', 'admin@example.com', 'Administrator', 1, '2025-10-28 08:38:04', '2025-10-28 15:37:01', '2025-10-28 15:38:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `warranty_id` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `username`, `action`, `warranty_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-27 17:50:25'),
(2, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-27 17:50:32'),
(3, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-27 17:51:38'),
(4, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-27 17:59:00'),
(5, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-27 18:01:58'),
(7, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-27 18:02:22'),
(8, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-28 00:57:12'),
(9, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-28 01:10:24'),
(10, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:12:07'),
(11, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-28 01:12:21'),
(12, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:13:24'),
(13, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:13:53'),
(14, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:18:21'),
(18, NULL, NULL, 'EXTEND', NULL, 'Extended warranty from K59PSTDA', '::1', NULL, '2025-10-28 01:19:41'),
(20, NULL, NULL, 'EXTEND', NULL, 'Extended warranty from K59PSTDA', '::1', NULL, '2025-10-28 01:20:03'),
(22, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-28 01:21:15'),
(23, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-28 01:33:23'),
(24, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:34:24'),
(25, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-28 01:34:43'),
(26, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 01:35:44'),
(27, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 01:36:15'),
(28, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-28 01:36:35'),
(29, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 01:37:03'),
(30, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-28 01:37:28'),
(31, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-28 01:38:20'),
(32, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-28 01:49:33'),
(33, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:50:24'),
(34, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp', '::1', NULL, '2025-10-28 01:53:41'),
(35, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp', '::1', NULL, '2025-10-28 01:53:47'),
(36, NULL, NULL, 'UPDATE', NULL, 'Updated warranty data', '::1', NULL, '2025-10-28 01:54:08'),
(37, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 01:54:31'),
(38, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp', '192.168.100.248', NULL, '2025-10-28 01:54:45'),
(39, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-28 01:55:44'),
(40, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '::1', NULL, '2025-10-28 01:56:38'),
(41, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '::1', NULL, '2025-10-28 01:57:51'),
(42, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '::1', NULL, '2025-10-28 01:58:13'),
(43, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 02:09:49'),
(44, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 02:11:30'),
(45, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.192', NULL, '2025-10-28 08:13:24'),
(46, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.192', NULL, '2025-10-28 08:13:45'),
(47, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp', '192.168.100.192', NULL, '2025-10-28 08:14:20'),
(49, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.192', NULL, '2025-10-28 08:14:52'),
(51, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.192', NULL, '2025-10-28 08:19:54'),
(52, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 08:24:25'),
(59, NULL, NULL, 'EXTEND', NULL, 'Extended warranty from K59', '192.168.100.248', NULL, '2025-10-28 08:25:18'),
(61, NULL, NULL, 'EXTEND', NULL, 'Extended warranty from K59AA', '192.168.100.248', NULL, '2025-10-28 08:28:39'),
(62, NULL, NULL, 'LOGOUT', NULL, 'Admin logged out', '192.168.100.248', NULL, '2025-10-28 08:29:00'),
(63, NULL, NULL, 'LOGIN', NULL, 'Admin logged in', '192.168.100.248', NULL, '2025-10-28 08:29:16'),
(66, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 08:30:02'),
(68, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 08:31:17'),
(70, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 08:33:06'),
(72, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 08:35:17'),
(73, NULL, NULL, 'EXTEND', NULL, 'Extended warranty from ADMIN', '192.168.100.248', NULL, '2025-10-28 08:35:30'),
(75, NULL, NULL, 'UPDATE_DURATION', NULL, 'Updated warranty duration to 11 days', '192.168.100.248', NULL, '2025-10-28 08:35:41'),
(76, NULL, NULL, 'UPDATE', NULL, 'Updated warranty data', '192.168.100.248', NULL, '2025-10-28 08:35:52'),
(77, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp', '192.168.100.248', NULL, '2025-10-28 08:35:56'),
(78, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp Bot', '192.168.100.248', NULL, '2025-10-28 08:45:12'),
(79, NULL, NULL, 'RESEND_WA', NULL, 'Resent warranty info via WhatsApp Bot', '192.168.100.248', NULL, '2025-10-28 08:45:54'),
(80, NULL, NULL, 'CREATE', NULL, 'Created new warranty', '192.168.100.248', NULL, '2025-10-28 08:46:59'),
(83, 2, 'admin', 'CREATE', NULL, 'Created new warranty', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 15:37:45'),
(85, 2, 'admin', 'LOGOUT', NULL, 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 15:37:50'),
(86, 1, 'admin', 'LOGIN', NULL, 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 15:38:04'),
(87, 1, 'admin', 'LOGOUT', NULL, 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 15:39:38');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `motorcycle_brand` varchar(50) DEFAULT NULL,
  `motorcycle_model` varchar(100) DEFAULT NULL,
  `motorcycle_year` int(11) DEFAULT NULL,
  `motorcycle_plate` varchar(20) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `unique_code` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'dana',
  `payment_status` enum('pending','paid','expired','cancelled') DEFAULT 'pending',
  `payment_proof` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `booking_status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `warranty_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `booking_logs`
--

CREATE TABLE `booking_logs` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payment_confirmations`
--

CREATE TABLE `payment_confirmations` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `proof_image` varchar(255) NOT NULL,
  `upload_time` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `warranty_days` int(11) DEFAULT 7,
  `is_active` tinyint(1) DEFAULT 1,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration_minutes`, `warranty_days`, `is_active`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Remap ECU Basic', 'Optimasi performa dasar dengan garansi 7 hari', '500000.00', 120, 7, 1, '🔧', '2025-10-30 08:06:20', '2025-10-30 08:06:20'),
(2, 'Remap ECU Premium', 'Optimasi performa maksimal dengan garansi 14 hari', '750000.00', 180, 14, 1, '⚡', '2025-10-30 08:06:20', '2025-10-30 08:06:20'),
(3, 'Tuning Full Service', 'Tuning lengkap + dynotest dengan garansi 30 hari', '1200000.00', 240, 30, 1, '🏍️', '2025-10-30 08:06:20', '2025-10-30 08:06:20'),
(4, 'Dyno Test', 'Pengetesan performa motor di dynamometer', '300000.00', 60, 0, 1, '📊', '2025-10-30 08:06:20', '2025-10-30 08:06:20'),
(5, 'Konsultasi Teknis', 'Konsultasi masalah performa motor', '150000.00', 45, 0, 1, '💬', '2025-10-30 08:06:20', '2025-10-30 08:06:20'),
(6, 'Remap ECU Basic', 'Optimasi performa dasar dengan garansi 7 hari', '500000.00', 120, 7, 1, '🔧', '2025-10-30 18:14:32', '2025-10-30 18:14:32'),
(7, 'Remap ECU Premium', 'Optimasi performa maksimal dengan garansi 14 hari', '750000.00', 180, 14, 1, '⚡', '2025-10-30 18:14:32', '2025-10-30 18:14:32'),
(8, 'Tuning Full Service', 'Tuning lengkap + dynotest dengan garansi 30 hari', '1200000.00', 240, 30, 1, '🏍️', '2025-10-30 18:14:32', '2025-10-30 18:14:32'),
(9, 'Dyno Test', 'Pengetesan performa motor di dynamometer', '300000.00', 60, 0, 1, '📊', '2025-10-30 18:14:32', '2025-10-30 18:14:32'),
(10, 'Konsultasi Teknis', 'Konsultasi masalah performa motor', '150000.00', 45, 0, 1, '💬', '2025-10-30 18:14:32', '2025-10-30 18:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `profile_photo` varchar(255) DEFAULT NULL,
  `registration_ip` varchar(45) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `is_verified`, `is_active`, `profile_photo`, `registration_ip`, `last_login`, `login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 'user@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', '081234567890', 1, 1, NULL, NULL, NULL, 0, NULL, '2025-10-30 03:33:45', '2025-10-30 03:33:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

CREATE TABLE `warranties` (
  `id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `nohp` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL,
  `registration_date` datetime NOT NULL,
  `expiry_date` datetime NOT NULL,
  `warranty_days` int(11) DEFAULT 7,
  `status` enum('active','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_warranty` (`warranty_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `warranty_id` (`warranty_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_booking_code` (`booking_code`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_booking_status` (`booking_status`),
  ADD KEY `idx_booking_date` (`booking_date`);

--
-- Indexes for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking` (`booking_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`reset_token`);

--
-- Indexes for table `payment_confirmations`
--
ALTER TABLE `payment_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_booking` (`booking_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_token` (`session_token`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nohp` (`nohp`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry` (`expiry_date`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_confirmations`
--
ALTER TABLE `payment_confirmations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD CONSTRAINT `booking_logs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_confirmations`
--
ALTER TABLE `payment_confirmations`
  ADD CONSTRAINT `payment_confirmations_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_confirmations_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warranties`
--
ALTER TABLE `warranties`
  ADD CONSTRAINT `warranties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
