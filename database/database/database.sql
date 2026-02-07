-- --------------------------------------------------------
-- SaaS Tennis Court Booking System - Production Schema
-- Database: MySQL 5.7+ / MariaDB 10.2+
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Use utf8mb4 for full unicode support (Names, Emojis)
CREATE DATABASE IF NOT EXISTS `tennis_saas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tennis_saas`;

-- ========================================================
-- LEVEL 1: SAAS & TENANT MANAGEMENT
-- ========================================================

-- 1. System Plans (The packages you sell to Clubs: Starter, Pro, Enterprise)
CREATE TABLE `system_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `price_cents` int(11) NOT NULL DEFAULT '0',
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_interval` enum('month','year') NOT NULL DEFAULT 'month',
  `features_config` json NOT NULL COMMENT '{"max_courts": 3, "allow_api": false}',
  `stripe_product_id` varchar(255) DEFAULT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Organizations (The Tennis Clubs / Tenants)
CREATE TABLE `organizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subdomain` varchar(64) DEFAULT NULL COMMENT 'clubname.yoursaas.com',
  `logo_url` varchar(512) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
  `billing_status` enum('free','active','past_due','cancelled') DEFAULT 'free',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `settings` json DEFAULT NULL COMMENT 'Club specific settings (open hours, rules)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SaaS Subscriptions (Link between Club and Your System Plan)
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `status` enum('incomplete','trialing','active','past_due','canceled') NOT NULL DEFAULT 'incomplete',
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_stripe_sub` (`stripe_subscription_id`),
  CONSTRAINT `fk_subs_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subs_plan` FOREIGN KEY (`plan_id`) REFERENCES `system_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- LEVEL 2: USERS & MEMBERSHIPS
-- ========================================================

-- 4. Users (Admins, Coaches, Players)
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL for Super Admin',
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(128) NOT NULL,
  `last_name` varchar(128) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `role` enum('super_admin','admin','staff','coach','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','disabled','banned','pending') NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL COMMENT '{"ntrp_rating": 4.5, "play_hand": "right"}',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email_org` (`email`, `organization_id`),
  CONSTRAINT `fk_users_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Club Membership Types (Gold, Silver, Guest)
CREATE TABLE `club_membership_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `price_cents` int(11) DEFAULT '0',
  `billing_cycle` enum('one_time','monthly','yearly') DEFAULT 'yearly',
  `booking_window_days` int(10) unsigned NOT NULL DEFAULT '7',
  `max_active_bookings` int(10) unsigned DEFAULT '2',
  `court_fee_discount_percent` decimal(5,2) DEFAULT '0.00',
  `is_public` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mem_type_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. User Memberships (Which user has which tier)
CREATE TABLE `user_club_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `membership_type_id` bigint(20) unsigned NOT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ucm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ucm_type` FOREIGN KEY (`membership_type_id`) REFERENCES `club_membership_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Wallets (Credits/Balance)
CREATE TABLE `user_wallets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `balance_cents` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wallet_user` (`organization_id`, `user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- LEVEL 3: RESOURCES (COURTS) & PRICING
-- ========================================================

-- 8. Resources (Courts)
CREATE TABLE `resources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL COMMENT 'Court 1, Center Court',
  `surface_type` enum('clay','hard','grass','carpet','synthetic') DEFAULT 'hard',
  `is_indoor` tinyint(1) DEFAULT '0',
  `has_lighting` tinyint(1) DEFAULT '0',
  `status` enum('enabled','disabled','maintenance') NOT NULL DEFAULT 'enabled',
  `priority` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Sorting order',
  
  -- Time Config
  `daily_start_time` time NOT NULL DEFAULT '07:00:00',
  `daily_end_time` time NOT NULL DEFAULT '22:00:00',
  `time_block_minutes` int(10) unsigned NOT NULL DEFAULT '60',
  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_res_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Pricing Rules (Cost per hour)
CREATE TABLE `pricing_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `resource_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL = All courts',
  `name` varchar(128) DEFAULT NULL COMMENT 'e.g. Weekend Rate',
  
  `day_of_week_start` tinyint(3) unsigned DEFAULT '1',
  `day_of_week_end` tinyint(3) unsigned DEFAULT '7',
  `time_start` time DEFAULT '00:00:00',
  `time_end` time DEFAULT '23:59:59',
  
  `price_cents` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_price_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_price_res` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- LEVEL 4: BOOKINGS
-- ========================================================

-- 10. Bookings (Head Table)
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'The person who booked',
  `resource_id` bigint(20) unsigned NOT NULL,
  
  `status` enum('confirmed','pending','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','partial','refunded') NOT NULL DEFAULT 'pending',
  `visibility` enum('public','private') NOT NULL DEFAULT 'private',
  
  `notes` text,
  `check_in_at` datetime DEFAULT NULL,
  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_user` (`user_id`),
  KEY `idx_booking_res_status` (`resource_id`, `status`),
  CONSTRAINT `fk_book_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_book_res` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Reservations (Time Slots)
CREATE TABLE `reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `resource_id` bigint(20) unsigned NOT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_calendar` (`resource_id`, `reservation_date`, `start_time`),
  CONSTRAINT `fk_resv_book` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Booking Participants (Players/Opponents)
CREATE TABLE `booking_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL if Guest',
  `guest_name` varchar(128) DEFAULT NULL,
  `role` enum('owner','partner','opponent','coach') NOT NULL DEFAULT 'partner',
  `share_cost_cents` int(11) DEFAULT '0',
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_part_book` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_part_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Booking Line Items (The Invoice)
CREATE TABLE `booking_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT '1',
  `unit_price_cents` int(11) NOT NULL,
  `total_cents` int(11) NOT NULL,
  `type` enum('court_fee','light_fee','rental','product','tax','discount') NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_item_book` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- LEVEL 5: OPERATIONS & EXTRAS
-- ========================================================

-- 14. Wallet Transactions (History)
CREATE TABLE `wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `amount_cents` int(11) NOT NULL,
  `type` enum('deposit','booking_payment','refund','adjustment') NOT NULL,
  `reference_id` varchar(64) DEFAULT NULL COMMENT 'Payment Gateway ID or Booking ID',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_txn_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `user_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Maintenance Logs (Block courts)
CREATE TABLE `maintenance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` bigint(20) unsigned NOT NULL,
  `organization_id` bigint(20) unsigned NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_maint_res` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Products / Add-ons (Ball rentals, etc)
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `price_cents` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_prod_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Coupons
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `code` varchar(64) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` int(11) NOT NULL,
  `valid_until` datetime DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `usage_count` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_coupon` (`organization_id`, `code`),
  CONSTRAINT `fk_coupon_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Access Codes (Optional: Gate/Door integration)
CREATE TABLE `booking_access_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `code` varchar(32) NOT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_access_book` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;