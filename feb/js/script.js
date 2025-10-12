function removeBesoin(button) {
  // Trouver le parent direct contenant le besoin (besoin-item)
  const besoinItem = button.closest(".besoin-item");
  if (besoinItem) {
    besoinItem.remove(); // Supprimer l'élément du DOM
  }
}

$(document).ready(function () {
  // Initialiser Select2
  $(".select2").select2({
    placeholder: "Sélectionnez une option",
    allowClear: true,
  });

  // Navigation du formulaire en mode "wizard"
  let currentStep = 0;
  const steps = document.querySelectorAll(".step");
  const progress = document.getElementById("progress");
  const stepsIndicator = document.querySelectorAll(".step-indicator");

  showStep(currentStep);

  function showStep(n) {
    steps.forEach((step, index) => {
      step.style.display = index === n ? "block" : "none";
    });

    // Mettre à jour la jauge de progression
    progress.style.width = ((n + 1) / steps.length) * 100 + "%";

    // Indicateurs actifs
    stepsIndicator.forEach((indicator, index) => {
      if (index <= n) {
        indicator.classList.add("active");
      } else {
        indicator.classList.remove("active");
      }
    });

    // Gestion des boutons
    document.getElementById("prevBtn").style.display =
      n === 0 ? "none" : "inline-block";
    document.getElementById("nextBtn").style.display =
      n === steps.length - 1 ? "none" : "inline-block";
    document.getElementById("submitBtn").style.display =
      n === steps.length - 1 ? "inline-block" : "none";
  }

  window.nextPrev = function (n) {
    currentStep += n;
    if (currentStep >= steps.length) return;
    showStep(currentStep);
  };

  let besoinCount = 0;

  document
    .getElementById("addBesoinBtn")
    .addEventListener("click", function () {
      besoinCount++; // Incrémenter le compteur à chaque ajout de besoin
      const besoinContainer = document.getElementById("besoin-container");

      // Créer un nouvel élément pour le besoin avec un id unique
      const newBesoin = document.createElement("div");
      newBesoin.classList.add("besoin-item");
      newBesoin.innerHTML = `
      <div class="form-group">
        <label for="type-${besoinCount}"><i class="fas fa-box-open"></i> Type de besoin</label>
        <select id="type-${besoinCount}" name="type[]" required>
          <option value="Bien">Bien</option>
          <option value="Service">Service</option>
        </select>
      </div>
      <div class="form-group">
        <label for="objet-${besoinCount}"><i class="fas fa-tag"></i> Libellé du besoin</label>
        <input type="text" id="objet-${besoinCount}" name="objet[]" placeholder="Ex: Achat de matériel" required>
      </div>
      <div class="form-group">
        <label for="quantite-${besoinCount}"><i class="fas fa-sort-numeric-up-alt"></i> Quantité</label>
        <input type="number" id="quantite-${besoinCount}" name="quantite[]" placeholder="Entrez la quantité" required>
      </div>
      <div class="form-group">
        <label for="fournisseur-${besoinCount}"><i class="fas fa-handshake"></i> J'ai un fournisseur :</label>
        <div class="switch-box">
          <input type="checkbox" id="fournisseur-${besoinCount}" name="fournisseur[]" class="switch-input">
          <label class="switch-label" for="fournisseur-${besoinCount}">
            <span class="switch-inner"></span>
            <span class="switch-button"></span>
          </label>
          <span class="switch-status" id="status-${besoinCount}">Non, je préfère que vous m'en trouviez un</span>
        </div>
      </div>
      <div class="form-group" id="fournisseur-details-${besoinCount}">
        <label for="nomFournisseur-${besoinCount}"><i class="fas fa-building"></i> Nom / Dénomination fournisseur</label>
        <input type="text" id="nomFournisseur-${besoinCount}" name="nomFournisseur[]" placeholder="Nom du fournisseur">
        <label for="prixUnitaire"><i class="fas fa-franc-sign"></i> Prix unitaire (en FCFA)</label>
        <input type="number" id="prixUnitaire-${besoinCount}" name="prixUnitaire[]" placeholder="Prix unitaire">
        <label for="telephone"><i class="fas fa-phone"></i> Téléphone</label>
        <input type="tel" id="telephone-${besoinCount}" name="telephone[]" placeholder="Numéro de téléphone">
      </div>
      <button type="button" class="remove-btn" onclick="removeBesoin(this)"><i class="fas fa-trash-alt"></i> Supprimer</button>
    `;

      console.log("Nouveau besoin ajpouté avec identifiant : " + besoinCount);

      // Ajouter l'élément dans le conteneur
      besoinContainer.appendChild(newBesoin);

      document.getElementById(
        `fournisseur-details-${besoinCount}`
      ).style.display = "none";

      // Ajouter l'événement au nouvel élément switch
      document
        .getElementById(`fournisseur-${besoinCount}`)
        .addEventListener("change", function () {
          const fournisseurDetails = document.getElementById(
            `fournisseur-details-${besoinCount}`
          );

          const switchStatus = document.getElementById(`status-${besoinCount}`);

          if (this.checked) {
            fournisseurDetails.style.display = "block";
            switchStatus.textContent = "Oui, j'ai mon fournisseur";
          } else {
            fournisseurDetails.style.display = "none";
            switchStatus.textContent =
              "Non, je souhaite que vous m'en trouviez un";
          }
        });
    });

  // Remplacer #fournisseur par une sélection dynamique
  console.log(document.querySelector(`[id^="fournisseur-"]`).value);

  if (document.querySelector(`#fournisseur-${besoinCount}`).checked) {
    // fournisseurDetails.style.display = "block";
    console.log("Switch coché");
  } else {
    // fournisseurDetails.style.display = "none";
    console.log("Switch décoché");
  }

  document.querySelectorAll('[id^="fournisseur-"]').forEach((el) => {
    el.addEventListener("change", function () {
      const fournisseurDetails = document.querySelector(
        `#fournisseur-details-${el.id.split("-")[1]}`
      );
      const switchStatus = document.querySelector(
        `#status-${el.id.split("-")[1]}`
      );

      if (el.checked) {
        fournisseurDetails.style.display = "block";
        switchStatus.textContent = "Oui, j'ai déjà mon fournisseur";
      } else {
        fournisseurDetails.style.display = "none";
        switchStatus.textContent = "Non, je souhaite que vous m'en trouviez un";
      }
    });
  });

  // Délégation d'événement pour les boutons Supprimer
  $(document).on("click", ".remove-btn", function () {
    removeBesoin(this);
  });
});
