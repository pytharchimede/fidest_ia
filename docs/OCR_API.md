# FIDEST IA — API OCR

L'API v1 permet d'extraire le texte brut d'une image ou d'un PDF sans lancer toute l'analyse documentaire.

## Disponibilité du moteur OCR

Avant d'envoyer un document, une application cliente doit interroger :

```http
GET /api/v1/ocr/status
Authorization: Bearer <clé avec documents:analyze>
Accept: application/json
```

États possibles :

- `available` : le document peut être envoyé ;
- `busy` : un autre OCR détient déjà le verrou ;
- `paused` : la charge CPU/mémoire du serveur est trop élevée ; aucun nouveau traitement lourd ne doit démarrer ;
- `unavailable` : moteur OCR indisponible.

Exemple de pause protectrice :

```json
{"success":true,"data":{"ocr":{"status":"paused","available":false,"busy":true,"paused":true,"message":"FIDEST IA est temporairement en pause pour protéger les ressources du serveur. Merci de patienter.","retry_after":15}}}
```

Si un POST arrive pendant cette pause, l'API retourne HTTP `409`, `error.code=OCR_RESOURCE_BUSY` et `Retry-After`.

Le client doit attendre `retry_after` secondes puis réinterroger `/ocr/status`. Il ne doit pas réuploader automatiquement le document tant que l'état n'est pas `available`.

## Extraire le texte OCR

```http
POST /api/v1/ocr
Authorization: Bearer <clé avec documents:analyze>
Content-Type: multipart/form-data
```

Champs :

- `document` obligatoire : PDF, JPG/JPEG, PNG, WEBP ou TIFF ;
- `language` optionnel : `fra`, `eng`, `fra+eng` ;
- `client_reference` optionnel.

FIDEST IA n'exécute qu'un seul OCR à la fois en mode mutualisé et peut suspendre temporairement le démarrage de nouveaux OCR lorsque les ressources serveur sont trop sollicitées.
