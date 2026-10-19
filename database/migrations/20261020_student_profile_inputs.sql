-- Run once on an existing SPI database to store student-entered profile details.
ALTER TABLE student_profile_details
    MODIFY COLUMN section_id BIGINT UNSIGNED NULL,
    ADD COLUMN track_name VARCHAR(100) NULL AFTER section_id,
    ADD COLUMN grade_level VARCHAR(20) NULL AFTER track_name,
    ADD COLUMN section_name VARCHAR(100) NULL AFTER grade_level;
