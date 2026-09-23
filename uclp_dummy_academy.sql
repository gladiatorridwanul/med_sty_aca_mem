-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 23, 2026 at 06:06 AM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uclp_dummy_academy`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','editor','doctor') DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `user_id`, `user_type`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:15:50'),
(2, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:38:08'),
(3, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:40:43'),
(4, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:43:05'),
(5, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:43:16'),
(6, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:43:30'),
(7, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 05:55:06'),
(8, 1, 'admin', 'Added Doctor', 'Added doctor: Desktop Checking (desktopchecking@uclp.edu)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:31:11'),
(9, 1, 'admin', 'Updated Doctor', 'Updated doctor ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:31:30'),
(10, 1, 'admin', 'Added Doctor', 'Added doctor: Desktop Checking 2345 (desktopchecking123@uclp.edu)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:43:55'),
(11, 1, 'admin', 'Doctor Verification', 'Verified doctor ID: 4, Status: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:44:07'),
(12, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:51:23'),
(13, 2, 'editor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 06:51:37'),
(14, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:01:19'),
(15, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:01:19'),
(16, 2, 'editor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:01:32'),
(17, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:01:38'),
(18, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:01:38'),
(19, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:03:07'),
(20, NULL, 'doctor', 'Requested Supply', 'book_2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:22:33'),
(21, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:29:39'),
(22, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:29:39'),
(23, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:29:43'),
(24, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:30:45'),
(25, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:30:45'),
(26, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:30:50'),
(27, 1, 'admin', 'Processed Supply Request', 'Request ID: 1, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:30:59'),
(28, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:31:04'),
(29, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:31:04'),
(30, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:31:09'),
(31, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:35:59'),
(32, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:35:59'),
(33, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:36:06'),
(34, 1, 'admin', 'Updated Book', 'Updated book ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:36:27'),
(35, 1, 'admin', 'Updated Book', 'Updated book ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:36:39'),
(36, 1, 'admin', 'Updated Journal', 'Updated journal ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:36:59'),
(37, 1, 'admin', 'Updated Journal', 'Updated journal ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:11'),
(38, 1, 'admin', 'Updated Journal Status', 'Journal ID: 1, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:16'),
(39, 1, 'admin', 'Updated Journal', 'Updated journal ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:23'),
(40, 1, 'admin', 'Updated Journal Status', 'Journal ID: 2, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:26'),
(41, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:32'),
(42, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:32'),
(43, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:37:36'),
(44, NULL, 'doctor', 'Download', 'Downloaded book: Neurology Essentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:38:33'),
(45, NULL, 'doctor', 'Download', 'Downloaded book: Neurology Essentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:44:58'),
(46, NULL, 'doctor', 'Requested Download Permission', 'book_2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:22:52'),
(47, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:26:49'),
(48, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:26:49'),
(49, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:26:57'),
(50, 1, 'admin', 'Processed Download Permission', 'Permission ID: 1, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:33:37'),
(51, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:12'),
(52, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:12'),
(53, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:16'),
(54, NULL, 'doctor', 'Download', 'Downloaded book: Neurology Essentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:28'),
(55, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:38:35'),
(56, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:38:35'),
(57, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:38:40'),
(58, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:39:11'),
(59, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:39:11'),
(60, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:39:15'),
(61, NULL, 'doctor', 'Requested Download Permission', 'journal_1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:44:42'),
(62, NULL, 'doctor', 'Requested Download Permission', 'book_1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:45:00'),
(63, NULL, 'doctor', 'Requested Download Permission (Renewal)', 'book_2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:48:45'),
(64, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:04'),
(65, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:04'),
(66, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:08'),
(67, 1, 'admin', 'Processed Download Permission', 'Permission ID: 4, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:19'),
(68, 1, 'admin', 'Processed Download Permission', 'Permission ID: 3, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:21'),
(69, 1, 'admin', 'Processed Download Permission', 'Permission ID: 2, Status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:24'),
(70, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:28'),
(71, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:28'),
(72, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:33'),
(73, NULL, 'doctor', 'Download', 'Downloaded book: Textbook of Cardiology', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:41'),
(74, NULL, 'doctor', 'Download', 'Downloaded book: Textbook of Cardiology', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:49:43'),
(75, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:53:16'),
(76, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:53:16'),
(77, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:53:31'),
(78, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:40'),
(79, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:40'),
(80, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:43'),
(81, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:20'),
(82, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:20'),
(83, 2, 'editor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:25'),
(84, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:42'),
(85, 2, 'editor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:42'),
(86, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:04:47'),
(87, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:23:26'),
(88, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:23:26'),
(89, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:26:04'),
(90, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:26:15'),
(91, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:26:15'),
(92, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:26:21'),
(93, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:29:53'),
(94, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:29:53'),
(95, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:29:56'),
(96, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:09'),
(97, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:09'),
(98, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:13'),
(99, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:54'),
(100, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:54'),
(101, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:32:57'),
(102, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:33:03'),
(103, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:33:03'),
(104, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:56:52'),
(105, 1, 'admin', 'Doctor Verification', 'Verified doctor ID: 3, Status: 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:58:06'),
(106, 1, 'admin', 'Doctor Verification', 'Verified doctor ID: 3, Status: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:58:09'),
(107, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:59:50'),
(108, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:59:50'),
(109, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:59:56'),
(110, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 10:00:02'),
(111, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 10:00:02'),
(112, NULL, 'doctor', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:44:33'),
(113, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:47:26'),
(114, NULL, 'doctor', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:47:26'),
(115, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:47:31'),
(116, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:51:57'),
(117, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:51:57'),
(118, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:52:00'),
(119, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:52:36'),
(120, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:52:36'),
(121, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:52:38'),
(122, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:53:13'),
(123, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:53:13'),
(124, 1, 'admin', 'User Login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:53:16'),
(125, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:55:40'),
(126, 1, 'admin', 'User Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 11:55:40'),
(127, 1, 'admin', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:51:44'),
(128, 1, 'admin', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:58:59'),
(129, 1, 'admin', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:58:59'),
(130, 1, 'admin', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:59:07'),
(131, 1, 'admin', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:15:44'),
(132, 1, 'admin', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:15:44'),
(133, 2, 'editor', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:16:20'),
(134, 2, 'editor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:31:35'),
(135, 2, 'editor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:31:35'),
(136, 2, 'editor', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:31:39'),
(137, 2, 'editor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:31:57'),
(138, 2, 'editor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:31:57'),
(139, NULL, 'doctor', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:32:31'),
(140, NULL, 'doctor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:33:07'),
(141, NULL, 'doctor', 'User Logout', 'User logged out', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 17:33:07'),
(142, 1, 'admin', 'User Login', 'User logged in successfully', '118.179.27.143', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 06:39:30'),
(143, 1, 'admin', 'User Login', 'User logged in successfully', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 15:35:08'),
(144, 1, 'admin', 'Added Book', 'Added book: Test', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 15:38:10'),
(145, 1, 'admin', 'Updated Book Status', 'Book ID: 3, Status: approved', '103.155.98.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 15:38:15'),
(146, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:30:22'),
(147, 1, 'admin', 'Added Journal', 'Added journal: Test', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:11'),
(148, 1, 'admin', 'Updated Journal Status', 'Journal ID: 3, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:14'),
(149, 1, 'admin', 'Updated Journal Status', 'Journal ID: 3, Status: pending', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:16'),
(150, 1, 'admin', 'Updated Book', 'Updated book ID: 3', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:39'),
(151, 1, 'admin', 'Updated Journal Status', 'Journal ID: 3, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:47'),
(152, 1, 'admin', 'Updated Journal', 'Updated journal ID: 3', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:41:43'),
(153, 1, 'admin', 'Added Doctor', 'Added doctor: Testing Doctor (testingdoctor@ucpl.academy)', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:42:52'),
(154, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:43:19'),
(155, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:43:19'),
(156, NULL, 'doctor', 'User Registration', 'New doctor registered', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:25'),
(157, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:42'),
(158, 1, 'admin', 'Doctor Verification', 'Verified doctor ID: 6, Status: 1', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:51'),
(159, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:52'),
(160, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:52'),
(161, NULL, 'doctor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:44:57'),
(162, NULL, 'doctor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:45:24'),
(163, NULL, 'doctor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:45:24'),
(164, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:53:02'),
(165, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:53:05'),
(166, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:53:05'),
(167, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:53:24'),
(168, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:00:33'),
(169, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:08:32'),
(170, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:08:32'),
(171, 2, 'editor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:08:49'),
(172, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:14:31'),
(173, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:14:31'),
(174, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:14:40'),
(175, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 03:20:57'),
(176, 1, 'admin', 'Added Book', 'Added book: Test 001', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:40:51'),
(177, 1, 'admin', 'Updated Book', 'Updated book ID: 4', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:41:03'),
(178, 1, 'admin', 'Updated Book Status', 'Book ID: 4, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:41:08'),
(179, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 03:41:48'),
(180, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:08:32'),
(181, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:08:32'),
(182, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:09:34'),
(183, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:15:20'),
(184, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:27:48'),
(185, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:27:48'),
(186, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:31:24'),
(187, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:31:32'),
(188, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:31:32'),
(189, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:38:26'),
(190, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:40:34'),
(191, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:40:34'),
(192, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 04:40:38'),
(193, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:40:46'),
(194, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:40:46'),
(195, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:40:49'),
(196, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:51:09'),
(197, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 04:51:09'),
(198, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:12:14'),
(199, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:12:21'),
(200, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:12:21'),
(201, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:13:12'),
(202, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 05:19:38'),
(203, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:20:49'),
(204, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 05:34:52'),
(205, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 05:34:52'),
(206, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 05:34:56'),
(207, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:35:22'),
(208, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:35:22'),
(209, 2, 'editor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:35:46'),
(210, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:36:02'),
(211, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:36:02'),
(212, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 05:54:01'),
(213, 1, 'admin', 'Updated Journal', 'Updated journal ID: 3', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 06:33:17'),
(214, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 06:34:07'),
(215, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 06:34:38'),
(216, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 06:34:38'),
(217, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 06:35:43'),
(218, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 06:35:43'),
(219, NULL, 'doctor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 06:51:50'),
(220, NULL, 'doctor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:04:22'),
(221, NULL, 'doctor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:04:22'),
(222, 2, 'editor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:04:28'),
(223, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:07:29'),
(224, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:07:29'),
(225, 2, 'editor', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 07:07:42'),
(226, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 07:08:05'),
(227, 2, 'editor', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 07:08:05'),
(228, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:08:29'),
(229, 1, 'admin', 'Added Book', 'Added book: General Specialty Surgical Instruments', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:18:30'),
(230, 1, 'admin', 'Added Book', 'Added book: Kaplan Medical Surgery', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:19:45'),
(231, 1, 'admin', 'Updated Book Status', 'Book ID: 6, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:19:50'),
(232, 1, 'admin', 'Updated Book Status', 'Book ID: 5, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:19:51'),
(233, 1, 'admin', 'Added Book', 'Added book: Langmans Medical Embryology 12th Edi.', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:21:33'),
(234, 1, 'admin', 'Added Book', 'Added book: Lipincott&#039;s Atlas of Anatomy', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:23:13'),
(235, 1, 'admin', 'Added Book', 'Added book: Lippincott&#039;s Pharmacology 5th Edi.', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:24:47'),
(236, 1, 'admin', 'Deleted Book', 'Deleted book ID: 4', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:24:58'),
(237, 1, 'admin', 'Deleted Book', 'Deleted book ID: 3', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:01'),
(238, 1, 'admin', 'Deleted Book', 'Deleted book ID: 1', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:04');
INSERT INTO `audit_trail` (`id`, `user_id`, `user_type`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(239, 1, 'admin', 'Deleted Book', 'Deleted book ID: 2', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:06'),
(240, 1, 'admin', 'Updated Book Status', 'Book ID: 9, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:48'),
(241, 1, 'admin', 'Updated Book Status', 'Book ID: 8, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:50'),
(242, 1, 'admin', 'Updated Book Status', 'Book ID: 7, Status: approved', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 07:25:51'),
(243, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 10:29:52'),
(244, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 10:51:44'),
(245, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 10:51:44'),
(246, 1, 'admin', 'User Login', 'User logged in successfully', '103.140.177.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-16 11:13:37'),
(247, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 11:14:30'),
(248, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 11:15:32'),
(249, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 11:15:32'),
(250, 1, 'admin', 'Added Book', 'Added book: AAOS Comprehensive Orthopaedic Review - Study Questions, 1st ed', '103.140.177.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-16 11:21:29'),
(251, 1, 'admin', 'Updated Book Status', 'Book ID: 10, Status: approved', '103.140.177.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-16 11:21:39'),
(252, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-16 11:39:18'),
(253, 1, 'admin', 'Added Book', 'Added book: General Specialty Surgical Instruments', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-16 12:33:30'),
(254, 1, 'admin', 'Updated Book Status', 'Book ID: 11, Status: approved', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-16 12:41:22'),
(255, 1, 'admin', 'User Login', 'User logged in successfully', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 13:52:21'),
(256, 1, 'admin', 'Added Book', 'Added book: IAP Textbook of Pediatrics 4th Ed', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:36:39'),
(257, 1, 'admin', 'Updated Book Status', 'Book ID: 12, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:36:46'),
(258, 1, 'admin', 'User Login', 'User logged in successfully', '37.111.200.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-16 14:59:52'),
(259, 1, 'admin', 'Added Book', 'Added book: Operative Techniques in Orthopaedic Surgical Oncology', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:04:46'),
(260, 1, 'admin', 'Updated Book Status', 'Book ID: 13, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:04:52'),
(261, 1, 'admin', 'Added Book', 'Added book: Ortho Notes: Clinical Examination Pocket Guide', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:08:24'),
(262, 1, 'admin', 'Updated Book Status', 'Book ID: 14, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:08:29'),
(263, 1, 'admin', 'Added Book', 'Added book: Apley’s System of Orthopaedics and Fractures', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:09:41'),
(264, 1, 'admin', 'Updated Book Status', 'Book ID: 15, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:10:01'),
(265, 1, 'admin', 'Added Book', 'Added book: Rapid Review Physiology', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:12:49'),
(266, 1, 'admin', 'Updated Book Status', 'Book ID: 16, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:14:42'),
(267, 1, 'admin', 'Added Book', 'Added book: Netter&#039;s Orthopaedic Clinical Examination', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:16:39'),
(268, 1, 'admin', 'Updated Book Status', 'Book ID: 17, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:16:50'),
(269, 1, 'admin', 'Added Book', 'Added book: Preoperative Assessment and Management', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:41:19'),
(270, 1, 'admin', 'Updated Book Status', 'Book ID: 18, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:44:19'),
(271, 1, 'admin', 'Added Book', 'Added book: Oxford Handbook of Operative Surgery', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:47:34'),
(272, 1, 'admin', 'Added Book', 'Added book: Williams obstetrics 23rd Ed', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:56:58'),
(273, 1, 'admin', 'Updated Book Status', 'Book ID: 19, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:57:09'),
(274, 1, 'admin', 'Updated Book Status', 'Book ID: 20, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:57:11'),
(275, 1, 'admin', 'Added Book', 'Added book: Snell&#039;s Clinical Neuroanatomy', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:12:54'),
(276, 1, 'admin', 'Updated Book Status', 'Book ID: 21, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:13:03'),
(277, 1, 'admin', 'Added Book', 'Added book: Pocket Guide to the Operating Room', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:16:05'),
(278, 1, 'admin', 'Updated Book Status', 'Book ID: 22, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:16:09'),
(279, 1, 'admin', 'Added Book', 'Added book: Robbins, Cotran &amp; Kumar Pathologic Basis of Disease', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:45:30'),
(280, 1, 'admin', 'Updated Book Status', 'Book ID: 23, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:00:26'),
(281, 1, 'admin', 'Added Book', 'Added book: Operative Techniques: Knee Surgery', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:04:17'),
(282, 1, 'admin', 'Updated Book Status', 'Book ID: 24, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:04:22'),
(283, 1, 'admin', 'Added Book', 'Added book: Miller&#039;s Review of Orthopaedics', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:09:27'),
(284, 1, 'admin', 'Updated Book Status', 'Book ID: 25, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:09:33'),
(285, 1, 'admin', 'Added Book', 'Added book: Schwartz&#039;s Manual of Surgery', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:11:59'),
(286, 1, 'admin', 'Added Book', 'Added book: Long Cases in General Surgery', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:14:34'),
(287, 1, 'admin', 'Updated Book Status', 'Book ID: 26, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:14:42'),
(288, 1, 'admin', 'Updated Book Status', 'Book ID: 27, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:14:45'),
(289, 1, 'admin', 'Added Book', 'Added book: Imaging for Surgical Disease', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:22:38'),
(290, 1, 'admin', 'Updated Book', 'Updated book ID: 28', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:23:48'),
(291, 1, 'admin', 'Updated Book Status', 'Book ID: 28, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:24:03'),
(292, 1, 'admin', 'Added Book', 'Added book: Browse&#039;s Introduction to the Investigation and Management of Surgical Disease', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:28:44'),
(293, 1, 'admin', 'Updated Book Status', 'Book ID: 29, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:28:54'),
(294, 1, 'admin', 'Added Book', 'Added book: Campbell&#039;s Operative Orthopaedics', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:33:21'),
(295, 1, 'admin', 'Updated Book Status', 'Book ID: 30, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:33:34'),
(296, NULL, 'doctor', 'User Registration', 'New doctor registered', '103.85.243.73', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_6_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/152.0.7977.40 Mobile/15E148 Safari/604.1', '2026-08-16 17:33:36'),
(297, 1, 'admin', 'Added Book', 'Added book: Really Essential Medical Immunology', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:37:32'),
(298, 1, 'admin', 'Updated Book Status', 'Book ID: 31, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:37:41'),
(299, 1, 'admin', 'Added Book', 'Added book: Textbook of Arthroscopy', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:44:13'),
(300, 1, 'admin', 'Updated Book Status', 'Book ID: 32, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:44:20'),
(301, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 02:48:29'),
(302, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:09:38'),
(303, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:10:01'),
(304, 1, 'admin', 'Added Book', 'Added book: Imaging For Surgical Disease 7th Ed', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:14:39'),
(305, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:14:39'),
(306, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:14:45'),
(307, 1, 'admin', 'Updated Book Status', 'Book ID: 33, Status: approved', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:14:45'),
(308, 1, 'admin', 'Added Book', 'Added book: Kaplan Medical Surgery Notes', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:20:47'),
(309, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:20:47'),
(310, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:20:56'),
(311, 1, 'admin', 'Updated Book Status', 'Book ID: 34, Status: approved', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:20:56'),
(312, 1, 'admin', 'Added Book', 'Added book: Lippincott_s Pharmacology 5th Ed', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:29:59'),
(313, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:29:59'),
(314, 1, 'admin', 'Deleted Book', 'Deleted book ID: 11', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:30:05'),
(315, 1, 'admin', 'Updated Book Status', 'Book ID: 35, Status: approved', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 03:30:05'),
(316, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 05:00:11'),
(317, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 05:21:56'),
(318, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 05:21:56'),
(319, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 06:20:45'),
(320, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 07:07:08'),
(321, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 07:07:08'),
(322, 1, 'admin', 'User Login', 'User logged in successfully', '115.127.26.210', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 11:48:21'),
(323, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 07:21:03'),
(324, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 07:22:02'),
(325, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 07:22:02'),
(326, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 11:23:00'),
(327, 1, 'admin', 'User Login', 'User logged in successfully', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 05:57:36'),
(328, 1, 'admin', 'Added Book', 'Added book: Shoulder Arthroscopy: Principles and Practice', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:08:12'),
(329, 1, 'admin', 'Updated Book Status', 'Book ID: 36, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:08:19'),
(330, 1, 'admin', 'Added Book', 'Added book: Hand Surgery', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:11:01'),
(331, 1, 'admin', 'Updated Book Status', 'Book ID: 37, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:11:20'),
(332, 1, 'admin', 'Added Book', 'Added book: Oxford Handbook of Trauma and Orthopaedics', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:18:41'),
(333, 1, 'admin', 'Updated Book Status', 'Book ID: 38, Status: approved', '118.179.124.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 06:18:46'),
(334, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 02:39:19'),
(335, 1, 'admin', 'Requested Download Permission', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 02:48:23'),
(336, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 02:49:52'),
(337, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 02:50:33'),
(338, 1, 'admin', 'Requested Supply', 'book_36', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 02:58:05'),
(339, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 03:01:19'),
(340, 1, 'admin', 'Requested Supply', 'book_25', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 03:03:45'),
(341, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 03:05:23'),
(342, 1, 'admin', 'Requested Supply', 'book_25', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 03:26:50'),
(343, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:14:55'),
(344, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:18:21'),
(345, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:18:54'),
(346, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:39:14'),
(347, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:40:58'),
(348, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 11; rk3568_r) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', '2026-08-22 05:43:33'),
(349, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:09:46'),
(350, 1, 'admin', 'Requested Supply', 'book_30', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:15:44'),
(351, 1, 'admin', 'Requested Supply', 'book_15', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:19:06'),
(352, 1, 'admin', 'Requested Supply', 'book_15', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:21:11'),
(353, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:32:33'),
(354, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:44:50'),
(355, 1, 'admin', 'Requested Supply', 'book_36', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:47:34'),
(356, 1, 'admin', 'Requested Supply', 'book_25', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 06:59:01'),
(357, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:00:19'),
(358, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:01:51'),
(359, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:04:34'),
(360, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:05:37'),
(361, 1, 'admin', 'Requested Supply', 'book_25', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:18:09'),
(362, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:19:19'),
(363, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:20:42'),
(364, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:22:37'),
(365, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:24:55'),
(366, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:26:11'),
(367, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:28:32'),
(368, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:30:49'),
(369, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:32:23'),
(370, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:33:36'),
(371, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:36:23'),
(372, 1, 'admin', 'Requested Supply', 'book_36', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:38:48'),
(373, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:45:48'),
(374, 1, 'admin', 'Processed Supply Request', 'Request ID: 7, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:47:30'),
(375, 1, 'admin', 'Requested Supply', 'book_30', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:50:33'),
(376, 1, 'admin', 'Requested Supply', 'book_25', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:51:26'),
(377, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:52:26'),
(378, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:54:04'),
(379, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:55:17'),
(380, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:56:13'),
(381, 1, 'admin', 'Requested Supply', 'book_30', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:57:45'),
(382, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:58:39'),
(383, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 07:59:54'),
(384, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 08:00:45'),
(385, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:45:31'),
(386, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:50:31'),
(387, 1, 'admin', 'Requested Supply', 'book_30', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:53:31'),
(388, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:55:47'),
(389, 1, 'admin', 'Requested Supply', 'book_13', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:59:05'),
(390, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:00:19'),
(391, 1, 'admin', 'Requested Supply', 'book_36', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:01:31'),
(392, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:03:05'),
(393, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:04:02'),
(394, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:05:50'),
(395, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:07:38'),
(396, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:21:01'),
(397, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:23:36'),
(398, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:24:52'),
(399, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:27:40'),
(400, 1, 'admin', 'Requested Supply', 'book_17', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:29:03'),
(401, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:30:09'),
(402, 1, 'admin', 'Requested Supply', 'book_30', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:32:19'),
(403, 1, 'admin', 'Requested Supply', 'book_10', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:34:02'),
(404, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:41:24'),
(405, 1, 'admin', 'Processed Supply Request', 'Request ID: 4, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:42:23'),
(406, 1, 'admin', 'Processed Supply Request', 'Request ID: 3, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:42:34'),
(407, 1, 'admin', 'Processed Supply Request', 'Request ID: 2, Status: approved', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:42:45'),
(408, 1, 'admin', 'Processed Supply Request', 'Request ID: 5, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:43:21'),
(409, 1, 'admin', 'Processed Supply Request', 'Request ID: 6, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:43:32'),
(410, 1, 'admin', 'Processed Supply Request', 'Request ID: 8, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:43:40'),
(411, 1, 'admin', 'Processed Supply Request', 'Request ID: 9, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:50:56'),
(412, 1, 'admin', 'Processed Supply Request', 'Request ID: 15, Status: rejected', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:51:33'),
(413, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:19:49'),
(414, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:23:48'),
(415, 1, 'admin', 'Requested Supply', 'book_37', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:26:37'),
(416, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:28:53'),
(417, 1, 'admin', 'Requested Supply', 'book_32', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:30:03'),
(418, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 15:32:07'),
(419, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 03:06:37'),
(420, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 03:30:39'),
(421, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 03:51:07'),
(422, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 03:51:07'),
(423, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:07:46'),
(424, 1, 'admin', 'Requested Supply', 'book_24', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:09:13'),
(425, 1, 'admin', 'Requested Supply', 'book_36', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:20:07'),
(426, 1, 'admin', 'Requested Supply', 'book_38', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:25:59'),
(427, 1, 'admin', 'User Login', 'User logged in successfully', '119.148.2.124', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:45:38'),
(428, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 03:55:36'),
(429, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 03:55:46'),
(430, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 03:55:46'),
(431, 1, 'admin', 'User Login', 'User logged in successfully', '49.229.236.180', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-09-06 20:55:29'),
(432, 1, 'admin', 'User Login', 'User logged in successfully', '37.111.213.169', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Mobile Safari/537.36', '2026-09-08 09:48:24'),
(433, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 09:57:14'),
(434, 1, 'admin', 'Processed Download Permission', 'Permission ID: 5, Status: approved', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 09:58:56'),
(435, 1, 'admin', 'Download', 'Downloaded book: Oxford Handbook of Trauma and Orthopaedics', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 09:59:15'),
(436, 1, 'admin', 'User Logout', 'User logged out', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 10:01:33'),
(437, 1, 'admin', 'User Logout', 'User logged out', '27.147.132.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 10:01:33'),
(438, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 03:42:28'),
(439, 1, 'admin', 'Added Journal', 'Added journal: Testing Journal', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 03:47:21'),
(440, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 03:48:47'),
(441, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 03:48:47'),
(442, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 06:10:49'),
(443, 1, 'admin', 'Updated Journal', 'Updated journal ID: 4', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 06:18:16'),
(444, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:07:19'),
(445, 1, 'admin', 'Added Committee Member', 'Name: Testing', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:25:41'),
(446, 1, 'admin', 'Updated Committee Member', 'ID: 1', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:26:03'),
(447, 1, 'admin', 'Updated Committee Member', 'ID: 1', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:26:44'),
(448, 1, 'admin', 'Added Committee Member', 'Name: Testing Secretary', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:38:20'),
(449, 1, 'admin', 'Updated Committee Member', 'ID: 2', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:38:31'),
(450, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:56:21'),
(451, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 07:56:21'),
(452, 1, 'admin', 'User Login', 'User logged in successfully', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 08:57:21'),
(453, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 08:57:48'),
(454, 1, 'admin', 'User Logout', 'User logged out', '27.147.137.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 08:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(200) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_watermarked` tinyint(1) DEFAULT 1,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `committee_members`
--

