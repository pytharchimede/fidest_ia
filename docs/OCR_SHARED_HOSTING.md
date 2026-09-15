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
