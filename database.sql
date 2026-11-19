-- ============================================================
-- DATABASE: Excellence Classes By Repswal
-- Complete Internship & Certification Management System
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+05:30";

-- Create database
CREATE DATABASE IF NOT EXISTS `excellence_classes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `excellence_classes`;

-- ============================================================
-- TABLE: admin_roles
-- ============================================================
CREATE TABLE `admin_roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `role_slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `permissions` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `avatar` VARCHAR(500) NULL,
    `designation` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `admin_roles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_admins_email` (`email`),
    INDEX `idx_admins_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: interns
-- ============================================================
CREATE TABLE `interns` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `college_name` VARCHAR(300) NOT NULL,
    `course_degree` VARCHAR(200) NOT NULL,
    `current_year` VARCHAR(50) NOT NULL,
    `avatar` VARCHAR(500) NULL,
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `email_verified_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_interns_email` (`email`),
    INDEX `idx_interns_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: courses
-- ============================================================
CREATE TABLE `courses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `short_description` TEXT NULL,
    `description` LONGTEXT NULL,
    `duration` VARCHAR(100) NULL,
    `level` ENUM('Beginner','Intermediate','Advanced','All Levels') NOT NULL DEFAULT 'All Levels',
    `mode` ENUM('Online','Offline','Hybrid') NOT NULL DEFAULT 'Online',
    `fee` DECIMAL(10,2) NULL,
    `fee_display` VARCHAR(100) NULL,
    `icon_class` VARCHAR(100) NULL,
    `image` VARCHAR(500) NULL,
    `technologies` JSON NULL,
    `syllabus` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_courses_slug` (`slug`),
    INDEX `idx_courses_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: internship_programs
-- ============================================================
CREATE TABLE `internship_programs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `short_description` TEXT NULL,
    `description` LONGTEXT NULL,
    `duration` VARCHAR(100) NOT NULL DEFAULT '4 Weeks',
    `mode` ENUM('Online','Offline','Hybrid') NOT NULL DEFAULT 'Online',
    `eligibility` TEXT NULL,
    `technologies` JSON NULL,
    `skills_covered` JSON NULL,
    `total_seats` INT UNSIGNED NULL,
    `filled_seats` INT UNSIGNED NOT NULL DEFAULT 0,
    `application_deadline` DATE NULL,
    `application_status` ENUM('Open','Closed') NOT NULL DEFAULT 'Closed',
    `icon_class` VARCHAR(100) NULL,
    `image` VARCHAR(500) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_programs_slug` (`slug`),
    INDEX `idx_programs_status` (`application_status`),
    INDEX `idx_programs_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: internship_applications
-- ============================================================
CREATE TABLE `internship_applications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `intern_id` INT UNSIGNED NOT NULL,
    `program_id` INT UNSIGNED NOT NULL,
    `application_status` ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
    `cover_letter` TEXT NULL,
    `admin_remarks` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`intern_id`) REFERENCES `interns`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`program_id`) REFERENCES `internship_programs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `uk_intern_program` (`intern_id`, `program_id`),
    INDEX `idx_applications_status` (`application_status`),
    INDEX `idx_applications_intern` (`intern_id`),
    INDEX `idx_applications_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: internship_enrollments
