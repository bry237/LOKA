-- Rattrapage : ces changements existent déjà sur la base de travail (ajoutés directement en base
-- pendant les sessions "gestion des biens/locataires/propriétaires" et "paiements et quittances"
-- du 2026-10-12) mais n'avaient jamais été capturés dans un fichier de migration versionné.
-- Ce fichier les rend reproductibles pour toute autre base (collègue, autre poste, recette).
-- Toutes les instructions sont idempotentes (IF NOT EXISTS) : sans risque de la relancer par erreur.

-- --------------------------------------------------------------------------
-- bien : caractéristiques détaillées du logement (DPE, équipements, exposition...)
-- --------------------------------------------------------------------------
ALTER TABLE bien
	ADD COLUMN IF NOT EXISTS acces_pmr TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS ascenseur TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS balcon TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS chauffage ENUM('INDIVIDUEL','COLLECTIF') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS chauffage_sol TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS classe_energetique ENUM('A','B','C','D','E','F','G') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS climatisation ENUM('OUI','NON') NOT NULL DEFAULT 'NON',
	ADD COLUMN IF NOT EXISTS etat_bien ENUM('TRES_BON','BON','RENOVER','TRAVAUX_A_VENIR') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS exposition ENUM('NORD','SUD','EST','OUEST','NORD_OUEST','NORD_EST','SUD_EST','SUD_OUEST') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS jardin TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS meuble VARCHAR(50) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS piscine TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS terrasse TINYINT(1) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS type_vendeur ENUM('PARTICULIER','PROFESSIONNEL') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS date_disponibilite DATE DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS dpe_classe ENUM('A','B','C','D','E','F','G') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS ges_classe ENUM('A','B','C','D','E','F','G') DEFAULT NULL;

-- Galerie photo d'un bien (plusieurs images, une principale) — distincte de la table `photo_bien`
-- déjà existante.
CREATE TABLE IF NOT EXISTS bien_image (
	id_image BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_bien BIGINT(20) UNSIGNED NOT NULL,
	image_url VARCHAR(500) NOT NULL,
	est_principale TINYINT(1) NOT NULL DEFAULT 0,
	ordre INT(10) UNSIGNED NOT NULL DEFAULT 0,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id_image),
	KEY idx_bien_image_bien (id_bien),
	CONSTRAINT fk_bien_image_bien FOREIGN KEY (id_bien) REFERENCES bien (id_bien) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- contrat : type de bail et révision annuelle du loyer (indice IRL)
-- --------------------------------------------------------------------------
ALTER TABLE contrat
	ADD COLUMN IF NOT EXISTS type_contrat ENUM('HABITATION_VIDE','HABITATION_MEUBLE','COMMERCIAL','PROFESSIONNEL','SAISONNIER') NOT NULL DEFAULT 'HABITATION_VIDE',
	ADD COLUMN IF NOT EXISTS indice_revision VARCHAR(50) DEFAULT 'IRL',
	ADD COLUMN IF NOT EXISTS date_prochaine_revision DATE DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS preavis_mois TINYINT(3) UNSIGNED NOT NULL DEFAULT 3;

-- Historique des révisions de loyer d'un contrat.
CREATE TABLE IF NOT EXISTS revision_loyer (
	id_revision BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_contrat BIGINT(20) UNSIGNED NOT NULL,
	date_revision DATE NOT NULL,
	ancien_loyer DECIMAL(12,2) NOT NULL,
	nouveau_loyer DECIMAL(12,2) NOT NULL,
	indice_reference VARCHAR(50) DEFAULT NULL,
	valeur_indice DECIMAL(10,4) DEFAULT NULL,
	commentaire TEXT DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id_revision),
	KEY idx_revision_contrat (id_contrat),
	CONSTRAINT fk_revision_contrat FOREIGN KEY (id_contrat) REFERENCES contrat (id_contrat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- États des lieux (entrée/sortie) liés à un contrat et un bien.
CREATE TABLE IF NOT EXISTS etat_des_lieux (
	id_etat_lieux BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_contrat BIGINT(20) UNSIGNED NOT NULL,
	id_bien BIGINT(20) UNSIGNED NOT NULL,
	type_etat ENUM('ENTREE','SORTIE') NOT NULL,
	date_etat DATE NOT NULL,
	observations TEXT DEFAULT NULL,
	chemin_fichier_pdf VARCHAR(500) DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id_etat_lieux),
	KEY idx_edl_bien (id_bien),
	KEY idx_edl_contrat (id_contrat),
	CONSTRAINT fk_edl_bien FOREIGN KEY (id_bien) REFERENCES bien (id_bien),
	CONSTRAINT fk_edl_contrat FOREIGN KEY (id_contrat) REFERENCES contrat (id_contrat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- locataire : pièce d'identité, situation familiale et garant
-- --------------------------------------------------------------------------
ALTER TABLE locataire
	ADD COLUMN IF NOT EXISTS numero_identite VARCHAR(50) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS type_identite ENUM('CNI','PASSEPORT','TITRE_SEJOUR','AUTRE') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS situation_familiale ENUM('CELIBATAIRE','MARIE','PACSE','DIVORCE','VEUF') DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS nombre_occupants TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
	ADD COLUMN IF NOT EXISTS garant_nom VARCHAR(150) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS garant_telephone VARCHAR(30) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS garant_email VARCHAR(255) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL;
