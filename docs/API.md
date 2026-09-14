# FIDEST IA — API de contrôle documentaire

Cette documentation décrit l'API HTTP permettant à FINEA, IFMAP, LBP ou toute autre application d'envoyer un document à FIDEST IA pour OCR, typage, extraction et validation.

## URL de base

En production, remplacez `https://ia.fidest.ci` par l'URL réelle de l'installation.

```text
https://ia.fidest.ci/api
```

En installation sous-dossier :

```text
https://example.com/fidest_ia/api
```

## 1. Analyser et contrôler un document

### Endpoint

```http
POST /documents/analyze.php
Content-Type: multipart/form-data
```

### Champs multipart

| Champ | Obligatoire | Description |
|---|---:|---|
| `document` | oui | Fichier à analyser. Images et PDF selon les capacités OCR du serveur. |
| `document_type` | non | `AUTO` par défaut, `GENERAL`, ou le code d'un type documentaire existant. |
| `client_reference` | non | Référence libre provenant de l'application appelante : dossier, client, FEB, commande, etc. |

### Modes de typage

- `AUTO` : FIDEST IA OCRise le document puis tente de déterminer son type.
- `GENERAL` : aucun typage spécialisé ; restitution OCR générique.
- code explicite, par exemple `FNE_INVOICE` : force l'analyse avec ce type.

### Exemple cURL

```bash
curl -X POST 'https://ia.fidest.ci/api/documents/analyze.php' \
  -F 'document=@/chemin/facture.pdf' \
  -F 'document_type=AUTO' \
  -F 'client_reference=FEB-2026-00125'
```

### Exemple PHP

```php
<?php

$ch = curl_init('https://ia.fidest.ci/api/documents/analyze.php');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => [
        'document' => new CURLFile('/chemin/facture.pdf'),
        'document_type' => 'AUTO',
        'client_reference' => 'FEB-2026-00125',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && ($result['success'] ?? false)) {
    $type = $result['document_type']['code'] ?? null;
    $valid = $result['validation']['valid'] ?? false;
    $data = $result['data'] ?? [];
}
```

### Exemple JavaScript

```javascript
const body = new FormData();
body.append('document', file);
body.append('document_type', 'AUTO');
body.append('client_reference', 'DOSSIER-2026-001');

const response = await fetch('https://ia.fidest.ci/api/documents/analyze.php', {
  method: 'POST',
  body
});

const result = await response.json();

if (!result.success) {
  throw new Error(result.error || 'Analyse impossible');
}

console.log(result.document_type);
console.log(result.data);
console.log(result.validation);
```

## Réponse d'analyse

Exemple simplifié :

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
  "data": {
    "invoice_number": "21185"
  },
  "validation": {
    "valid": true,
    "results": []
  }
}
```

### Champs à exploiter en priorité

- `success` : traitement technique réussi ou non.
- `status` : `validated`, `rejected` ou `error` selon le traitement et les règles.
- `uuid` : identifiant stable du document dans FIDEST IA.
- `document_type.code` : type reconnu ou forcé.
- `classification.score` : score du typage automatique lorsqu'il existe.
- `classification.signals` : signaux ayant contribué au classement.
- `classification.fallback_to_general` : vrai lorsque le moteur n'a pas trouvé un type suffisamment fiable.
- `data` : données structurées extraites.
- `validation.valid` : résultat global des règles métier.
- `validation.results` : détail des contrôles appliqués.
- `ocr.text` : texte OCR brut ; utile pour audit/recherche mais à éviter comme seule source de décision métier.
- `file.sha256` : empreinte du fichier reçu.

## 2. Lister les types documentaires

```http
GET /document-types/
```

Exemple :

```bash
curl 'https://ia.fidest.ci/api/document-types/'
```

Réponse :

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "GENERAL",
      "name": "Document libre",
      "description": "...",
      "fields": [],
      "keywords": []
    }
  ]
}
```

Une application consommatrice peut utiliser cet endpoint pour alimenter automatiquement une liste de types sans recopier les codes dans son propre code source.

## 3. Créer un type documentaire

```http
POST /document-types/
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

Le code est généré depuis le nom s'il est omis. `AUTO` et `GENERAL` sont réservés.

## Intégration recommandée dans FINEA

Pour un contrôle de pièce jointe, FINEA doit :

1. recevoir le fichier utilisateur ;
2. l'envoyer à FIDEST IA via `POST /documents/analyze.php` ;
3. conserver au minimum `uuid`, `document_type.code`, `validation.valid`, `data` et éventuellement `sha256` ;
4. appliquer sa propre décision métier en fonction du résultat ;
5. conserver le JSON complet si un audit détaillé est nécessaire.

Exemple :

```php
if (!($result['success'] ?? false)) {
    // Erreur technique : ne pas considérer le document comme contrôlé.
}

if (($result['document_type']['code'] ?? '') !== 'FNE_INVOICE') {
    // La pièce fournie n'est pas reconnue comme FNE.
}

if (!($result['validation']['valid'] ?? false)) {
    // Le document a été analysé mais une règle de contrôle a échoué.
}
```

Ne confondez donc pas `success=true` avec `validation.valid=true` : le premier signifie que l'API a traité la requête, le second que les règles métier appliquées ont été satisfaites.

## Codes HTTP actuels

- `200` : analyse terminée ou lecture des types réussie.
- `201` : type documentaire créé.
- `204` : réponse CORS OPTIONS sur l'endpoint d'analyse.
- `405` : méthode HTTP non autorisée.
- `422` : document/requête invalide ou erreur pendant l'analyse.

Les consommateurs doivent toujours lire également `success` et `error` dans le JSON.

## Formats et OCR

Les images prises en charge dépendent de Tesseract et de la configuration serveur. Pour les PDF, l'installation actuelle utilise Poppler (`pdftoppm`) pour rasteriser les pages avant OCR Tesseract. Un serveur ne disposant pas de ces binaires ne pourra pas effectuer le même traitement PDF.

La taille maximale est pilotée par `MAX_UPLOAD_MB` dans la configuration de FIDEST IA.

## Sécurité — état actuel

L'endpoint d'analyse annonce actuellement `X-API-Key` dans les en-têtes CORS, mais aucune authentification par clé n'est encore appliquée. L'endpoint de gestion des types n'est pas non plus authentifié.

**Ne considérez donc pas l'API actuelle comme suffisamment sécurisée pour une exposition publique à des applications tierces non maîtrisées.** Avant ouverture Internet, ajouter des clés API par application, stockage hashé, scopes (`documents:analyze`, `types:read`, `types:write`), limitation de débit et restriction des origines autorisées.

Pour des applications hébergées sur le même serveur, privilégier les appels serveur-à-serveur plutôt qu'un appel JavaScript depuis le navigateur.

## Versionnement conseillé

La prochaine évolution de l'API devrait introduire une URL versionnée :

```text
/api/v1/documents/analyze
/api/v1/document-types
```

Cela permettra de faire évoluer FIDEST IA sans casser FINEA, IFMAP, LBP ou d'autres consommateurs existants.
