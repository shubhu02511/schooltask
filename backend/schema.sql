-- ==========================================================================
-- BRIO WORLD SCHOOL - Core PHP & MySQL Database Schema
-- Compatible with cPanel MySQL / MariaDB & phpMyAdmin
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `transfer_certificates`;
DROP TABLE IF EXISTS `news_events`;
DROP TABLE IF EXISTS `vacancies`;
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

-- 5. VACANCIES TABLE (Job Vacancy Management)
CREATE TABLE `vacancies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_title` VARCHAR(150) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(150) NOT NULL,
  `experience` VARCHAR(50) NOT NULL,
  `location` VARCHAR(100) DEFAULT 'Gujarat & Delhi Campuses',
  `job_type` VARCHAR(50) DEFAULT 'Full-Time',
  `description` TEXT DEFAULT NULL,
  `requirements` TEXT DEFAULT NULL,
  `slug` VARCHAR(180) NOT NULL UNIQUE,
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Initial Job Openings
INSERT INTO `vacancies` (`job_title`, `position`, `qualification`, `experience`, `location`, `job_type`, `description`, `requirements`, `slug`, `status`) VALUES
('PGT Physics & JEE Foundation Lead', 'Senior Secondary Wing', 'M.Sc. Physics & B.Ed', '5+ Years', 'Gujarat & Delhi Campuses', 'Full-Time', 'Lead senior secondary physics classes and JEE Advanced coaching.', 'Master degree in Physics with 5+ years experience.', 'pgt-physics-jee-foundation-lead', 'published'),
('AI & Robotics STEM Coach', 'STEM & AI Innovation', 'B.Tech / M.Tech (CS / Robotics)', '3+ Years', 'Vadodara, Gujarat Campus', 'Full-Time', 'Guide students in AI modeling, 3D printing, and competitive robotics.', 'Engineering background with hands-on robotics coaching expertise.', 'ai-robotics-stem-coach', 'published'),
('TGT English & Drama Facilitator', 'Middle School Wing', 'M.A. English & B.Ed', '4+ Years', 'South Delhi, NCR Campus', 'Full-Time', 'Teach middle school English literature, public speaking, and theatrical drama.', 'Degree in English literature with strong communication skills.', 'tgt-english-drama-facilitator', 'published'),
('Head Aquatics & Swimming Coach', 'Sports Academy', 'NSNIS Diploma in Swimming / International Certification', '5+ Years', 'Vadodara, Gujarat Campus', 'Full-Time', 'Manage Olympic-size swimming pool operations and competitive swim training.', 'Certified swim coach with lifeguard certification.', 'head-aquatics-swimming-coach', 'published');

-- 6. NEWS & EVENTS TABLE (News & Events Management)
CREATE TABLE `news_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `event_date` DATE NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Initial News & Events
INSERT INTO `news_events` (`title`, `description`, `category`, `event_date`, `image_path`, `status`) VALUES
('National Science & Robotics Expo 2026', 'Over 50 innovative student projects featured in our annual STEM exhibition.', 'STEM & Innovation', '2026-08-12', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=600&q=80', 'published'),
('B-SAT Scholarship Entrance Test Announced', 'Registration opens for Pre-K to Grade 11 scholarship entrance test for 2026-27.', 'Admissions', '2026-09-01', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=600&q=80', 'published'),
('Inter-School Athletics & Aquatics Meet', 'Over 500 athletes competed in track events, 50m swimming trials, and football finals.', 'Sports & Athletics', '2026-07-25', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80', 'published'),
('Annual Cultural Fest & Musical Gala', 'Classical orchestra, theatrical drama plays, and choir performances in the grand auditorium.', 'Arts & Culture', '2026-07-18', 'https://images.unsplash.com/photo-1469488865564-c2de10f69f96?auto=format&fit=crop&w=600&q=80', 'published'),
('NASA Stargazing & Astronomy Camp', 'Overnight celestial observation using optical computerized telescopes with astrophysics guides.', 'Space & Astronomy', '2026-05-14', 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?auto=format&fit=crop&w=600&q=80', 'published'),
('Fine Arts & Clay Sculpting Expo', 'Canvas paintings, pottery masterpieces, and digital art created by student artists.', 'Fine Arts & Design', '2026-04-22', 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80', 'published');

-- 7. TRANSFER CERTIFICATES TABLE (TC Verification & Downloads)
CREATE TABLE `transfer_certificates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(150) NOT NULL,
  `tc_number` VARCHAR(50) NOT NULL UNIQUE,
  `dob` DATE NOT NULL,
  `admission_no` VARCHAR(50) DEFAULT NULL,
  `class_name` VARCHAR(50) NOT NULL,
  `issue_date` DATE NOT NULL,
  `campus` VARCHAR(100) DEFAULT 'Gujarat Campus',
  `verification_status` ENUM('verified', 'pending', 'revoked') DEFAULT 'verified',
  `pdf_filename` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Initial Verified Transfer Certificates with Date of Birth (DOB)
INSERT INTO `transfer_certificates` (`student_name`, `tc_number`, `dob`, `admission_no`, `class_name`, `issue_date`, `campus`, `verification_status`, `pdf_filename`) VALUES
('Aarav Sharma', 'TC2026/001', '2010-05-15', 'ADM9821', 'Grade 10', '2026-06-15', 'Gujarat Campus', 'verified', 'TC2026_001.pdf'),
('Ananya Verma', 'TC2026/002', '2008-11-20', 'ADM9822', 'Grade 12', '2026-06-20', 'Delhi NCR Campus', 'verified', 'TC2026_002.pdf');
