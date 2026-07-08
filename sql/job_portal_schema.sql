CREATE DATABASE IF NOT EXISTS `job_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `job_portal`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Table: users
-- Referenced in: src/db.php, login.php, register.php,
-- profile/edit_profile.php, applications/upload_cv.php,
-- admin/manage_users.php, admin/make_admin.php, admin/delete_user.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `is_company` TINYINT(1) NOT NULL DEFAULT 0,
  `phone` VARCHAR(50) DEFAULT NULL,
  `resume_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Table: companies
-- Referenced in: company/create_company.php, company/edit_company.php,
-- dashboard/company_dashboard.php, admin/manage_companies.php,
-- company_list.php, register.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `industry` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Table: jobs
-- Referenced in: jobs/add_job.php, jobs/edit_job.php, jobs/delete_job.php,
-- jobs.php, job_view.php, admin/manage_jobs.php, admin/approve_job.php,
-- admin/pending_jobs.php, admin/rejected_jobs.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `requirements` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `job_type` VARCHAR(50) DEFAULT NULL,
  `salary_range` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `posted_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Table: applications
-- Referenced in: applications/apply.php, applications/hire_action.php,
-- applications/reject_action.php, applications/shortlist_action.php,
-- applications/my_applications.php
-- (Column list/types cross-checked against sql/job_portal_schema.sql dump)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `cover_letter` TEXT DEFAULT NULL,
  `resume_path` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'pending',
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Table: notifications
-- Referenced in: applications/reject_action.php, applications/shortlist_action.php,
-- notifications/mark_read.php, dashboard/user_dashboard.php
-- (Exact CREATE TABLE statement was found verbatim in the PHP code)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `application_id` INT DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL,
  `message` TEXT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;