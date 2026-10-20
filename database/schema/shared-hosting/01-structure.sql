-- LOKA — 1/3 : structure (tables, sans clés étrangères)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `abonnement`;

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

DROP TABLE IF EXISTS `adresse`;

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

DROP TABLE IF EXISTS `agence`;

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

DROP TABLE IF EXISTS `agence_abonnement`;

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
  KEY `idx_aa_abonnement` (`id_abonnement`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `bien`;

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
  KEY `idx_bien_loyer` (`loyer`)
) ENGINE=InnoDB AUTO_INCREMENT=911 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `bien_equipement`;

CREATE TABLE `bien_equipement` (
  `id_bien` bigint(20) unsigned NOT NULL,
  `id_equipement` bigint(20) unsigned NOT NULL,
  `quantite` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_bien`,`id_equipement`),
  KEY `fk_be_equipement` (`id_equipement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `bien_image`;

CREATE TABLE `bien_image` (
  `id_image` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bien` bigint(20) unsigned NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_image`),
  KEY `idx_bien_image_bien` (`id_bien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bien_proprietaire`;

CREATE TABLE `bien_proprietaire` (
  `id_bien` bigint(20) unsigned NOT NULL,
  `id_proprietaire` bigint(20) unsigned NOT NULL,
  `quote_part` decimal(5,2) NOT NULL DEFAULT 100.00,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  PRIMARY KEY (`id_bien`,`id_proprietaire`,`date_debut`),
  KEY `idx_bp_proprietaire` (`id_proprietaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `commentaire_intervention`;

CREATE TABLE `commentaire_intervention` (
  `id_commentaire` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_intervention` bigint(20) unsigned NOT NULL,
  `id_utilisateur` bigint(20) unsigned DEFAULT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_commentaire`),
  KEY `idx_commentaire_intervention` (`id_intervention`),
  KEY `fk_commentaire_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=904 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `contrat`;

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
  KEY `idx_contrat_dates` (`date_debut`,`date_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `contrat_locataire`;

CREATE TABLE `contrat_locataire` (
  `id_contrat` bigint(20) unsigned NOT NULL,
  `id_locataire` bigint(20) unsigned NOT NULL,
  `titulaire_principal` tinyint(1) NOT NULL DEFAULT 0,
  `date_entree` date DEFAULT NULL,
  `date_sortie` date DEFAULT NULL,
  PRIMARY KEY (`id_contrat`,`id_locataire`),
  KEY `idx_cl_locataire` (`id_locataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `contrat_signature`;

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

DROP TABLE IF EXISTS `demande_paiement`;

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
  KEY `fk_demande_paiement_agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `document`;

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
  KEY `idx_document_intervention` (`id_intervention`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `document_agence`;

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
  KEY `fk_document_agence_validateur` (`id_utilisateur_validateur`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `document_utilisateur`;

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
  KEY `fk_document_utilisateur_validateur` (`id_utilisateur_validateur`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `echeance`;

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
  KEY `idx_echeance_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=910 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `equipement`;

CREATE TABLE `equipement` (
  `id_equipement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_equipement`),
  UNIQUE KEY `uq_equipement_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `etat_des_lieux`;

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
  KEY `idx_edl_contrat` (`id_contrat`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `facture_abonnement`;

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
  KEY `fk_facture_abonnement_abonnement` (`id_abonnement`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `immeuble`;

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
  KEY `fk_immeuble_adresse` (`id_adresse`)
) ENGINE=InnoDB AUTO_INCREMENT=902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `intervention`;

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
  KEY `fk_intervention_createur` (`id_utilisateur_createur`)
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `journal_audit`;

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
  KEY `idx_audit_date` (`date_action`)
) ENGINE=InnoDB AUTO_INCREMENT=355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `locataire`;

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
  KEY `fk_locataire_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `notification`;

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
  KEY `idx_notification_date` (`date_envoi`)
) ENGINE=InnoDB AUTO_INCREMENT=918 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `otp_code`;

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
  KEY `idx_otp_code_utilisateur` (`id_utilisateur`,`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset`;

CREATE TABLE `password_reset` (
  `id_password_reset` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_utilisateur` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `date_expiration` datetime NOT NULL,
  `date_utilisation` datetime DEFAULT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_password_reset`),
  KEY `idx_password_reset_utilisateur` (`id_utilisateur`),
  KEY `idx_password_reset_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `paiement`;

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
  KEY `idx_paiement_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `parametre_agence`;

CREATE TABLE `parametre_agence` (
  `id_parametre` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_agence` bigint(20) unsigned NOT NULL,
  `cle` varchar(150) NOT NULL,
  `valeur` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `uq_parametre_agence` (`id_agence`,`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `parametre_plateforme`;

CREATE TABLE `parametre_plateforme` (
  `id_parametre` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cle` varchar(150) NOT NULL,
  `valeur` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `uk_parametre_plateforme_cle` (`cle`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `permission`;

CREATE TABLE `permission` (
  `id_permission` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `code` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `uq_permission_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `photo_bien`;

CREATE TABLE `photo_bien` (
  `id_photo` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bien` bigint(20) unsigned NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre_affichage` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_photo`),
  KEY `idx_photo_bien` (`id_bien`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `proprietaire`;

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
  KEY `fk_proprietaire_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quittance`;

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
  UNIQUE KEY `uq_quittance_numero` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `revision_loyer`;

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
  KEY `idx_revision_contrat` (`id_contrat`)
) ENGINE=InnoDB AUTO_INCREMENT=902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
  `id_role` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uq_role_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_permission`;

CREATE TABLE `role_permission` (
  `id_role` bigint(20) unsigned NOT NULL,
  `id_permission` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id_role`,`id_permission`),
  KEY `fk_rp_permission` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `session_utilisateur`;

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
  KEY `idx_session_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `type_bien`;

CREATE TABLE `type_bien` (
  `id_type_bien` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_type_bien`),
  UNIQUE KEY `uq_type_bien_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `type_document`;

CREATE TABLE `type_document` (
  `id_type_document` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_type_document`),
  UNIQUE KEY `uq_type_document_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `utilisateur`;

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
  KEY `idx_utilisateur_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=911 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `v_biens_agence`;

/*!50001 DROP VIEW IF EXISTS `v_biens_agence`*/;

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

/*!50001 DROP VIEW IF EXISTS `v_biens_agence`*/;

/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_biens_agence` AS select `b`.`id_bien` AS `id_bien`,`b`.`id_agence` AS `id_agence`,`a`.`nom` AS `agence`,`b`.`reference` AS `reference`,`b`.`titre` AS `titre`,`tb`.`nom` AS `type_bien`,`ad`.`ville` AS `ville`,`b`.`surface` AS `surface`,`b`.`loyer` AS `loyer`,`b`.`charges` AS `charges`,`b`.`statut` AS `statut` from (((`bien` `b` join `agence` `a` on(`a`.`id_agence` = `b`.`id_agence`)) join `type_bien` `tb` on(`tb`.`id_type_bien` = `b`.`id_type_bien`)) join `adresse` `ad` on(`ad`.`id_adresse` = `b`.`id_adresse`)) where `b`.`deleted_at` is null */;
