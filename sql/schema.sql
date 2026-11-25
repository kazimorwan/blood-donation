-- Blood Care database (based on blood_donations.sql prepared by the team)
-- Import this file once. It DROPS the tables below and recreates them.
-- Changes from the original dump are marked with "CHANGED" or "ADDED".

CREATE DATABASE IF NOT EXISTS `blood_donations` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `blood_donations`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `donation_requests_transactions`;
DROP TABLE IF EXISTS `donation_requests`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `areas`;
SET FOREIGN_KEY_CHECKS = 1;

-- Areas / locations
CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,            -- CHANGED: added AUTO_INCREMENT
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Users: every person (donor or seeker) is a user
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mobile_no` varchar(15) NOT NULL,                -- CHANGED: was int(11), too small for 01XXXXXXXXX and loses the leading 0
  `password` varchar(255) NOT NULL,                -- CHANGED: was varchar(100), 255 is the safe size for password_hash()
  `blood_group` varchar(10) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL COMMENT 'dob=date of birth',
  `nid` varchar(20) DEFAULT NULL,                  -- CHANGED: was int(11), NID numbers have 10-17 digits
  `last_donation_date` date DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,     -- CHANGED: added DEFAULT 0
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=active, 2= unavailable,3=inactive',  -- CHANGED: added DEFAULT 1
  `role` enum('user','admin') NOT NULL DEFAULT 'user',  -- ADDED: needed for the admin panel
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_no` (`mobile_no`),
  CONSTRAINT `fk_users_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL   -- ADDED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Blood requests
CREATE TABLE `donation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,            -- CHANGED: added AUTO_INCREMENT
  `patient_problem` varchar(255) DEFAULT NULL,
  `blood_seeker_id` int(11) NOT NULL COMMENT 'Who is requested for blood',
  `blood_group` varchar(10) NOT NULL,
  `donation_date` date NOT NULL,
  `time` time DEFAULT NULL,
  `quantity` tinyint(1) NOT NULL,
  `area_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1= pending\r\n2= ongoing\r\n3=accepted\r\n4=completed\r\n5=cancelled',  -- CHANGED: added DEFAULT 1
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),   -- CHANGED: auto-update
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_requests_seeker` FOREIGN KEY (`blood_seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,  -- ADDED
  CONSTRAINT `fk_requests_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)                               -- ADDED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Which donor accepted which request
CREATE TABLE `donation_requests_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_request_id` int(11) NOT NULL,
  `doner_id` int(11) NOT NULL COMMENT 'users'' primary key',
  `donation_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=accepted, 2=cancelled, 3=completed',  -- CHANGED: added DEFAULT 1
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),   -- CHANGED: auto-update
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tx_request` FOREIGN KEY (`donation_request_id`) REFERENCES `donation_requests` (`id`) ON DELETE CASCADE,  -- ADDED
  CONSTRAINT `fk_tx_donor` FOREIGN KEY (`doner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE                             -- ADDED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ADDED: notifications for donors when a matching blood request is posted
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Who receives the notification',
  `donation_request_id` int(11) DEFAULT NULL COMMENT 'The blood request this is about',
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unread, 1=read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_request` FOREIGN KEY (`donation_request_id`) REFERENCES `donation_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
