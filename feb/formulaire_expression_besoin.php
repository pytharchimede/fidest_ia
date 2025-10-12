<?php include('header_feb.php'); ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="icon/favicon.ico" sizes="32x32">
    <title>Formulaire Wizard Amélioré</title>
    <!-- Font Google -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">

        <h1><i class="fas fa-file-signature"></i> Expression de Besoin</h1>

        <!-- Jauge de progression -->
        <div class="progress-bar">
            <div class="progress" id="progress"></div>
            <div class="step-indicator active"><i class="fas fa-user"></i></div>
            <div class="step-indicator"><i class="fas fa-tasks"></i></div>
            <div class="step-indicator"><i class="fas fa-info-circle"></i></div>
            <div class="step-indicator"><i class="fas fa-check"></i></div>
        </div>

        <form id="besoinForm" class="form" enctype="multipart/form-data">

            <!-- Étape 1 : Identification -->
            <div class="step">

                <h1><i class="fas fa-user"></i> Qui suis-je ? </h1>

                <div class="form-group">
                    <label for="nom"><i class="fas fa-user"></i> Nom du salarié</label>
                    <input type="text" id="nom" name="nom" placeholder="Entrez votre nom" readonly value="<?= strtoupper($employeeDetails['nom_personnel_tasks']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="matricule"><i class="fas fa-id-badge"></i> Matricule</label>
                    <input readonly type="text" id="matricule" name="matricule" placeholder="Entrez votre matricule" value="<?= strtoupper($employeeDetails['matricule_personnel_tasks']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="departement"><i class="fas fa-building"></i> Département/Service bénéficiaire</label>
                    <select id="departement" class="select2" name="departement" required>

                        <?php foreach ($allService as $service) { ?>
                            <option value="<?= $service['id_service_tasks'] ?>"><?= $service['lib_service_tasks'] ?></option>
                        <?php } ?>

                    </select>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Veuillez sélectionner le département bénéficiaire, pas nécessairement le votre.
                    </small>
                </div>


            </div>

            <!-- Étape 2 : Type de besoin -->
            <div class="step">
                <h1><i class="fas fa-box-open"></i> Quel est mon besoin ?</h1>

                <div id="besoin-container">
                    <!-- Les besoin y sont ajoutés de façon dynamique depuis le js -->
                </div>

                <div class="form-group">
                    <button type="button" class="add-btn" id="addBesoinBtn"><i class="fas fa-plus-circle"></i> Ajouter un besoin</button>
                </div>
            </div>

            <!-- Étape 3 : Détails du besoin -->
            <div class="step">

                <h1><i class="fas fa-align-left"></i> Pourquoi est ce que je fais cette demande ?</h1>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description détaillée</label>
                    <textarea id="description" name="description" placeholder="Décrivez votre besoin" required></textarea>
                </div>

                <div class="form-group">
                    <label for="montant"><i class="fas fa-franc-sign"></i> Montant estimé (XOF)</label>
                    <input type="number" id="montant" name="montant" placeholder="Ex: 1000" required>
                </div>

                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar-day"></i> Date de livraison proposée</label>
                    <input type="date" id="date" name="date" required>
                </div>
            </div>

            <!-- Étape 4 : Validation -->
            <div class="step">

                <h1><i class="fas fa-exclamation-circle"></i> Quelles sont les conséquences ? </h1>

                <div class="form-group">
                    <label for="impact"><i class="fas fa-exclamation-circle"></i> Impact de la non-satisfaction du besoin</label>
                    <textarea id="impact" name="impact" placeholder="Expliquez l'impact" required></textarea>
                </div>

                <div class="form-group">
                    <label for="frequence"><i class="fas fa-sync-alt"></i> Fréquence du besoin</label>
                    <select id="frequence" class="select2" name="frequence" required>
                        <option value="">Sélectionnez votre département</option>
                        <option value="unique">Unique</option>
                        <option value="hebdomadaire">Hebdomadaire</option>
                        <option value="mensuelle">Mensuelle</option>
                        <option value="trimestrielle">Trimestrielle</option>
                        <option value="semestrielle">Semestrielle</option>
                        <option value="annuelle">Annuelle</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="file-upload">
                        <i class="fas fa-upload"></i> Fichiers ou images justificatifs
                        <span class="required-badge" title="Preuve obligatoire" style="margin-left:8px;background:#dc3545;color:#fff;font-size:12px;padding:2px 8px;border-radius:999px;">Obligatoire</span>
                    </label>
                    <div id="file-dropzone" class="dropzone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Glissez et déposez vos fichiers ici ou cliquez pour les sélectionner</p>
                        <input required type="file" id="file-upload" name="files[]" multiple class="hidden-input">
                    </div>
                    <div id="preview-container"></div> <!-- Zone d'aperçu des fichiers -->
                </div>

            </div>

            <!-- Navigation entre étapes -->
            <div class="form-group navigation">
                <button type="button" id="prevBtn" class="nav-btn" onclick="nextPrev(-1)"><i class="fas fa-angle-double-left"></i> Précédent</button>
                <button type="button" id="nextBtn" class="nav-btn" onclick="nextPrev(1)">Suivant <i class="fas fa-angle-double-right"></i></button>
            </div>

            <!-- Soumettre -->
            <div class="form-group">
                <button type="submit" id="submitBtn" class="submit-btn" disabled aria-disabled="true">Soumettre</button>
            </div>
        </form>
    </div>

    <!-- JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        $(document).ready(function() {
            const fileDropzone = document.getElementById("file-dropzone");
            const fileInput = document.getElementById("file-upload");
            const previewContainer = document.getElementById("preview-container");
            const submitBtn = document.getElementById("submitBtn");

            // Tableau pour conserver les fichiers déposés (drag & drop)
            let droppedFiles = [];

            // Met à jour l'état du bouton Soumettre (grisé si aucune preuve)
            function updateSubmitState() {
                const hasFileInput = fileInput.files && fileInput.files.length > 0;
                const hasDropped = droppedFiles.length > 0;
                const enabled = hasFileInput || hasDropped;
                submitBtn.disabled = !enabled;
                submitBtn.setAttribute('aria-disabled', String(!enabled));
            }

            // Initial: bouton désactivé
            updateSubmitState();

            // Fonction pour gérer le clic et ouvrir l'input file
            fileDropzone.addEventListener("click", function() {
                fileInput.click();
            });

            // Fonction pour gérer l'ajout de fichiers par clic
            fileInput.addEventListener("change", function() {
                handleFiles(this.files, false); // false => ne pas stocker côté droppedFiles
                updateSubmitState();
            });

            // Fonction pour gérer le drop de fichiers
            fileDropzone.addEventListener("drop", function(e) {
                e.preventDefault();
                handleFiles(e.dataTransfer.files, true); // true => stocker côté droppedFiles
                updateSubmitState();
            });

            fileDropzone.addEventListener("dragover", function(e) {
                e.preventDefault();
                fileDropzone.classList.add("dragging");
            });

            fileDropzone.addEventListener("dragleave", function() {
                fileDropzone.classList.remove("dragging");
            });

            function handleFiles(files, addToDroppedArray = false) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // Créer un conteneur pour chaque fichier avec une jauge et un aperçu
                    const previewItem = document.createElement("div");
                    previewItem.classList.add("preview-item");

                    const image = document.createElement("img");
                    const progressBar = document.createElement("div");
                    progressBar.classList.add("progress-bar");

                    // Ajouter l'élément d'aperçu
                    previewItem.appendChild(image);
                    previewItem.appendChild(progressBar);
                    previewContainer.appendChild(previewItem);

                    // Lecture du fichier en tant qu'image (si applicable)
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        image.src = e.target.result;
                    };

                    // Lire les images uniquement
                    if (file.type.startsWith("image/")) {
                        reader.readAsDataURL(file);
                    } else {
                        image.src = "img/default-file-icon.png"; // Image par défaut pour les fichiers non image
                    }

                    // Simuler une jauge de chargement (ici on fait une animation pour exemple)
                    let progress = 0;
                    const interval = setInterval(function() {
                        progress += 10;
                        progressBar.style.width = progress + "%";
                        if (progress >= 100) {
                            clearInterval(interval);
                        }
                    }, 200); // Vitesse de l'animation

                    // Stocker les fichiers déposés pour l'envoi via AJAX si nécessaire
                    if (addToDroppedArray) {
                        droppedFiles.push(file);
                    }
                }
            }


            function uploadFile(file) {
                const reader = new FileReader();
                const previewItem = document.createElement("div");
                previewItem.classList.add("preview-item");

                // Ajouter le nom du fichier au preview
                const fileNameElement = document.createElement("div");
                fileNameElement.classList.add("file-name");
                fileNameElement.textContent = file.name; // Afficher le nom du fichier

                if (file.type.startsWith("image/")) {
                    reader.readAsDataURL(file);
                    reader.onload = function(e) {
                        previewItem.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}">
                <div class="progress-bar"></div>
            `;
                        previewItem.appendChild(fileNameElement); // Ajout du nom du fichier
                    };
                } else {
                    previewItem.innerHTML = `
            <div>${file.name}</div>
            <div class="progress-bar"></div>
        `;
                    previewItem.appendChild(fileNameElement); // Ajout du nom du fichier pour les fichiers non-image
                }

                previewContainer.appendChild(previewItem);
                simulateUpload(file, previewItem.querySelector(".progress-bar"));
            }

            $('#besoinForm').on('submit', function(e) {
                e.preventDefault(); // Empêche le rechargement de la page

                // Vérification: au moins une pièce justificative (input fichier ou drag&drop)
                const hasFileInput = fileInput.files && fileInput.files.length > 0;
                const hasDropped = droppedFiles.length > 0;
                if (!hasFileInput && !hasDropped) {
                    alert('Veuillez joindre au moins une pièce justificative.');
                    return;
                }

                var formData = new FormData(this); // Récupère toutes les données du formulaire y compris les fichiers sélectionnés via input

                // Ajouter également les fichiers déposés (non présents dans l'input file)
                if (hasDropped) {
                    for (const f of droppedFiles) {
                        formData.append('files[]', f);
                    }
                }

                $.ajax({
                    url: '../request/insert_expression_besoin.php',
                    type: 'POST',
                    data: formData,
                    contentType: false, // Important pour ne pas définir le type de contenu
                    processData: false, // Ne traite pas les données de la manière classique
                    success: function(response) {
                        // Gestion de la réponse du serveur
                        console.log('Votre demande a été soumise avec succès.');
                        console.log(response); // Pour déboguer
                        $(location).attr('href', '../success_create_feb.php');

                    },
                    error: function(xhr, status, error) {
                        // Gestion des erreurs
                        console.log('Une erreur est survenue lors de la soumission de votre demande.');
                        console.error(xhr, status, error);
                    }
                });
            });


        });
    </script>
</body>

</html>