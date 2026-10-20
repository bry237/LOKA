-- LOKA — 2/3 : données de référence (role, permission, type_bien, type_document, equipement, abonnement)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

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
