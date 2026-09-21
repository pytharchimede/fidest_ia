# OCR gratuit sur hébergement mutualisé

## Choix technique

FIDEST IA utilise **Tesseract OCR**, moteur open source sous licence Apache-2.0. Le paquet Composer existant n'est qu'un wrapper : le vrai travail OCR est toujours effectué par l'exécutable Tesseract.

Il n'existe pas de moteur OCR PHP pur, maintenu et de qualité comparable qui résolve l'absence de cet exécutable. Tesseract.js est un port WebAssembly réel, mais un navigateur est requis : il ne peut pas traiter les appels serveur de FINEA. L'API utilise donc soit Tesseract système, soit un AppImage/binaire utilisateur placé dans le compte cPanel.

## Protection de charge en production

En mode mutualisé, utiliser à la fois le verrou OCR et le garde-fou global de ressources :

```dotenv
OCR_SHARED_HOSTING_MODE=true
OCR_OMP_THREAD_LIMIT=1
OCR_LOCK_FILE=/home/fidestci/ia.fidest.ci/storage/locks/ocr.lock
OCR_LOCK_WAIT_SECONDS=2
PDF_SHARED_HOSTING_DPI=75

SERVER_RESOURCE_GUARD=true
SERVER_MAX_LOAD_PER_CPU=1.20
SERVER_MAX_MEMORY_PERCENT=85
SERVER_RESOURCE_RETRY_AFTER=15
```

Le verrou empêche plusieurs OCR lourds de s'empiler. Le garde-fou vérifie en plus la charge système avant la conversion PDF, avant chaque page OCR et pendant l'attente du verrou.

Quand la charge CPU normalisée par coeur ou la mémoire utilisée dépasse le seuil configuré, FIDEST IA ne démarre pas de nouveau traitement lourd et retourne l'état `paused`. L'objectif est de protéger Apache/PHP/MySQL et les autres applications du même hébergement.

`GET /api/v1/ocr/status` peut alors retourner :

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

Un POST lancé pendant cette pause retourne HTTP `409`, `error.code=OCR_RESOURCE_BUSY` et un en-tête `Retry-After`.

Le client doit attendre puis refaire uniquement le contrôle de statut. Il ne doit pas renvoyer le fichier en boucle.

### Compatibilité hébergement mutualisé

Le contrôle lit `sys_getloadavg()`, `/proc/cpuinfo` et `/proc/meminfo` lorsqu'ils sont accessibles. Si certaines métriques sont masquées par l'hébergeur, elles sont simplement ignorées : les protections par verrou, timeout et limitation de threads restent actives.

## Pipeline PDF

Ordre automatique : Poppler, Ghostscript, ImageMagick. En production actuelle, `/bin/gs` peut être sélectionné. Une résolution faible en mode mutualisé limite CPU et mémoire.

## Valeurs recommandées

- `SERVER_MAX_LOAD_PER_CPU=1.20` : seuil prudent pour un serveur partagé ;
- `SERVER_MAX_MEMORY_PERCENT=85` : laisse une marge pour Apache, PHP et MySQL ;
- `SERVER_RESOURCE_RETRY_AFTER=15` : évite un polling agressif ;
- `OCR_OMP_THREAD_LIMIT=1` : Tesseract reste mono-thread côté instance FIDEST IA.

Ajuster les seuils à partir des observations cPanel sans chercher à utiliser 100 % des ressources disponibles.
