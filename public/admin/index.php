<?php

declare(strict_types=1);

use FidestIA\Core\AdminAuth;
use FidestIA\Core\Database;
use FidestIA\Repositories\ApiClientRepository;

$root = dirname(__DIR__, 2);
$config = require $root . '/bootstrap.php';
AdminAuth::start($config);

$error = null;
$createdKey = null;

if (isset($_GET['logout'])) {
    AdminAuth::logout($config);
    header('Location: ./');
    exit;
}

if (!AdminAuth::check($config)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_token'])) {
        if (AdminAuth::login($config, (string) $_POST['admin_token'])) {
            header('Location: ./');
            exit;
        }
        $error = 'Accès administrateur refusé.';
    }

    ?><!doctype html>
    <html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FIDEST IA — Administration</title>
    <style>body{margin:0;font-family:Arial,sans-serif;background:#f8f9fa;color:#202124;display:grid;min-height:100vh;place-items:center}.card{width:min(420px,calc(100% - 32px));background:#fff;border:1px solid #dadce0;border-radius:18px;padding:28px;box-shadow:0 10px 30px rgba(60,64,67,.08)}h1{font-size:24px;margin:0 0 8px}p{color:#5f6368;line-height:1.5}input,button{width:100%;box-sizing:border-box;height:46px;border-radius:10px;font:inherit}input{border:1px solid #dadce0;padding:0 14px;margin:10px 0}button{border:0;background:#1a73e8;color:#fff;font-weight:600;cursor:pointer}.err{color:#b3261e;font-size:14px}</style></head>
    <body><form class="card" method="post"><h1>Administration FIDEST IA</h1><p>Connectez-vous avec la clé maître d’administration configurée sur le serveur.</p><?php if ($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?><input type="password" name="admin_token" required autocomplete="current-password" placeholder="Clé administrateur"><button type="submit">Se connecter</button></form></body></html><?php
    exit;
}

$db = Database::connection($config);
$repo = new ApiClientRepository($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $scopes = array_values(array_intersect(
            (array) ($_POST['scopes'] ?? []),
            ['documents:analyze', 'types:read', 'types:write']
        ));
        $expiresAt = trim((string) ($_POST['expires_at'] ?? '')) ?: null;

        if ($name === '') {
            $error = 'Le nom de l’application est requis.';
        } elseif ($scopes === []) {
            $error = 'Sélectionnez au moins un droit.';
        } else {
            $createdKey = $repo->create($name, $scopes, $expiresAt);
        }
    }

    if ($action === 'revoke') {
        $repo->revoke((int) ($_POST['id'] ?? 0));
        header('Location: ./');
        exit;
    }
}