-- ============================================================
CREATE TABLE `internship_enrollments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_id` INT UNSIGNED NOT NULL,
    `intern_id` INT UNSIGNED NOT NULL,
    `program_id` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `internship_status` ENUM('Not Started','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Not Started',
    `completion_approved` TINYINT(1) NOT NULL DEFAULT 0,
    `completion_approved_by` INT UNSIGNED NULL,
    `completion_approved_at` DATETIME NULL,
    `completion_remarks` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `internship_applications`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`intern_id`) REFERENCES `interns`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`program_id`) REFERENCES `internship_programs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`completion_approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `uk_enrollment_application` (`application_id`),
    INDEX `idx_enrollments_intern` (`intern_id`),
    INDEX `idx_enrollments_status` (`internship_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tasks
-- ============================================================
CREATE TABLE `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(300) NOT NULL,
    `description` LONGTEXT NULL,
    `reference_materials` TEXT NULL,
    `deadline` DATE NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `assigned_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollment_id`) REFERENCES `internship_enrollments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_tasks_enrollment` (`enrollment_id`),
    INDEX `idx_tasks_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: task_submissions
-- ============================================================
CREATE TABLE `task_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `intern_id` INT UNSIGNED NOT NULL,
    `project_link` VARCHAR(500) NULL,
    `submission_text` LONGTEXT NULL,
    `attachment` VARCHAR(500) NULL,
    `submission_status` ENUM('Pending','Approved','Rejected','Resubmit') NOT NULL DEFAULT 'Pending',
    `admin_feedback` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`intern_id`) REFERENCES `interns`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `uk_task_intern` (`task_id`, `intern_id`),
    INDEX `idx_submissions_task` (`task_id`),
    INDEX `idx_submissions_intern` (`intern_id`),
    INDEX `idx_submissions_status` (`submission_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: internship_progress
-- ============================================================
CREATE TABLE `internship_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `total_tasks` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed_tasks` INT UNSIGNED NOT NULL DEFAULT 0,
    `progress_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `performance_rating` DECIMAL(3,2) NULL,
    `remarks` TEXT NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollment_id`) REFERENCES `internship_enrollments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `uk_progress_enrollment` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(300) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `target_audience` ENUM('All','Interns','Admins') NOT NULL DEFAULT 'All',
    `priority` ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `published_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_announcements_active` (`is_active`),
    INDEX `idx_announcements_audience` (`target_audience`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: documents
-- ============================================================
CREATE TABLE `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `document_id` VARCHAR(50) NOT NULL UNIQUE,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `intern_id` INT UNSIGNED NOT NULL,
    `document_type` ENUM('Offer Letter','Joining Letter','Experience Certificate') NOT NULL,
    `file_path` VARCHAR(500) NULL,
    `issued_date` DATE NOT NULL,
    `status` ENUM('Draft','Issued','Revoked') NOT NULL DEFAULT 'Draft',
    `issued_by` INT UNSIGNED NULL,
    `revoked_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollment_id`) REFERENCES `internship_enrollments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`intern_id`) REFERENCES `interns`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`issued_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_documents_document_id` (`document_id`),
    INDEX `idx_documents_intern` (`intern_id`),
    INDEX `idx_documents_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: certificates
-- ============================================================
CREATE TABLE `certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` VARCHAR(50) NOT NULL UNIQUE,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `intern_id` INT UNSIGNED NOT NULL,
    `program_id` INT UNSIGNED NOT NULL,
    `intern_name` VARCHAR(150) NOT NULL,
    `internship_domain` VARCHAR(200) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `issue_date` DATE NOT NULL,
    `file_path` VARCHAR(500) NULL,
    `certificate_status` ENUM('Not Eligible','Pending Approval','Issued','Revoked') NOT NULL DEFAULT 'Not Eligible',
    `issued_by` INT UNSIGNED NULL,
    `issued_at` DATETIME NULL,
    `revoked_reason` TEXT NULL,
    `revoked_by` INT UNSIGNED NULL,
    `revoked_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollment_id`) REFERENCES `internship_enrollments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`intern_id`) REFERENCES `interns`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`program_id`) REFERENCES `internship_programs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`issued_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`revoked_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `uk_certificate_enrollment` (`enrollment_id`),
    INDEX `idx_certificates_cert_id` (`certificate_id`),
    INDEX `idx_certificates_intern` (`intern_id`),
    INDEX `idx_certificates_status` (`certificate_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: certificate_audit_logs
