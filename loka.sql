-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 14 sep. 2026 à 18:02
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `loka`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnement`
--

CREATE TABLE `abonnement` (
  `id_abonnement` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_mensuel` decimal(12,2) NOT NULL DEFAULT 0.00,
  `limite_utilisateurs` int(10) UNSIGNED DEFAULT NULL,
  `limite_biens` int(10) UNSIGNED DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `abonnement`
--

INSERT INTO `abonnement` (`id_abonnement`, `nom`, `description`, `prix_mensuel`, `limite_utilisateurs`, `limite_biens`, `actif`, `created_at`) VALUES
(1, 'Gratuit', 'Offre de demonstration.', 0.00, 3, 10, 1, '2026-09-14 17:56:42'),
(2, 'Standard', 'Offre standard.', 9.99, 10, 100, 1, '2026-09-14 17:56:42'),
(3, 'Professionnel', 'Offre professionnelle.', 19.99, NULL, NULL, 1, '2026-09-14 17:56:42');

-- --------------------------------------------------------

--
-- Structure de la table `adresse`
--

CREATE TABLE `adresse` (
  `id_adresse` bigint(20) UNSIGNED NOT NULL,
  `ligne1` varchar(255) NOT NULL,
  `ligne2` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `pays` varchar(100) NOT NULL DEFAULT 'France'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `agence`
--

CREATE TABLE `agence` (
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `logo` varchar(500) DEFAULT NULL,
  `statut` enum('ACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `agence_abonnement`
--

CREATE TABLE `agence_abonnement` (
  `id_agence_abonnement` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `id_abonnement` bigint(20) UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('ACTIVE','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `bien`
--

CREATE TABLE `bien` (
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `id_type_bien` bigint(20) UNSIGNED NOT NULL,
  `id_adresse` bigint(20) UNSIGNED NOT NULL,
  `id_immeuble` bigint(20) UNSIGNED DEFAULT NULL,
  `reference` varchar(100) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `surface` decimal(10,2) NOT NULL,
  `nombre_pieces` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `etage` int(11) DEFAULT NULL,
  `loyer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `caution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('CREATED','AVAILABLE','OCCUPIED','MAINTENANCE','ARCHIVED') NOT NULL DEFAULT 'CREATED',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `bien_equipement`
--

CREATE TABLE `bien_equipement` (
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `id_equipement` bigint(20) UNSIGNED NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 1
) ;

-- --------------------------------------------------------

--
-- Structure de la table `bien_proprietaire`
--

CREATE TABLE `bien_proprietaire` (
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `id_proprietaire` bigint(20) UNSIGNED NOT NULL,
  `quote_part` decimal(5,2) NOT NULL DEFAULT 100.00,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire_intervention`
--

CREATE TABLE `commentaire_intervention` (
  `id_commentaire` bigint(20) UNSIGNED NOT NULL,
  `id_intervention` bigint(20) UNSIGNED NOT NULL,
  `id_utilisateur` bigint(20) UNSIGNED DEFAULT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contrat`
--

CREATE TABLE `contrat` (
  `id_contrat` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `numero` varchar(100) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `loyer` decimal(12,2) NOT NULL,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `depot_garantie` decimal(12,2) NOT NULL DEFAULT 0.00,
  `frequence_paiement` enum('MONTHLY','QUARTERLY','YEARLY') NOT NULL DEFAULT 'MONTHLY',
  `jour_echeance` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `statut` enum('DRAFT','ACTIVE','EXPIRED','RENEWED','TERMINATED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `motif_resiliation` text DEFAULT NULL,
  `date_resiliation` date DEFAULT NULL,
  `chemin_fichier_pdf` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `contrat_locataire`
--

CREATE TABLE `contrat_locataire` (
  `id_contrat` bigint(20) UNSIGNED NOT NULL,
  `id_locataire` bigint(20) UNSIGNED NOT NULL,
  `titulaire_principal` tinyint(1) NOT NULL DEFAULT 0,
  `date_entree` date DEFAULT NULL,
  `date_sortie` date DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id_document` bigint(20) UNSIGNED NOT NULL,
  `id_type_document` bigint(20) UNSIGNED DEFAULT NULL,
  `id_bien` bigint(20) UNSIGNED DEFAULT NULL,
  `id_contrat` bigint(20) UNSIGNED DEFAULT NULL,
  `id_proprietaire` bigint(20) UNSIGNED DEFAULT NULL,
  `id_locataire` bigint(20) UNSIGNED DEFAULT NULL,
  `id_intervention` bigint(20) UNSIGNED DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_original` varchar(255) DEFAULT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `taille_octets` bigint(20) UNSIGNED DEFAULT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `document`
--
DELIMITER $$
CREATE TRIGGER `trg_document_avant_insert` BEFORE INSERT ON `document` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_document_avant_update` BEFORE UPDATE ON `document` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `echeance`
--

CREATE TABLE `echeance` (
  `id_echeance` bigint(20) UNSIGNED NOT NULL,
  `id_contrat` bigint(20) UNSIGNED NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `montant_loyer` decimal(12,2) NOT NULL,
  `montant_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_total` decimal(12,2) GENERATED ALWAYS AS (`montant_loyer` + `montant_charges`) STORED,
  `date_echeance` date NOT NULL,
  `statut` enum('PENDING','PARTIAL','PAID','LATE','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `equipement`
--

CREATE TABLE `equipement` (
  `id_equipement` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `equipement`
--

INSERT INTO `equipement` (`id_equipement`, `nom`, `description`) VALUES
(1, 'Ascenseur', 'Ascenseur de l immeuble ou du logement.'),
(2, 'Parking', 'Place de parking.'),
(3, 'Balcon', 'Balcon.'),
(4, 'Terrasse', 'Terrasse.'),
(5, 'Cave', 'Cave.'),
(6, 'Interphone', 'Interphone.'),
(7, 'Climatisation', 'Systeme de climatisation.'),
(8, 'Chauffage', 'Systeme de chauffage.');

-- --------------------------------------------------------

--
-- Structure de la table `immeuble`
--

CREATE TABLE `immeuble` (
  `id_immeuble` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `id_adresse` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `nombre_etages` int(10) UNSIGNED DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `intervention`
--

CREATE TABLE `intervention` (
  `id_intervention` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `id_locataire` bigint(20) UNSIGNED DEFAULT NULL,
  `id_utilisateur_createur` bigint(20) UNSIGNED DEFAULT NULL,
  `id_utilisateur_assigne` bigint(20) UNSIGNED DEFAULT NULL,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `journal_audit`
--

CREATE TABLE `journal_audit` (
  `id_audit` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED DEFAULT NULL,
  `id_utilisateur` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','EXPORT','DOWNLOAD','OTHER') NOT NULL,
  `entite` varchar(100) NOT NULL,
  `id_entite` bigint(20) UNSIGNED DEFAULT NULL,
  `ancienne_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ancienne_valeur`)),
  `nouvelle_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nouvelle_valeur`)),
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `locataire`
--

CREATE TABLE `locataire` (
  `id_locataire` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `date_naissance` date DEFAULT NULL,
  `profession` varchar(150) DEFAULT NULL,
  `revenu_mensuel` decimal(12,2) DEFAULT NULL,
  `statut` enum('ACTIVE','INACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id_notification` bigint(20) UNSIGNED NOT NULL,
  `id_utilisateur` bigint(20) UNSIGNED NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('INTERNAL','EMAIL','SMS') NOT NULL DEFAULT 'INTERNAL',
  `statut` enum('PENDING','SENT','READ','FAILED') NOT NULL DEFAULT 'PENDING',
  `date_envoi` datetime DEFAULT NULL,
  `date_lecture` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id_paiement` bigint(20) UNSIGNED NOT NULL,
  `id_echeance` bigint(20) UNSIGNED NOT NULL,
  `id_contrat` bigint(20) UNSIGNED NOT NULL,
  `id_locataire` bigint(20) UNSIGNED DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `mode_paiement` enum('CASH','BANK_TRANSFER','CARD','CHEQUE','DIRECT_DEBIT','OTHER') NOT NULL,
  `reference_transaction` varchar(150) DEFAULT NULL,
  `statut` enum('PENDING','PAID','LATE','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `commentaire` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `parametre_agence`
--

CREATE TABLE `parametre_agence` (
  `id_parametre` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `cle` varchar(150) NOT NULL,
  `valeur` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permission`
--

CREATE TABLE `permission` (
  `id_permission` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `code` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permission`
--

INSERT INTO `permission` (`id_permission`, `nom`, `code`, `description`) VALUES
(1, 'Gerer les agences', 'AGENCE_GERER', 'Creer, modifier, suspendre une agence.'),
(2, 'Gerer les utilisateurs', 'UTILISATEUR_GERER', 'Creer, modifier et desactiver les utilisateurs.'),
(3, 'Gerer les roles', 'ROLE_GERER', 'Gerer les roles et permissions.'),
(4, 'Gerer les biens', 'BIEN_GERER', 'Gerer les biens immobiliers.'),
(5, 'Consulter les biens', 'BIEN_CONSULTER', 'Consulter les biens autorises.'),
(6, 'Gerer les proprietaires', 'PROPRIETAIRE_GERER', 'Gerer les proprietaires.'),
(7, 'Gerer les locataires', 'LOCATAIRE_GERER', 'Gerer les locataires.'),
(8, 'Gerer les contrats', 'CONTRAT_GERER', 'Gerer les contrats de location.'),
(9, 'Gerer les paiements', 'PAIEMENT_GERER', 'Enregistrer et suivre les paiements.'),
(10, 'Consulter les paiements', 'PAIEMENT_CONSULTER', 'Consulter les paiements autorises.'),
(11, 'Gerer la maintenance', 'MAINTENANCE_GERER', 'Traiter les interventions.'),
(12, 'Gerer les documents', 'DOCUMENT_GERER', 'Importer, classer et gerer les documents.'),
(13, 'Consulter les documents', 'DOCUMENT_CONSULTER', 'Consulter les documents autorises.'),
(14, 'Consulter le dashboard', 'DASHBOARD_CONSULTER', 'Acceder aux indicateurs.'),
(15, 'Consulter les audits', 'AUDIT_CONSULTER', 'Consulter le journal d audit.'),
(16, 'Configurer l agence', 'AGENCE_PARAMETRER', 'Modifier les parametres de l agence.');

-- --------------------------------------------------------

--
-- Structure de la table `photo_bien`
--

CREATE TABLE `photo_bien` (
  `id_photo` bigint(20) UNSIGNED NOT NULL,
  `id_bien` bigint(20) UNSIGNED NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_stockage` varchar(500) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre_affichage` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `proprietaire`
--

CREATE TABLE `proprietaire` (
  `id_proprietaire` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `date_naissance` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quittance`
--

CREATE TABLE `quittance` (
  `id_quittance` bigint(20) UNSIGNED NOT NULL,
  `id_paiement` bigint(20) UNSIGNED NOT NULL,
  `numero` varchar(100) NOT NULL,
  `date_emission` datetime NOT NULL DEFAULT current_timestamp(),
  `periode_debut` date DEFAULT NULL,
  `periode_fin` date DEFAULT NULL,
  `chemin_fichier_pdf` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id_role` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`id_role`, `nom`, `description`, `created_at`) VALUES
(1, 'Administrateur plateforme', 'Administration globale de la plateforme.', '2026-09-14 17:56:42'),
(2, 'Administrateur agence', 'Administration de son agence.', '2026-09-14 17:56:42'),
(3, 'Gestionnaire immobilier', 'Gestion quotidienne des biens et locations.', '2026-09-14 17:56:42'),
(4, 'Comptable', 'Suivi des paiements, impayes et rapports financiers.', '2026-09-14 17:56:42'),
(5, 'Proprietaire', 'Consultation des biens et revenus qui lui appartiennent.', '2026-09-14 17:56:42'),
(6, 'Locataire', 'Acces aux informations liées a sa location.', '2026-09-14 17:56:42'),
(7, 'Technicien', 'Gestion des interventions qui lui sont assignees.', '2026-09-14 17:56:42');

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `id_role` bigint(20) UNSIGNED NOT NULL,
  `id_permission` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `session_utilisateur`
--

CREATE TABLE `session_utilisateur` (
  `id_session` bigint(20) UNSIGNED NOT NULL,
  `id_utilisateur` bigint(20) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_expiration` datetime NOT NULL,
  `date_derniere_activite` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `type_bien`
--

CREATE TABLE `type_bien` (
  `id_type_bien` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `type_bien`
--

INSERT INTO `type_bien` (`id_type_bien`, `nom`, `description`) VALUES
(1, 'Appartement', 'Logement en appartement.'),
(2, 'Maison', 'Maison individuelle.'),
(3, 'Bureau', 'Local a usage professionnel.'),
(4, 'Commercial', 'Local commercial.'),
(5, 'Terrain', 'Terrain.'),
(6, 'Garage', 'Garage ou stationnement.'),
(7, 'Entrepot', 'Entrepot ou local de stockage.'),
(8, 'Autre', 'Autre type de bien.');

-- --------------------------------------------------------

--
-- Structure de la table `type_document`
--

CREATE TABLE `type_document` (
  `id_type_document` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `type_document`
--

INSERT INTO `type_document` (`id_type_document`, `nom`, `description`) VALUES
(1, 'Piece identite', 'Piece d identite.'),
(2, 'Bail', 'Contrat ou bail de location.'),
(3, 'Quittance', 'Quittance de loyer.'),
(4, 'Etat des lieux', 'Etat des lieux.'),
(5, 'Justificatif', 'Justificatif administratif ou financier.'),
(6, 'Facture', 'Facture.'),
(7, 'Photo', 'Document image.'),
(8, 'Autre', 'Autre document.');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` bigint(20) UNSIGNED NOT NULL,
  `id_agence` bigint(20) UNSIGNED DEFAULT NULL,
  `id_role` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `statut` enum('ACTIVE','INACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_biens_agence`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_biens_agence` (
`id_bien` bigint(20) unsigned
,`id_agence` bigint(20) unsigned
,`agence` varchar(150)
,`reference` varchar(100)
,`titre` varchar(200)
,`type_bien` varchar(100)
,`ville` varchar(100)
,`surface` decimal(10,2)
,`loyer` decimal(12,2)
,`charges` decimal(12,2)
,`statut` enum('CREATED','AVAILABLE','OCCUPIED','MAINTENANCE','ARCHIVED')
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_biens_agence`
--
DROP TABLE IF EXISTS `v_biens_agence`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_biens_agence`  AS SELECT `b`.`id_bien` AS `id_bien`, `b`.`id_agence` AS `id_agence`, `a`.`nom` AS `agence`, `b`.`reference` AS `reference`, `b`.`titre` AS `titre`, `tb`.`nom` AS `type_bien`, `ad`.`ville` AS `ville`, `b`.`surface` AS `surface`, `b`.`loyer` AS `loyer`, `b`.`charges` AS `charges`, `b`.`statut` AS `statut` FROM (((`bien` `b` join `agence` `a` on(`a`.`id_agence` = `b`.`id_agence`)) join `type_bien` `tb` on(`tb`.`id_type_bien` = `b`.`id_type_bien`)) join `adresse` `ad` on(`ad`.`id_adresse` = `b`.`id_adresse`)) WHERE `b`.`deleted_at` is null ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnement`
--
ALTER TABLE `abonnement`
  ADD PRIMARY KEY (`id_abonnement`),
  ADD UNIQUE KEY `uq_abonnement_nom` (`nom`);

--
-- Index pour la table `adresse`
--
ALTER TABLE `adresse`
  ADD PRIMARY KEY (`id_adresse`);

--
-- Index pour la table `agence`
--
ALTER TABLE `agence`
  ADD PRIMARY KEY (`id_agence`),
  ADD UNIQUE KEY `uq_agence_email` (`email`),
  ADD KEY `idx_agence_statut` (`statut`);

--
-- Index pour la table `agence_abonnement`
--
ALTER TABLE `agence_abonnement`
  ADD PRIMARY KEY (`id_agence_abonnement`),
  ADD KEY `idx_aa_agence` (`id_agence`),
  ADD KEY `idx_aa_abonnement` (`id_abonnement`);

--
-- Index pour la table `bien`
--
ALTER TABLE `bien`
  ADD PRIMARY KEY (`id_bien`),
  ADD UNIQUE KEY `uq_bien_reference` (`reference`),
  ADD KEY `idx_bien_agence` (`id_agence`),
  ADD KEY `idx_bien_type` (`id_type_bien`),
  ADD KEY `idx_bien_adresse` (`id_adresse`),
  ADD KEY `idx_bien_immeuble` (`id_immeuble`),
  ADD KEY `idx_bien_statut` (`statut`),
  ADD KEY `idx_bien_loyer` (`loyer`);

--
-- Index pour la table `bien_equipement`
--
ALTER TABLE `bien_equipement`
  ADD PRIMARY KEY (`id_bien`,`id_equipement`),
  ADD KEY `fk_be_equipement` (`id_equipement`);

--
-- Index pour la table `bien_proprietaire`
--
ALTER TABLE `bien_proprietaire`
  ADD PRIMARY KEY (`id_bien`,`id_proprietaire`,`date_debut`),
  ADD KEY `idx_bp_proprietaire` (`id_proprietaire`);

--
-- Index pour la table `commentaire_intervention`
--
ALTER TABLE `commentaire_intervention`
  ADD PRIMARY KEY (`id_commentaire`),
  ADD KEY `idx_commentaire_intervention` (`id_intervention`),
  ADD KEY `fk_commentaire_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD PRIMARY KEY (`id_contrat`),
  ADD UNIQUE KEY `uq_contrat_numero` (`numero`),
  ADD KEY `idx_contrat_agence` (`id_agence`),
  ADD KEY `idx_contrat_bien` (`id_bien`),
  ADD KEY `idx_contrat_statut` (`statut`),
  ADD KEY `idx_contrat_dates` (`date_debut`,`date_fin`);

--
-- Index pour la table `contrat_locataire`
--
ALTER TABLE `contrat_locataire`
  ADD PRIMARY KEY (`id_contrat`,`id_locataire`),
  ADD KEY `idx_cl_locataire` (`id_locataire`);

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id_document`),
  ADD KEY `idx_document_type` (`id_type_document`),
  ADD KEY `idx_document_bien` (`id_bien`),
  ADD KEY `idx_document_contrat` (`id_contrat`),
  ADD KEY `idx_document_proprietaire` (`id_proprietaire`),
  ADD KEY `idx_document_locataire` (`id_locataire`),
  ADD KEY `idx_document_intervention` (`id_intervention`);

--
-- Index pour la table `echeance`
--
ALTER TABLE `echeance`
  ADD PRIMARY KEY (`id_echeance`),
  ADD UNIQUE KEY `uq_echeance_contrat_periode` (`id_contrat`,`periode_debut`,`periode_fin`),
  ADD KEY `idx_echeance_date` (`date_echeance`),
  ADD KEY `idx_echeance_statut` (`statut`);

--
-- Index pour la table `equipement`
--
ALTER TABLE `equipement`
  ADD PRIMARY KEY (`id_equipement`),
  ADD UNIQUE KEY `uq_equipement_nom` (`nom`);

--
-- Index pour la table `immeuble`
--
ALTER TABLE `immeuble`
  ADD PRIMARY KEY (`id_immeuble`),
  ADD KEY `idx_immeuble_agence` (`id_agence`),
  ADD KEY `fk_immeuble_adresse` (`id_adresse`);

--
-- Index pour la table `intervention`
--
ALTER TABLE `intervention`
  ADD PRIMARY KEY (`id_intervention`),
  ADD KEY `idx_intervention_agence` (`id_agence`),
  ADD KEY `idx_intervention_bien` (`id_bien`),
  ADD KEY `idx_intervention_locataire` (`id_locataire`),
  ADD KEY `idx_intervention_assigne` (`id_utilisateur_assigne`),
  ADD KEY `idx_intervention_priorite` (`priorite`),
  ADD KEY `idx_intervention_statut` (`statut`),
  ADD KEY `fk_intervention_createur` (`id_utilisateur_createur`);

--
-- Index pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD PRIMARY KEY (`id_audit`),
  ADD KEY `idx_audit_agence` (`id_agence`),
  ADD KEY `idx_audit_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_audit_entite` (`entite`,`id_entite`),
  ADD KEY `idx_audit_date` (`date_action`);

--
-- Index pour la table `locataire`
--
ALTER TABLE `locataire`
  ADD PRIMARY KEY (`id_locataire`),
  ADD KEY `idx_locataire_agence` (`id_agence`),
  ADD KEY `idx_locataire_email` (`email`),
  ADD KEY `idx_locataire_statut` (`statut`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `idx_notification_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_notification_statut` (`statut`),
  ADD KEY `idx_notification_date` (`date_envoi`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id_paiement`),
  ADD UNIQUE KEY `uq_paiement_reference` (`reference_transaction`),
  ADD KEY `idx_paiement_echeance` (`id_echeance`),
  ADD KEY `idx_paiement_contrat` (`id_contrat`),
  ADD KEY `idx_paiement_locataire` (`id_locataire`),
  ADD KEY `idx_paiement_date` (`date_paiement`),
  ADD KEY `idx_paiement_statut` (`statut`);

--
-- Index pour la table `parametre_agence`
--
ALTER TABLE `parametre_agence`
  ADD PRIMARY KEY (`id_parametre`),
  ADD UNIQUE KEY `uq_parametre_agence` (`id_agence`,`cle`);

--
-- Index pour la table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`id_permission`),
  ADD UNIQUE KEY `uq_permission_code` (`code`);

--
-- Index pour la table `photo_bien`
--
ALTER TABLE `photo_bien`
  ADD PRIMARY KEY (`id_photo`),
  ADD KEY `idx_photo_bien` (`id_bien`);

--
-- Index pour la table `proprietaire`
--
ALTER TABLE `proprietaire`
  ADD PRIMARY KEY (`id_proprietaire`),
  ADD KEY `idx_proprietaire_agence` (`id_agence`),
  ADD KEY `idx_proprietaire_email` (`email`);

--
-- Index pour la table `quittance`
--
ALTER TABLE `quittance`
  ADD PRIMARY KEY (`id_quittance`),
  ADD UNIQUE KEY `uq_quittance_paiement` (`id_paiement`),
  ADD UNIQUE KEY `uq_quittance_numero` (`numero`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `uq_role_nom` (`nom`);

--
-- Index pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id_role`,`id_permission`),
  ADD KEY `fk_rp_permission` (`id_permission`);

--
-- Index pour la table `session_utilisateur`
--
ALTER TABLE `session_utilisateur`
  ADD PRIMARY KEY (`id_session`),
  ADD UNIQUE KEY `uq_session_token` (`token_hash`),
  ADD KEY `idx_session_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `type_bien`
--
ALTER TABLE `type_bien`
  ADD PRIMARY KEY (`id_type_bien`),
  ADD UNIQUE KEY `uq_type_bien_nom` (`nom`);

--
-- Index pour la table `type_document`
--
ALTER TABLE `type_document`
  ADD PRIMARY KEY (`id_type_document`),
  ADD UNIQUE KEY `uq_type_document_nom` (`nom`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `uq_utilisateur_email` (`email`),
  ADD KEY `idx_utilisateur_agence` (`id_agence`),
  ADD KEY `idx_utilisateur_role` (`id_role`),
  ADD KEY `idx_utilisateur_statut` (`statut`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnement`
--
ALTER TABLE `abonnement`
  MODIFY `id_abonnement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `adresse`
--
ALTER TABLE `adresse`
  MODIFY `id_adresse` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `agence`
--
ALTER TABLE `agence`
  MODIFY `id_agence` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `agence_abonnement`
--
ALTER TABLE `agence_abonnement`
  MODIFY `id_agence_abonnement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bien`
--
ALTER TABLE `bien`
  MODIFY `id_bien` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaire_intervention`
--
ALTER TABLE `commentaire_intervention`
  MODIFY `id_commentaire` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contrat`
--
ALTER TABLE `contrat`
  MODIFY `id_contrat` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `document`
--
ALTER TABLE `document`
  MODIFY `id_document` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `echeance`
--
ALTER TABLE `echeance`
  MODIFY `id_echeance` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `equipement`
--
ALTER TABLE `equipement`
  MODIFY `id_equipement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `immeuble`
--
ALTER TABLE `immeuble`
  MODIFY `id_immeuble` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `intervention`
--
ALTER TABLE `intervention`
  MODIFY `id_intervention` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  MODIFY `id_audit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `locataire`
--
ALTER TABLE `locataire`
  MODIFY `id_locataire` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id_notification` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id_paiement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametre_agence`
--
ALTER TABLE `parametre_agence`
  MODIFY `id_parametre` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `permission`
--
ALTER TABLE `permission`
  MODIFY `id_permission` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `photo_bien`
--
ALTER TABLE `photo_bien`
  MODIFY `id_photo` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `proprietaire`
--
ALTER TABLE `proprietaire`
  MODIFY `id_proprietaire` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quittance`
--
ALTER TABLE `quittance`
  MODIFY `id_quittance` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id_role` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `session_utilisateur`
--
ALTER TABLE `session_utilisateur`
  MODIFY `id_session` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `type_bien`
--
ALTER TABLE `type_bien`
  MODIFY `id_type_bien` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `type_document`
--
ALTER TABLE `type_document`
  MODIFY `id_type_document` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `agence_abonnement`
--
ALTER TABLE `agence_abonnement`
  ADD CONSTRAINT `fk_aa_abonnement` FOREIGN KEY (`id_abonnement`) REFERENCES `abonnement` (`id_abonnement`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aa_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `bien`
--
ALTER TABLE `bien`
  ADD CONSTRAINT `fk_bien_adresse` FOREIGN KEY (`id_adresse`) REFERENCES `adresse` (`id_adresse`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_immeuble` FOREIGN KEY (`id_immeuble`) REFERENCES `immeuble` (`id_immeuble`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_type` FOREIGN KEY (`id_type_bien`) REFERENCES `type_bien` (`id_type_bien`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `bien_equipement`
--
ALTER TABLE `bien_equipement`
  ADD CONSTRAINT `fk_be_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_be_equipement` FOREIGN KEY (`id_equipement`) REFERENCES `equipement` (`id_equipement`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `bien_proprietaire`
--
ALTER TABLE `bien_proprietaire`
  ADD CONSTRAINT `fk_bp_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bp_proprietaire` FOREIGN KEY (`id_proprietaire`) REFERENCES `proprietaire` (`id_proprietaire`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `commentaire_intervention`
--
ALTER TABLE `commentaire_intervention`
  ADD CONSTRAINT `fk_commentaire_intervention` FOREIGN KEY (`id_intervention`) REFERENCES `intervention` (`id_intervention`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commentaire_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD CONSTRAINT `fk_contrat_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contrat_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `contrat_locataire`
--
ALTER TABLE `contrat_locataire`
  ADD CONSTRAINT `fk_cl_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cl_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_intervention` FOREIGN KEY (`id_intervention`) REFERENCES `intervention` (`id_intervention`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_proprietaire` FOREIGN KEY (`id_proprietaire`) REFERENCES `proprietaire` (`id_proprietaire`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_type` FOREIGN KEY (`id_type_document`) REFERENCES `type_document` (`id_type_document`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `echeance`
--
ALTER TABLE `echeance`
  ADD CONSTRAINT `fk_echeance_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `immeuble`
--
ALTER TABLE `immeuble`
  ADD CONSTRAINT `fk_immeuble_adresse` FOREIGN KEY (`id_adresse`) REFERENCES `adresse` (`id_adresse`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_immeuble_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `intervention`
--
ALTER TABLE `intervention`
  ADD CONSTRAINT `fk_intervention_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_intervention_assigne` FOREIGN KEY (`id_utilisateur_assigne`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_intervention_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_intervention_createur` FOREIGN KEY (`id_utilisateur_createur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_intervention_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD CONSTRAINT `fk_audit_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `locataire`
--
ALTER TABLE `locataire`
  ADD CONSTRAINT `fk_locataire_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `fk_paiement_contrat` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_paiement_echeance` FOREIGN KEY (`id_echeance`) REFERENCES `echeance` (`id_echeance`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_paiement_locataire` FOREIGN KEY (`id_locataire`) REFERENCES `locataire` (`id_locataire`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `parametre_agence`
--
ALTER TABLE `parametre_agence`
  ADD CONSTRAINT `fk_parametre_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `photo_bien`
--
ALTER TABLE `photo_bien`
  ADD CONSTRAINT `fk_photo_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `proprietaire`
--
ALTER TABLE `proprietaire`
  ADD CONSTRAINT `fk_proprietaire_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `quittance`
--
ALTER TABLE `quittance`
  ADD CONSTRAINT `fk_quittance_paiement` FOREIGN KEY (`id_paiement`) REFERENCES `paiement` (`id_paiement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`id_permission`) REFERENCES `permission` (`id_permission`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `session_utilisateur`
--
ALTER TABLE `session_utilisateur`
  ADD CONSTRAINT `fk_session_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD CONSTRAINT `fk_utilisateur_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utilisateur_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