$clients = $repo->all();
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FIDEST IA — Applications API</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#fff;color:#202124}.wrap{width:min(1100px,calc(100% - 32px));margin:0 auto;padding:26px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:34px}.brand{font-size:18px;font-weight:700}.logout{color:#5f6368;text-decoration:none;font-size:14px}.hero{max-width:760px;margin-bottom:28px}.hero h1{font-size:34px;letter-spacing:-.03em;margin:0 0 10px}.hero p{color:#5f6368;line-height:1.6;margin:0}.grid{display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start}.card{border:1px solid #dadce0;border-radius:16px;padding:20px;background:#fff}.card h2{font-size:18px;margin:0 0 16px}.field{margin-bottom:14px}.field label{display:block;font-size:13px;font-weight:600;margin-bottom:7px}.field input{width:100%;height:42px;border:1px solid #dadce0;border-radius:9px;padding:0 11px;font:inherit}.checks{display:grid;gap:8px}.checks label{display:flex;gap:8px;align-items:center;font-size:14px}.primary{width:100%;height:44px;border:0;border-radius:9px;background:#1a73e8;color:#fff;font-weight:600;cursor:pointer;margin-top:8px}.notice{border:1px solid #a8c7fa;background:#f8fbff;border-radius:14px;padding:16px;margin-bottom:22px}.notice strong{display:block;margin-bottom:7px}.key{display:flex;gap:8px;align-items:center;margin-top:10px}.key code{flex:1;overflow:auto;background:#fff;border:1px solid #dadce0;border-radius:9px;padding:11px;font-size:12px}.copy{border:1px solid #dadce0;background:#fff;border-radius:9px;padding:10px 12px;cursor:pointer}.warning{font-size:13px;color:#b06000;margin-top:8px}.error{color:#b3261e;font-size:14px;margin-bottom:12px}.table-wrap{overflow:auto;border:1px solid #dadce0;border-radius:16px}table{width:100%;border-collapse:collapse;min-width:720px}th,td{text-align:left;padding:13px 14px;border-bottom:1px solid #eee;font-size:13px;vertical-align:top}th{color:#5f6368;background:#f8f9fa;font-weight:600}.pill{display:inline-block;border-radius:999px;padding:4px 8px;background:#eef3fe;color:#174ea6;font-size:12px;margin:2px}.active{color:#137333;font-weight:600}.revoked{color:#b3261e;font-weight:600}.danger{border:1px solid #f1c7c3;background:#fff;color:#b3261e;border-radius:8px;padding:7px 10px;cursor:pointer}@media(max-width:850px){.grid{grid-template-columns:1fr}.hero h1{font-size:28px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top"><div class="brand">FIDEST IA · Administration</div><a class="logout" href="?logout=1">Déconnexion</a></div>
  <div class="hero"><h1>Applications API</h1><p>Enregistrez une application cliente, attribuez-lui les droits nécessaires puis copiez sa clé API. La valeur complète de la clé n’est affichée qu’une seule fois.</p></div>

  <?php if ($createdKey): ?>
  <div class="notice"><strong>Application créée : <?=htmlspecialchars($createdKey['name'])?></strong><div>Copiez cette clé maintenant :</div><div class="key"><code id="newKey"><?=htmlspecialchars($createdKey['key'])?></code><button class="copy" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newKey').textContent)">Copier</button></div><div class="warning">Cette clé ne pourra plus être affichée après avoir quitté cette page.</div></div>
  <?php endif; ?>

  <div class="grid">
    <form class="card" method="post">
      <input type="hidden" name="action" value="create">
      <h2>Créer une application</h2>
      <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <div class="field"><label>Nom de l’application</label><input name="name" required placeholder="Ex. FINEA Production"></div>
      <div class="field"><label>Droits</label><div class="checks">
        <label><input type="checkbox" name="scopes[]" value="documents:analyze" checked> documents:analyze</label>
        <label><input type="checkbox" name="scopes[]" value="types:read" checked> types:read</label>
        <label><input type="checkbox" name="scopes[]" value="types:write"> types:write</label>
      </div></div>
      <div class="field"><label>Expiration (optionnelle)</label><input type="datetime-local" name="expires_at"></div>
      <button class="primary" type="submit">Créer et générer la clé</button>
    </form>

    <div>
      <div class="table-wrap"><table><thead><tr><th>Application</th><th>Préfixe</th><th>Scopes</th><th>Dernière utilisation</th><th>Statut</th><th></th></tr></thead><tbody>
      <?php if (!$clients): ?><tr><td colspan="6">Aucune application enregistrée.</td></tr><?php endif; ?>
      <?php foreach ($clients as $client): $scopes=json_decode((string)$client['scopes'],true)?:[]; ?>
        <tr>
          <td><strong><?=htmlspecialchars((string)$client['name'])?></strong><br><span style="color:#5f6368"><?=htmlspecialchars((string)$client['created_at'])?></span></td>
          <td><code><?=htmlspecialchars((string)$client['key_prefix'])?>…</code></td>
          <td><?php foreach($scopes as $scope): ?><span class="pill"><?=htmlspecialchars((string)$scope)?></span><?php endforeach; ?></td>
          <td><?=htmlspecialchars((string)($client['last_used_at'] ?: 'Jamais'))?></td>
          <td class="<?=((int)$client['active']===1?'active':'revoked')?>"><?=((int)$client['active']===1?'Active':'Révoquée')?></td>
          <td><?php if ((int)$client['active']===1): ?><form method="post" onsubmit="return confirm('Révoquer cette clé API ?')"><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?= (int)$client['id'] ?>"><button class="danger" type="submit">Révoquer</button></form><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
</div>
</body></html>
