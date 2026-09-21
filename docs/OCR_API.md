# FIDEST IA — API OCR

L'API v1 permet d'extraire le texte brut d'une image ou d'un PDF sans lancer toute l'analyse documentaire.

## Extraire le texte OCR

```http
POST /api/v1/ocr
Authorization: Bearer <clé avec documents:analyze>
Content-Type: multipart/form-data
```

Champs :

- `document` (obligatoire) : PDF, JPG/JPEG, PNG, WEBP ou TIFF ;
- `language` (optionnel) : `fra`, `eng`, `fra+eng`, etc. La valeur par défaut vient de `OCR_LANGUAGES` ;
- `client_reference` (optionnel) : référence métier de l'application appelante.

Exemple :

```bash
curl --fail-with-body -X POST 'https://ia.fidest.ci/api/v1/ocr' \
  -H 'Authorization: Bearer VOTRE_CLE_API' \
  -H 'Accept: application/json' \
  -F 'document=@facture.pdf' \
  -F 'language=fra+eng'
```

Réponse :

```json
{
  "success": true,
  "data": {
    "document_id": "4c1a3e69-376c-4941-9898-98d27824237a",
    "filename": "facture.pdf",
    "mime_type": "application/pdf",
    "ocr": {
      "text": "FACTURE N° ...",
      "language": "fra+eng",
      "engine": "tesseract",
      "confidence": 0.91,
      "pages": 1,
      "processing_time_ms": 842
    }
  },
  "meta": {"request_id": "..."}
}
```

Le champ `data.ocr.text` contient le texte OCR brut complet. Il n'est pas tronqué par l'API.

## Relire le texte OCR d'un document

```http
GET /api/v1/documents/{uuid}/ocr
Authorization: Bearer <clé avec documents:read>
```

Une application cliente ne peut relire que les documents qui lui appartiennent. Le jeton maître conserve l'accès administratif.

```bash
curl 'https://ia.fidest.ci/api/v1/documents/4c1a3e69-376c-4941-9898-98d27824237a/ocr' \
  -H 'Authorization: Bearer VOTRE_CLE_API' \
  -H 'Accept: application/json'
```

## Analyse documentaire

`POST /api/v1/documents/analyze` continue de retourner le résultat OCR dans `data.ocr.text`, ainsi que `raw_text`, les champs structurés, la classification, les anomalies, scores et validations.

`GET /api/v1/documents/{uuid}` et `/analysis` exposent également un objet `ocr` contenant le texte stocké et sa confiance.

## Sécurité et limites

- authentification Bearer obligatoire ;
- contrôle de scope ;
- isolation des documents entre applications lors de la lecture ;
- contrôle serveur du MIME ;
- noms de stockage générés côté serveur ;
- taille maximale définie par `MAX_UPLOAD_MB` ;
- rate limiting par application ;
- fichiers stockés hors exposition publique directe.

Codes d'erreur usuels : `FILE_REQUIRED`, `FILE_TOO_LARGE`, `INVALID_DOCUMENT`, `INVALID_OCR_LANGUAGE`, `INVALID_API_KEY`, `DOCUMENT_NOT_FOUND`, `DOCUMENT_ACCESS_DENIED`, `OCR_FAILED`, `RATE_LIMIT_EXCEEDED` et `INTERNAL_ERROR`.


## Disponibilité du moteur OCR

Avant d'envoyer un document, une application cliente peut interroger :

```http
GET /api/v1/ocr/status
Authorization: Bearer <clé avec documents:analyze>
Accept: application/json
```

Réponse disponible :

```json
{"success":true,"data":{"ocr":{"status":"available","available":true,"busy":false,"message":"FIDEST IA est disponible."}}}
```

Pendant le traitement d'un autre document :

```json
{"success":true,"data":{"ocr":{"status":"busy","available":false,"busy":true,"message":"Je suis occupée en ce moment. Merci de patienter."}}}
```

Si Tesseract n'est pas opérationnel, `status` vaut `unavailable`. Dans ce cas le client ne doit pas envoyer le document.

FIDEST IA n'exécute qu'un seul OCR à la fois lorsque `OCR_SHARED_HOSTING_MODE=true`. Si deux clients démarrent simultanément malgré le contrôle préalable, le second reçoit HTTP `409` avec `error.code=OCR_BUSY`. Le client doit conserver son formulaire, afficher un loader et réinterroger `/ocr/status` après 2 à 3 secondes. Il ne doit pas renvoyer automatiquement le fichier tant que `status` n'est pas `available`.


## Pause protectrice liée aux ressources serveur

Le statut OCR peut aussi retourner :

```json
{
  "success": true,
  "data": {
    "ocr": {
      "status": "paused",
      "available": false,
      "busy": true,
      "paused": true,
      "message": "FIDEST IA est temporairement en pause pour protéger les ressources du serveur. Merci de patienter.",
      "retry_after": 15
    }
  }
}
```

Cet état signifie que FIDEST IA a détecté une charge CPU ou mémoire élevée. Aucun nouveau Tesseract/Ghostscript n'est démarré tant que les ressources ne sont pas revenues sous les seuils configurés.

Un POST reçu dans cet état retourne HTTP `409`, `error.code=OCR_RESOURCE_BUSY` et un en-tête `Retry-After`. Le client doit attendre puis interroger de nouveau `/ocr/status`.
