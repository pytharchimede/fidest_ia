INSERT INTO document_types (code, name, description, extraction_schema, active)
VALUES (
    'COMMERCIAL_INVOICE_CI',
    'Facture commerciale / fournisseur',
    'Facture commerciale classique, fournisseur ou prestataire, distincte d’une FNE.',
    JSON_OBJECT(
        'category', 'commercial',
        'fields', JSON_ARRAY('invoice_number','supplier_name','client_name','date','due_date','description','amount_ht','tax_amount','amount_ttc','balance'),
        'keywords', JSON_ARRAY('facture','date de facturation','date d échéance','sous-total','total','solde'),
        'classification', JSON_OBJECT(
            'required_any', JSON_ARRAY('facture','invoice'),
            'strong_keywords', JSON_ARRAY('date de facturation','date d échéance','facturé à','sous-total','solde'),
            'negative_keywords', JSON_ARRAY('relevé d identité bancaire','attestation bancaire'),
            'min_score', 4
        )
    ),
    1
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    extraction_schema = VALUES(extraction_schema),
    active = 1;

UPDATE document_types
SET extraction_schema = JSON_SET(
    COALESCE(extraction_schema, JSON_OBJECT()),
    '$.classification', JSON_OBJECT(
        'required_any', JSON_ARRAY('relevé d identité bancaire','releve d identite bancaire','rib bancaire'),
        'strong_keywords', JSON_ARRAY('relevé d identité bancaire','releve d identite bancaire','titulaire du compte','code banque','code guichet','clé rib','cle rib'),
        'negative_keywords', JSON_ARRAY('facture','date de facturation','sous-total','solde','bon de commande'),
        'min_score', 5
    )
)
WHERE code = 'RIB_CI';

UPDATE document_types
SET extraction_schema = JSON_SET(
    COALESCE(extraction_schema, JSON_OBJECT()),
    '$.classification', JSON_OBJECT(
        'required_any', JSON_ARRAY('fne','facture normalisée','facture normalisee','direction générale des impôts','direction generale des impots'),
        'strong_keywords', JSON_ARRAY('facture normalisée électronique','facture normalisee electronique','fne','direction générale des impôts','direction generale des impots','dgi'),
        'negative_keywords', JSON_ARRAY('facture proforma'),
        'min_score', 5
    )
)
WHERE code = 'FNE_INVOICE';

UPDATE document_types
SET extraction_schema = JSON_SET(
    COALESCE(extraction_schema, JSON_OBJECT()),
    '$.classification', JSON_OBJECT(
        'required_any', JSON_ARRAY('bon de commande','purchase order'),
        'strong_keywords', JSON_ARRAY('bon de commande','purchase order','n° commande','numero commande'),
        'negative_keywords', JSON_ARRAY('facture','relevé d identité bancaire'),
        'min_score', 4
    )
)
WHERE code = 'PURCHASE_ORDER';
