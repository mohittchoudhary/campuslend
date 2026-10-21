-- CampusLend Database Schema
-- Optimized for MySQL 8.0+ / MariaDB 10.5+
-- Charset: utf8mb4, Collation: utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS `campuslend` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campuslend`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to allow clean re-import
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `user_badges`;
DROP TABLE IF EXISTS `user_blocks`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `borrow_transactions`;
DROP TABLE IF EXISTS `offers`;
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `otp_codes`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `enrollment_no` VARCHAR(50) NOT NULL UNIQUE,
    `department` VARCHAR(100) NOT NULL,
    `year_of_study` VARCHAR(30) NOT NULL DEFAULT '1st Year',
    `phone` VARCHAR(25) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT 'default_avatar.svg',
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `is_banned` TINYINT(1) NOT NULL DEFAULT 0,
    `trust_score` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
    `total_ratings` INT NOT NULL DEFAULT 0,
    `remember_token` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_enrollment` (`enrollment_no`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. OTP CODES TABLE
CREATE TABLE `otp_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `code` VARCHAR(6) NOT NULL,
    `purpose` ENUM('registration', 'password_reset') NOT NULL DEFAULT 'registration',
    `attempts` INT NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_otp_user_purpose` (`user_id`, `purpose`),
    INDEX `idx_otp_expires` (`expires_at`),
    CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PASSWORD RESETS TABLE
CREATE TABLE `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_resets_token` (`token`),
    INDEX `idx_resets_expires` (`expires_at`),
    CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CATEGORIES TABLE
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'box',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ITEMS (LEND LISTINGS) TABLE
CREATE TABLE `items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `photo` VARCHAR(255) NULL,
    `item_condition` ENUM('Like New', 'Good', 'Fair', 'Usable') NOT NULL DEFAULT 'Good',
    `pickup_location` VARCHAR(150) NOT NULL,
    `max_duration_hours` INT NOT NULL DEFAULT 24,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `is_paused` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_items_user` (`user_id`),
    INDEX `idx_items_category` (`category_id`),
    INDEX `idx_items_available` (`is_available`, `is_paused`),
    CONSTRAINT `fk_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. REQUESTS (EMERGENCY BORROW REQUESTS) TABLE
CREATE TABLE `requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `item_name` VARCHAR(150) NOT NULL,
    `urgency` ENUM('Critical', 'High', 'Normal') NOT NULL DEFAULT 'Normal',
    `campus_block` VARCHAR(50) NOT NULL,
    `room_location` VARCHAR(100) NOT NULL,
    `needed_by` DATETIME NOT NULL,
    `note` TEXT NULL,
    `status` ENUM('open', 'offered', 'matched', 'fulfilled', 'expired', 'cancelled') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_requests_user` (`user_id`),
    INDEX `idx_requests_category` (`category_id`),
    INDEX `idx_requests_urgency` (`urgency`),
    INDEX `idx_requests_status` (`status`),
    INDEX `idx_requests_block` (`campus_block`),
    INDEX `idx_requests_needed_by` (`needed_by`),
    CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_requests_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. OFFERS TABLE ("I CAN LEND THIS")
CREATE TABLE `offers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `lender_id` INT NOT NULL,
    `item_id` INT NULL,
    `message` TEXT NULL,
    `status` ENUM('pending', 'accepted', 'declined', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_offers_request` (`request_id`),
    INDEX `idx_offers_lender` (`lender_id`),
    INDEX `idx_offers_status` (`status`),
    CONSTRAINT `fk_offers_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_offers_lender` FOREIGN KEY (`lender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_offers_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. BORROW TRANSACTIONS TABLE
CREATE TABLE `borrow_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `offer_id` INT NOT NULL,
    `borrower_id` INT NOT NULL,
    `lender_id` INT NOT NULL,
    `item_id` INT NULL,
    `status` ENUM('accepted', 'handed_over', 'returned', 'overdue', 'cancelled') NOT NULL DEFAULT 'accepted',
    `handover_confirmed_borrower` TINYINT(1) NOT NULL DEFAULT 0,
    `handover_confirmed_lender` TINYINT(1) NOT NULL DEFAULT 0,
    `return_confirmed_borrower` TINYINT(1) NOT NULL DEFAULT 0,
    `return_confirmed_lender` TINYINT(1) NOT NULL DEFAULT 0,
    `handover_time` DATETIME NULL,
    `due_time` DATETIME NOT NULL,
    `returned_time` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tx_request` (`request_id`),
    INDEX `idx_tx_borrower` (`borrower_id`),
    INDEX `idx_tx_lender` (`lender_id`),
    INDEX `idx_tx_status` (`status`),
    INDEX `idx_tx_due` (`due_time`),
    CONSTRAINT `fk_tx_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tx_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tx_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tx_lender` FOREIGN KEY (`lender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tx_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. MESSAGES (CHAT) TABLE
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NULL,
    `request_id` INT NULL,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_msg_tx` (`transaction_id`),
    INDEX `idx_msg_req` (`request_id`),
    INDEX `idx_msg_receiver_read` (`receiver_id`, `is_read`),
    INDEX `idx_msg_created` (`created_at`),
    CONSTRAINT `fk_msg_tx` FOREIGN KEY (`transaction_id`) REFERENCES `borrow_transactions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_req` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. NOTIFICATIONS TABLE
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notif_user_read` (`user_id`, `is_read`),
    INDEX `idx_notif_created` (`created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. RATINGS AND REVIEWS TABLE
CREATE TABLE `ratings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `reviewer_id` INT NOT NULL,
    `reviewed_user_id` INT NOT NULL,
    `role_in_tx` ENUM('borrower', 'lender') NOT NULL,
    `stars` TINYINT NOT NULL CHECK (`stars` BETWEEN 1 AND 5),
    `comment` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_rating_per_party` (`transaction_id`, `reviewer_id`),
    INDEX `idx_ratings_reviewed` (`reviewed_user_id`),
    CONSTRAINT `fk_ratings_tx` FOREIGN KEY (`transaction_id`) REFERENCES `borrow_transactions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ratings_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ratings_reviewed` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. REPORTS TABLE
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT NOT NULL,
    `reported_user_id` INT NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `status` ENUM('pending', 'reviewed', 'dismissed', 'action_taken') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reports_status` (`status`),
    CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reports_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. USER BLOCKS TABLE
CREATE TABLE `user_blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `blocker_id` INT NOT NULL,
    `blocked_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_block_pair` (`blocker_id`, `blocked_id`),
    CONSTRAINT `fk_blocks_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_blocks_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. USER BADGES TABLE
CREATE TABLE `user_badges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `badge_name` VARCHAR(100) NOT NULL,
    `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_badge` (`user_id`, `badge_name`),
    CONSTRAINT `fk_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. ADMIN LOGS TABLE
CREATE TABLE `admin_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NULL,
    `target_id` INT NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_admin` (`admin_id`),
    INDEX `idx_logs_action` (`action`),
    CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. SETTINGS TABLE
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
