INSERT INTO document_types (code, name, description, extraction_schema, active)
VALUES (
    'GENERAL',
    'Document libre',
    'Type générique permettant d’extraire le contenu de tout document sans règle métier spécifique.',
    JSON_OBJECT(
        'fields', JSON_ARRAY(),
        'keywords', JSON_ARRAY()
    ),
    1
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    active = 1;

UPDATE document_types
SET extraction_schema = JSON_SET(
    COALESCE(extraction_schema, JSON_OBJECT()),
    '$.keywords', JSON_ARRAY('facture', 'fne', 'ttc', 'net à payer', 'ncc')
)
WHERE code = 'FNE_INVOICE';

UPDATE document_types
SET extraction_schema = JSON_SET(
    COALESCE(extraction_schema, JSON_OBJECT()),
    '$.keywords', JSON_ARRAY('bon de commande', 'commande', 'purchase order', 'bc')
)
WHERE code = 'PURCHASE_ORDER';
