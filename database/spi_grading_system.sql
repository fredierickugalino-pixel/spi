CREATE DATABASE IF NOT EXISTS spi_grading_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spi_grading_system;

CREATE TABLE users (
 user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(190) NOT NULL UNIQUE, email VARCHAR(190) NULL UNIQUE,
 password VARCHAR(255) NOT NULL, role ENUM('admin','teacher','student') NOT NULL, account_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 must_change_password TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE teacher_profiles (user_id BIGINT UNSIGNED PRIMARY KEY, full_name VARCHAR(200) NOT NULL, FOREIGN KEY(user_id) REFERENCES users(user_id));
CREATE TABLE students (
 student_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lrn VARCHAR(12) NOT NULL UNIQUE, firstname VARCHAR(100) NOT NULL, middle_name VARCHAR(100) NULL,
 lastname VARCHAR(100) NOT NULL, suffix VARCHAR(30) NULL, record_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE student_account_requests (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE, student_id BIGINT UNSIGNED NULL UNIQUE,
 submitted_lrn VARCHAR(12) NOT NULL, submitted_firstname VARCHAR(100) NOT NULL, submitted_middle_name VARCHAR(100) NULL, submitted_lastname VARCHAR(100) NOT NULL,
 request_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', rejection_reason VARCHAR(500) NULL,
 reviewed_by BIGINT UNSIGNED NULL, reviewed_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_req_user FOREIGN KEY(user_id) REFERENCES users(user_id), CONSTRAINT fk_req_student FOREIGN KEY(student_id) REFERENCES students(student_id),
 CONSTRAINT fk_req_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(user_id), INDEX(submitted_lrn), INDEX(request_status)
) ENGINE=InnoDB;
CREATE TABLE school_years (school_year_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_year_name VARCHAR(20) NOT NULL UNIQUE, start_date DATE NULL, end_date DATE NULL, is_active TINYINT(1) NOT NULL DEFAULT 0);
CREATE TABLE strands (strand_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, strand_code VARCHAR(30) NOT NULL UNIQUE, strand_name VARCHAR(100) NOT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active');
INSERT IGNORE INTO strands (strand_code,strand_name) VALUES
 ('HUMSS','Humanities and Social Sciences'),
 ('ICT','Information and Communications Technology'),
 ('ABM','Accountancy, Business, and Management'),
 ('STEM','Science, Technology, Engineering, and Mathematics'),
 ('HE','Home Economics');
CREATE TABLE sections (section_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_year_id BIGINT UNSIGNED NOT NULL, grade_level ENUM('11','12') NOT NULL, strand_id BIGINT UNSIGNED NOT NULL, section_name VARCHAR(100) NOT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', UNIQUE(school_year_id,grade_level,strand_id,section_name), FOREIGN KEY(school_year_id) REFERENCES school_years(school_year_id), FOREIGN KEY(strand_id) REFERENCES strands(strand_id));
CREATE TABLE student_enrollments (student_enrollment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id BIGINT UNSIGNED NOT NULL, section_id BIGINT UNSIGNED NOT NULL, school_year_id BIGINT UNSIGNED NOT NULL, assignment_status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(student_id,school_year_id), FOREIGN KEY(student_id) REFERENCES students(student_id), FOREIGN KEY(section_id) REFERENCES sections(section_id), FOREIGN KEY(school_year_id) REFERENCES school_years(school_year_id));
CREATE TABLE subjects (subject_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, subject_code VARCHAR(40) NOT NULL UNIQUE, subject_name VARCHAR(150) NOT NULL, description TEXT NULL, grade_level ENUM('11','12') NOT NULL, strand_id BIGINT UNSIGNED NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', FOREIGN KEY(strand_id) REFERENCES strands(strand_id));
CREATE TABLE subject_grading_components (component_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, subject_id BIGINT UNSIGNED NOT NULL, component_name VARCHAR(100) NOT NULL, weight_percent DECIMAL(5,2) NOT NULL, sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1, status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(subject_id,component_name), FOREIGN KEY(subject_id) REFERENCES subjects(subject_id));
CREATE TABLE teacher_assignments (teacher_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, subject_id BIGINT UNSIGNED NOT NULL, section_id BIGINT UNSIGNED NOT NULL, school_year_id BIGINT UNSIGNED NOT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', UNIQUE(teacher_id,subject_id,section_id,school_year_id), FOREIGN KEY(teacher_id) REFERENCES users(user_id), FOREIGN KEY(subject_id) REFERENCES subjects(subject_id), FOREIGN KEY(section_id) REFERENCES sections(section_id), FOREIGN KEY(school_year_id) REFERENCES school_years(school_year_id));
CREATE TABLE grading_periods (grading_period_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_year_id BIGINT UNSIGNED NOT NULL, period_name VARCHAR(60) NOT NULL, period_order SMALLINT UNSIGNED NOT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', UNIQUE(school_year_id,period_name), UNIQUE(school_year_id,period_order), FOREIGN KEY(school_year_id) REFERENCES school_years(school_year_id));
CREATE TABLE grades (grade_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id BIGINT UNSIGNED NOT NULL, teacher_assignment_id BIGINT UNSIGNED NOT NULL, grading_period_id BIGINT UNSIGNED NOT NULL, grade_value DECIMAL(5,2) NULL, grade_status ENUM('draft','submitted','approved','finalized','released') NOT NULL DEFAULT 'draft', submitted_at DATETIME NULL, finalized_at DATETIME NULL, released_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE(student_id,teacher_assignment_id,grading_period_id), FOREIGN KEY(student_id) REFERENCES students(student_id), FOREIGN KEY(teacher_assignment_id) REFERENCES teacher_assignments(teacher_assignment_id), FOREIGN KEY(grading_period_id) REFERENCES grading_periods(grading_period_id));
CREATE TABLE audit_logs (audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, action VARCHAR(100) NOT NULL, entity_type VARCHAR(60) NOT NULL, entity_id BIGINT UNSIGNED NULL, details JSON NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET NULL, INDEX(created_at), INDEX(entity_type,entity_id));
CREATE TABLE password_resets (reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(user_id), INDEX(expires_at));
