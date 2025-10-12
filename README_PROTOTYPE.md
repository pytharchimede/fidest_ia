Prototype vérification justificatifs (OCR + règles locales + HF optional)

Pré-requis:

- PHP >=7.4
- Composer
- Tesseract installé et accessible (tesseract en PATH)

Installation:

1. composer install
2. Vérifier que Tesseract est installé: `tesseract --version`
3. Lancer un serveur de test: `php -S localhost:8000 -t public`
4. Ouvrir http://localhost:8000

Configuration optionnelle Hugging Face:

- Exporter HF_API_TOKEN et HF_MODEL (ex: export HF_API_TOKEN=hf_xxx ; export HF_MODEL=eleutherai/gpt-neo-125M)

Endpoints:

- POST /api/verify.php : form upload depuis la page d'accueil; renvoie JSON de décision.
