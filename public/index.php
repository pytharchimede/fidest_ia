<?php
// Minimal upload form for testing
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Upload facture - Fidest IA prototype</title>
</head>

<body>
    <h1>Upload facture - Prototype</h1>
    <form action="api/verify.php" method="post" enctype="multipart/form-data">
        <label>Libellé<br><input name="label" value="achat de camera de surveillance" style="width:400px"></label><br><br>
        <label>Montant déclaré<br><input name="amount" value="10000"></label><br><br>
        <label>Fichier (image/pdf)<br><input type="file" name="file"></label><br><br>
        <button type="submit">Vérifier</button>
    </form>
</body>

</html>