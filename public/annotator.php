<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Annotateur - etalonIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/swipe.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3 class="mb-3">Annotateur interactif</h3>
        <div class="card mx-auto shadow-sm" style="max-width:720px;">
            <div class="card-body text-center">
                <div id="notice" class="alert alert-warning d-none" role="alert"></div>
                <div id="card" class="swipe-card mb-3">Chargement...</div>
                <div class="d-flex justify-content-center gap-3">
                    <button id="no" class="btn btn-outline-danger btn-lg">Non, ce justificatif nest pas recevable</button>
                    <button id="yes" class="btn btn-success btn-lg">Oui, ce justificatif est correct</button>
                </div>
                <div class="mt-3 text-muted small">Déposez vos images dans <code>public/uploads/images/</code></div>
            </div>
        </div>
    </div>
    <script src="assets/swipe.js"></script>
</body>

</html>