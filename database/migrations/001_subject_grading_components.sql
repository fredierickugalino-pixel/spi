CREATE TABLE IF NOT EXISTS subject_grading_components (
    component_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subject_component_name (subject_id, component_name),
    CONSTRAINT fk_component_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
) ENGINE=InnoDB;
