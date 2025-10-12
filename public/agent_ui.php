<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Agent etalonIA — Annotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/agent.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">etalonIA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navmenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Annotation</a></li>
                    <li class="nav-item"><a class="nav-link" href="#examples">Exemples</a></li>
                    <li class="nav-item"><a class="nav-link" href="#settings">Settings</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Vérification & annotation</h5>
                        <div class="mb-3">
                            <label class="form-label">Libellé de la demande</label>
                            <input id="label" class="form-control" value="achat de camera de surveillance">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Test par image</label>
                            <select id="image_select" class="form-select">
                                <option>Chargement...</option>
                            </select>
                            <div class="form-text">Choisissez une image pour préremplir le champ OCR et tester l'agent.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Texte extrait du document (ocr_text)</label>
                            <textarea id="ocr_text" rows="6" class="form-control">Produit: camera de surveillance
Référence: 123</textarea>
                        </div>
                        <div class="d-flex gap-2 align-items-center mb-3">
                            <button id="predict" class="btn btn-success">Prédire</button>
                            <button id="save_example" class="btn btn-primary">Enregistrer comme exemple</button>
                            <div class="form-check ms-3">
                                <input class="form-check-input" type="checkbox" id="auto_add">
                                <label class="form-check-label" for="auto_add">Auto-ajouter après confirmation</label>
                            </div>
                        </div>
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Paramètres</h6>
                        <div class="mb-2"><label class="form-label">Auteur</label><input id="author" class="form-control" placeholder="nom utilisateur"></div>
                        <div class="mb-2"><label class="form-label">Version agent</label><input id="agent_version" class="form-control" value="v0.1"></div>
                        <div class="mb-2"><label class="form-label">Agent</label><input id="agent_name" class="form-control" value="etalonIA"></div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h6 id="examples">Exemples récents</h6>
                        <div id="examples_list">Chargement...</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/agent.js"></script>
</body>

</html>