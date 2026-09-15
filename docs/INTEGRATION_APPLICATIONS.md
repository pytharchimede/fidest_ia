# Intégrer une application à FIDEST IA

Cette procédure s'applique à IFMAP, LBP et à toute nouvelle application cliente.

## Créer une identité par application

Dans `https://ia.fidest.ci/admin/applications`, créer une entrée distincte pour
chaque application et chaque environnement, par exemple :

```text
IFMAP Production
IFMAP Staging
LBP Production
```

Accorder uniquement les scopes nécessaires. Pour une application qui envoie
un document et consulte son résultat :

```text
documents:analyze
documents:read
types:read
```

Ajouter `documents:list` seulement si elle doit effectuer des recherches. Les
scopes `types:write` et `rules:write` sont réservés aux outils d'administration.

## Configuration serveur

Stocker la clé uniquement dans le gestionnaire de secrets ou le `.env` du
serveur appelant :

```dotenv
FIDEST_IA_URL=https://ia.fidest.ci
FIDEST_IA_API_TOKEN=fia_live_xxxxxxxxx
FIDEST_IA_TIMEOUT=180
```

Ne jamais exposer la clé dans du JavaScript livré au navigateur. L'appel doit
être effectué par le serveur de l'application cliente.

## Contrat minimal

```http
POST https://ia.fidest.ci/api/v1/documents/analyze
Authorization: Bearer fia_live_...
Accept: application/json
Content-Type: multipart/form-data
```

Champs :

| Champ | Requis | Valeur |
|---|---:|---|
| `document` | oui | PDF, JPG, PNG, WEBP ou TIFF, 15 Mo maximum |
| `document_type` | non | `AUTO`, `GENERAL` ou code du catalogue |
| `client_reference` | non | identifiant stable du dossier appelant |

```bash
curl --fail-with-body -X POST \
  'https://ia.fidest.ci/api/v1/documents/analyze' \
  -H "Authorization: Bearer $FIDEST_IA_API_TOKEN" \
  -H 'Accept: application/json' \
  -F 'document=@/chemin/document.pdf' \
  -F 'document_type=AUTO' \
  -F 'client_reference=APPLICATION-123'
```

La réussite technique est indiquée par `success`. La décision métier se trouve
dans `data.validation.valid`. L'identifiant durable du document est `data.uuid`
et l'identifiant de diagnostic de l'appel est `meta.request_id`.

## Autres opérations

| Méthode | Route | Scope |
|---|---|---|
| `GET` | `/api/v1/health` | public |
| `GET` | `/api/v1/document-types` | `types:read` |
| `GET` | `/api/v1/documents/{uuid}/analysis` | `documents:read` |
| `GET` | `/api/v1/documents?q=...&status=...&limit=50` | `documents:list` |

## Erreurs et reprise

Toutes les erreurs API structurées utilisent :

```json
{
  "success": false,
  "error": {"code": "INVALID_API_KEY", "message": "Clé API invalide."},
  "meta": {"request_id": "..."}
}
```

- ne pas réessayer automatiquement les réponses `401`, `403` et `422` ;
- respecter `Retry-After` pour une réponse `429` ;
- réessayer les erreurs `5xx` avec temporisation progressive et nombre limité
  de tentatives ;
- journaliser le code HTTP, `error.code` et `meta.request_id`, jamais la clé ni
  le document complet.

La référence exhaustive des routes et réponses est disponible dans
[`API.md`](API.md).
