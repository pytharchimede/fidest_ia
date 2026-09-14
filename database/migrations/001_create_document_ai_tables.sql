CREATE TABLE IF NOT EXISTS document_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    extraction_schema JSON NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    document_type_id BIGINT UNSIGNED NULL,
    client_reference VARCHAR(150) NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sha256 CHAR(64) NOT NULL,
    extracted_text LONGTEXT NULL,
    extracted_data JSON NULL,
    status ENUM('received','processing','validated','rejected','error') NOT NULL DEFAULT 'received',
    confidence DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documents_type (document_type_id),
    INDEX idx_documents_sha256 (sha256),
    INDEX idx_documents_client (client_reference),
    CONSTRAINT fk_documents_type FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS validation_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    rule_type VARCHAR(80) NOT NULL,
    field_name VARCHAR(120) NULL,
    scope_fields JSON NULL,
    parameters JSON NULL,
    error_message VARCHAR(255) NOT NULL,
    severity ENUM('info','warning','error') NOT NULL DEFAULT 'error',
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rules_type (document_type_id),
    CONSTRAINT fk_rules_type FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_validation_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    validation_rule_id BIGINT UNSIGNED NULL,
    passed TINYINT(1) NOT NULL,
    severity ENUM('info','warning','error') NOT NULL DEFAULT 'error',
    message VARCHAR(500) NOT NULL,
    context JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_validation_document (document_id),
    CONSTRAINT fk_validation_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_validation_rule FOREIGN KEY (validation_rule_id) REFERENCES validation_rules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO document_types (code, name, description, extraction_schema)
VALUES
('FNE_INVOICE', 'Facture FNE', 'Facture normalisée électronique', JSON_OBJECT('fields', JSON_ARRAY('invoice_number','supplier_name','client_name','date','amount_ttc'))),
('PURCHASE_ORDER', 'Bon de commande', 'Bon de commande client/fournisseur', JSON_OBJECT('fields', JSON_ARRAY('order_number','client_name','supplier_name','date','amount')))
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO validation_rules (document_type_id, name, rule_type, field_name, scope_fields, parameters, error_message, severity, sort_order)
SELECT id, 'Numéro FNE unique', 'unique_field', 'invoice_number', JSON_ARRAY(), JSON_OBJECT(), 'Une facture FNE avec ce numéro existe déjà.', 'error', 10
FROM document_types WHERE code='FNE_INVOICE'
AND NOT EXISTS (SELECT 1 FROM validation_rules vr WHERE vr.document_type_id=document_types.id AND vr.name='Numéro FNE unique');

INSERT INTO validation_rules (document_type_id, name, rule_type, field_name, scope_fields, parameters, error_message, severity, sort_order)
SELECT id, 'Numéro de BC unique par client', 'unique_field_with_scope', 'order_number', JSON_ARRAY('client_name'), JSON_OBJECT(), 'Ce numéro de bon de commande existe déjà pour ce client.', 'error', 10
FROM document_types WHERE code='PURCHASE_ORDER'
AND NOT EXISTS (SELECT 1 FROM validation_rules vr WHERE vr.document_type_id=document_types.id AND vr.name='Numéro de BC unique par client');
