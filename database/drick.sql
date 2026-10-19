-- Clean SPI grading schema. Safe to import alongside the existing database.
CREATE DATABASE IF NOT EXISTS drick
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE drick;

CREATE TABLE users (
    user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(190) NOT NULL,
    email VARCHAR(190) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') NOT NULL,
    account_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE teacher_profiles (
    user_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_teacher_profiles_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE students (
    student_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lrn VARCHAR(12) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    lastname VARCHAR(100) NOT NULL,
    suffix VARCHAR(30) NULL,
    record_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_lrn (lrn),
    KEY idx_students_name (lastname, firstname)
) ENGINE=InnoDB;

CREATE TABLE student_account_requests (
    request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    submitted_lrn VARCHAR(12) NOT NULL,
    submitted_firstname VARCHAR(100) NOT NULL,
    submitted_middle_name VARCHAR(100) NULL,
    submitted_lastname VARCHAR(100) NOT NULL,
    request_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(500) NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_student_request_user (user_id),
    UNIQUE KEY uq_student_request_record (student_id),
    KEY idx_student_request_status (request_status),
    KEY idx_student_request_lrn (submitted_lrn),
    CONSTRAINT fk_student_request_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_student_request_record FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_student_request_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE school_years (
    school_year_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_year_name VARCHAR(20) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (school_year_id),
    UNIQUE KEY uq_school_year_name (school_year_name),
    KEY idx_school_year_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE strands (
    strand_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    strand_code VARCHAR(30) NOT NULL,
    strand_name VARCHAR(100) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (strand_id),
    UNIQUE KEY uq_strand_code (strand_code)
) ENGINE=InnoDB;

CREATE TABLE sections (
    section_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_year_id BIGINT UNSIGNED NOT NULL,
    grade_level ENUM('11','12') NOT NULL,
    strand_id BIGINT UNSIGNED NOT NULL,
    section_name VARCHAR(100) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (section_id),
    UNIQUE KEY uq_section_year_grade_strand_name (school_year_id, grade_level, strand_id, section_name),
    KEY idx_sections_strand (strand_id),
    CONSTRAINT fk_sections_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
    CONSTRAINT fk_sections_strand FOREIGN KEY (strand_id) REFERENCES strands(strand_id)
) ENGINE=InnoDB;

CREATE TABLE student_enrollments (
    student_enrollment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    school_year_id BIGINT UNSIGNED NOT NULL,
    assignment_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_enrollment_id),
    UNIQUE KEY uq_student_enrollment_year (student_id, school_year_id),
    KEY idx_enrollment_section (section_id),
    KEY idx_enrollment_year (school_year_id),
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_enrollments_section FOREIGN KEY (section_id) REFERENCES sections(section_id),
    CONSTRAINT fk_enrollments_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id)
) ENGINE=InnoDB;

CREATE TABLE student_profile_details (
    user_id BIGINT UNSIGNED NOT NULL,
    track_name VARCHAR(100) NULL,
    grade_level VARCHAR(20) NULL,
    section_name VARCHAR(100) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_student_profile_details_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE subjects (
    subject_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_code VARCHAR(40) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    grade_level ENUM('11','12') NOT NULL,
    strand_id BIGINT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (subject_id),
    UNIQUE KEY uq_subject_code (subject_code),
    KEY idx_subject_strand (strand_id),
    CONSTRAINT fk_subjects_strand FOREIGN KEY (strand_id) REFERENCES strands(strand_id)
) ENGINE=InnoDB;

CREATE TABLE subject_grading_components (
    component_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id BIGINT UNSIGNED NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (component_id),
    UNIQUE KEY uq_subject_component (subject_id, component_name),
    CONSTRAINT fk_components_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
) ENGINE=InnoDB;

CREATE TABLE teacher_assignments (
    teacher_assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    school_year_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (teacher_assignment_id),
    UNIQUE KEY uq_teacher_subject_section_year (teacher_id, subject_id, section_id, school_year_id),
    KEY idx_teacher_assignments_subject (subject_id),
    KEY idx_teacher_assignments_section (section_id),
    KEY idx_teacher_assignments_year (school_year_id),
    CONSTRAINT fk_assignments_teacher FOREIGN KEY (teacher_id) REFERENCES users(user_id),
    CONSTRAINT fk_assignments_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    CONSTRAINT fk_assignments_section FOREIGN KEY (section_id) REFERENCES sections(section_id),
    CONSTRAINT fk_assignments_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id)
) ENGINE=InnoDB;

CREATE TABLE grading_periods (
    grading_period_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_year_id BIGINT UNSIGNED NOT NULL,
    period_name VARCHAR(60) NOT NULL,
    period_order SMALLINT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (grading_period_id),
    UNIQUE KEY uq_period_year_name (school_year_id, period_name),
    UNIQUE KEY uq_period_year_order (school_year_id, period_order),
    CONSTRAINT fk_periods_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id)
) ENGINE=InnoDB;

CREATE TABLE grades (
    grade_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    teacher_assignment_id BIGINT UNSIGNED NOT NULL,
    grading_period_id BIGINT UNSIGNED NOT NULL,
    grade_value DECIMAL(5,2) NULL,
    grade_status ENUM('draft','submitted','approved','finalized','released') NOT NULL DEFAULT 'draft',
    submitted_at DATETIME NULL,
    finalized_at DATETIME NULL,
    released_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (grade_id),
    UNIQUE KEY uq_student_assignment_period (student_id, teacher_assignment_id, grading_period_id),
    KEY idx_grades_assignment_period (teacher_assignment_id, grading_period_id),
    KEY idx_grades_status (grade_status),
    CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_grades_assignment FOREIGN KEY (teacher_assignment_id) REFERENCES teacher_assignments(teacher_assignment_id),
    CONSTRAINT fk_grades_period FOREIGN KEY (grading_period_id) REFERENCES grading_periods(grading_period_id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_entity (entity_type, entity_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    token_salt CHAR(32) NOT NULL,
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reset_id),
    UNIQUE KEY uq_password_reset_hash (token_hash),
    KEY idx_password_reset_expiry (expires_at),
    KEY idx_password_reset_user_created (user_id, created_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

INSERT INTO strands (strand_code, strand_name) VALUES
    ('HUMSS', 'Humanities and Social Sciences'),
    ('ICT', 'Information and Communications Technology'),
    ('ABM', 'Accountancy, Business, and Management'),
    ('STEM', 'Science, Technology, Engineering, and Mathematics'),
    ('HE', 'Home Economics');
