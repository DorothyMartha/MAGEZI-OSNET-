-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2025 at 09:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create Database if it doesn't exist and select it
CREATE DATABASE IF NOT EXISTS `magezi_osnet` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `magezi_osnet`;

-- ============================================================
-- TABLE ORDER:
-- 1. access_logs
-- 2. failed_logins
-- 3. alumni_associations
-- 4. donations
-- 5. fundraising_campaigns
-- 6. membership_categories
-- 7. reports
-- 8. settings
-- 9. users (updated with new columns: membership, association_id)
-- 10. withdrawals
-- 11. transactions
-- 12. subscriptions
-- ============================================================

-- --------------------------------------------------------
-- Table structure for table `access_logs`
-- --------------------------------------------------------
CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `failed_logins`
-- --------------------------------------------------------
CREATE TABLE `failed_logins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(50) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `alumni_associations`
-- --------------------------------------------------------
CREATE TABLE `alumni_associations` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `bank_account_details` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `donations`
-- --------------------------------------------------------
CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('PesaPal','MobileMoney','CreditCard') DEFAULT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `donated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `fundraising_campaigns`
-- --------------------------------------------------------
CREATE TABLE `fundraising_campaigns` (
  `id` int(11) NOT NULL,
  `association_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `membership_categories`
-- --------------------------------------------------------
CREATE TABLE `membership_categories` (
  `id` int(11) NOT NULL,
  `association_id` int(11) DEFAULT NULL,
  `category_name` varchar(100) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `reports`
-- --------------------------------------------------------
CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
-- Updated to include new columns: membership, association_id
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `years_of_attendance` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `country_of_residence` varchar(100) DEFAULT NULL,
  `marital_status` enum('Single','Married','Other') DEFAULT NULL,
  `role` enum('SuperAdmin','AssociationAdmin','Member') DEFAULT 'Member',
  `membership` enum('Registered','Subscribed') NOT NULL DEFAULT 'Registered',
  `association_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `withdrawals`
-- --------------------------------------------------------
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `association_id` int(11) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `association_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('Donation','Withdrawal') NOT NULL,
  `payment_method` enum('PesaPal','MobileMoney','CreditCard') DEFAULT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `subscriptions`
-- --------------------------------------------------------
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `association_id` int(11) NOT NULL,
  `membership_category_id` int(11) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Additional Indexes, AUTO_INCREMENT Modifications & Constraints
-- ============================================================

-- Indexes for table `access_logs`
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

-- Indexes for table `failed_logins`
ALTER TABLE `failed_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

-- Indexes for table `alumni_associations`
ALTER TABLE `alumni_associations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_name` (`school_name`),
  ADD KEY `created_by` (`created_by`);

-- Indexes for table `donations`
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `donor_id` (`donor_id`);

-- Indexes for table `fundraising_campaigns`
ALTER TABLE `fundraising_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `association_id` (`association_id`),
  ADD KEY `created_by` (`created_by`);

-- Indexes for table `membership_categories`
ALTER TABLE `membership_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `association_id` (`association_id`);

-- Indexes for table `reports`
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

-- Indexes for table `settings`
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

-- Indexes for table `users`
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- Indexes for table `withdrawals`
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `association_id` (`association_id`),
  ADD KEY `requested_by` (`requested_by`);

-- AUTO_INCREMENT modifications
ALTER TABLE `access_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `failed_logins` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `alumni_associations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `donations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `fundraising_campaigns` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `membership_categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `reports` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `withdrawals` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Additional Constraints
ALTER TABLE `access_logs` ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`);
ALTER TABLE `failed_logins` ADD CONSTRAINT `failed_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`);
ALTER TABLE `alumni_associations` ADD CONSTRAINT `alumni_associations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
ALTER TABLE `donations` ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `fundraising_campaigns`(`id`),
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`);
ALTER TABLE `fundraising_campaigns` ADD CONSTRAINT `fundraising_campaigns_ibfk_1` FOREIGN KEY (`association_id`) REFERENCES `alumni_associations`(`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fundraising_campaigns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
ALTER TABLE `membership_categories` ADD CONSTRAINT `membership_categories_ibfk_1` FOREIGN KEY (`association_id`) REFERENCES `alumni_associations`(`id`) ON DELETE CASCADE;
ALTER TABLE `reports` ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`);
ALTER TABLE `withdrawals` ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`association_id`) REFERENCES `alumni_associations`(`id`),
  ADD CONSTRAINT `withdrawals_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
