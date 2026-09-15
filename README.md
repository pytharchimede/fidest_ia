# FIDEST IA

Plateforme de contrôle, typage, extraction et validation documentaire pour les applications internes FIDEST.

## Architecture

- `app/Core` : infrastructure, environnement, base de données et migrations
- `app/Contracts` : interfaces
- `app/Repositories` : accès aux données
- `app/Services` : OCR, typage, extraction, validation, stockage et exports
- `database/migrations` : schéma SQL, catalogue documentaire et règles
- `public` : interface web et endpoints API
- `storage/documents` : copies des documents analysés
- `.env` : configuration locale non versionnée

## Installation locale

1. `composer install`
2. `cp .env.example .env`
3. Renseigner les accès MySQL dans `.env`.
4. Installer Tesseract et les langues `fra` et `eng`.
5. Ouvrir l’application : les migrations SQL en attente sont appliquées automatiquement si `AUTO_MIGRATE=true`.

Commande manuelle disponible :

```bash
php scripts/migrate.php
```

## Migrations automatiques

FIDEST IA crée une table `schema_migrations` et exécute une seule fois chaque fichier `database/migrations/*.sql` non encore enregistré.

Le mécanisme est déclenché par `bootstrap.php`, ce qui permet à une mise en ligne par Git Deploy ou `git pull` d’appliquer les changements de base au premier chargement de l’application.

Variable d’environnement :

```dotenv
AUTO_MIGRATE=true
```

En production, les migrations doivent rester idempotentes. Ne jamais modifier une migration déjà livrée : ajouter une nouvelle migration numérotée.

## Catalogue documentaire ivoirien

Le catalogue initial couvre notamment :

- identité et état civil : CNI, attestation d’identité, certificat de résidence, carte de résident, passeport, actes de naissance, mariage et décès ;
- justice : certificat de nationalité, casier judiciaire bulletin n°3, déclaration de perte ;
- entreprise : RCCM, statuts, DSV, PV d’assemblée, bail, procuration ;
- fiscalité : DFE, attestations fiscales, régularité fiscale, patente, avis d’imposition, FNE ;
- CNPS : immatriculation, déclaration travailleur/employeur, DISA, cessation d’emploi, accident du travail ;
- emploi : attestation de travail, certificat de travail, bulletin de paie ;
- transport : carte grise, visite technique, assurance automobile, permis de conduire ;
- banque et justificatifs : RIB, facture CIE, facture SODECI, attestation d’hébergement ;
- éducation et santé : certificat de scolarité, diplôme/attestation de réussite, certificat médical ;
- documents commerciaux : bon de commande, bon de livraison, devis, reçu.

Ces types servent au typage et à l’extraction. Ils ne constituent pas une certification de validité juridique du document.

## API

Documentation complète :

- [`docs/API.md`](docs/API.md) : référence de l'API v1 ;
- [`docs/INTEGRATION_FINEA.md`](docs/INTEGRATION_FINEA.md) : intégration prête à l'emploi pour FINEA ;
- [`docs/INTEGRATION_APPLICATIONS.md`](docs/INTEGRATION_APPLICATIONS.md) : intégration de toute autre application.

URL de production :

```text
https://ia.fidest.ci/api/v1
```

Endpoint principal :

```http
POST /api/v1/documents/analyze
Authorization: Bearer fia_live_...
Content-Type: multipart/form-data
```

Multipart fields :

- `document` : fichier à analyser
- `document_type` : `AUTO`, `GENERAL` ou le code d’un type configuré
- `client_reference` : optionnel

La réponse JSON contient le type détecté, le texte OCR, les champs extraits, le statut et le détail des contrôles.

Chaque application cliente doit disposer de sa propre clé, créée dans
`https://ia.fidest.ci/admin/applications`. Ne jamais utiliser le jeton
administrateur comme clé API.

## Règles initiales

- Facture FNE : `invoice_number` doit être unique globalement.
- Bon de commande : `order_number` doit être unique pour un même `client_name`.

Les règles sont stockées dans `validation_rules` et peuvent être enrichies sans modifier le moteur.

## OCR

Le moteur par défaut est Tesseract OCR, open source. L’interface `OcrEngineInterface` permet de brancher ultérieurement un autre moteur local ou distant.

## Sécurité

Ne jamais versionner `.env`, mots de passe BDD, clés API ou secrets. Les fichiers sont renommés aléatoirement et leur SHA-256 est enregistré en base.
