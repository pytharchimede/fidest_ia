<?php
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FIDEST IA — Contrôle documentaire</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--text:#111827;--muted:#6b7280;--line:#e5e7eb;--primary:#111827;--success:#0f766e;--danger:#b42318;--radius:22px}*{box-sizing:border-box}body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:linear-gradient(180deg,#fff 0,#f6f8fb 60%);color:var(--text)}.shell{max-width:1220px;margin:auto;padding:42px 22px 70px}.nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:52px}.brand{display:flex;align-items:center;gap:12px;font-weight:800}.logo{width:40px;height:40px;border-radius:13px;background:#111827;color:#fff;display:grid;place-items:center}.badge{font-size:12px;border:1px solid var(--line);border-radius:999px;padding:7px 11px;color:var(--muted);background:#fff}.hero{display:grid;grid-template-columns:1.05fr .95fr;gap:42px;align-items:center;margin-bottom:38px}.hero h1{font-size:clamp(42px,6vw,76px);line-height:.98;letter-spacing:-.055em;margin:0 0 22px}.hero p{font-size:18px;line-height:1.65;color:var(--muted);max-width:700px}.panel{background:rgba(255,255,255,.94);border:1px solid var(--line);border-radius:30px;padding:24px;box-shadow:0 24px 70px rgba(17,24,39,.08)}.drop{border:1.5px dashed #cbd5e1;border-radius:22px;padding:34px;text-align:center;background:#fbfcfe;cursor:pointer}.drop strong{display:block;font-size:18px;margin-bottom:8px}.drop span{font-size:14px;color:var(--muted)}input[type=file]{display:none}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px}.field{display:flex;flex-direction:column;gap:7px}.field label{font-size:13px;font-weight:700}.field input,.field select{height:46px;border:1px solid var(--line);border-radius:14px;padding:0 13px;background:white;font:inherit}.action{width:100%;height:50px;border:0;border-radius:15px;background:#111827;color:white;font-weight:750;margin-top:18px;cursor:pointer}.action:disabled{opacity:.5}.result{margin-top:18px;border:1px solid var(--line);border-radius:18px;overflow:hidden;display:none}.result-head{padding:14px 16px;background:#f9fafb;border-bottom:1px solid var(--line);display:flex;justify-content:space-between}.status{font-size:12px;font-weight:800;border-radius:999px;padding:5px 9px}.status.ok{background:#ecfdf3;color:var(--success)}.status.bad{background:#fef3f2;color:var(--danger)}pre{margin:0;padding:16px;max-height:390px;overflow:auto;background:#0b1020;color:#d1e0ff;font-size:12px;line-height:1.55}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:42px}.feature{background:#fff;border:1px solid var(--line);border-radius:20px;padding:22px}.feature h3{margin:8px 0 8px}.feature p{margin:0;color:var(--muted);line-height:1.55;font-size:14px}@media(max-width:850px){.hero,.features{grid-template-columns:1fr}.grid{grid-template-columns:1fr}.nav{margin-bottom:30px}}
</style>
</head>
<body>
<div class="shell">
  <div class="nav"><div class="brand"><div class="logo">FI</div>FIDEST IA</div><div class="badge">Document Intelligence API</div></div>
  <section class="hero">
    <div><h1>Le contrôle documentaire, automatisé.</h1><p>Déposez une facture, un bon de commande ou un justificatif. FIDEST IA extrait le texte, structure les données, applique vos règles métier et retourne immédiatement une réponse JSON exploitable par vos autres applications.</p></div>
    <div class="panel">
      <form id="form">
        <label class="drop" for="document"><strong id="fileName">Déposer un document</strong><span>JPG, PNG, WEBP, TIFF — PDF selon configuration serveur</span></label>
        <input id="document" name="document" type="file" required>
        <div class="grid">
          <div class="field"><label>Type de document</label><select name="document_type"><option value="FNE_INVOICE">Facture FNE</option><option value="PURCHASE_ORDER">Bon de commande</option></select></div>
          <div class="field"><label>Référence client</label><input name="client_reference" placeholder="Ex. BANAMUR"></div>
        </div>
        <button class="action" id="submit">Analyser le document</button>
      </form>
      <div class="result" id="result"><div class="result-head"><strong>Résultat API</strong><span class="status" id="status"></span></div><pre id="json"></pre></div>
    </div>
  </section>
  <section class="features">
    <div class="feature"><small>01</small><h3>OCR local</h3><p>Tesseract permet une extraction gratuite sans coût par document lorsque le serveur autorise le binaire.</p></div>
    <div class="feature"><small>02</small><h3>Règles métier</h3><p>Unicité globale, unicité par client, champs obligatoires et contrôles futurs configurables en base.</p></div>
    <div class="feature"><small>03</small><h3>API inter-applications</h3><p>Les applications du même serveur peuvent envoyer un document et consommer directement le JSON de contrôle.</p></div>
  </section>
</div>
<script>
const form=document.getElementById('form'),file=document.getElementById('document'),nameEl=document.getElementById('fileName'),result=document.getElementById('result'),json=document.getElementById('json'),status=document.getElementById('status'),submit=document.getElementById('submit');
file.addEventListener('change',()=>{nameEl.textContent=file.files[0]?.name||'Déposer un document'});
form.addEventListener('submit',async e=>{e.preventDefault();submit.disabled=true;submit.textContent='Analyse en cours…';result.style.display='block';json.textContent='Traitement du document…';status.textContent='PROCESSING';status.className='status';try{const r=await fetch('api/documents/analyze.php',{method:'POST',body:new FormData(form)});const data=await r.json();json.textContent=JSON.stringify(data,null,2);const ok=data.success&&data.status==='validated';status.textContent=data.status|| (data.success?'OK':'ERROR');status.className='status '+(ok?'ok':'bad')}catch(err){json.textContent=JSON.stringify({success:false,error:err.message},null,2);status.textContent='ERROR';status.className='status bad'}finally{submit.disabled=false;submit.textContent='Analyser le document'}});
</script>
</body>
</html>
