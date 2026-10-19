-- =============================================================================
-- LOKA — schéma MySQL consolidé (une seule base, toutes les tables)
-- =============================================================================
-- Fichier généré le 2026-10-19 par export (mysqldump) de la base de
-- développement loka, qui avait déjà reçu toutes les migrations ci-dessous.
-- Il remplace et consolide :
--   - loka.sql (dump racine, figé au 2026-09-14)
--   - database/schema/20260916_add_locataire_revenu_fourchette.sql
--   - database/schema/20260929_add_document_agence.sql
--   - database/schema/20260930_add_document_utilisateur.sql
--   - database/schema/20260930_add_phone_verification_otp.sql
--   - database/schema/20261001_add_abonnements_stripe.sql
--   - database/schema/20261012_add_biens_contrats_locataires_extensions.sql
--   - database/schema/20261013_add_agence_couleur_accent.sql
--   - database/schema/20261013_add_demande_paiement.sql
--   - database/schema/20261015_add_abonnement_deleted_at.sql
--   - database/schema/20261015_add_parametre_plateforme.sql
--
-- Contenu : structure complète (CREATE TABLE pour les 41 tables + la vue
-- v_biens_agence), plus les données de référence indispensables au
-- fonctionnement de l'application (role, permission, type_bien,
-- type_document, equipement, abonnement). Aucune donnée transactionnelle
-- ou de test (agences, utilisateurs, contrats, paiements...) n'est incluse.
--
-- Installation sur une base vierge :
--   mysql -u root -e "CREATE DATABASE loka CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--   mysql -u root loka < database/schema/loka_database_mysql.sql
-- =============================================================================


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonnement` (
  `id_abonnement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_mensuel` decimal(12,2) NOT NULL DEFAULT 0.00,
  `limite_utilisateurs` int(10) unsigned DEFAULT NULL,
  `limite_biens` int(10) unsigned DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_abonnement`),
  UNIQUE KEY `uq_abonnement_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adresse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adresse` (
  `id_adresse` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ligne1` varchar(255) NOT NULL,
  `ligne2` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `pays` varchar(100) NOT NULL DEFAULT 'France',
  PRIMARY KEY (`id_adresse`)
) ENGINE=InnoDB AUTO_INCREMENT=912 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence` (
  `id_agence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `indicatif_pays` varchar(5) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `logo` varchar(500) DEFAULT NULL,
  `couleur_accent` varchar(7) DEFAULT NULL,
  `statut` enum('ACTIVE','SUSPENDED','PENDING') NOT NULL DEFAULT 'ACTIVE',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_agence`),
  UNIQUE KEY `uq_agence_email` (`email`),
  KEY `idx_agence_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agence_abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence_abonnement` (
  `id_agence_abonnement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_abonnement` bigint(20) unsigned NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('ACTIVE','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_agence_abonnement`),
  KEY `idx_aa_agence` (`id_agence`),
  KEY `idx_aa_abonnement` (`id_abonnement`),
  CONSTRAINT `fk_aa_abonnement` FOREIGN KEY (`id_abonnement`) REFERENCES `abonnement` (`id_abonnement`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aa_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bien` (
  `id_bien` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_responsable` bigint(20) unsigned DEFAULT NULL,
  `id_type_bien` bigint(20) unsigned NOT NULL,
  `id_adresse` bigint(20) unsigned NOT NULL,
  `id_immeuble` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(100) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `surface` decimal(10,2) NOT NULL,
  `nombre_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `etage` int(11) DEFAULT NULL,
  `loyer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `caution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('CREATED','AVAILABLE','OCCUPIED','MAINTENANCE','ARCHIVED') NOT NULL DEFAULT 'CREATED',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `acces_pmr` tinyint(1) NOT NULL DEFAULT 0,
  `ascenseur` tinyint(1) NOT NULL DEFAULT 0,
  `balcon` tinyint(1) NOT NULL DEFAULT 0,
  `chauffage` enum('INDIVIDUEL','COLLECTIF') DEFAULT NULL,
  `chauffage_sol` tinyint(1) NOT NULL DEFAULT 0,
  `classe_energetique` enum('A','B','C','D','E','F','G') DEFAULT NULL,
  `climatisation` enum('OUI','NON') NOT NULL DEFAULT 'NON',
  `etat_bien` enum('TRES_BON','BON','RENOVER','TRAVAUX_A_VENIR') DEFAULT NULL,
  `exposition` enum('NORD','SUD','EST','OUEST','NORD_OUEST','NORD_EST','SUD_EST','SUD_OUEST') DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `jardin` tinyint(1) NOT NULL DEFAULT 0,
  `meuble` varchar(50) DEFAULT NULL,
  `piscine` tinyint(1) NOT NULL DEFAULT 0,
  `terrasse` tinyint(1) NOT NULL DEFAULT 0,
  `type_vendeur` enum('PARTICULIER','PROFESSIONNEL') DEFAULT NULL,
  `date_disponibilite` date DEFAULT NULL,
  `dpe_classe` enum('A','B','C','D','E','F','G') DEFAULT NULL,
  `ges_classe` enum('A','B','C','D','E','F','G') DEFAULT NULL,
  PRIMARY KEY (`id_bien`),
  UNIQUE KEY `uq_bien_reference` (`reference`),
  KEY `idx_bien_agence` (`id_agence`),
  KEY `idx_bien_type` (`id_type_bien`),
  KEY `idx_bien_adresse` (`id_adresse`),
  KEY `idx_bien_immeuble` (`id_immeuble`),
  KEY `idx_bien_statut` (`statut`),
  KEY `idx_bien_loyer` (`loyer`),
  CONSTRAINT `fk_bien_adresse` FOREIGN KEY (`id_adresse`) REFERENCES `adresse` (`id_adresse`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bien_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bien_immeuble` FOREIGN KEY (`id_immeuble`) REFERENCES `immeuble` (`id_immeuble`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bien_type` FOREIGN KEY (`id_type_bien`) REFERENCES `type_bien` (`id_type_bien`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=911 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bien_equipement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bien_equipement` (
  `id_bien` bigint(20) unsigned NOT NULL,
  `id_equipement` bigint(20) unsigned NOT NULL,
  `quantite` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_bien`,`id_equipement`),
  KEY `fk_be_equipement` (`id_equipement`),
  CONSTRAINT `fk_be_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_be_equipement` FOREIGN KEY (`id_equipement`) REFERENCES `equipement` (`id_equipement`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bien_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bien_image` (
  `id_image` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bien` bigint(20) unsigned NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_image`),
  KEY `idx_bien_image_bien` (`id_bien`),
  CONSTRAINT `fk_bien_image_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bien_proprietaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bien_proprietaire` (
  `id_bien` bigint(20) unsigned NOT NULL,
  `id_proprietaire` bigint(20) unsigned NOT NULL,
  `quote_part` decimal(5,2) NOT NULL DEFAULT 100.00,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  PRIMARY KEY (`id_bien`,`id_proprietaire`,`date_debut`),
  KEY `idx_bp_proprietaire` (`id_proprietaire`),
  CONSTRAINT `fk_bp_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bp_proprietaire` FOREIGN KEY (`id_proprietaire`) REFERENCES `proprietaire` (`id_proprietaire`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commentaire_intervention`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commentaire_intervention` (
  `id_commentaire` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_intervention` bigint(20) unsigned NOT NULL,
  `id_utilisateur` bigint(20) unsigned DEFAULT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_commentaire`),
  KEY `idx_commentaire_intervention` (`id_intervention`),
  KEY `fk_commentaire_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_commentaire_intervention` FOREIGN KEY (`id_intervention`) REFERENCES `intervention` (`id_intervention`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commentaire_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=904 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat` (
  `id_contrat` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_bien` bigint(20) unsigned NOT NULL,
  `numero` varchar(100) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `loyer` decimal(12,2) NOT NULL,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `depot_garantie` decimal(12,2) NOT NULL DEFAULT 0.00,
  `frequence_paiement` enum('MONTHLY','QUARTERLY','YEARLY') NOT NULL DEFAULT 'MONTHLY',
  `jour_echeance` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `statut` enum('DRAFT','ACTIVE','EXPIRED','RENEWED','TERMINATED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `motif_resiliation` text DEFAULT NULL,
  `date_resiliation` date DEFAULT NULL,
  `chemin_fichier_pdf` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `type_contrat` enum('HABITATION_VIDE','HABITATION_MEUBLE','COMMERCIAL','PROFESSIONNEL','SAISONNIER') NOT NULL DEFAULT 'HABITATION_VIDE',
  `indice_revision` varchar(50) DEFAULT 'IRL',
  `date_prochaine_revision` date DEFAULT NULL,
  `preavis_mois` tinyint(3) unsigned NOT NULL DEFAULT 3,
  PRIMARY KEY (`id_contrat`),
  UNIQUE KEY `uq_contrat_numero` (`numero`),
  KEY `idx_contrat_agence` (`id_agence`),
  KEY `idx_contrat_bien` (`id_bien`),
  KEY `idx_contrat_statut` (`statut`),
  KEY `idx_contrat_dates` (`date_debut`,`date_fin`),
  CONSTRAINT `fk_contrat_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_contrat_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrat_locataire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat_locataire` (
  `id_contrat` bigint(20) unsigned NOT NULL,
  `id_locataire` bigint(20) unsigned NOT NULL,
  `titulaire_principal` tinyint(1) NOT NULL DEFAULT 0,
  `date_entree` date DEFAULT NULL,
  `date_sortie` date DEFAULT NULL,
  PRIMARY KEY (`id_contrat`,`id_locataire`),
  KEY `idx_cl_locataire` (`id_locataire`),
  CONSTRAINT `fk_cl_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cl_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrat_signature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat_signature` (
  `id_signature` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) NOT NULL,
  `id_locataire` int(11) NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'EN_ATTENTE',
  `date_signature` datetime DEFAULT NULL,
  `date_depot` datetime DEFAULT NULL,
  `chemin_fichier_signe` varchar(255) DEFAULT NULL,
  `confirmation` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_signature`),
  KEY `idx_signature_contrat` (`id_contrat`),
  KEY `idx_signature_locataire` (`id_locataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `demande_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demande_paiement` (
  `id_demande` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_echeance` bigint(20) unsigned NOT NULL,
  `id_locataire` bigint(20) unsigned DEFAULT NULL,
  `id_paiement` bigint(20) unsigned DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `stripe_checkout_session_id` varchar(255) DEFAULT NULL,
  `checkout_url` varchar(500) DEFAULT NULL,
  `statut` enum('PENDING','PAID','EXPIRED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `id_utilisateur_createur` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_demande`),
  KEY `idx_demande_paiement_echeance` (`id_echeance`),
  KEY `idx_demande_paiement_session` (`stripe_checkout_session_id`),
  KEY `fk_demande_paiement_agence` (`id_agence`),
  CONSTRAINT `fk_demande_paiement_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_demande_paiement_echeance` FOREIGN KEY (`id_echeance`) REFERENCES `echeance` (`id_echeance`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document` (
  `id_document` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_type_document` bigint(20) unsigned DEFAULT NULL,
  `id_bien` bigint(20) unsigned DEFAULT NULL,
  `id_contrat` bigint(20) unsigned DEFAULT NULL,
  `id_proprietaire` bigint(20) unsigned DEFAULT NULL,
  `id_locataire` bigint(20) unsigned DEFAULT NULL,
  `id_intervention` bigint(20) unsigned DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_original` varchar(255) DEFAULT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `taille_octets` bigint(20) unsigned DEFAULT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_document`),
  KEY `idx_document_type` (`id_type_document`),
  KEY `idx_document_bien` (`id_bien`),
  KEY `idx_document_contrat` (`id_contrat`),
  KEY `idx_document_proprietaire` (`id_proprietaire`),
  KEY `idx_document_locataire` (`id_locataire`),
  KEY `idx_document_intervention` (`id_intervention`),
  CONSTRAINT `fk_document_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_intervention` FOREIGN KEY (`id_intervention`) REFERENCES `intervention` (`id_intervention`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_proprietaire` FOREIGN KEY (`id_proprietaire`) REFERENCES `proprietaire` (`id_proprietaire`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_type` FOREIGN KEY (`id_type_document`) REFERENCES `type_document` (`id_type_document`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_document_avant_insert` BEFORE INSERT ON `document` FOR EACH ROW BEGIN
    IF (
        (NEW.id_bien IS NOT NULL) +
        (NEW.id_contrat IS NOT NULL) +
        (NEW.id_proprietaire IS NOT NULL) +
        (NEW.id_locataire IS NOT NULL) +
        (NEW.id_intervention IS NOT NULL)
    ) <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un document doit etre rattache a une seule entite metier.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_document_avant_update` BEFORE UPDATE ON `document` FOR EACH ROW BEGIN
    IF (
        (NEW.id_bien IS NOT NULL) +
        (NEW.id_contrat IS NOT NULL) +
        (NEW.id_proprietaire IS NOT NULL) +
        (NEW.id_locataire IS NOT NULL) +
        (NEW.id_intervention IS NOT NULL)
    ) <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un document doit etre rattache a une seule entite metier.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `document_agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_agence` (
  `id_document_agence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `type` enum('PIECE_IDENTITE','JUSTIFICATIF_IMMATRICULATION','AUTRE') NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `taille_octets` bigint(20) unsigned DEFAULT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `statut_verification` enum('EN_ATTENTE','VALIDE','REJETE') NOT NULL DEFAULT 'EN_ATTENTE',
  `analyse_ia_resume` text DEFAULT NULL,
  `analyse_ia_alertes` text DEFAULT NULL CHECK (`analyse_ia_alertes` is null or json_valid(`analyse_ia_alertes`)),
  `analyse_ia_date` datetime DEFAULT NULL,
  `id_utilisateur_validateur` bigint(20) unsigned DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `motif_rejet` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_document_agence`),
  KEY `idx_document_agence_agence` (`id_agence`),
  KEY `fk_document_agence_validateur` (`id_utilisateur_validateur`),
  CONSTRAINT `fk_document_agence_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_agence_validateur` FOREIGN KEY (`id_utilisateur_validateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_utilisateur` (
  `id_document_utilisateur` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned NOT NULL,
  `type` enum('PIECE_IDENTITE','JUSTIFICATIF_DOMICILE','AUTRE') NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `taille_octets` bigint(20) unsigned DEFAULT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `statut_verification` enum('EN_ATTENTE','VALIDE','REJETE') NOT NULL DEFAULT 'EN_ATTENTE',
  `analyse_ia_resume` text DEFAULT NULL,
  `analyse_ia_alertes` text DEFAULT NULL CHECK (`analyse_ia_alertes` is null or json_valid(`analyse_ia_alertes`)),
  `analyse_ia_date` datetime DEFAULT NULL,
  `id_utilisateur_validateur` bigint(20) unsigned DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `motif_rejet` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_document_utilisateur`),
  KEY `idx_document_utilisateur_utilisateur` (`id_utilisateur`),
  KEY `fk_document_utilisateur_validateur` (`id_utilisateur_validateur`),
  CONSTRAINT `fk_document_utilisateur_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_utilisateur_validateur` FOREIGN KEY (`id_utilisateur_validateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `echeance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echeance` (
  `id_echeance` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrat` bigint(20) unsigned NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `montant_loyer` decimal(12,2) NOT NULL,
  `montant_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_total` decimal(12,2) GENERATED ALWAYS AS (`montant_loyer` + `montant_charges`) STORED,
  `date_echeance` date NOT NULL,
  `statut` enum('PENDING','PARTIAL','PAID','LATE','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_echeance`),
  UNIQUE KEY `uq_echeance_contrat_periode` (`id_contrat`,`periode_debut`,`periode_fin`),
  KEY `idx_echeance_date` (`date_echeance`),
  KEY `idx_echeance_statut` (`statut`),
  CONSTRAINT `fk_echeance_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=910 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipement` (
  `id_equipement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_equipement`),
  UNIQUE KEY `uq_equipement_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etat_des_lieux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `etat_des_lieux` (
  `id_etat_lieux` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrat` bigint(20) unsigned NOT NULL,
  `id_bien` bigint(20) unsigned NOT NULL,
  `type_etat` enum('ENTREE','SORTIE') NOT NULL,
  `date_etat` date NOT NULL,
  `observations` text DEFAULT NULL,
  `chemin_fichier_pdf` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_etat_lieux`),
  KEY `idx_edl_bien` (`id_bien`),
  KEY `idx_edl_contrat` (`id_contrat`),
  CONSTRAINT `fk_edl_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`),
  CONSTRAINT `fk_edl_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facture_abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facture_abonnement` (
  `id_facture` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence_abonnement` bigint(20) unsigned DEFAULT NULL,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_abonnement` bigint(20) unsigned NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `stripe_checkout_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `statut` enum('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_facture`),
  KEY `idx_facture_abonnement_agence` (`id_agence`),
  KEY `idx_facture_abonnement_session` (`stripe_checkout_session_id`),
  KEY `fk_facture_abonnement_abonnement` (`id_abonnement`),
  CONSTRAINT `fk_facture_abonnement_abonnement` FOREIGN KEY (`id_abonnement`) REFERENCES `abonnement` (`id_abonnement`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_facture_abonnement_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `immeuble`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `immeuble` (
  `id_immeuble` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_adresse` bigint(20) unsigned NOT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `nombre_etages` int(10) unsigned DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_immeuble`),
  KEY `idx_immeuble_agence` (`id_agence`),
  KEY `fk_immeuble_adresse` (`id_adresse`),
  CONSTRAINT `fk_immeuble_adresse` FOREIGN KEY (`id_adresse`) REFERENCES `adresse` (`id_adresse`) ON UPDATE CASCADE,
  CONSTRAINT `fk_immeuble_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intervention`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intervention` (
  `id_intervention` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `id_bien` bigint(20) unsigned NOT NULL,
  `id_locataire` bigint(20) unsigned DEFAULT NULL,
  `id_utilisateur_createur` bigint(20) unsigned DEFAULT NULL,
  `id_utilisateur_assigne` bigint(20) unsigned DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priorite` enum('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
  `statut` enum('OPEN','ASSIGNED','IN_PROGRESS','WAITING','RESOLVED','CLOSED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `cout_estime` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cout_final` decimal(12,2) DEFAULT NULL,
  `compte_rendu` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_intervention`),
  KEY `idx_intervention_agence` (`id_agence`),
  KEY `idx_intervention_bien` (`id_bien`),
  KEY `idx_intervention_locataire` (`id_locataire`),
  KEY `idx_intervention_assigne` (`id_utilisateur_assigne`),
  KEY `idx_intervention_priorite` (`priorite`),
  KEY `idx_intervention_statut` (`statut`),
  KEY `fk_intervention_createur` (`id_utilisateur_createur`),
  CONSTRAINT `fk_intervention_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_intervention_assigne` FOREIGN KEY (`id_utilisateur_assigne`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_intervention_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_intervention_createur` FOREIGN KEY (`id_utilisateur_createur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_intervention_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_audit` (
  `id_audit` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned DEFAULT NULL,
  `id_utilisateur` bigint(20) unsigned DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','EXPORT','DOWNLOAD','OTHER') NOT NULL,
  `entite` varchar(100) NOT NULL,
  `id_entite` bigint(20) unsigned DEFAULT NULL,
  `ancienne_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ancienne_valeur`)),
  `nouvelle_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nouvelle_valeur`)),
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_audit`),
  KEY `idx_audit_agence` (`id_agence`),
  KEY `idx_audit_utilisateur` (`id_utilisateur`),
  KEY `idx_audit_entite` (`entite`,`id_entite`),
  KEY `idx_audit_date` (`date_action`),
  CONSTRAINT `fk_audit_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locataire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locataire` (
  `id_locataire` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned DEFAULT NULL,
  `id_agence` bigint(20) unsigned DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `indicatif_pays` varchar(5) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `date_naissance` date DEFAULT NULL,
  `profession` varchar(150) DEFAULT NULL,
  `revenu_mensuel` decimal(12,2) DEFAULT NULL,
  `revenu_mensuel_fourchette` varchar(50) DEFAULT NULL,
  `statut` enum('ACTIVE','INACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `numero_identite` varchar(50) DEFAULT NULL,
  `type_identite` enum('CNI','PASSEPORT','TITRE_SEJOUR','AUTRE') DEFAULT NULL,
  `situation_familiale` enum('CELIBATAIRE','MARIE','PACSE','DIVORCE','VEUF') DEFAULT NULL,
  `nombre_occupants` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `garant_nom` varchar(150) DEFAULT NULL,
  `garant_telephone` varchar(30) DEFAULT NULL,
  `garant_email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id_locataire`),
  KEY `idx_locataire_agence` (`id_agence`),
  KEY `idx_locataire_email` (`email`),
  KEY `idx_locataire_statut` (`statut`),
  KEY `idx_locataire_utilisateur` (`id_utilisateur`),
  KEY `fk_locataire_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_locataire_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_locataire_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `id_notification` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('INTERNAL','EMAIL','SMS') NOT NULL DEFAULT 'INTERNAL',
  `statut` enum('PENDING','SENT','READ','FAILED') NOT NULL DEFAULT 'PENDING',
  `date_envoi` datetime DEFAULT NULL,
  `date_lecture` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notification`),
  KEY `idx_notification_utilisateur` (`id_utilisateur`),
  KEY `idx_notification_statut` (`statut`),
  KEY `idx_notification_date` (`date_envoi`),
  CONSTRAINT `fk_notification_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=918 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otp_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_code` (
  `id_otp` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned NOT NULL,
  `objectif` enum('PHONE_VERIFICATION') NOT NULL DEFAULT 'PHONE_VERIFICATION',
  `code_hash` char(64) NOT NULL,
  `tentatives` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `tentatives_max` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `statut` enum('PENDING','VERIFIED','EXPIRED','FAILED') NOT NULL DEFAULT 'PENDING',
  `date_expiration` datetime NOT NULL,
  `date_verification` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_otp`),
  KEY `idx_otp_code_utilisateur` (`id_utilisateur`,`statut`),
  CONSTRAINT `fk_otp_code_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiement` (
  `id_paiement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_echeance` bigint(20) unsigned NOT NULL,
  `id_contrat` bigint(20) unsigned NOT NULL,
  `id_locataire` bigint(20) unsigned DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `mode_paiement` enum('CASH','BANK_TRANSFER','CARD','CHEQUE','DIRECT_DEBIT','OTHER') NOT NULL,
  `reference_transaction` varchar(150) DEFAULT NULL,
  `statut` enum('PENDING','PAID','LATE','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `commentaire` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `uq_paiement_reference` (`reference_transaction`),
  KEY `idx_paiement_echeance` (`id_echeance`),
  KEY `idx_paiement_contrat` (`id_contrat`),
  KEY `idx_paiement_locataire` (`id_locataire`),
  KEY `idx_paiement_date` (`date_paiement`),
  KEY `idx_paiement_statut` (`statut`),
  CONSTRAINT `fk_paiement_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON UPDATE CASCADE,
  CONSTRAINT `fk_paiement_echeance` FOREIGN KEY (`id_echeance`) REFERENCES `echeance` (`id_echeance`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_paiement_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parametre_agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parametre_agence` (
  `id_parametre` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `cle` varchar(150) NOT NULL,
  `valeur` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `uq_parametre_agence` (`id_agence`,`cle`),
  CONSTRAINT `fk_parametre_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parametre_plateforme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parametre_plateforme` (
  `id_parametre` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cle` varchar(150) NOT NULL,
  `valeur` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `uk_parametre_plateforme_cle` (`cle`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission` (
  `id_permission` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `code` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `uq_permission_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `photo_bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photo_bien` (
  `id_photo` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bien` bigint(20) unsigned NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre_affichage` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_photo`),
  KEY `idx_photo_bien` (`id_bien`),
  CONSTRAINT `fk_photo_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proprietaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proprietaire` (
  `id_proprietaire` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned DEFAULT NULL,
  `id_agence` bigint(20) unsigned DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `indicatif_pays` varchar(5) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `date_naissance` date DEFAULT NULL,
  `projet` enum('rent','sell','rent-sell') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_proprietaire`),
  KEY `idx_proprietaire_agence` (`id_agence`),
  KEY `idx_proprietaire_email` (`email`),
  KEY `idx_proprietaire_utilisateur` (`id_utilisateur`),
  KEY `fk_proprietaire_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_proprietaire_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proprietaire_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quittance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quittance` (
  `id_quittance` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_paiement` bigint(20) unsigned NOT NULL,
  `numero` varchar(100) NOT NULL,
  `date_emission` datetime NOT NULL DEFAULT current_timestamp(),
  `periode_debut` date DEFAULT NULL,
  `periode_fin` date DEFAULT NULL,
  `chemin_fichier_pdf` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_quittance`),
  UNIQUE KEY `uq_quittance_paiement` (`id_paiement`),
  UNIQUE KEY `uq_quittance_numero` (`numero`),
  CONSTRAINT `fk_quittance_paiement` FOREIGN KEY (`id_paiement`) REFERENCES `paiement` (`id_paiement`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `revision_loyer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revision_loyer` (
  `id_revision` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrat` bigint(20) unsigned NOT NULL,
  `date_revision` date NOT NULL,
  `ancien_loyer` decimal(12,2) NOT NULL,
  `nouveau_loyer` decimal(12,2) NOT NULL,
  `indice_reference` varchar(50) DEFAULT NULL,
  `valeur_indice` decimal(10,4) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_revision`),
  KEY `idx_revision_contrat` (`id_contrat`),
  CONSTRAINT `fk_revision_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`)
) ENGINE=InnoDB AUTO_INCREMENT=902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id_role` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uq_role_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permission` (
  `id_role` bigint(20) unsigned NOT NULL,
  `id_permission` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id_role`,`id_permission`),
  KEY `fk_rp_permission` (`id_permission`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`id_permission`) REFERENCES `permission` (`id_permission`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session_utilisateur` (
  `id_session` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_expiration` datetime NOT NULL,
  `date_derniere_activite` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_session`),
  UNIQUE KEY `uq_session_token` (`token_hash`),
  KEY `idx_session_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_session_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `type_bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_bien` (
  `id_type_bien` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_type_bien`),
  UNIQUE KEY `uq_type_bien_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `type_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_document` (
  `id_type_document` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_type_document`),
  UNIQUE KEY `uq_type_document_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateur` (
  `id_utilisateur` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned DEFAULT NULL,
  `id_role` bigint(20) unsigned NOT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `indicatif_pays` varchar(5) DEFAULT NULL,
  `telephone_verifie` tinyint(1) NOT NULL DEFAULT 0,
  `statut` enum('PENDING','ACTIVE','INACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `uq_utilisateur_email` (`email`),
  KEY `idx_utilisateur_agence` (`id_agence`),
  KEY `idx_utilisateur_role` (`id_role`),
  KEY `idx_utilisateur_statut` (`statut`),
  CONSTRAINT `fk_utilisateur_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  CONSTRAINT `fk_utilisateur_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=911 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_biens_agence`;
/*!50001 DROP VIEW IF EXISTS `v_biens_agence`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_biens_agence` AS SELECT
 1 AS `id_bien`,
  1 AS `id_agence`,
  1 AS `agence`,
  1 AS `reference`,
  1 AS `titre`,
  1 AS `type_bien`,
  1 AS `ville`,
  1 AS `surface`,
  1 AS `loyer`,
  1 AS `charges`,
  1 AS `statut` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `v_biens_agence`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_biens_agence` AS select `b`.`id_bien` AS `id_bien`,`b`.`id_agence` AS `id_agence`,`a`.`nom` AS `agence`,`b`.`reference` AS `reference`,`b`.`titre` AS `titre`,`tb`.`nom` AS `type_bien`,`ad`.`ville` AS `ville`,`b`.`surface` AS `surface`,`b`.`loyer` AS `loyer`,`b`.`charges` AS `charges`,`b`.`statut` AS `statut` from (((`bien` `b` join `agence` `a` on(`a`.`id_agence` = `b`.`id_agence`)) join `type_bien` `tb` on(`tb`.`id_type_bien` = `b`.`id_type_bien`)) join `adresse` `ad` on(`ad`.`id_adresse` = `b`.`id_adresse`)) where `b`.`deleted_at` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- =============================================================================
-- Données de référence (catalogues indispensables au fonctionnement de l'app)
-- =============================================================================


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` (`id_role`, `nom`, `description`, `created_at`) VALUES (1,'Administrateur plateforme','Administration globale de la plateforme.','2026-09-14 17:56:42'),(2,'Administrateur agence','Administration de son agence.','2026-09-14 17:56:42'),(3,'Gestionnaire immobilier','Gestion quotidienne des biens et locations.','2026-09-14 17:56:42'),(4,'Comptable','Suivi des paiements, impayes et rapports financiers.','2026-09-14 17:56:42'),(5,'Proprietaire','Consultation des biens et revenus qui lui appartiennent.','2026-09-14 17:56:42'),(6,'Locataire','Acces aux informations liées a sa location.','2026-09-14 17:56:42'),(7,'Technicien','Gestion des interventions qui lui sont assignees.','2026-09-14 17:56:42');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `permission` WRITE;
/*!40000 ALTER TABLE `permission` DISABLE KEYS */;
INSERT INTO `permission` (`id_permission`, `nom`, `code`, `description`) VALUES (1,'Gerer les agences','AGENCE_GERER','Creer, modifier, suspendre une agence.'),(2,'Gerer les utilisateurs','UTILISATEUR_GERER','Creer, modifier et desactiver les utilisateurs.'),(3,'Gerer les roles','ROLE_GERER','Gerer les roles et permissions.'),(4,'Gerer les biens','BIEN_GERER','Gerer les biens immobiliers.'),(5,'Consulter les biens','BIEN_CONSULTER','Consulter les biens autorises.'),(6,'Gerer les proprietaires','PROPRIETAIRE_GERER','Gerer les proprietaires.'),(7,'Gerer les locataires','LOCATAIRE_GERER','Gerer les locataires.'),(8,'Gerer les contrats','CONTRAT_GERER','Gerer les contrats de location.'),(9,'Gerer les paiements','PAIEMENT_GERER','Enregistrer et suivre les paiements.'),(10,'Consulter les paiements','PAIEMENT_CONSULTER','Consulter les paiements autorises.'),(11,'Gerer la maintenance','MAINTENANCE_GERER','Traiter les interventions.'),(12,'Gerer les documents','DOCUMENT_GERER','Importer, classer et gerer les documents.'),(13,'Consulter les documents','DOCUMENT_CONSULTER','Consulter les documents autorises.'),(14,'Consulter le dashboard','DASHBOARD_CONSULTER','Acceder aux indicateurs.'),(15,'Consulter les audits','AUDIT_CONSULTER','Consulter le journal d audit.'),(16,'Configurer l agence','AGENCE_PARAMETRER','Modifier les parametres de l agence.');
/*!40000 ALTER TABLE `permission` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `type_bien` WRITE;
/*!40000 ALTER TABLE `type_bien` DISABLE KEYS */;
INSERT INTO `type_bien` (`id_type_bien`, `nom`, `description`) VALUES (1,'Appartement','Logement en appartement.'),(2,'Maison','Maison individuelle.'),(3,'Bureau','Local a usage professionnel.'),(4,'Commercial','Local commercial.'),(5,'Terrain','Terrain.'),(6,'Garage','Garage ou stationnement.'),(7,'Entrepot','Entrepot ou local de stockage.'),(8,'Autre','Autre type de bien.');
/*!40000 ALTER TABLE `type_bien` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `type_document` WRITE;
/*!40000 ALTER TABLE `type_document` DISABLE KEYS */;
INSERT INTO `type_document` (`id_type_document`, `nom`, `description`) VALUES (1,'Piece identite','Piece d identite.'),(2,'Bail','Contrat ou bail de location.'),(3,'Quittance','Quittance de loyer.'),(4,'Etat des lieux','Etat des lieux.'),(5,'Justificatif','Justificatif administratif ou financier.'),(6,'Facture','Facture.'),(7,'Photo','Document image.'),(8,'Autre','Autre document.');
/*!40000 ALTER TABLE `type_document` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `equipement` WRITE;
/*!40000 ALTER TABLE `equipement` DISABLE KEYS */;
INSERT INTO `equipement` (`id_equipement`, `nom`, `description`) VALUES (1,'Ascenseur','Ascenseur de l immeuble ou du logement.'),(2,'Parking','Place de parking.'),(3,'Balcon','Balcon.'),(4,'Terrasse','Terrasse.'),(5,'Cave','Cave.'),(6,'Interphone','Interphone.'),(7,'Climatisation','Systeme de climatisation.'),(8,'Chauffage','Systeme de chauffage.'),(9,'Digicode','Digicode ou contrôle d\'accès.'),(10,'Gardien','Gardien ou concierge.'),(11,'Piscine','Piscine privative ou collective.'),(12,'Jardin','Jardin privatif.'),(13,'Fibre optique','Raccordement fibre optique.'),(14,'Lave-vaisselle','Lave-vaisselle intégré.'),(15,'Lave-linge','Lave-linge ou branchement.'),(16,'tableau','Tableau électrique aux normes.');
/*!40000 ALTER TABLE `equipement` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `abonnement` WRITE;
/*!40000 ALTER TABLE `abonnement` DISABLE KEYS */;
INSERT INTO `abonnement` (`id_abonnement`, `nom`, `description`, `prix_mensuel`, `limite_utilisateurs`, `limite_biens`, `actif`, `deleted_at`, `stripe_price_id`, `created_at`) VALUES (1,'Gratuit','Offre de demonstration.',0.00,3,10,1,NULL,NULL,'2026-09-14 17:56:42'),(2,'Pro','Offre standard.',9.99,10,100,1,NULL,'price_1ULn2oCzovH9rkANffxahryO','2026-09-14 17:56:42'),(3,'Max','Offre professionnelle.',19.99,NULL,NULL,1,NULL,'price_1ULn2rCzovH9rkANLGqhsddQ','2026-09-14 17:56:42');
/*!40000 ALTER TABLE `abonnement` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

