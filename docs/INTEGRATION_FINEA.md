# Intégration FINEA avec FIDEST IA

Créer « FINEA Production » dans `/admin/applications`, attribuer les scopes nécessaires, puis copier la clé affichée une seule fois.

```dotenv
FIDEST_IA_URL=https://ia.fidest.ci
FIDEST_IA_API_TOKEN=fia_live_xxxxxxxxx
```

```bash
curl --fail-with-body -X POST "$FIDEST_IA_URL/api/v1/documents/analyze" \
  -H "Authorization: Bearer $FIDEST_IA_API_TOKEN" \
  -H "Accept: application/json" \
  -F "document=@facture.pdf" \
  -F "document_type=AUTO" \
  -F "client_reference=FINEA-2026-001"
```

```php
<?php
$curl = curl_init(getenv('FIDEST_IA_URL') . '/api/v1/documents/analyze');
curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . getenv('FIDEST_IA_API_TOKEN'), 'Accept: application/json'],
    CURLOPT_POSTFIELDS => ['document' => new CURLFile('/chemin/facture.pdf'), 'document_type' => 'AUTO', 'client_reference' => 'FINEA-2026-001'],
]);
$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
```

Une réussite contient `success`, `data` et `meta.request_id`. Une erreur contient `error.code` et `error.message`. Traiter 401, 403, 422, 429 et 500. Le contrôle ne constitue pas une validation juridique d’authenticité sans interrogation d’un service officiel.
