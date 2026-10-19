-- One-time data-preserving copy. Import database/drick.sql first.
-- This copies existing rows; it does not drop or alter spi_grading_system.
INSERT INTO drick.users (user_id, username, email, password, role, account_status, must_change_password, created_at, updated_at)
SELECT user_id, username, email, password, role, account_status, must_change_password, created_at, updated_at FROM spi_grading_system.users;

INSERT INTO drick.teacher_profiles (user_id, full_name)
SELECT user_id, full_name FROM spi_grading_system.teacher_profiles;

INSERT INTO drick.students (student_id, lrn, firstname, middle_name, lastname, suffix, record_status, created_at, updated_at)
SELECT student_id, lrn, firstname, middle_name, lastname, suffix, record_status, created_at, updated_at FROM spi_grading_system.students;

INSERT INTO drick.school_years (school_year_id, school_year_name, start_date, end_date, is_active)
SELECT school_year_id, school_year_name, start_date, end_date, is_active FROM spi_grading_system.school_years;

-- The fresh schema seeds default strands. Replace those seed rows with source IDs before copying dependent sections/subjects.
DELETE FROM drick.strands;
INSERT INTO drick.strands (strand_id, strand_code, strand_name, status)
SELECT strand_id, strand_code, strand_name, status FROM spi_grading_system.strands;

INSERT INTO drick.sections (section_id, school_year_id, grade_level, strand_id, section_name, status)
SELECT section_id, school_year_id, grade_level, strand_id, section_name, status FROM spi_grading_system.sections;

INSERT INTO drick.student_enrollments (student_enrollment_id, student_id, section_id, school_year_id, assignment_status, created_at)
SELECT student_enrollment_id, student_id, section_id, school_year_id, assignment_status, created_at FROM spi_grading_system.student_enrollments;

INSERT INTO drick.student_account_requests (request_id, user_id, student_id, submitted_lrn, submitted_firstname, submitted_middle_name, submitted_lastname, request_status, rejection_reason, reviewed_by, reviewed_at, created_at)
SELECT request_id, user_id, student_id, submitted_lrn, submitted_firstname, submitted_middle_name, submitted_lastname, request_status, rejection_reason, reviewed_by, reviewed_at, created_at FROM spi_grading_system.student_account_requests;

INSERT INTO drick.student_profile_details (user_id, track_name, grade_level, section_name, updated_at)
SELECT pd.user_id,
       COALESCE(pd.track_name, s.strand_code),
       COALESCE(pd.grade_level, sec.grade_level),
       COALESCE(pd.section_name, sec.section_name),
       pd.updated_at
FROM spi_grading_system.student_profile_details pd
LEFT JOIN spi_grading_system.sections sec ON sec.section_id = pd.section_id
LEFT JOIN spi_grading_system.strands s ON s.strand_id = sec.strand_id;

INSERT INTO drick.subjects (subject_id, subject_code, subject_name, description, grade_level, strand_id, status)
SELECT subject_id, subject_code, subject_name, description, grade_level, strand_id, status FROM spi_grading_system.subjects;

INSERT INTO drick.subject_grading_components (component_id, subject_id, component_name, weight_percent, sort_order, status, created_at)
SELECT component_id, subject_id, component_name, weight_percent, sort_order, status, created_at FROM spi_grading_system.subject_grading_components;

INSERT INTO drick.teacher_assignments (teacher_assignment_id, teacher_id, subject_id, section_id, school_year_id, status)
SELECT teacher_assignment_id, teacher_id, subject_id, section_id, school_year_id, status FROM spi_grading_system.teacher_assignments;

INSERT INTO drick.grading_periods (grading_period_id, school_year_id, period_name, period_order, status)
SELECT grading_period_id, school_year_id, period_name, period_order, status FROM spi_grading_system.grading_periods;

INSERT INTO drick.grades (grade_id, student_id, teacher_assignment_id, grading_period_id, grade_value, grade_status, submitted_at, finalized_at, released_at, created_at, updated_at)
SELECT grade_id, student_id, teacher_assignment_id, grading_period_id, grade_value, grade_status, submitted_at, finalized_at, released_at, created_at, updated_at FROM spi_grading_system.grades;

INSERT INTO drick.audit_logs (audit_id, user_id, action, entity_type, entity_id, details, created_at)
SELECT audit_id, user_id, action, entity_type, entity_id, details, created_at FROM spi_grading_system.audit_logs;

INSERT INTO drick.password_resets (reset_id, user_id, token_hash, token_salt, attempt_count, expires_at, used_at, created_at)
SELECT reset_id, user_id, token_hash, token_salt, attempt_count, expires_at, used_at, created_at FROM spi_grading_system.password_resets;

INSERT IGNORE INTO drick.strands (strand_code, strand_name) VALUES
    ('HUMSS', 'Humanities and Social Sciences'),
    ('ICT', 'Information and Communications Technology'),
    ('ABM', 'Accountancy, Business, and Management'),
    ('STEM', 'Science, Technology, Engineering, and Mathematics'),
    ('HE', 'Home Economics');