-- ============================================================
CREATE TABLE `certificate_audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` INT UNSIGNED NOT NULL,
    `action` ENUM('Created','Issued','Reissued','Revoked','Downloaded','Verified') NOT NULL,
    `performed_by` INT UNSIGNED NULL,
    `performer_type` ENUM('Admin','Intern','Public') NOT NULL DEFAULT 'Admin',
    `ip_address` VARCHAR(45) NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`certificate_id`) REFERENCES `certificates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_cert_audit_cert` (`certificate_id`),
    INDEX `idx_cert_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: contact_messages
-- ============================================================
CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `subject` VARCHAR(300) NOT NULL,
    `message` LONGTEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_by` INT UNSIGNED NULL,
    `read_at` DATETIME NULL,
    `is_replied` TINYINT(1) NOT NULL DEFAULT 0,
    `replied_at` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`read_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_contact_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: website_settings
-- ============================================================
CREATE TABLE `website_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL,
    `setting_type` ENUM('text','textarea','image','json','boolean','number') NOT NULL DEFAULT 'text',
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(300) NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_settings_key` (`setting_key`),
    INDEX `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: password_reset_tokens
-- ============================================================
CREATE TABLE `password_reset_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `user_type` ENUM('admin','intern') NOT NULL DEFAULT 'intern',
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reset_email` (`email`),
    INDEX `idx_reset_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_audit_logs
-- ============================================================
CREATE TABLE `admin_audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NULL,
    `action` VARCHAR(200) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id` INT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_audit_admin` (`admin_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: login_attempts
-- ============================================================
CREATE TABLE `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_type` ENUM('admin','intern') NOT NULL DEFAULT 'intern',
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_email` (`email`),
    INDEX `idx_login_ip` (`ip_address`),
    INDEX `idx_login_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Admin Roles
-- ============================================================
INSERT INTO `admin_roles` (`role_name`, `role_slug`, `description`, `permissions`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', '{"all": true}'),
('Proprietor', 'proprietor', 'Proprietor with full administrative access including certificate issuance', '{"dashboard": true, "manage_interns": true, "manage_applications": true, "manage_programs": true, "manage_courses": true, "manage_tasks": true, "manage_submissions": true, "manage_progress": true, "manage_announcements": true, "manage_documents": true, "manage_certificates": true, "issue_certificates": true, "revoke_certificates": true, "manage_settings": true, "manage_admins": true, "view_audit_logs": true}'),
('Internship Manager', 'internship_manager', 'Manages interns, tasks, and submissions', '{"dashboard": true, "manage_interns": true, "manage_applications": true, "manage_tasks": true, "manage_submissions": true, "manage_progress": true, "manage_announcements": true}');

-- ============================================================
-- SEED DATA: Default Admin (Proprietor)
-- Password: Change this after first login!
-- Default password hash is for: Admin@123 (MUST be changed immediately)
-- ============================================================
INSERT INTO `admins` (`role_id`, `full_name`, `email`, `password`, `designation`, `is_active`) VALUES
(2, 'Amit Repswal', 'admin@excellenceclasses.com', '$2y$10$w6kJU1Xbl20LaHXxi7tPlej8CUn4ZbcdT7Plsh/S5kaIsaH6Hzx16', 'Proprietor', 1);

-- ============================================================
-- SEED DATA: Website Settings
-- ============================================================
INSERT INTO `website_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`) VALUES
('site_name', 'Excellence Classes By Repswal', 'text', 'general', 'Website name'),
('site_tagline', 'Learn. Develop. Grow.', 'text', 'general', 'Website tagline'),
('site_description', 'Excellence Classes By Repswal is an educational and IT training institute offering quality courses, IT training programs, and internship opportunities.', 'textarea', 'general', 'Website description for SEO'),
('site_email', 'info@excellenceclasses.com', 'text', 'contact', 'Primary email address'),
('site_phone', '', 'text', 'contact', 'Primary phone number'),
('site_address', 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308', 'textarea', 'contact', 'Office address'),
('proprietor_name', 'Amit Repswal', 'text', 'general', 'Proprietor full name'),
('proprietor_designation', 'Proprietor', 'text', 'general', 'Proprietor designation'),
('site_logo', '', 'image', 'branding', 'Website logo path'),
('authorized_signature', '', 'image', 'branding', 'Authorized signature image path'),
('hero_title', 'Build Your Future in Technology', 'text', 'homepage', 'Homepage hero title'),
('hero_subtitle', 'Join our industry-focused internship programs and transform your skills with hands-on experience at Excellence Classes By Repswal.', 'textarea', 'homepage', 'Homepage hero subtitle'),
('about_text', 'Excellence Classes By Repswal is an educational and IT training institute based in Nawalgarh, Rajasthan. We provide quality education, practical training, and internship programs to help students develop real-world skills in technology and software development.', 'textarea', 'homepage', 'About section text on homepage'),
('facebook_url', '', 'text', 'social', 'Facebook page URL'),
('twitter_url', '', 'text', 'social', 'Twitter profile URL'),
('instagram_url', '', 'text', 'social', 'Instagram profile URL'),
('linkedin_url', '', 'text', 'social', 'LinkedIn page URL'),
('youtube_url', '', 'text', 'social', 'YouTube channel URL'),
('google_maps_embed', '', 'textarea', 'contact', 'Google Maps embed iframe code'),
('meta_keywords', 'excellence classes, repswal, IT training, internship, web development, programming, Nawalgarh, Rajasthan', 'textarea', 'seo', 'Default meta keywords'),
('maintenance_mode', '0', 'boolean', 'general', 'Enable maintenance mode');

-- ============================================================
-- SEED DATA: Initial Internship Programs
-- ============================================================
INSERT INTO `internship_programs` (`title`, `slug`, `short_description`, `description`, `duration`, `mode`, `eligibility`, `technologies`, `skills_covered`, `application_status`, `icon_class`, `sort_order`, `created_by`) VALUES
('Web Development Internship', 'web-development', 'Learn to build modern, responsive websites using HTML, CSS, JavaScript, and PHP.', 'This internship program covers the fundamentals and advanced concepts of web development. You will learn to create responsive websites, work with databases, and build dynamic web applications.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent IT/CS courses. Basic understanding of HTML is preferred.', '["HTML5", "CSS3", "JavaScript", "PHP", "MySQL", "Bootstrap"]', '["Frontend Development", "Backend Development", "Database Management", "Responsive Design", "Version Control"]', 'Closed', 'bi-globe', 1, 1),

('Frontend Development Internship', 'frontend-development', 'Master modern frontend technologies including HTML5, CSS3, JavaScript, and React.', 'This internship focuses on building beautiful, interactive, and responsive user interfaces. You will work with modern frontend frameworks and tools.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Basic HTML/CSS knowledge required.', '["HTML5", "CSS3", "JavaScript", "React", "Bootstrap", "Sass"]', '["UI Development", "Responsive Design", "JavaScript Frameworks", "CSS Architecture", "Web Performance"]', 'Closed', 'bi-layout-text-window', 2, 1),

('Backend Development Internship', 'backend-development', 'Build robust server-side applications with PHP, Node.js, and MySQL.', 'This internship covers server-side programming, database design, API development, and security best practices for building scalable web applications.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Basic programming knowledge required.', '["PHP", "Node.js", "MySQL", "REST APIs", "Git"]', '["Server-side Programming", "Database Design", "API Development", "Authentication", "Security"]', 'Closed', 'bi-server', 3, 1),

('Full Stack Development Internship', 'full-stack-development', 'Become a complete developer with both frontend and backend skills.', 'This comprehensive internship covers the entire web development stack from frontend design to backend logic, database management, and deployment.', '6 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Some programming experience preferred.', '["HTML5", "CSS3", "JavaScript", "PHP", "MySQL", "React", "Node.js", "Git"]', '["Full Stack Development", "Frontend Development", "Backend Development", "Database Management", "Deployment"]', 'Closed', 'bi-stack', 4, 1),

('Python Programming Internship', 'python-programming', 'Learn Python programming for web development, data science, and automation.', 'This internship introduces Python programming with practical projects covering web development with Django/Flask, data analysis, and automation scripting.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. No prior Python experience needed.', '["Python", "Django", "Flask", "NumPy", "Pandas", "SQLite"]', '["Python Programming", "Web Development", "Data Analysis", "Automation", "Problem Solving"]', 'Closed', 'bi-filetype-py', 5, 1),

('Java Programming Internship', 'java-programming', 'Master Java programming for enterprise applications and Android development.', 'This internship covers core Java, object-oriented programming, data structures, and introduction to enterprise Java development.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Basic programming concepts helpful.', '["Java", "Spring Boot", "JDBC", "MySQL", "Maven"]', '["Core Java", "OOP Concepts", "Data Structures", "Enterprise Development", "Database Connectivity"]', 'Closed', 'bi-cup-hot', 6, 1),

('C/C++ Programming Internship', 'c-cpp-programming', 'Build a strong programming foundation with C and C++.', 'This internship covers fundamentals of C and C++ programming, data structures, algorithms, and system-level programming concepts.', '4 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Suitable for beginners.', '["C", "C++", "Data Structures", "Algorithms", "STL"]', '["C Programming", "C++ Programming", "Data Structures", "Algorithms", "Problem Solving"]', 'Closed', 'bi-braces', 7, 1),

('App Development Internship', 'app-development', 'Learn to build mobile applications for Android and cross-platform.', 'This internship covers mobile application development, UI/UX for mobile, and publishing apps. Learn to build real-world mobile applications.', '6 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Basic programming knowledge required.', '["Flutter", "Dart", "Android Studio", "Firebase", "REST APIs"]', '["Mobile Development", "Cross-platform Development", "Mobile UI/UX", "App Publishing", "API Integration"]', 'Closed', 'bi-phone', 8, 1),

('Software Development Internship', 'software-development', 'Learn the complete software development lifecycle and best practices.', 'This internship covers software engineering principles, project management, coding standards, testing, and deployment in a professional development environment.', '6 Weeks', 'Online', 'Students pursuing B.Tech, BCA, MCA, or equivalent courses. Some programming experience preferred.', '["Python", "Java", "Git", "Docker", "SQL", "Agile"]', '["Software Engineering", "Project Management", "Version Control", "Testing", "Agile Methodology"]', 'Closed', 'bi-gear', 9, 1);

-- ============================================================
-- SEED DATA: Initial Courses
-- ============================================================
INSERT INTO `courses` (`title`, `slug`, `short_description`, `duration`, `level`, `mode`, `icon_class`, `sort_order`, `created_by`) VALUES
('Web Development', 'web-development', 'Learn HTML, CSS, JavaScript, PHP, and MySQL to build modern websites and web applications.', '3 Months', 'Beginner', 'Online', 'bi-globe', 1, 1),
('Python Programming', 'python-programming', 'Master Python for web development, data science, automation, and machine learning.', '3 Months', 'Beginner', 'Online', 'bi-filetype-py', 2, 1),
('Java Programming', 'java-programming', 'Learn core Java, OOP, data structures, and enterprise application development.', '3 Months', 'Beginner', 'Online', 'bi-cup-hot', 3, 1),
('C/C++ Programming', 'c-cpp-programming', 'Build a strong programming foundation with C and C++ languages.', '2 Months', 'Beginner', 'Online', 'bi-braces', 4, 1),
('App Development', 'app-development', 'Build mobile applications for Android and cross-platform using Flutter.', '4 Months', 'Intermediate', 'Online', 'bi-phone', 5, 1),
('Frontend Development', 'frontend-development', 'Master HTML5, CSS3, JavaScript, React, and modern frontend frameworks.', '3 Months', 'Beginner', 'Online', 'bi-layout-text-window', 6, 1),
('Database Management', 'database-management', 'Learn MySQL, MongoDB, database design, optimization, and administration.', '2 Months', 'Intermediate', 'Online', 'bi-database', 7, 1),
('Software Engineering', 'software-engineering', 'Learn software development lifecycle, design patterns, and best practices.', '4 Months', 'Intermediate', 'Online', 'bi-gear', 8, 1);

COMMIT;
