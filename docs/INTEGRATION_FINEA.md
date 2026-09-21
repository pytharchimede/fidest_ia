# Intégration FINEA avec FIDEST IA

## 1. Créer l'accès FINEA

Dans `https://ia.fidest.ci/admin/applications`, créer **FINEA Production** avec
les scopes suivants :

```text
documents:analyze
documents:read
types:read
```

Copier immédiatement la clé `fia_live_...` : elle ne sera affichée qu'une
fois. Cette clé est distincte du jeton de connexion à l'administration.

## 2. Configurer FINEA

Ajouter les valeurs suivantes au `.env` de FINEA, sans les versionner :

```dotenv
FIDEST_IA_URL=https://ia.fidest.ci
FIDEST_IA_API_TOKEN=fia_live_xxxxxxxxx
FIDEST_IA_TIMEOUT=180
```

Après modification, recharger la configuration de FINEA selon son framework.

## 3. Vérifier le service

```bash
curl --fail-with-body 'https://ia.fidest.ci/api/v1/health'
```

Une réponse valide contient `"status": "ok"`.

## 4. Envoyer un document

```bash
curl --fail-with-body -X POST "$FIDEST_IA_URL/api/v1/documents/analyze" \
  -H "Authorization: Bearer $FIDEST_IA_API_TOKEN" \
  -H "Accept: application/json" \
  -F "document=@facture.pdf" \
  -F "document_type=AUTO" \
  -F "client_reference=FINEA-2026-001"
```

```php
<?php
$curl = curl_init(getenv('FIDEST_IA_URL') . '/api/v1/documents/analyze');
curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . getenv('FIDEST_IA_API_TOKEN'), 'Accept: application/json'],
    CURLOPT_POSTFIELDS => ['document' => new CURLFile('/chemin/facture.pdf'), 'document_type' => 'AUTO', 'client_reference' => 'FINEA-2026-001'],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => (int) (getenv('FIDEST_IA_TIMEOUT') ?: 180),
]);
$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$transportError = curl_error($curl);
curl_close($curl);

if ($response === false) {
    throw new RuntimeException('FIDEST IA inaccessible : ' . $transportError);
}

$payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
$requestId = $payload['meta']['request_id'] ?? null;

if ($status !== 200 || !($payload['success'] ?? false)) {
    $code = $payload['error']['code'] ?? 'UNKNOWN_ERROR';
    $message = $payload['error']['message'] ?? 'Erreur FIDEST IA';
    throw new RuntimeException("$code: $message (requête $requestId)");
}

$analysis = $payload['data'];
$documentUuid = $analysis['uuid'];
$documentType = $analysis['document_type']['code'];
$businessValid = $analysis['validation']['valid'];
$values = $analysis['data'];
$confidence = $analysis['scores']['overall'] ?? null;
```

## 5. Règles de traitement dans FINEA

- `success=true` signifie que le traitement technique a abouti.
- `data.validation.valid=true` signifie que les règles métier configurées sont
  satisfaites.
- Conserver `data.uuid` avec le dossier FINEA pour consulter ultérieurement
  `GET /api/v1/documents/{uuid}/analysis`.
- Conserver `meta.request_id` dans les logs techniques.
- Soumettre `client_reference` avec l'identifiant stable du dossier FINEA.
- Ne jamais journaliser `FIDEST_IA_API_TOKEN`.

Traiter `401` comme une clé absente, invalide, expirée ou révoquée ; `403`
comme un scope insuffisant ; `422` comme un document ou paramètre invalide ;
`429` comme une limitation temporaire ; et `5xx` comme une indisponibilité à
réessayer avec temporisation progressive.

Le contrôle ne constitue pas une validation juridique d'authenticité sans
interrogation d'un service officiel.


## 6. Gestion de l'occupation OCR dans FINEA

FINEA doit vérifier `GET /api/v1/ocr/status` avant d'envoyer un document.

Comportement d'interface recommandé :

- `available` : afficher « IA disponible » puis autoriser l'analyse ;
- `busy` : afficher « Je suis occupée en ce moment. Merci de patienter. », désactiver le bouton d'analyse et afficher un loader ;
- pendant `busy`, interroger `/api/v1/ocr/status` toutes les 3 secondes ;
- dès que l'état redevient `available`, réactiver le bouton et poursuivre l'envoi du document ;
- `unavailable` : afficher « Service OCR temporairement indisponible » et ne pas envoyer le fichier.

Le contrôle préalable ne remplace pas le traitement de concurrence : si le POST `/documents/analyze` reçoit HTTP `409` avec `error.code=OCR_BUSY`, FINEA doit revenir au même état d'attente avec loader et reprendre le polling. Ne pas traiter `OCR_BUSY` comme une erreur définitive.

Pseudo-flux :

```text
Utilisateur demande l'analyse
        |
GET /api/v1/ocr/status
        |
  +-----+------------------+
  |                        |
available                 busy
  |                        |
POST analyze          loader + message
  |                        |
résultat              polling /status 3 s
                           |
                       available
                           |
                       POST analyze
```

Le polling de statut est léger : il ne lance ni Ghostscript ni OCR de document. Un seul traitement lourd est autorisé à la fois sur l'hébergement mutualisé.


### Cas `paused` : serveur momentanément chargé

Si `GET /api/v1/ocr/status` retourne `status=paused`, FINEA doit conserver le formulaire et le fichier, afficher un message du type « IA temporairement en pause pour protéger le serveur », puis attendre la valeur `retry_after` avant de refaire uniquement le contrôle de statut.

Si un POST reçoit HTTP `409` avec `error.code=OCR_RESOURCE_BUSY`, appliquer exactement le même comportement. Ne pas relancer plusieurs analyses en parallèle et ne pas réuploader automatiquement le document pendant la pause.
