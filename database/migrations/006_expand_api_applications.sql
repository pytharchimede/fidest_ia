ALTER TABLE api_clients ADD COLUMN code VARCHAR(100) NULL AFTER name;
ALTER TABLE api_clients ADD COLUMN description TEXT NULL AFTER code;
ALTER TABLE api_clients ADD COLUMN environment ENUM('production','staging','development') NOT NULL DEFAULT 'production' AFTER description;
ALTER TABLE api_clients ADD COLUMN origin VARCHAR(255) NULL AFTER environment;
ALTER TABLE api_clients ADD COLUMN last_four CHAR(4) NULL AFTER key_hash;
ALTER TABLE api_clients ADD COLUMN rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 60 AFTER scopes;
ALTER TABLE api_clients ADD COLUMN request_count BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER rate_limit_per_minute;
ALTER TABLE api_clients ADD COLUMN notes TEXT NULL AFTER request_count;
ALTER TABLE api_clients ADD UNIQUE INDEX uq_api_clients_code (code);
