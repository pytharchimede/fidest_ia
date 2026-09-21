# FIDEST IA — API de contrôle documentaire

## État OCR

`GET /api/v1/ocr/status` expose quatre états : `available`, `busy`, `paused` et `unavailable`.

- `busy` signifie qu'un autre OCR est en cours ;
- `paused` signifie que FIDEST IA protège l'hébergement parce que la charge CPU/mémoire dépasse les seuils configurés.

Pendant `paused`, les POST OCR/analyse reçoivent HTTP `409` avec `error.code=OCR_RESOURCE_BUSY`, ainsi qu'un délai `Retry-After`.

Cette protection complète le verrou mono-traitement, `OCR_OMP_THREAD_LIMIT=1`, les timeouts et le DPI réduit du mode mutualisé.
