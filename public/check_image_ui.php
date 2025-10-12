<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Test par image — justificatif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3>Vérifier une image comme justificatif</h3>
        <form id="frm" class="my-3">
            <div class="mb-3">
                <label class="form-label">Type de document</label>
                <select class="form-select" name="doc_type" id="doc_type">
                    <option value="JUSTIFICATIF_FEB">Justificatif FEB</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input class="form-control" type="file" name="image" id="image" accept="image/*" required>
            </div>
            <button class="btn btn-primary" type="submit">Analyser</button>
        </form>
        <div id="out"></div>
    </div>
    <script>
        const frm = document.getElementById('frm');
        const out = document.getElementById('out');
        frm.addEventListener('submit', async (e) => {
            e.preventDefault();
            out.innerHTML = 'Analyse en cours...';
            const data = new FormData(frm);
            const res = await fetch('./api/check_image.php', {
                method: 'POST',
                body: data
            });
            const json = await res.json();
            const img = json.image ? `<img src="${json.image}" class="img-fluid my-2" style="max-height:300px;">` : '';
            const reasons = (json.reasons || []).map(r => `<li>${r.message}</li>`).join('');
            out.innerHTML = `
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Décision: <span class="badge ${json.decision==='ACCEPTE'?'bg-success':'bg-danger'}">${json.decision}</span></h5>
              <span class="text-muted">Type: ${json.doc_type}</span>
            </div>
            ${img}
            <div class="mt-2"><strong>Classification:</strong> ${json.classification?.class || 'n/a'}</div>
            <ul class="mt-2">${reasons}</ul>
            <details class="mt-2"><summary>Signaux</summary><pre>${JSON.stringify(json.classification?.signals||[], null, 2)}</pre></details>
          </div>
        </div>`;
        });
    </script>
</body>

</html>