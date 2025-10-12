CREATE TABLE fiche_expression_besoin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255),
    matricule VARCHAR(100),
    departement INT,
    description TEXT,
    montant DECIMAL(10,2),
    date DATE,
    impact TEXT,
    frequence VARCHAR(50)
);


CREATE TABLE besoin_expression (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expression_besoin_id INT,
    type VARCHAR(50),
    objet VARCHAR(255),
    quantite INT,
    fournisseur TINYINT(1),
    nom_fournisseur VARCHAR(255),
    prix_unitaire DECIMAL(10,2),
    telephone VARCHAR(20)
);

CREATE TABLE expression_besoin_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expression_besoin_id INT,
    file_path VARCHAR(255),
    file_type VARCHAR(255)
);

