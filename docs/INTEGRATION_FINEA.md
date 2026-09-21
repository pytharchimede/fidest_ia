# Intégration FINEA avec FIDEST IA

## Gestion de l'occupation et de la charge serveur

FINEA doit vérifier `GET /api/v1/ocr/status` avant tout envoi.

Comportement recommandé :

- `available` : autoriser l'analyse ;
- `busy` : afficher un loader et réinterroger après environ 3 secondes ;
- `paused` : afficher « IA temporairement en pause pour protéger le serveur », conserver le fichier/formulaire et attendre `retry_after` secondes avant de réinterroger ;
- `unavailable` : ne pas envoyer le fichier.

Si le POST reçoit HTTP `409` avec `OCR_BUSY` ou `OCR_RESOURCE_BUSY`, revenir au même état d'attente. Pour `OCR_RESOURCE_BUSY`, respecter l'en-tête `Retry-After` ou `meta.retry_after`.

Ne jamais lancer plusieurs retries lourds en parallèle et ne jamais réuploader automatiquement le fichier pendant l'état `paused`.
