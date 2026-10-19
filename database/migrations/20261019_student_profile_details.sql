-- Run once on an existing SPI database to enable student-entered profile details.
CREATE TABLE IF NOT EXISTS student_profile_details (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_profile_details_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_student_profile_details_section FOREIGN KEY (section_id) REFERENCES sections(section_id)
) ENGINE=InnoDB;
