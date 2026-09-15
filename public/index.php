<?php
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FIDEST IA</title>
<style>
:root{--text:#202124;--muted:#5f6368;--line:#dadce0;--soft:#f8f9fa;--blue:#1a73e8;--blue2:#1967d2;--ok:#137333;--bad:#b3261e}
*{box-sizing:border-box;min-width:0}html,body{margin:0;max-width:100%;overflow-x:hidden}body{font-family:Arial,Helvetica,sans-serif;background:#fff;color:var(--text)}button,input,select,textarea{font:inherit}
.topbar{min-height:64px;display:flex;align-items:center;gap:28px;padding:0 24px;border-bottom:1px solid #f1f3f4}.brand{font-size:20px;font-weight:600;letter-spacing:-.3px;white-space:nowrap}.brand span{color:var(--blue)}.main-nav{display:flex;align-items:center;gap:4px;flex:1}.nav-link,.link-btn{border:0;background:transparent;color:var(--muted);padding:9px 12px;border-radius:9px;cursor:pointer;text-decoration:none;white-space:nowrap}.nav-link:hover,.link-btn:hover{background:var(--soft);color:var(--text)}.nav-link.active{color:var(--blue);background:#e8f0fe}.top-actions{display:flex;align-items:center;gap:8px}.admin-link{border:1px solid var(--line);color:var(--text)}.mobile-nav{display:none;position:relative}.mobile-nav summary{list-style:none;border:1px solid var(--line);border-radius:9px;padding:8px 12px;cursor:pointer}.mobile-nav summary::-webkit-details-marker{display:none}.mobile-menu{position:absolute;right:0;top:46px;width:230px;padding:8px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 30px rgba(60,64,67,.18);z-index:20}.mobile-menu a{display:block;color:var(--text);text-decoration:none;padding:11px;border-radius:8px}.mobile-menu a:hover{background:var(--soft)}
.page{width:min(760px,calc(100% - 32px));margin:0 auto;padding:64px 0 52px}.hero{text-align:center;margin-bottom:34px}.hero h1{font-size:clamp(34px,6vw,54px);font-weight:500;letter-spacing:-1.8px;margin:0 0 14px}.hero p{margin:0 auto;color:var(--muted);font-size:16px;line-height:1.6;max-width:610px}
.card{border:1px solid var(--line);border-radius:24px;padding:24px;background:#fff}.drop{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:170px;border:1px dashed #c4c7c5;border-radius:18px;background:#fff;cursor:pointer;padding:24px;text-align:center;transition:.15s}.drop:hover{background:var(--soft);border-color:#9aa0a6}.drop-icon{width:48px;height:48px;border-radius:50%;background:#e8f0fe;color:var(--blue);display:grid;place-items:center;font-size:24px;margin-bottom:13px}.drop strong{font-size:16px;font-weight:500;overflow-wrap:anywhere}.drop small{margin-top:7px;color:var(--muted);font-size:13px;line-height:1.45}input[type=file]{display:none}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px}.field{display:flex;flex-direction:column;gap:7px}.field label{font-size:12px;color:var(--muted);font-weight:600}.field input,.field select,.field textarea{width:100%;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--text);outline:none}.field input,.field select{height:46px;padding:0 12px}.field textarea{min-height:88px;padding:12px;resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--blue);box-shadow:0 0 0 1px var(--blue)}
.primary{width:100%;height:46px;border:0;border-radius:23px;background:var(--blue);color:#fff;font-weight:600;cursor:pointer;margin-top:18px}.primary:hover{background:var(--blue2)}.primary:disabled{opacity:.55;cursor:default}.helper{text-align:center;color:var(--muted);font-size:12px;margin-top:14px}
.result{display:none;margin-top:22px;border-top:1px solid var(--line);padding-top:20px}.result-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}.result-head strong{font-size:15px}.status{font-size:12px;padding:5px 9px;border-radius:999px;background:var(--soft);color:var(--muted)}.status.ok{background:#e6f4ea;color:var(--ok)}.status.bad{background:#fce8e6;color:var(--bad)}pre{margin:0;width:100%;max-width:100%;max-height:430px;overflow-y:auto;overflow-x:hidden;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;background:var(--soft);border:1px solid #eef0f1;border-radius:14px;padding:16px;font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#3c4043}.exports{display:none;gap:8px;flex-wrap:wrap;margin-top:12px}.secondary{border:1px solid var(--line);background:#fff;color:#3c4043;border-radius:20px;padding:9px 13px;cursor:pointer}.secondary:hover{background:var(--soft)}
.footer{text-align:center;color:#9aa0a6;font-size:12px;margin-top:30px}.modal{position:fixed;inset:0;background:rgba(32,33,36,.38);display:none;place-items:center;padding:18px;z-index:50}.modal.open{display:grid}.modal-card{width:min(600px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#fff;border-radius:24px;padding:24px;box-shadow:0 12px 44px rgba(60,64,67,.24)}.modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.modal-head h2{font-size:20px;margin:0;font-weight:500}.close{border:0;background:transparent;width:38px;height:38px;border-radius:50%;font-size:24px;cursor:pointer}.close:hover{background:var(--soft)}.hint,.success-note,.error-note{font-size:12px;line-height:1.5}.hint{color:var(--muted)}.success-note{color:var(--ok);margin-top:10px}.error-note{color:var(--bad);margin-top:10px}
@media(max-width:850px){.topbar{min-height:56px;padding:0 14px;justify-content:space-between}.main-nav,.top-actions{display:none}.mobile-nav{display:block}.page{width:min(100% - 20px,760px);padding:38px 0}.hero{margin-bottom:24px}.hero h1{font-size:36px}.hero p{font-size:15px}.card{padding:16px;border-radius:20px}.drop{min-height:145px}.form-grid{grid-template-columns:1fr}.exports .secondary{flex:1 1 calc(50% - 8px)}}
@media(max-width:420px){.brand{font-size:18px}.page{padding-top:28px}.hero h1{font-size:32px;letter-spacing:-1px}.exports .secondary{flex:1 1 100%}}
</style>
</head>
<body>
<header class="topbar">
  <div class="brand">FIDEST <span>IA</span></div>
  <nav class="main-nav" aria-label="Navigation principale">
    <a class="nav-link active" href="./">Analyser</a>
    <a class="nav-link" href="admin/documents">Documents</a>
    <a class="nav-link" href="admin/document-types">Types documentaires</a>
    <a class="nav-link" href="admin/documentation">Documentation API</a>
  </nav>
  <div class="top-actions">
    <button class="link-btn" type="button" id="openTypeModal">Créer un type</button>
    <a class="link-btn admin-link" href="admin">Administration</a>
  </div>
  <details class="mobile-nav">
    <summary aria-label="Ouvrir le menu">☰ Menu</summary>
    <nav class="mobile-menu">
      <a href="./">Analyser un document</a>
      <a href="admin/documents">Documents</a>
      <a href="admin/document-types">Types documentaires</a>
      <a href="admin/documentation">Documentation API</a>
      <a href="admin">Administration</a>
    </nav>
  </details>
</header>

<main class="page">
  <section class="hero">
    <h1>Analysez un document.</h1>
    <p>Déposez un fichier. FIDEST IA extrait son contenu, identifie son type et structure les informations utiles.</p>
  </section>

  <section class="card">
    <form id="form">
      <label class="drop" for="document">
        <span class="drop-icon">↑</span>
        <strong id="fileName">Choisir ou déposer un document</strong>
        <small>PDF, JPG, PNG, WEBP ou TIFF</small>
      </label>
      <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.tif,.tiff" required>

      <div class="form-grid">
        <div class="field">
          <label for="documentType">Type de document</label>
          <select id="documentType" name="document_type">
            <option value="AUTO">Détection automatique</option>
            <option value="GENERAL">Document libre</option>
          </select>
        </div>
        <div class="field">
          <label for="clientReference">Référence client / dossier</label>
          <input id="clientReference" name="client_reference" placeholder="Optionnel">
        </div>
      </div>

      <button class="primary" id="submit" type="submit">Analyser</button>
      <div class="helper">Le document est conservé dans le stockage privé de FIDEST IA.</div>
    </form>

    <div class="result" id="result">
      <div class="result-head">
        <strong>Résultat</strong>
        <span class="status" id="status"></span>
      </div>
      <pre id="json"></pre>
      <div class="exports" id="exports">
        <button class="secondary" type="button" id="exportExcel">Excel</button>
        <button class="secondary" type="button" id="exportWord">Word</button>
        <button class="secondary" type="button" id="copyJson">Copier le JSON</button>
      </div>
    </div>
  </section>

  <div class="footer">FIDEST IA · Intelligence documentaire</div>
</main>

<div class="modal" id="typeModal">
  <div class="modal-card">
    <div class="modal-head">
      <h2>Nouveau type de document</h2>
      <button class="close" id="closeTypeModal" type="button">×</button>
    </div>
    <form id="typeForm">
      <div class="form-grid">
        <div class="field"><label>Nom</label><input id="typeName" required placeholder="Ex. Attestation fiscale"></div>
        <div class="field"><label>Code</label><input id="typeCode" placeholder="Généré si vide"></div>
      </div>
      <div class="field" style="margin-top:14px"><label>Description</label><textarea id="typeDescription" placeholder="Description du document"></textarea></div>
      <div class="form-grid">
        <div class="field"><label>Champs à extraire</label><input id="typeFields" placeholder="ncc, raison_sociale, date"></div>
        <div class="field"><label>Mots-clés</label><input id="typeKeywords" placeholder="attestation, fiscale, impôts"></div>
      </div>
      <p class="hint">Séparez les valeurs par des virgules.</p>
      <button class="primary" type="submit">Créer le type</button>
      <div id="typeMessage"></div>
    </form>
  </div>
</div>

<script>
const form=document.getElementById('form');
const file=document.getElementById('document');
const nameEl=document.getElementById('fileName');
const result=document.getElementById('result');
const json=document.getElementById('json');
const status=document.getElementById('status');
const submit=document.getElementById('submit');
const typeSelect=document.getElementById('documentType');
const exportsEl=document.getElementById('exports');
let lastResult=null;
const splitCsv=v=>v.split(',').map(x=>x.trim()).filter(Boolean);

async function loadTypes(){
  try{
    const r=await fetch('api/document-types/');
    const data=await r.json();
    if(!data.success)return;
    const current=typeSelect.value;
    typeSelect.innerHTML='<option value="AUTO">Détection automatique</option>';
    for(const t of data.data){
      const o=document.createElement('option');
      o.value=t.code;
      o.textContent=t.code==='GENERAL'?'Document libre':t.name;
      typeSelect.appendChild(o);
    }
    typeSelect.value=[...typeSelect.options].some(o=>o.value===current)?current:'AUTO';
  }catch(e){}
}
loadTypes();

file.addEventListener('change',()=>{
  nameEl.textContent=file.files[0]?.name||'Choisir ou déposer un document';
});

form.addEventListener('submit',async e=>{
  e.preventDefault();
  submit.disabled=true;
  submit.textContent='Analyse en cours…';
  result.style.display='block';
  exportsEl.style.display='none';
  json.textContent='Traitement du document…';
  status.textContent='Analyse';
  status.className='status';
  try{
    const r=await fetch('api/documents/analyze.php',{method:'POST',body:new FormData(form)});
    const data=await r.json();
    lastResult=data;
    json.textContent=JSON.stringify(data,null,2);
    const ok=data.success&&data.status==='validated';
    status.textContent=data.document_type?.name||data.status||(data.success?'OK':'Erreur');
    status.className='status '+(ok?'ok':'bad');
    if(data.success)exportsEl.style.display='flex';
  }catch(err){
    json.textContent=JSON.stringify({success:false,error:err.message},null,2);
    status.textContent='Erreur';
    status.className='status bad';
  }finally{
    submit.disabled=false;
    submit.textContent='Analyser';
  }
});

function download(content,type,filename){
  const blob=new Blob([content],{type});
  const url=URL.createObjectURL(blob);
  const a=document.createElement('a');
  a.href=url;a.download=filename;a.click();
  setTimeout(()=>URL.revokeObjectURL(url),500);
}

document.getElementById('exportExcel').onclick=()=>{
  if(!lastResult)return;
  const rows=[['Champ','Valeur']];
  for(const [k,v] of Object.entries(lastResult.data||{}))rows.push([k,typeof v==='object'?JSON.stringify(v):v]);
  rows.push(['texte_ocr',lastResult.ocr?.text||'']);
  const csv='\ufeff'+rows.map(r=>r.map(v=>'"'+String(v??'').replaceAll('"','""')+'"').join(';')).join('\n');
  download(csv,'text/csv;charset=utf-8','fidest-ia-'+(lastResult.uuid||'document')+'.csv');
};

document.getElementById('exportWord').onclick=()=>{
  if(!lastResult)return;
  const fields=Object.entries(lastResult.data||{}).map(([k,v])=>`<tr><td><b>${escapeHtml(k)}</b></td><td>${escapeHtml(typeof v==='object'?JSON.stringify(v):String(v??''))}</td></tr>`).join('');
  const html=`<html><head><meta charset="utf-8"></head><body><h1>FIDEST IA — ${escapeHtml(lastResult.document_type?.name||'Document')}</h1><p><b>Fichier :</b> ${escapeHtml(lastResult.file?.original_name||'')}</p><table border="1" cellspacing="0" cellpadding="7">${fields}</table><h2>Texte extrait</h2><pre>${escapeHtml(lastResult.ocr?.text||'')}</pre></body></html>`;
  download(html,'application/msword','fidest-ia-'+(lastResult.uuid||'document')+'.doc');
};

document.getElementById('copyJson').onclick=()=>lastResult&&navigator.clipboard.writeText(JSON.stringify(lastResult,null,2));
function escapeHtml(v){return String(v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]))}

const modal=document.getElementById('typeModal');
document.getElementById('openTypeModal').onclick=()=>modal.classList.add('open');
document.getElementById('closeTypeModal').onclick=()=>modal.classList.remove('open');
modal.addEventListener('click',e=>{if(e.target===modal)modal.classList.remove('open')});

document.getElementById('typeForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const message=document.getElementById('typeMessage');
  message.textContent='Création…';message.className='hint';
  try{
    const payload={
      name:document.getElementById('typeName').value,
      code:document.getElementById('typeCode').value,
      description:document.getElementById('typeDescription').value,
      fields:splitCsv(document.getElementById('typeFields').value),
      keywords:splitCsv(document.getElementById('typeKeywords').value)
    };
    const r=await fetch('api/document-types/',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const data=await r.json();
    if(!data.success)throw new Error(data.error||'Création impossible');
    message.textContent='Type créé.';message.className='success-note';
    e.target.reset();await loadTypes();typeSelect.value=data.code;
    setTimeout(()=>modal.classList.remove('open'),600);
  }catch(err){message.textContent=err.message;message.className='error-note'}
});
</script>
</body>
</html>
