ALTER TABLE documents ADD COLUMN normalized_text LONGTEXT NULL AFTER extracted_text;
ALTER TABLE documents ADD COLUMN anomalies JSON NULL AFTER extracted_data;
ALTER TABLE documents ADD COLUMN confidence_scores JSON NULL AFTER anomalies;

CREATE TABLE IF NOT EXISTS document_field_corrections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(120) NOT NULL,
    detected_value TEXT NULL,
    corrected_value TEXT NULL,
    corrected_by VARCHAR(120) NOT NULL DEFAULT 'admin',
    corrected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_corrections_document (document_id, corrected_at),
    CONSTRAINT fk_corrections_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
