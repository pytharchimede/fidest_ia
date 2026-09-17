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
