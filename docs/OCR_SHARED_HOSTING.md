# OCR gratuit sur hébergement mutualisé

## Choix technique

FIDEST IA utilise **Tesseract OCR**, moteur open source sous licence Apache-2.0. Le paquet Composer existant n'est qu'un wrapper : le vrai travail OCR est toujours effectué par l'exécutable Tesseract.

Il n'existe pas de moteur OCR PHP pur, maintenu et de qualité comparable qui résolve l'absence de cet exécutable. Tesseract.js est un port WebAssembly réel, mais un navigateur est requis : il ne peut pas traiter les appels serveur de FINEA. L'API utilise donc soit Tesseract système, soit un AppImage/binaire utilisateur placé dans le compte cPanel. Références : [installation Tesseract](https://tesseract-ocr.github.io/tessdoc/Installation.html), [utilisation en ligne de commande](https://tesseract-ocr.github.io/tessdoc/Command-Line-Usage.html).

## Installation cPanel sans root

Créer hors du dossier public :

```text
/home/fidestci/ia.fidest.ci/tools/tesseract/
```

Y déposer un AppImage Tesseract Linux x86_64 compatible, le rendre exécutable et vérifier qu'il inclut `fra` et `eng` :

```bash
chmod 0755 tools/tesseract/tesseract.AppImage
tools/tesseract/tesseract.AppImage --version
tools/tesseract/tesseract.AppImage --list-langs
```

La documentation Tesseract décrit officiellement l'option AppImage et indique que les images proposées incluent notamment `fra` et `eng`. Aucun binaire tiers n'est commité dans le dépôt : son origine, son checksum et sa compatibilité avec l'hébergeur doivent être vérifiés avant installation.

## Pipeline PDF

Ordre automatique : Poppler, Ghostscript, ImageMagick. En production actuelle, `/bin/gs` est sélectionné. Ghostscript est lancé avec `-dSAFER`, `-dBATCH`, `-dNOPAUSE`, une résolution configurable et une limite de pages. Voir la [documentation Ghostscript](https://ghostscript.readthedocs.io/en/master/Use.html).

ImageMagick est le dernier recours et reçoit des limites mémoire, map et disque. Sa configuration globale doit aussi appliquer une politique restrictive, conformément à la [politique de sécurité ImageMagick](https://imagemagick.org/security-policy/).

## Configuration

```dotenv
OCR_DRIVER=auto
OCR_BINARY=tesseract
OCR_EMBEDDED_BINARY=/home/fidestci/ia.fidest.ci/tools/tesseract/tesseract.AppImage
OCR_LANGUAGES=fra+eng
OCR_TIMEOUT_SECONDS=120

PDF_CONVERTER=auto
PDF_POPPLER_BINARY=pdftoppm
PDF_GS_BINARY=/bin/gs
PDF_IMAGEMAGICK_BINARY=/bin/convert
PDF_DPI=150
PDF_MAX_PAGES=20

AI_ENABLED=false
```

`auto` essaie le binaire utilisateur, puis Tesseract système. Sans moteur réel, l'API retourne une erreur JSON contrôlée ; elle ne simule jamais l'OCR.

La valeur 150 DPI est le réglage mutualisé validé sur un document réel : elle conserve les champs utiles tout en réduisant fortement le temps de traitement. Augmenter à 200–250 uniquement pour des scans difficiles et avec un timeout adapté.

## Dépendances

| Élément | Obligatoire | Rôle |
|---|---:|---|
| PHP 8.2+ | Oui | Application |
| fileinfo, PDO MySQL | Oui | Sécurité fichier et stockage |
| GD | Recommandé | Prétraitement non destructif |
| proc_open | Oui pour OCR local | Exécution avec timeout |
| Tesseract système ou utilisateur | Oui | Véritable OCR |
| Ghostscript/Poppler/ImageMagick | Un des trois pour PDF | Rasterisation PDF |
| cron | Non | Réservé aux futurs traitements différés |
| mod_rewrite | Oui | URLs API sans `.php` |


## Protection de charge en production

En mode mutualisé, configurer :

```dotenv
OCR_SHARED_HOSTING_MODE=true
OCR_OMP_THREAD_LIMIT=1
OCR_LOCK_FILE=/home/fidestci/ia.fidest.ci/storage/locks/ocr.lock
OCR_LOCK_WAIT_SECONDS=2
PDF_SHARED_HOSTING_DPI=75
```

Le verrou global limite l'instance à un seul traitement OCR lourd. Les applications clientes utilisent `GET /api/v1/ocr/status` pour afficher l'état disponible/occupé et attendre avant l'envoi. Cette stratégie évite l'empilement de processus Tesseract/Ghostscript qui peut dégrader les autres applications du même compte mutualisé.


## Garde-fou global CPU / mémoire

Le verrou mono-OCR évite la concurrence, mais il ne suffit pas si le serveur est déjà chargé par Apache, PHP, MariaDB ou une autre application. FIDEST IA peut donc suspendre préventivement le démarrage des traitements lourds.

Configuration recommandée :

```dotenv
SERVER_RESOURCE_GUARD=true
SERVER_MAX_LOAD_PER_CPU=1.20
SERVER_MAX_MEMORY_PERCENT=85
SERVER_RESOURCE_RETRY_AFTER=15
```

Le contrôle est effectué avant la conversion PDF, avant chaque page OCR et pendant l'attente du verrou. Lorsque le seuil est dépassé, `GET /api/v1/ocr/status` retourne `status=paused`, et les nouveaux POST OCR/analyse reçoivent HTTP `409` avec `error.code=OCR_RESOURCE_BUSY`.

Les métriques utilisées sont `sys_getloadavg()`, `/proc/cpuinfo` et `/proc/meminfo` lorsqu'elles sont accessibles. Sur un hébergement mutualisé qui masque certaines de ces informations, les métriques indisponibles sont ignorées et les protections existantes (verrou, timeout, DPI réduit, thread limit) restent actives.
