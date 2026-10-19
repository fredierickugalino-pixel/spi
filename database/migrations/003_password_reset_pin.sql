USE spi_grading_system;

ALTER TABLE password_resets
    ADD COLUMN token_salt CHAR(32) NOT NULL DEFAULT '' AFTER token_hash,
    ADD COLUMN attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER used_at,
    ADD INDEX idx_password_reset_user_created (user_id, created_at);

UPDATE password_resets SET used_at = NOW() WHERE used_at IS NULL;
