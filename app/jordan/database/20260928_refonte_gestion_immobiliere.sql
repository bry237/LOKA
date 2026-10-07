-- ============================================================
-- Migration : Refonte Gestion Immobilière
-- Date      : 2026-09-28
-- Auteur    : LOKA
-- ============================================================

-- -----------------------------------------------
-- 1. Table `bien` — Ajout DPE, GES, meublé, date
-- -----------------------------------------------
ALTER TABLE `bien`
  ADD COLUMN `meuble` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`,
  ADD COLUMN `date_disponibilite` DATE DEFAULT NULL AFTER `meuble`,
  ADD COLUMN `dpe_classe` ENUM('A','B','C','D','E','F','G') DEFAULT NULL AFTER `date_disponibilite`,
  ADD COLUMN `ges_classe` ENUM('A','B','C','D','E','F','G') DEFAULT NULL AFTER `dpe_classe`;

-- -----------------------------------------------
-- 2. Table `locataire` — Identité, situation, garant
-- -----------------------------------------------
ALTER TABLE `locataire`
  ADD COLUMN `numero_identite` VARCHAR(50) DEFAULT NULL AFTER `pays`,
  ADD COLUMN `type_identite` ENUM('CNI','PASSEPORT','TITRE_SEJOUR','AUTRE') DEFAULT NULL AFTER `numero_identite`,
  ADD COLUMN `situation_familiale` ENUM('CELIBATAIRE','MARIE','PACSE','DIVORCE','VEUF') DEFAULT NULL AFTER `type_identite`,
  ADD COLUMN `nombre_occupants` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `situation_familiale`,
  ADD COLUMN `garant_nom` VARCHAR(150) DEFAULT NULL AFTER `nombre_occupants`,
  ADD COLUMN `garant_telephone` VARCHAR(30) DEFAULT NULL AFTER `garant_nom`,
  ADD COLUMN `garant_email` VARCHAR(255) DEFAULT NULL AFTER `garant_telephone`,
  ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `garant_email`;

-- -----------------------------------------------
-- 3. Table `contrat` — Type, révision, préavis
-- -----------------------------------------------
ALTER TABLE `contrat`
  ADD COLUMN `type_contrat` ENUM('HABITATION_VIDE','HABITATION_MEUBLE','COMMERCIAL','PROFESSIONNEL','SAISONNIER')
    NOT NULL DEFAULT 'HABITATION_VIDE' AFTER `numero`,
  ADD COLUMN `indice_revision` VARCHAR(50) DEFAULT 'IRL' AFTER `jour_echeance`,
  ADD COLUMN `date_prochaine_revision` DATE DEFAULT NULL AFTER `indice_revision`,
  ADD COLUMN `preavis_mois` TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER `date_prochaine_revision`;

-- -----------------------------------------------
-- 4. Nouvelle table `etat_des_lieux`
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `etat_des_lieux` (
  `id_etat_lieux` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_contrat` BIGINT UNSIGNED NOT NULL,
  `id_bien` BIGINT UNSIGNED NOT NULL,
  `type_etat` ENUM('ENTREE','SORTIE') NOT NULL,
  `date_etat` DATE NOT NULL,
  `observations` TEXT DEFAULT NULL,
  `chemin_fichier_pdf` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_etat_lieux`),
  KEY `idx_edl_contrat` (`id_contrat`),
  KEY `idx_edl_bien` (`id_bien`),
  CONSTRAINT `fk_edl_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat`(`id_contrat`),
  CONSTRAINT `fk_edl_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien`(`id_bien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 5. Nouvelle table `revision_loyer`
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `revision_loyer` (
  `id_revision` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_contrat` BIGINT UNSIGNED NOT NULL,
  `date_revision` DATE NOT NULL,
  `ancien_loyer` DECIMAL(12,2) NOT NULL,
  `nouveau_loyer` DECIMAL(12,2) NOT NULL,
  `indice_reference` VARCHAR(50) DEFAULT NULL,
  `valeur_indice` DECIMAL(10,4) DEFAULT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_revision`),
  KEY `idx_revision_contrat` (`id_contrat`),
  CONSTRAINT `fk_revision_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat`(`id_contrat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 6. Ajout des nouveaux équipements courants
-- -----------------------------------------------
INSERT IGNORE INTO `equipement` (`nom`, `description`) VALUES
  ('Digicode', 'Digicode ou contrôle d''accès.'),
  ('Gardien', 'Gardien ou concierge.'),
  ('Piscine', 'Piscine privative ou collective.'),
  ('Jardin', 'Jardin privatif.'),
  ('Fibre optique', 'Raccordement fibre optique.'),
  ('Lave-vaisselle', 'Lave-vaisselle intégré.'),
  ('Lave-linge', 'Lave-linge ou branchement.'),
  ('tableau', 'Tableau électrique aux normes.');
