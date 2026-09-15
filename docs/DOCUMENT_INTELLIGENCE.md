# Moteur Document Intelligence déterministe

Le pipeline est : validation MIME → stockage privé → conversion/prétraitement → OCR → conservation du texte brut → normalisation → classification pondérée → extracteur spécialisé → règles métier → anomalies → scores → stockage JSON → API.

Le moteur ne complète jamais une valeur absente : une extraction insuffisamment étayée vaut `null`. Chaque champ structuré peut contenir `value`, `confidence` et l'extrait `source`. Les scores sont déterministes et arrondis ; la méthode OCR actuelle est une heuristique de qualité du texte, explicitement indiquée dans les métadonnées, et non une probabilité statistique.

## Extension

- Ajouter un extracteur : implémenter `DocumentExtractorInterface`, puis l'enregistrer avant `GenericExtractor` dans `ExtractorRegistry`.
- Ajouter un type : utiliser l'administration ou `POST /api/v1/document-types`, avec mots-clés forts, négatifs, requis et seuil.
- Ajouter une règle : utiliser `ValidationRuleRepository` et `ValidationEngine`.
- Ajouter ultérieurement un fournisseur IA : implémenter `AIProviderInterface`. `NullAIProvider` reste le comportement par défaut avec `AI_ENABLED=false`.

## Réponse

Les anciens blocs (`ocr`, `data`, `validation`, `document_type`) restent présents. S'ajoutent : `engine`, `raw_text`, `normalized_text`, `fields`, `anomalies`, `scores`, `warnings`, `pages` et `metadata`.

```php
$analysis = $response['data'];
$type = $analysis['document_type']['code'];
$fields = $analysis['fields'];
$anomalies = $analysis['anomalies'];
$overall = $analysis['scores']['overall'];
```

Une anomalie mathématique signale une incohérence sans nécessairement rejeter le document. L'administration distingue : score ≥ 90 % fiable, 70–89 % à vérifier, inférieur à 70 % vérification requise.
