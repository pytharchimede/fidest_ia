document.addEventListener("DOMContentLoaded", () => {
  const predictBtn = document.getElementById("predict");
  const saveBtn = document.getElementById("save_example");
  const labelEl = document.getElementById("label");
  const ocrEl = document.getElementById("ocr_text");
  const resultEl = document.getElementById("result");
  const examplesList = document.getElementById("examples_list");
  const agentNameEl = document.getElementById("agent_name");
  const autoAddEl = document.getElementById("auto_add");

  function renderResult(json) {
    resultEl.innerHTML = `<pre>${JSON.stringify(json, null, 2)}</pre>`;
  }

  async function predict() {
    const form = new FormData();
    form.append("label", labelEl.value);
    form.append("ocr_text", ocrEl.value);
    const res = await fetch(
      `/api/agent.php?agent=${encodeURIComponent(agentNameEl.value)}`,
      { method: "POST", body: form }
    );
    const json = await res.json();
    renderResult(json);
    return json;
  }

  async function loadExamples() {
    const res = await fetch(
      `/api/agent.php?action=list_examples&agent=${encodeURIComponent(
        agentNameEl.value
      )}`
    );
    const json = await res.json();
    if (!json || json.length === 0) examplesList.innerText = "Aucun exemple.";
    else {
      examplesList.innerHTML = json
        .slice(0, 10)
        .map(
          (e) =>
            `<div class="example p-2 border mb-2"><strong>${
              e.decision
            }</strong> — ${e.label}<br><small>${
              (e.meta && e.meta.author) || ""
            } ${e.created_at}</small></div>`
        )
        .join("");
    }
  }

  // load images for test-by-image
  async function loadImages() {
    const sel = document.getElementById("image_select");
    if (!sel) return;
    const res = await fetch("/api/images_list.php");
    const imgs = await res.json();
    if (!imgs || imgs.length === 0) {
      sel.innerHTML = "<option>Aucune image</option>";
      return;
    }
    sel.innerHTML = imgs
      .map((i) => `<option value="${i.url}">${i.name}</option>`)
      .join("");
    sel.addEventListener("change", async (e) => {
      const v = e.target.value;
      // prefill ocr_text with image path and auto predict
      ocrEl.value = v;
      await predict();
    });
  }

  predictBtn.addEventListener("click", async () => {
    const json = await predict();
    // auto-add if enabled and decision is final
    if (
      autoAddEl &&
      autoAddEl.checked &&
      json &&
      ["ACCEPTE", "REFUSE"].includes(json.decision)
    ) {
      const form = new FormData();
      form.append("auto_add", "1");
      form.append("label", labelEl.value);
      form.append("ocr_text", ocrEl.value);
      form.append("author", document.getElementById("author").value || "web");
      form.append(
        "agent_version",
        document.getElementById("agent_version").value || "v0.1"
      );
      await fetch(
        `/api/agent.php?agent=${encodeURIComponent(agentNameEl.value)}`,
        { method: "POST", body: form }
      );
      loadExamples();
    }
  });

  saveBtn.addEventListener("click", async () => {
    const form = new FormData();
    form.append("action", "add_example");
    form.append("label", labelEl.value);
    form.append("ocr_text", ocrEl.value);
    // ask user for decision
    const decision =
      prompt("Decision (ACCEPTE / REFUSE / HUMAN_REVIEW):", "ACCEPTE") ||
      "ACCEPTE";
    form.append("decision", decision);
    form.append("author", document.getElementById("author").value || "web");
    form.append(
      "agent_version",
      document.getElementById("agent_version").value || "v0.1"
    );
    const res = await fetch(
      `/api/agent.php?agent=${encodeURIComponent(agentNameEl.value)}`,
      { method: "POST", body: form }
    );
    const json = await res.json();
    alert("Exemple enregistré");
    loadExamples();
  });

  // load examples on start
  loadExamples();
  loadImages();
});
