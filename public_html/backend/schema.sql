-- ==========================================================================
-- BRIO WORLD SCHOOL - Core PHP & MySQL Database Schema
-- Compatible with cPanel MySQL / MariaDB & phpMyAdmin
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `careers`;
DROP TABLE IF EXISTS `admissions`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE (Authentication & Portal Access)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `otp_code` VARCHAR(10) DEFAULT NULL,
  `otp_expires` DATETIME DEFAULT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin Account (Email: admin@brioworldschool.edu.in | Password: Admin@123456)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_verified`) VALUES
('BRIO Super Admin', 'admin@brioworldschool.edu.in', '3e414ed81e3a68128362fb76ec7c010d29f8c66e', 'admin', 1);

-- 2. ADMISSIONS TABLE (Student Registrations)
CREATE TABLE `admissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(100) NOT NULL,
  `parent_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `grade` VARCHAR(50) NOT NULL,
  `campus` VARCHAR(50) DEFAULT 'Gujarat Campus',
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CAREERS TABLE (Faculty & Staff Job Applications)
CREATE TABLE `careers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `job_title` VARCHAR(100) NOT NULL,
  `experience` INT DEFAULT 0,
  `resume_path` VARCHAR(255) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'reviewed', 'shortlisted') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CONTACTS TABLE (Inquiries & Support Messages)
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(150) DEFAULT 'General Inquiry',
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
