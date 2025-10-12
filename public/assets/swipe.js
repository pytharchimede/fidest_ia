document.addEventListener("DOMContentLoaded", () => {
  const card = document.getElementById("card");
  const notice = document.getElementById("notice");
  const yes = document.getElementById("yes");
  const no = document.getElementById("no");
  let current = null;

  async function fetchOne() {
    card.innerHTML = "Chargement...";
    let res;
    try {
      // use relative path because annotator.php lives in public/
      res = await fetch("./api/stream.php");
    } catch (err) {
      console.error("fetch error", err);
      if (notice) {
        notice.classList.remove("d-none");
        notice.innerText = "Erreur de connexion à l'API.";
      }
      card.innerHTML = "Erreur de connexion à l'API.";
      return;
    }
    let json;
    try {
      json = await res.json();
    } catch (err) {
      console.error("invalid json", err);
      // show response text for easier debugging
      const txt = await res.text().catch(() => "");
      card.innerHTML = "Réponse API invalide: <pre>" + txt + "</pre>";
      return;
    }
    if (json.error) {
      if (notice) {
        notice.classList.remove("d-none");
        notice.innerText = "Aucune image trouvée.";
      }
      card.innerHTML = "Aucune image trouvée.";
      return;
    }
    // clear notice
    if (notice) {
      notice.classList.add("d-none");
      notice.innerText = "";
    }
    current = json;
    // build display
    // image path is relative to public/, no leading slash
    const imgSrc = json.image.replace(/^\//, "");
    const html = `<div class="img-wrap"><img src="${imgSrc}" alt="visu"></div><div class="mt-2 text-start"><strong>Libellé:</strong> ${
      json.label || "<em>aucun</em>"
    }</div>`;
    card.innerHTML = html;
  }

  async function submitJudgement(j) {
    if (!current) return;
    const form = new FormData();
    form.append("agent", "etalonIA");
    form.append("request_id", current.request_id || "");
    form.append("image", current.image);
    form.append("label", current.label || "");
    form.append("judgement", j);
    form.append("author", "web");
    await fetch("./api/judge.php", { method: "POST", body: form });
    // animate
    card.classList.add("pushed");
    setTimeout(() => {
      card.classList.remove("pushed");
      fetchOne();
    }, 300);
  }

  yes.addEventListener("click", () => submitJudgement("YES"));
  no.addEventListener("click", () => submitJudgement("NO"));

  fetchOne();
});
