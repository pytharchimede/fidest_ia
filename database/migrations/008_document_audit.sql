ALTER TABLE documents ADD COLUMN api_client_id BIGINT UNSIGNED NULL AFTER document_type_id;
ALTER TABLE documents ADD INDEX idx_documents_api_client (api_client_id);

CREATE TABLE IF NOT EXISTS document_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document_history_document (document_id, created_at),
    CONSTRAINT fk_history_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
