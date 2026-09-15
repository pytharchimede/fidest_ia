CREATE TABLE IF NOT EXISTS api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_client_id BIGINT UNSIGNED NULL,
    request_id CHAR(36) NOT NULL,
    method VARCHAR(10) NOT NULL,
    route VARCHAR(255) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    document_uuid CHAR(36) NULL,
    error_code VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_logs_client_date (api_client_id, created_at),
    INDEX idx_api_logs_route (route),
    INDEX idx_api_logs_status (status_code),
    INDEX idx_api_logs_request (request_id),
    CONSTRAINT fk_api_logs_client FOREIGN KEY (api_client_id) REFERENCES api_clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS api_rate_limits (
    api_client_id BIGINT UNSIGNED NOT NULL,
    window_start DATETIME NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (api_client_id, window_start),
    CONSTRAINT fk_rate_client FOREIGN KEY (api_client_id) REFERENCES api_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
