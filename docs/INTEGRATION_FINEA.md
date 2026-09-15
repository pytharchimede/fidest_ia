# Intégration FINEA avec FIDEST IA

## 1. Créer l'accès FINEA

Dans `https://ia.fidest.ci/admin/applications`, créer **FINEA Production** avec
les scopes suivants :

```text
documents:analyze
documents:read
types:read
```

Copier immédiatement la clé `fia_live_...` : elle ne sera affichée qu'une
fois. Cette clé est distincte du jeton de connexion à l'administration.

## 2. Configurer FINEA

Ajouter les valeurs suivantes au `.env` de FINEA, sans les versionner :

```dotenv
FIDEST_IA_URL=https://ia.fidest.ci
FIDEST_IA_API_TOKEN=fia_live_xxxxxxxxx
FIDEST_IA_TIMEOUT=180
```

Après modification, recharger la configuration de FINEA selon son framework.

## 3. Vérifier le service

```bash
curl --fail-with-body 'https://ia.fidest.ci/api/v1/health'
```

Une réponse valide contient `"status": "ok"`.

## 4. Envoyer un document

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
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => (int) (getenv('FIDEST_IA_TIMEOUT') ?: 180),
]);
$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$transportError = curl_error($curl);
curl_close($curl);

if ($response === false) {
    throw new RuntimeException('FIDEST IA inaccessible : ' . $transportError);
}

$payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
$requestId = $payload['meta']['request_id'] ?? null;

if ($status !== 200 || !($payload['success'] ?? false)) {
    $code = $payload['error']['code'] ?? 'UNKNOWN_ERROR';
    $message = $payload['error']['message'] ?? 'Erreur FIDEST IA';
    throw new RuntimeException("$code: $message (requête $requestId)");
}

$analysis = $payload['data'];
$documentUuid = $analysis['uuid'];
$documentType = $analysis['document_type']['code'];
$businessValid = $analysis['validation']['valid'];
$values = $analysis['data'];
$confidence = $analysis['scores']['overall'] ?? null;
```

## 5. Règles de traitement dans FINEA

- `success=true` signifie que le traitement technique a abouti.
- `data.validation.valid=true` signifie que les règles métier configurées sont
  satisfaites.
- Conserver `data.uuid` avec le dossier FINEA pour consulter ultérieurement
  `GET /api/v1/documents/{uuid}/analysis`.
- Conserver `meta.request_id` dans les logs techniques.
- Soumettre `client_reference` avec l'identifiant stable du dossier FINEA.
- Ne jamais journaliser `FIDEST_IA_API_TOKEN`.

Traiter `401` comme une clé absente, invalide, expirée ou révoquée ; `403`
comme un scope insuffisant ; `422` comme un document ou paramètre invalide ;
`429` comme une limitation temporaire ; et `5xx` comme une indisponibilité à
réessayer avec temporisation progressive.

Le contrôle ne constitue pas une validation juridique d'authenticité sans
interrogation d'un service officiel.