CREATE TABLE `committee_members` (
  `id` int(11) NOT NULL,
  `year_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_members`
--

INSERT INTO `committee_members` (`id`, `year_id`, `designation_id`, `name`, `photo`, `affiliation`, `bio`, `email`, `phone`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Testing', '1789975604_1fab7bf5d1268d27.jpg', 'Checking', 'Checking\r\nChecking', 'Checking@Checking.com', '', 0, 1, '2026-09-21 07:25:41', '2026-09-21 07:26:44'),
(2, 1, 3, 'Testing Secretary', '1789976311_d2ec64b8aed0a7e0.jpg', 'Checking', 'Checking\r\nChecking\r\nChecking', 'Checking@Checking.com', '01600000000', 0, 1, '2026-09-21 07:38:20', '2026-09-21 07:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `committee_years`
--

CREATE TABLE `committee_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_years`
--

INSERT INTO `committee_years` (`id`, `year_label`, `start_date`, `end_date`, `is_current`, `status`, `created_at`) VALUES
(1, '2024-2025', NULL, NULL, 1, 1, '2026-09-21 06:47:22'),
(2, '2023-2024', NULL, NULL, 0, 1, '2026-09-21 06:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `name`, `description`, `sort_order`, `status`, `created_at`) VALUES
(1, 'President', 'Head of the committee', 1, 1, '2026-09-21 06:47:22'),
(2, 'Vice President', 'Second in command', 2, 1, '2026-09-21 06:47:22'),
(3, 'Secretary General', 'Chief administrative officer', 3, 1, '2026-09-21 06:47:22'),
(4, 'Treasurer', 'Manages finances', 4, 1, '2026-09-21 06:47:22'),
(5, 'Joint Secretary', 'Assists the Secretary', 5, 1, '2026-09-21 06:47:22'),
(6, 'Organizing Secretary', 'Handles events & logistics', 6, 1, '2026-09-21 06:47:22'),
(7, 'Scientific Secretary', 'Coordinates academic activities', 7, 1, '2026-09-21 06:47:22'),
(8, 'Public Relations Secretary', 'Handles PR & communications', 8, 1, '2026-09-21 06:47:22'),
(9, 'Executive Member', 'Governing body member', 9, 1, '2026-09-21 06:47:22'),
(10, 'Advisor', 'Senior advisory role', 10, 1, '2026-09-21 06:47:22'),
(11, 'Editor', 'Journal editorial role', 11, 1, '2026-09-21 06:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `download_permissions`
--

CREATE TABLE `download_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('book','journal') NOT NULL,
  `item_id` int(11) NOT NULL,
  `max_downloads` int(11) DEFAULT 2,
  `used_downloads` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected','expired') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journals`
--

CREATE TABLE `journals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `journal_name` varchar(200) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `issue` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `volume` varchar(50) DEFAULT NULL,
  `pages` varchar(50) DEFAULT NULL,
  `doi` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `is_watermarked` tinyint(1) DEFAULT 1,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journals`
--

INSERT INTO `journals` (`id`, `title`, `journal_name`, `specialty_id`, `issue`, `date`, `volume`, `pages`, `doi`, `file_path`, `cover_image`, `abstract`, `is_watermarked`, `status`, `uploaded_by`, `download_count`, `view_count`, `created_at`, `updated_at`) VALUES
(4, 'Testing Journal', 'Testing Journal', 35, 'I-1', '2005-12-12', 'V-1', '0-30', '', '1789962441_cc67b58d762ddec8.pdf', '1789971496_b19ad3a54440c3b0.jpg', 'Testing Journal\r\nTesting Journal', 1, 'approved', 1, 0, 2, '2026-09-21 03:47:21', '2026-09-21 06:18:16');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 'Allergy & Immunology', NULL, 1, '2026-08-13 03:44:44'),
(2, 'Anaesthesiology', NULL, 1, '2026-08-13 03:44:44'),
(3, 'Cardiology', NULL, 1, '2026-08-13 03:44:44'),
(4, 'Clinical Haematology', NULL, 1, '2026-08-13 03:44:44'),
(5, 'Critical Care Medicine', NULL, 1, '2026-08-13 03:44:44'),
(6, 'Dermatology & Venereology', NULL, 1, '2026-08-13 03:44:44'),
(7, 'Diabetology', NULL, 1, '2026-08-13 03:44:44'),
(8, 'Emergency Medicine', NULL, 1, '2026-08-13 03:44:44'),
(9, 'ENT', NULL, 1, '2026-08-13 03:44:44'),
(10, 'Endocrinology & Metabolism', NULL, 1, '2026-08-13 03:44:44'),
(11, 'Family Medicine', NULL, 1, '2026-08-13 03:44:44'),
(12, 'Gastroenterology', NULL, 1, '2026-08-13 03:44:44'),
(13, 'Hepatology', NULL, 1, '2026-08-13 03:44:44'),
(14, 'Infectious Diseases', NULL, 1, '2026-08-13 03:44:44'),
(15, 'Internal Medicine', NULL, 1, '2026-08-13 03:44:44'),
(16, 'Interventional Cardiology', NULL, 1, '2026-08-13 03:44:44'),
(17, 'Medical Oncology', NULL, 1, '2026-08-13 03:44:44'),
(18, 'Nephrology', NULL, 1, '2026-08-13 03:44:44'),
(19, 'Neurology', NULL, 1, '2026-08-13 03:44:44'),
(20, 'Neuro Medicine', NULL, 1, '2026-08-13 03:44:44'),
(21, 'Nuclear Medicine', NULL, 1, '2026-08-13 03:44:44'),
(22, 'Paediatric Medicine', NULL, 1, '2026-08-13 03:44:44'),
(23, 'Paediatric Neurology', NULL, 1, '2026-08-13 03:44:44'),
(24, 'Pain Medicine', NULL, 1, '2026-08-13 03:44:44'),
(25, 'Palliative Medicine', NULL, 1, '2026-08-13 03:44:44'),
(26, 'Physical Medicine & Rehabilitation', NULL, 1, '2026-08-13 03:44:44'),
(27, 'Psychiatry', NULL, 1, '2026-08-13 03:44:44'),
(28, 'Pulmonology / Respiratory Medicine', NULL, 1, '2026-08-13 03:44:44'),
(29, 'Rheumatology', NULL, 1, '2026-08-13 03:44:44'),
(30, 'Sleep Medicine', NULL, 1, '2026-08-13 03:44:44'),
(31, 'Sports Medicine', NULL, 1, '2026-08-13 03:44:44'),
(32, 'Surgery', NULL, 1, '2026-08-13 03:44:44'),
(33, 'Tropical Medicine', NULL, 1, '2026-08-13 03:44:44'),
(34, 'Transfusion Medicine', NULL, 1, '2026-08-13 03:44:44'),
(35, 'Orthopaedics', 'Orthopaedics', 1, '2026-08-16 10:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `supply_requests`
--

CREATE TABLE `supply_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `journal_id` int(11) DEFAULT NULL,
  `request_type` enum('book','journal') NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_type` enum('doctor','admin','editor') NOT NULL DEFAULT 'doctor',
  `name` varchar(100) NOT NULL,
  `bmdc_reg_no` varchar(50) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `hospital_institute` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `download_limit` int(11) DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_type`, `name`, `bmdc_reg_no`, `specialty`, `hospital_institute`, `mobile`, `email`, `password`, `profile_image`, `is_verified`, `is_active`, `download_limit`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Admin User', NULL, NULL, NULL, NULL, 'admin@uclp.edu', '$2y$10$PC5DN6zQIAYrnozuzlvJQehqSsmIYhAS1.QvPWdYEe9YFnVopqto.', '1787455900_b739ceedf4721078.jpg', 1, 1, 2, '2026-08-13 03:44:44', '2026-08-23 03:31:40'),
(2, 'editor', 'Editor User', NULL, NULL, NULL, NULL, 'editor@uclp.edu', '$2y$10$YQa5pZLHKu6gqmG/mx9G0OAVaPySKNcW6a4AGUGdBKLpeW0gmOJiO', 'default.jpg', 1, 1, 2, '2026-08-13 03:44:44', '2026-08-13 05:15:33');

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
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `committee_members`
--
ALTER TABLE `committee_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `year_id` (`year_id`),
  ADD KEY `designation_id` (`designation_id`);

--
-- Indexes for table `committee_years`
--
ALTER TABLE `committee_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_label` (`year_label`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `download_permissions`
--
ALTER TABLE `download_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `journals`
--
ALTER TABLE `journals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_unique` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`);

--
-- Indexes for table `supply_requests`
--
ALTER TABLE `supply_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token_unique` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=455;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `committee_members`
--
ALTER TABLE `committee_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `committee_years`
--
ALTER TABLE `committee_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `download_permissions`
--
ALTER TABLE `download_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `journals`
--
ALTER TABLE `journals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `supply_requests`
--
ALTER TABLE `supply_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `committee_members`
--
ALTER TABLE `committee_members`
  ADD CONSTRAINT `committee_members_ibfk_1` FOREIGN KEY (`year_id`) REFERENCES `committee_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `committee_members_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `download_permissions`
--
ALTER TABLE `download_permissions`
  ADD CONSTRAINT `download_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journals`
--
ALTER TABLE `journals`
  ADD CONSTRAINT `journals_ibfk_1` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_requests`
--
ALTER TABLE `supply_requests`
  ADD CONSTRAINT `supply_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_requests_ibfk_3` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_requests_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
