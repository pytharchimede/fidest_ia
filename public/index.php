<?php
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FIDEST IA — Intelligence documentaire</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--text:#111827;--muted:#6b7280;--line:#e5e7eb;--primary:#111827;--success:#0f766e;--danger:#b42318;--soft:#f9fafb}
*{box-sizing:border-box;min-width:0}
html,body{max-width:100%;overflow-x:hidden}
body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:linear-gradient(180deg,#fff 0,#f6f8fb 62%);color:var(--text)}
button,input,select,textarea{font:inherit;max-width:100%}
.shell{width:min(1180px,100%);margin:auto;padding:24px 18px 56px}
.nav{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:28px}
.brand{display:flex;align-items:center;gap:10px;font-weight:850;white-space:nowrap}.logo{width:40px;height:40px;border-radius:13px;background:#111827;color:#fff;display:grid;place-items:center;flex:0 0 auto}
.nav-actions{display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap}.badge,.ghost{font-size:12px;border:1px solid var(--line);border-radius:999px;padding:8px 11px;background:#fff;color:var(--muted)}.ghost{cursor:pointer;color:var(--text)}
.hero{display:grid;grid-template-columns:minmax(0,.9fr) minmax(420px,1.1fr);gap:24px;align-items:start}
.intro{padding:18px 0}.intro h1{font-size:clamp(36px,4.5vw,64px);line-height:1;letter-spacing:-.045em;margin:0 0 18px;overflow-wrap:anywhere}.intro p{font-size:16px;line-height:1.65;color:var(--muted);max-width:650px}.chips{display:flex;gap:7px;flex-wrap:wrap;margin-top:22px}.chip{background:#fff;border:1px solid var(--line);padding:7px 10px;border-radius:999px;font-size:12px;color:#475569}
.panel{width:100%;background:rgba(255,255,255,.97);border:1px solid var(--line);border-radius:24px;padding:20px;box-shadow:0 20px 60px rgba(17,24,39,.07);overflow:hidden}
.drop{border:1.5px dashed #cbd5e1;border-radius:18px;padding:28px 18px;text-align:center;background:#fbfcfe;cursor:pointer;display:block;overflow:hidden}.drop strong{display:block;font-size:17px;margin-bottom:7px;overflow-wrap:anywhere}.drop span{font-size:13px;color:var(--muted)}input[type=file]{display:none}
.grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;margin-top:15px}.field{display:flex;flex-direction:column;gap:7px}.field label{font-size:13px;font-weight:750}.field input,.field select,.field textarea{width:100%;border:1px solid var(--line);border-radius:12px;padding:0 12px;background:white}.field input,.field select{height:44px}.field textarea{min-height:90px;padding:11px;resize:vertical}.action{width:100%;height:48px;border:0;border-radius:13px;background:#111827;color:white;font-weight:780;margin-top:16px;cursor:pointer}.action:disabled{opacity:.5}
.result{width:100%;margin-top:16px;border:1px solid var(--line);border-radius:16px;overflow:hidden;display:none}.result-head{padding:12px 14px;background:var(--soft);border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.status{font-size:12px;font-weight:800;border-radius:999px;padding:5px 9px;max-width:100%;overflow-wrap:anywhere}.status.ok{background:#ecfdf3;color:var(--success)}.status.bad{background:#fef3f2;color:var(--danger)}
pre{margin:0;padding:14px;width:100%;max-width:100%;max-height:430px;overflow-y:auto;overflow-x:hidden;background:#0b1020;color:#d1e0ff;font-size:12px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;tab-size:2}
.exports{display:none;gap:8px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line);background:#fff}.exports button{border:1px solid var(--line);background:#fff;border-radius:10px;padding:8px 10px;cursor:pointer;font-weight:700;font-size:13px}
.features{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:28px}.feature{background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px}.feature h3{margin:7px 0;font-size:16px}.feature p{margin:0;color:var(--muted);line-height:1.5;font-size:13px}
.modal{position:fixed;inset:0;background:rgba(15,23,42,.46);display:none;place-items:center;padding:16px;z-index:100}.modal.open{display:grid}.modal-card{width:min(680px,100%);max-height:calc(100vh - 32px);overflow:auto;background:#fff;border-radius:22px;padding:20px;box-shadow:0 30px 100px rgba(0,0,0,.2)}.modal-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}.modal-head h2{margin:0;font-size:22px}.close{border:0;background:#f3f4f6;border-radius:10px;width:36px;height:36px;cursor:pointer;flex:0 0 auto}.hint{font-size:12px;color:var(--muted);line-height:1.5}.success-note{font-size:13px;color:var(--success);margin-top:10px}.error-note{font-size:13px;color:var(--danger);margin-top:10px}
@media(max-width:1080px){.hero{grid-template-columns:1fr}.intro{padding:8px 0}.intro p{max-width:none}.features{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.shell{padding:16px 12px 40px}.nav{align-items:flex-start;margin-bottom:18px}.nav-actions{max-width:60%}.badge{display:none}.panel{padding:14px;border-radius:18px}.grid,.features{grid-template-columns:1fr}.drop{padding:22px 12px}.intro h1{font-size:clamp(34px,11vw,48px)}.intro p{font-size:15px}.features{margin-top:18px}.exports button{flex:1 1 100%}}
@media(max-width:420px){.nav{flex-direction:column}.nav-actions{max-width:100%;width:100%;justify-content:flex-start}.ghost{width:100%}.intro h1{font-size:34px}}
</style>
</head>
<body>
<div class="shell">
  <div class="nav">
    <div class="brand"><div class="logo">FI</div>FIDEST IA</div>
    <div class="nav-actions"><span class="badge">Document Intelligence</span><button class="ghost" id="openTypeModal">+ Créer un type</button></div>
  </div>
  <section class="hero">
    <div class="intro">
      <h1>Comprendre n’importe quel document.</h1>
      <p>Déposez un document, laissez FIDEST IA tenter de le typer automatiquement ou forcez un type précis. Le texte est extrait, structuré, contrôlé et exportable pour vos applications, Excel ou Word.</p>
      <div class="chips"><span class="chip">Typage automatique</span><span class="chip">Document libre</span><span class="chip">Règles métier</span><span class="chip">Exports</span><span class="chip">API inter-applications</span></div>
    </div>
    <div class="panel">
      <form id="form">
        <label class="drop" for="document"><strong id="fileName">Déposer un document</strong><span>JPG, PNG, WEBP, TIFF — PDF selon configuration serveur</span></label>
        <input id="document" name="document" type="file" required>
        <div class="grid">
          <div class="field"><label>Mode de typage</label><select id="documentType" name="document_type"><option value="AUTO">Détection automatique</option><option value="GENERAL">Document libre</option></select></div>
          <div class="field"><label>Référence client / dossier</label><input name="client_reference" placeholder="Ex. BANAMUR, IFMAP, DOSSIER-2026"></div>
        </div>
        <button class="action" id="submit">Analyser le document</button>
      </form>
      <div class="result" id="result">
        <div class="result-head"><strong>Résultat de l’analyse</strong><span class="status" id="status"></span></div>
        <pre id="json"></pre>
        <div class="exports" id="exports"><button type="button" id="exportExcel">Exporter pour Excel</button><button type="button" id="exportWord">Exporter vers Word</button><button type="button" id="copyJson">Copier le JSON</button></div>
      </div>
    </div>
  </section>
  <section class="features">
    <div class="feature"><small>01</small><h3>Document libre</h3><p>OCR et restitution du contenu même lorsqu’aucun modèle documentaire n’existe encore.</p></div>
    <div class="feature"><small>02</small><h3>Typage intelligent</h3><p>Le mode automatique combine des marqueurs forts, des exclusions et des seuils pour éviter les faux positifs.</p></div>
    <div class="feature"><small>03</small><h3>Types configurables</h3><p>Créez vos propres catégories, champs attendus et mots-clés sans toucher au code source.</p></div>
    <div class="feature"><small>04</small><h3>Exports exploitables</h3><p>Export tabulaire compatible Excel et document Word à partir des données et du texte extraits.</p></div>
  </section>
</div>

<div class="modal" id="typeModal">
  <div class="modal-card">
    <div class="modal-head"><h2>Nouveau type de document</h2><button class="close" id="closeTypeModal">×</button></div>
    <form id="typeForm">
      <div class="grid">
        <div class="field"><label>Nom</label><input id="typeName" required placeholder="Ex. Attestation fiscale"></div>
        <div class="field"><label>Code</label><input id="typeCode" placeholder="Généré automatiquement si vide"></div>
      </div>
      <div class="field" style="margin-top:14px"><label>Description</label><textarea id="typeDescription" placeholder="À quoi sert ce document ?"></textarea></div>
      <div class="grid">
        <div class="field"><label>Champs à extraire</label><input id="typeFields" placeholder="ncc, raison_sociale, date"></div>
        <div class="field"><label>Mots-clés de détection</label><input id="typeKeywords" placeholder="attestation, fiscale, impôts"></div>
      </div>
      <p class="hint">Sépare les champs et mots-clés par des virgules. Les mots-clés servent au typage automatique.</p>
      <button class="action" type="submit">Créer le type</button>
      <div id="typeMessage"></div>
    </form>
  </div>
</div>

<script>
const form=document.getElementById('form'),file=document.getElementById('document'),nameEl=document.getElementById('fileName'),result=document.getElementById('result'),json=document.getElementById('json'),status=document.getElementById('status'),submit=document.getElementById('submit'),typeSelect=document.getElementById('documentType'),exportsEl=document.getElementById('exports');
let lastResult=null;
const splitCsv=v=>v.split(',').map(x=>x.trim()).filter(Boolean);

async function loadTypes(){
  try{
    const r=await fetch('api/document-types/');
    const data=await r.json();
    if(!data.success)return;
    const current=typeSelect.value;
    typeSelect.innerHTML='<option value="AUTO">Détection automatique</option>';
    for(const t of data.data){const o=document.createElement('option');o.value=t.code;o.textContent=t.code==='GENERAL'?'Document libre':t.name;typeSelect.appendChild(o)}
    typeSelect.value=[...typeSelect.options].some(o=>o.value===current)?current:'AUTO';
  }catch(e){}
}
loadTypes();
file.addEventListener('change',()=>{nameEl.textContent=file.files[0]?.name||'Déposer un document'});
form.addEventListener('submit',async e=>{
  e.preventDefault();submit.disabled=true;submit.textContent='Analyse en cours…';result.style.display='block';exportsEl.style.display='none';json.textContent='Traitement du document…';status.textContent='PROCESSING';status.className='status';
  try{
    const r=await fetch('api/documents/analyze.php',{method:'POST',body:new FormData(form)});const data=await r.json();lastResult=data;json.textContent=JSON.stringify(data,null,2);const ok=data.success&&data.status==='validated';status.textContent=data.document_type?.name||data.status||(data.success?'OK':'ERROR');status.className='status '+(ok?'ok':'bad');if(data.success)exportsEl.style.display='flex';
  }catch(err){json.textContent=JSON.stringify({success:false,error:err.message},null,2);status.textContent='ERROR';status.className='status bad'}finally{submit.disabled=false;submit.textContent='Analyser le document'}
});
function download(content,type,filename){const blob=new Blob([content],{type});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),500)}
document.getElementById('exportExcel').onclick=()=>{if(!lastResult)return;const rows=[['Champ','Valeur']];for(const [k,v] of Object.entries(lastResult.data||{}))rows.push([k,typeof v==='object'?JSON.stringify(v):v]);rows.push(['texte_ocr',lastResult.ocr?.text||'']);const csv='\ufeff'+rows.map(r=>r.map(v=>'"'+String(v??'').replaceAll('"','""')+'"').join(';')).join('\n');download(csv,'text/csv;charset=utf-8','fidest-ia-'+(lastResult.uuid||'document')+'.csv')};
document.getElementById('exportWord').onclick=()=>{if(!lastResult)return;const fields=Object.entries(lastResult.data||{}).map(([k,v])=>`<tr><td><b>${escapeHtml(k)}</b></td><td>${escapeHtml(typeof v==='object'?JSON.stringify(v):String(v??''))}</td></tr>`).join('');const html=`<html><head><meta charset="utf-8"></head><body><h1>FIDEST IA — ${escapeHtml(lastResult.document_type?.name||'Document')}</h1><p><b>Fichier :</b> ${escapeHtml(lastResult.file?.original_name||'')}</p><table border="1" cellspacing="0" cellpadding="7">${fields}</table><h2>Texte extrait</h2><pre>${escapeHtml(lastResult.ocr?.text||'')}</pre></body></html>`;download(html,'application/msword','fidest-ia-'+(lastResult.uuid||'document')+'.doc')};
document.getElementById('copyJson').onclick=()=>lastResult&&navigator.clipboard.writeText(JSON.stringify(lastResult,null,2));
function escapeHtml(v){return String(v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]))}
const modal=document.getElementById('typeModal');document.getElementById('openTypeModal').onclick=()=>modal.classList.add('open');document.getElementById('closeTypeModal').onclick=()=>modal.classList.remove('open');modal.addEventListener('click',e=>{if(e.target===modal)modal.classList.remove('open')});
document.getElementById('typeForm').addEventListener('submit',async e=>{e.preventDefault();const message=document.getElementById('typeMessage');message.textContent='Création…';message.className='hint';try{const payload={name:document.getElementById('typeName').value,code:document.getElementById('typeCode').value,description:document.getElementById('typeDescription').value,fields:splitCsv(document.getElementById('typeFields').value),keywords:splitCsv(document.getElementById('typeKeywords').value)};const r=await fetch('api/document-types/',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const data=await r.json();if(!data.success)throw new Error(data.error||'Création impossible');message.textContent='Type créé avec succès.';message.className='success-note';e.target.reset();await loadTypes();typeSelect.value=data.code;setTimeout(()=>modal.classList.remove('open'),700)}catch(err){message.textContent=err.message;message.className='error-note'}});
</script>
</body>
</html>
