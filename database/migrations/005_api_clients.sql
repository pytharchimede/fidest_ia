CREATE TABLE IF NOT EXISTS api_clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    key_prefix VARCHAR(32) NOT NULL UNIQUE,
    key_hash CHAR(64) NOT NULL UNIQUE,
    scopes JSON NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    INDEX idx_api_clients_active (active),
    INDEX idx_api_clients_prefix (key_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
