# FIDEST IA — API v1 de contrôle documentaire

FIDEST IA expose une API HTTP versionnée permettant à FINEA, IFMAP, LBP ou toute autre application autorisée d'envoyer des documents pour OCR, typage, extraction et validation.

## Base URL

```text
https://votre-domaine.tld/api/v1
```

Si FIDEST IA est installé dans un sous-dossier :

```text
https://votre-domaine.tld/fidest_ia/api/v1
```

Aucune route publique v1 ne contient `.php`.

## Authentification

Toutes les routes v1 exigent :

```http
Authorization: Bearer VOTRE_JETON_API
Accept: application/json
```

Le jeton est défini côté serveur avec `API_BEARER_TOKEN`. Il ne doit jamais être placé dans du JavaScript livré au navigateur. Pour FINEA, privilégier un appel PHP serveur-à-serveur.

## Routes

| Méthode | Route | Fonction |
|---|---|---|
| `POST` | `/documents/analyze` | OCR, classification, extraction et contrôle d'un document |
| `GET` | `/document-types` | Liste des types documentaires disponibles |
| `POST` | `/document-types` | Création d'un type documentaire |
| `GET` | `/health` | Vérification de disponibilité de l'API |

## Analyser un document

```http
POST /api/v1/documents/analyze
Authorization: Bearer ...
Content-Type: multipart/form-data
```

Champs :

- `document` : obligatoire ; fichier à analyser.
- `document_type` : facultatif ; `AUTO` par défaut, `GENERAL` ou code explicite.
- `client_reference` : facultatif ; identifiant du dossier dans l'application appelante.

### PHP / FINEA

```php
$endpoint = 'https://ia.example.com/api/v1/documents/analyze';
$token = getenv('FIDEST_IA_API_TOKEN');

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => [
        'document' => new CURLFile($filePath),
        'document_type' => 'AUTO',
        'client_reference' => 'FEB-' . $febId,
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 120,
]);

$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($raw === false || $error !== '') {
    throw new RuntimeException('FIDEST IA indisponible: ' . $error);
}

$result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

if ($httpCode !== 200 || !($result['success'] ?? false)) {
    throw new RuntimeException($result['error'] ?? 'Contrôle documentaire impossible.');
}

$isValid = (bool) ($result['validation']['valid'] ?? false);
$type = $result['document_type']['code'] ?? null;
$data = $result['data'] ?? [];
$uuid = $result['uuid'] ?? null;
```

## Exemple de réponse

```json
{
  "success": true,
  "document_id": 10,
  "uuid": "4c1a3e69-376c-4941-9898-98d27824237a",
  "status": "validated",
  "classification": {
    "automatic": true,
    "score": 20,
    "signals": [],
    "fallback_to_general": false
  },
  "document_type": {
    "code": "COMMERCIAL_INVOICE_CI",
    "name": "Facture commerciale / fournisseur"
  },
  "file": {
    "original_name": "facture.pdf",
    "mime_type": "application/pdf",
    "size": 146488,
    "sha256": "..."
  },
  "ocr": {
    "engine": "tesseract+poppler",
    "confidence": null,
    "text": "..."
  },
  "data": {},
  "validation": {
    "valid": true,
    "results": []
  }
}
```

`success=true` signifie que la requête a été traitée techniquement. `validation.valid=true` signifie que les règles métier du type documentaire ont été satisfaites. Les deux notions ne doivent pas être confondues.

## Types documentaires

### Lister

```http
GET /api/v1/document-types
Authorization: Bearer ...
```

### Créer

```http
POST /api/v1/document-types
Authorization: Bearer ...
Content-Type: application/json
```

```json
{
  "name": "Bon de livraison",
  "code": "DELIVERY_NOTE",
  "description": "Bon de livraison fournisseur",
  "fields": ["numero", "fournisseur", "client", "date"],
  "keywords": ["bon de livraison", "livré à", "réception"]
}
```

## Health check

```http
GET /api/v1/health
Authorization: Bearer ...
```

## Codes HTTP

- `200` : requête réussie.
- `201` : ressource créée.
- `204` : requête `OPTIONS` CORS.
- `401` : jeton absent ou invalide.
- `404` : route inconnue.
- `422` : document, données ou traitement invalides.

## Configuration serveur

```dotenv
API_ENABLED=true
API_BEARER_TOKEN=jeton-secret-long-et-aleatoire
API_ALLOWED_ORIGINS=
```

Pour une intégration PHP serveur-à-serveur comme FINEA, `API_ALLOWED_ORIGINS` peut rester vide : CORS concerne les navigateurs, pas les appels backend cURL.

Ne versionnez jamais le jeton API dans Git. Dans l'application consommatrice, stockez également son jeton dans son propre `.env` ou gestionnaire de secrets.

## Recommandation d'intégration

Conserver dans l'application appelante au minimum :

- `uuid` FIDEST IA ;
- `document_type.code` ;
- `validation.valid` ;
- `data` ;
- `file.sha256` si la traçabilité du fichier est nécessaire.

Pour audit, conserver aussi la réponse JSON complète.
