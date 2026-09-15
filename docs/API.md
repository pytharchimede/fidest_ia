# FIDEST IA — API de contrôle documentaire

FIDEST IA expose une API HTTP versionnée permettant à FINEA, IFMAP, LBP ou toute autre application d'envoyer un document pour OCR, typage, extraction et validation.

## URL de base

```text
https://votre-domaine/api/v1
```

Exemples de routes :

```text
POST /api/v1/documents/analyze
POST /api/v1/documents
GET  /api/v1/documents/{uuid}/analysis
GET  /api/v1/document-types
POST /api/v1/document-types
GET  /api/v1/health
GET  /api/v1/documents
GET  /api/v1/documents/{uuid}
GET  /api/v1/validation-rules
POST /api/v1/validation-rules
```

Aucune route publique v1 ne contient `.php`.

## Enregistrer une application cliente

Les clés API ne doivent pas être créées manuellement dans le code des applications clientes.

Depuis FIDEST IA :

```text
/admin
```

1. se connecter à l'administration ;
2. créer une application, par exemple `FINEA Production` ;
3. sélectionner les scopes nécessaires ;
4. définir éventuellement une date d'expiration ;
5. cliquer sur `Créer et générer la clé` ;
6. copier immédiatement la clé affichée.

Exemple de clé :

```text
fia_live_ab12cd34_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

La clé complète n'est affichée qu'une seule fois. FIDEST IA ne conserve en base qu'une empreinte SHA-256 de la clé.

Chaque application possède donc sa propre clé. Une clé peut être révoquée sans affecter les autres applications.

## Scopes disponibles

```text
documents:analyze   analyser et contrôler des documents
documents:read      consulter un document par UUID
documents:list      rechercher et lister les documents
types:read          lire le catalogue des types documentaires
types:write         créer des types documentaires
rules:read          lire les règles de validation
rules:write         créer les règles de validation
```

Recommandation pour FINEA :

```text
documents:analyze
types:read
```

N'accordez `types:write` qu'à une application autorisée à administrer le catalogue documentaire.

## Authentification

Toutes les requêtes API v1 utilisent :

```http
Authorization: Bearer VOTRE_CLE_API
Accept: application/json
```

Exemple :

```bash
curl 'https://votre-domaine/api/v1/health' \
  -H 'Authorization: Bearer fia_live_...'
```

## 1. Analyser et contrôler un document

### Endpoint

```http
POST /api/v1/documents/analyze
Authorization: Bearer <clé avec documents:analyze>
Content-Type: multipart/form-data
```

### Champs multipart

| Champ | Obligatoire | Description |
|---|---:|---|
| `document` | oui | Fichier à analyser. |
| `document_type` | non | `AUTO` par défaut, `GENERAL`, ou code d'un type existant. |
| `client_reference` | non | Référence libre de l'application appelante. |

### Exemple cURL

```bash
curl -X POST 'https://votre-domaine/api/v1/documents/analyze' \
  -H 'Authorization: Bearer fia_live_...' \
  -H 'Accept: application/json' \
  -F 'document=@/chemin/facture.pdf' \
  -F 'document_type=AUTO' \
  -F 'client_reference=FEB-2026-00125'
```

### Exemple PHP pour FINEA

Stocker dans le `.env` de FINEA :

```dotenv
FIDEST_IA_URL=https://votre-domaine
FIDEST_IA_API_TOKEN=fia_live_...
```

Puis :

```php
<?php

$endpoint = rtrim((string) getenv('FIDEST_IA_URL'), '/') . '/api/v1/documents/analyze';
$token = (string) getenv('FIDEST_IA_API_TOKEN');

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => [
        'document' => new CURLFile('/chemin/facture.pdf'),
        'document_type' => 'AUTO',
        'client_reference' => 'FEB-2026-00125',
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 120,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode((string) $response, true);

if ($httpCode === 200 && ($result['success'] ?? false)) {
    $analysis = $result['data'] ?? [];
    $type = $analysis['document_type']['code'] ?? null;
    $valid = $analysis['validation']['valid'] ?? false;
    $fields = $analysis['data'] ?? [];
}
```

## Réponse d'analyse

```json
{
  "success": true,
  "data": {
    "document_id": 10,
    "uuid": "4c1a3e69-376c-4941-9898-98d27824237a",
    "status": "validated",
    "classification": {
      "automatic": true,
      "score": 20,
      "confidence": 1,
      "signals": [],
      "fallback_to_general": false
    },
    "document_type": {"code": "COMMERCIAL_INVOICE_CI", "name": "Facture commerciale"},
    "data": {},
    "validation": {"valid": true, "results": []}
  },
  "meta": {"request_id": "..."}
}
```

Ne pas confondre :

- `success=true` : traitement technique réussi ;
- `validation.valid=true` : règles métier satisfaites.

La réponse conserve ces champs et ajoute de manière rétrocompatible `raw_text`, `normalized_text`, `fields` avec score/source, `anomalies`, `scores`, `pages` et `metadata`. `POST /api/v1/documents` est un alias professionnel de la route d'analyse existante ; `GET /api/v1/documents/{uuid}/analysis` expose le résultat stocké.

## 2. Lister les types documentaires

```http
GET /api/v1/document-types
Authorization: Bearer <clé avec types:read>
```

```bash
curl 'https://votre-domaine/api/v1/document-types' \
  -H 'Authorization: Bearer fia_live_...'
```

## 3. Créer un type documentaire

```http
POST /api/v1/document-types
Authorization: Bearer <clé avec types:write>
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

## 4. Santé de l'API

`GET /api/v1/health` est public et ne révèle aucune information sensible.

## Rate limiting et erreurs

Chaque application dispose d'une limite par minute (60 par défaut). Les réponses exposent `X-RateLimit-Limit`, `X-RateLimit-Remaining` et, en cas de HTTP 429, `Retry-After`.

```json
{"success":false,"error":{"code":"INSUFFICIENT_SCOPE","message":"Scope requis : documents:read"},"meta":{"request_id":"..."}}
```

Codes usuels : 401 clé absente/invalide/expirée/révoquée, 403 scope insuffisant, 404 ressource absente, 422 entrée invalide, 429 limite atteinte, 500 erreur interne. Aucune trace d'exécution n'est retournée en production.

```http
GET /api/v1/health
Authorization: Bearer <clé valide>
```

La réponse indique également l'application reconnue et ses scopes.

## Gestion des clés dans FIDEST IA

Depuis `/admin`, l'administrateur peut :

- créer une application cliente ;
- générer automatiquement sa clé ;
- sélectionner les scopes ;
- définir une expiration ;
- voir le préfixe de la clé ;
- voir la dernière utilisation ;
- révoquer la clé.

La clé complète n'est jamais réaffichée après sa création.

## Codes HTTP

- `200` : succès ;
- `201` : ressource créée ;
- `204` : réponse OPTIONS ;
- `401` : clé absente, invalide, expirée ou révoquée ;
- `403` : clé valide mais scope insuffisant ;
- `404` : route inconnue ;
- `405` : méthode non autorisée ;
- `422` : requête/document invalide ou erreur d'analyse.

## Bonnes pratiques

- une clé différente par application et par environnement ;
- ne jamais stocker une clé API dans Git ;
- privilégier les appels serveur-à-serveur ;
- révoquer immédiatement une clé compromise ;
- n'accorder que les scopes nécessaires ;
- créer des clés distinctes pour développement, staging et production.
