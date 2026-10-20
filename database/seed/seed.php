<?php

declare(strict_types=1);

/**
 * Réinitialise les données métier de LOKA et recrée un jeu de données de démonstration
 * cohérent : 3 agences, 3 utilisateurs par rôle (21 comptes @loka.fr, même mot de passe),
 * biens, contrats, paiements, interventions et notifications associés.
 *
 * Conserve les tables de référence système (role, permission, type_bien, type_document,
 * equipement, abonnement, parametre_plateforme) : elles ne sont pas des données
 * utilisateur et d'autres parties de l'app (Stripe, catalogues) s'appuient sur leurs ids.
 *
 * Usage : php database/seed/seed.php
 */

require_once dirname(__DIR__, 2) . '/core/Database.php';

const SHARED_PASSWORD = 'Loka2026!';

$db = Database::connection();
$passwordHash = password_hash(SHARED_PASSWORD, PASSWORD_DEFAULT);

echo "=== LOKA — réinitialisation des données ===\n";

$db->exec('SET FOREIGN_KEY_CHECKS = 0');

$tablesToWipe = [
	'session_utilisateur', 'otp_code', 'journal_audit', 'password_reset',
	'commentaire_intervention', 'document', 'document_utilisateur', 'document_agence',
	'quittance', 'paiement', 'echeance', 'contrat_locataire', 'contrat',
	'bien_image', 'bien_equipement', 'bien_proprietaire', 'bien', 'immeuble', 'adresse',
	'intervention', 'notification', 'parametre_agence', 'agence_abonnement',
	'locataire', 'proprietaire', 'utilisateur', 'agence',
];
foreach ($tablesToWipe as $table) {
	$exists = $db->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
	if ($exists) {
		$db->exec("TRUNCATE TABLE `{$table}`");
		echo "  truncate {$table}\n";
	}
}

$db->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\n--- Référentiel conservé : role, permission, type_bien, type_document, equipement, abonnement ---\n";

$roleIds = [];
foreach ($db->query('SELECT id_role, nom FROM role') as $row) {
	$roleIds[$row['nom']] = (int) $row['id_role'];
}

$typeBienIds = [];
foreach ($db->query('SELECT id_type_bien, nom FROM type_bien') as $row) {
	$typeBienIds[$row['nom']] = (int) $row['id_type_bien'];
}

$abonnementIds = [];
foreach ($db->query("SELECT id_abonnement, nom FROM abonnement WHERE nom IN ('Gratuit', 'Pro')") as $row) {
	$abonnementIds[$row['nom']] = (int) $row['id_abonnement'];
}

// ---------------------------------------------------------------------------
// 1) Agences
// ---------------------------------------------------------------------------

echo "\n--- Agences ---\n";

$agences = [
	[
		'key' => 'horizon', 'nom' => 'Agence Horizon Immobilier', 'email' => 'contact.horizon@loka.fr',
		'telephone' => '0145670001', 'adresse' => '18 avenue de la République', 'ville' => 'Paris',
		'code_postal' => '75011', 'couleur_accent' => '#0F8B8D', 'abonnement' => 'Pro',
	],
	[
		'key' => 'atlantique', 'nom' => 'Agence Atlantique Gestion', 'email' => 'contact.atlantique@loka.fr',
		'telephone' => '0240670002', 'adresse' => '7 cours Franklin Roosevelt', 'ville' => 'Nantes',
		'code_postal' => '44000', 'couleur_accent' => '#1C6E8C', 'abonnement' => 'Gratuit',
	],
	[
		'key' => 'sud', 'nom' => 'Agence Lumière du Sud', 'email' => 'contact.sud@loka.fr',
		'telephone' => '0491670003', 'adresse' => '2 quai du Port', 'ville' => 'Marseille',
		'code_postal' => '13002', 'couleur_accent' => '#D97706', 'abonnement' => 'Pro',
	],
];

$agenceInsert = $db->prepare(
	'INSERT INTO agence (nom, email, telephone, indicatif_pays, adresse, ville, code_postal, pays, couleur_accent, statut)
	 VALUES (:nom, :email, :telephone, \'+33\', :adresse, :ville, :code_postal, \'France\', :couleur_accent, \'ACTIVE\')'
);
$agenceIds = [];
foreach ($agences as $a) {
	$agenceInsert->execute([
		'nom' => $a['nom'], 'email' => $a['email'], 'telephone' => $a['telephone'],
		'adresse' => $a['adresse'], 'ville' => $a['ville'], 'code_postal' => $a['code_postal'],
		'couleur_accent' => $a['couleur_accent'],
	]);
	$agenceIds[$a['key']] = (int) $db->lastInsertId();
	echo "  + {$a['nom']} (#{$agenceIds[$a['key']]})\n";
}

$abonnementInsert = $db->prepare(
	'INSERT INTO agence_abonnement (id_agence, id_abonnement, date_debut, statut) VALUES (:id_agence, :id_abonnement, :date_debut, \'ACTIVE\')'
);
foreach ($agences as $a) {
	$abonnementInsert->execute([
		'id_agence' => $agenceIds[$a['key']],
		'id_abonnement' => $abonnementIds[$a['abonnement']],
		'date_debut' => (new DateTimeImmutable('first day of this year'))->format('Y-m-d'),
	]);
}

// ---------------------------------------------------------------------------
// 2) Utilisateurs — 3 par rôle, tous @loka.fr, même mot de passe
// ---------------------------------------------------------------------------

echo "\n--- Utilisateurs (mot de passe commun : " . SHARED_PASSWORD . ") ---\n";

$userInsert = $db->prepare(
	'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, indicatif_pays, telephone_verifie, statut)
	 VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, \'+33\', 1, \'ACTIVE\')'
);

function createUser(PDO $db, PDOStatement $stmt, string $hash, ?int $idAgence, int $idRole, string $nom, string $prenom, string $email, string $telephone): int
{
	$stmt->execute([
		'id_agence' => $idAgence, 'id_role' => $idRole, 'nom' => $nom, 'prenom' => $prenom,
		'email' => $email, 'mot_de_passe' => $hash, 'telephone' => $telephone,
	]);
	$id = (int) $db->lastInsertId();
	echo "  + {$email} — {$prenom} {$nom}\n";
	return $id;
}

$users = []; // key => id_utilisateur

// Administrateur plateforme (3, pas d'agence)
$users['admin.claire'] = createUser($db, $userInsert, $passwordHash, null, $roleIds['Administrateur plateforme'], 'Fontaine', 'Claire', 'admin.claire@loka.fr', '0601000001');
$users['admin.julien'] = createUser($db, $userInsert, $passwordHash, null, $roleIds['Administrateur plateforme'], 'Moreau', 'Julien', 'admin.julien@loka.fr', '0601000002');
$users['admin.sophie'] = createUser($db, $userInsert, $passwordHash, null, $roleIds['Administrateur plateforme'], 'Lambert', 'Sophie', 'admin.sophie@loka.fr', '0601000003');

// Équipes d'agence (Administrateur agence, Gestionnaire immobilier, Comptable, Technicien) — un de chaque par agence
$equipes = [
	'horizon' => [
		'agence' => ['Girard', 'Nicolas', 'agence.nicolas@loka.fr', '0602000001'],
		'gestion' => ['Bonnet', 'Léa', 'gestion.lea@loka.fr', '0603000001'],
		'compta' => ['Perrin', 'Antoine', 'compta.antoine@loka.fr', '0604000001'],
		'technicien' => ['Haddad', 'Karim', 'technicien.karim@loka.fr', '0605000001'],
	],
	'atlantique' => [
		'agence' => ['Dubois', 'Camille', 'agence.camille@loka.fr', '0602000002'],
		'gestion' => ['Faure', 'Hugo', 'gestion.hugo@loka.fr', '0603000002'],
		'compta' => ['Masson', 'Chloé', 'compta.chloe@loka.fr', '0604000002'],
		'technicien' => ['Picard', 'Yanis', 'technicien.yanis@loka.fr', '0605000002'],
	],
	'sud' => [
		'agence' => ['Lefevre', 'Thomas', 'agence.thomas@loka.fr', '0602000003'],
		'gestion' => ['Roche', 'Manon', 'gestion.manon@loka.fr', '0603000003'],
		'compta' => ['Robin', 'Mathieu', 'compta.mathieu@loka.fr', '0604000003'],
		'technicien' => ['Caron', 'Romain', 'technicien.romain@loka.fr', '0605000003'],
	],
];
$roleForKey = [
	'agence' => 'Administrateur agence', 'gestion' => 'Gestionnaire immobilier',
	'compta' => 'Comptable', 'technicien' => 'Technicien',
];
foreach ($equipes as $agenceKey => $roster) {
	foreach ($roster as $roleKey => [$nom, $prenom, $email, $tel]) {
		$users["{$agenceKey}.{$roleKey}"] = createUser(
			$db, $userInsert, $passwordHash, $agenceIds[$agenceKey], $roleIds[$roleForKey[$roleKey]], $nom, $prenom, $email, $tel
		);
	}
}

// Propriétaires (3, un par agence)
$proprietaires = [
	'horizon' => ['Noel', 'Isabelle', 'proprio.isabelle@loka.fr', '0606000001'],
	'atlantique' => ['Vidal', 'Marc', 'proprio.marc@loka.fr', '0606000002'],
	'sud' => ['Blanchard', 'Nathalie', 'proprio.nathalie@loka.fr', '0606000003'],
];
foreach ($proprietaires as $agenceKey => [$nom, $prenom, $email, $tel]) {
	$users["proprio.{$agenceKey}"] = createUser($db, $userInsert, $passwordHash, null, $roleIds['Proprietaire'], $nom, $prenom, $email, $tel);
}

// Locataires (3, un par agence)
$locatairesUsers = [
	'horizon' => ['Petit', 'Emma', 'locataire.emma@loka.fr', '0607000001'],
	'atlantique' => ['Bernard', 'Lucas', 'locataire.lucas@loka.fr', '0607000002'],
	'sud' => ['Michel', 'Sarah', 'locataire.sarah@loka.fr', '0607000003'],
];
foreach ($locatairesUsers as $agenceKey => [$nom, $prenom, $email, $tel]) {
	$users["locataire.{$agenceKey}"] = createUser($db, $userInsert, $passwordHash, null, $roleIds['Locataire'], $nom, $prenom, $email, $tel);
}

echo "\n  Total : " . count($users) . " utilisateurs créés (attendu : 21)\n";

// ---------------------------------------------------------------------------
// 3) Propriétaires / Locataires (profils métier, liés à leur agence gestionnaire)
// ---------------------------------------------------------------------------

echo "\n--- Profils propriétaire / locataire ---\n";

$proprietaireInsert = $db->prepare(
	'INSERT INTO proprietaire (id_utilisateur, id_agence, nom, prenom, email, telephone, indicatif_pays, adresse, code_postal, ville, pays, date_naissance, projet)
	 VALUES (:id_utilisateur, :id_agence, :nom, :prenom, :email, :telephone, \'+33\', :adresse, :code_postal, :ville, \'France\', :date_naissance, \'rent\')'
);
$proprietaireIds = [];
$proprioAdresses = [
	'horizon' => ['22 rue de Rivoli', '75004', 'Paris', '1978-03-12'],
	'atlantique' => ['9 rue Crébillon', '44000', 'Nantes', '1982-07-25'],
	'sud' => ['14 rue Saint-Ferréol', '13001', 'Marseille', '1975-11-03'],
];
foreach ($proprietaires as $agenceKey => [$nom, $prenom, $email, $tel]) {
	[$adr, $cp, $ville, $naissance] = $proprioAdresses[$agenceKey];
	$proprietaireInsert->execute([
		'id_utilisateur' => $users["proprio.{$agenceKey}"], 'id_agence' => $agenceIds[$agenceKey],
		'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'telephone' => $tel,
		'adresse' => $adr, 'code_postal' => $cp, 'ville' => $ville, 'date_naissance' => $naissance,
	]);
	$proprietaireIds[$agenceKey] = (int) $db->lastInsertId();
	echo "  + proprietaire {$prenom} {$nom} (#{$proprietaireIds[$agenceKey]}) — {$agences[array_search($agenceKey, array_column($agences, 'key'), true)]['nom']}\n";
}

$locataireInsert = $db->prepare(
	'INSERT INTO locataire (id_utilisateur, id_agence, nom, prenom, email, telephone, indicatif_pays, adresse, code_postal, ville, pays, date_naissance, profession, revenu_mensuel, revenu_mensuel_fourchette, statut, situation_familiale, nombre_occupants)
	 VALUES (:id_utilisateur, :id_agence, :nom, :prenom, :email, :telephone, \'+33\', :adresse, :code_postal, :ville, \'France\', :date_naissance, :profession, :revenu, :fourchette, \'ACTIVE\', :situation, :occupants)'
);
$locataireIds = [];
$locataireDetails = [
	'horizon' => ['8 rue des Archives', '75004', 'Paris', '1996-07-08', 'Infirmière', 2300.00, '2 000 € à 2 999 €', 'CELIBATAIRE', 1],
	'atlantique' => ['3 cours Franklin Roosevelt', '44000', 'Nantes', '1993-02-17', 'Développeur', 2600.00, '2 000 € à 2 999 €', 'PACSE', 2],
	'sud' => ['Avenue du Prado', '13008', 'Marseille', '1990-05-21', 'Architecte', 3100.00, '3 000 € à 3 999 €', 'MARIE', 2],
];
foreach ($locatairesUsers as $agenceKey => [$nom, $prenom, $email, $tel]) {
	[$adr, $cp, $ville, $naissance, $profession, $revenu, $fourchette, $situation, $occupants] = $locataireDetails[$agenceKey];
	$locataireInsert->execute([
		'id_utilisateur' => $users["locataire.{$agenceKey}"], 'id_agence' => $agenceIds[$agenceKey],
		'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'telephone' => $tel,
		'adresse' => $adr, 'code_postal' => $cp, 'ville' => $ville, 'date_naissance' => $naissance,
		'profession' => $profession, 'revenu' => $revenu, 'fourchette' => $fourchette,
		'situation' => $situation, 'occupants' => $occupants,
	]);
	$locataireIds[$agenceKey] = (int) $db->lastInsertId();
	echo "  + locataire {$prenom} {$nom} (#{$locataireIds[$agenceKey]})\n";
}

// ---------------------------------------------------------------------------
// 4) Immeubles / Adresses / Biens
// ---------------------------------------------------------------------------

echo "\n--- Immeubles et biens ---\n";

$adresseInsert = $db->prepare('INSERT INTO adresse (ligne1, code_postal, ville, pays) VALUES (:ligne1, :code_postal, :ville, \'France\')');
function createAdresse(PDO $db, PDOStatement $stmt, string $ligne1, string $cp, string $ville): int
{
	$stmt->execute(['ligne1' => $ligne1, 'code_postal' => $cp, 'ville' => $ville]);
	return (int) $db->lastInsertId();
}

$immeubleInsert = $db->prepare('INSERT INTO immeuble (id_agence, id_adresse, nom, nombre_etages) VALUES (:id_agence, :id_adresse, :nom, :etages)');

$bienInsert = $db->prepare(
	'INSERT INTO bien (id_agence, id_responsable, id_type_bien, id_adresse, id_immeuble, reference, titre, surface, nombre_pieces, etage, loyer, charges, caution, statut, description, classe_energetique, exposition, meuble, date_disponibilite)
	 VALUES (:id_agence, :id_responsable, :id_type_bien, :id_adresse, :id_immeuble, :reference, :titre, :surface, :pieces, :etage, :loyer, :charges, :caution, :statut, :description, :classe, :exposition, :meuble, :disponibilite)'
);

$bienSpecs = [
	'horizon' => [
		'immeuble' => ['Résidence Archives', '8 rue des Archives', '75004', 'Paris', 4],
		'biens' => [
			['ref' => 'HOR-APT-001', 'titre' => 'Appartement T3 Marais', 'type' => 'Appartement', 'surface' => 62, 'pieces' => 3, 'etage' => 2, 'loyer' => 1450, 'charges' => 90, 'caution' => 1450, 'statut' => 'OCCUPIED', 'classe' => 'C', 'exposition' => 'SUD', 'inImmeuble' => true, 'adresse' => ['8 rue des Archives', '75004', 'Paris']],
			['ref' => 'HOR-APT-002', 'titre' => 'Studio Bastille', 'type' => 'Appartement', 'surface' => 24, 'pieces' => 1, 'etage' => 4, 'loyer' => 850, 'charges' => 60, 'caution' => 850, 'statut' => 'AVAILABLE', 'classe' => 'D', 'exposition' => 'EST', 'inImmeuble' => false, 'adresse' => ['12 rue de la Roquette', '75011', 'Paris']],
		],
	],
	'atlantique' => [
		'immeuble' => ['Résidence Graslin', '3 cours Franklin Roosevelt', '44000', 'Nantes', 5],
		'biens' => [
			['ref' => 'ATL-APT-001', 'titre' => 'Appartement T2 Centre-ville', 'type' => 'Appartement', 'surface' => 45, 'pieces' => 2, 'etage' => 3, 'loyer' => 650, 'charges' => 50, 'caution' => 650, 'statut' => 'OCCUPIED', 'classe' => 'C', 'exposition' => 'OUEST', 'inImmeuble' => true, 'adresse' => ['3 cours Franklin Roosevelt', '44000', 'Nantes']],
			['ref' => 'ATL-MAI-001', 'titre' => 'Maison avec jardin Chantenay', 'type' => 'Maison', 'surface' => 95, 'pieces' => 5, 'etage' => null, 'loyer' => 1100, 'charges' => 70, 'caution' => 1100, 'statut' => 'AVAILABLE', 'classe' => 'D', 'exposition' => 'SUD', 'inImmeuble' => false, 'adresse' => ['5 rue de la Tour', '44100', 'Nantes']],
		],
	],
	'sud' => [
		'immeuble' => ['Résidence Corniche Kennedy', 'Avenue John Fitzgerald Kennedy', '13007', 'Marseille', 6],
		'biens' => [
			['ref' => 'SUD-APT-001', 'titre' => 'Appartement vue mer Corniche', 'type' => 'Appartement', 'surface' => 70, 'pieces' => 3, 'etage' => 5, 'loyer' => 1250, 'charges' => 95, 'caution' => 1250, 'statut' => 'OCCUPIED', 'classe' => 'B', 'exposition' => 'SUD', 'inImmeuble' => true, 'adresse' => ['Avenue John Fitzgerald Kennedy', '13007', 'Marseille']],
			['ref' => 'SUD-COM-001', 'titre' => 'Local commercial Vieux-Port', 'type' => 'Commercial', 'surface' => 55, 'pieces' => 1, 'etage' => 0, 'loyer' => 1600, 'charges' => 150, 'caution' => 3200, 'statut' => 'AVAILABLE', 'classe' => 'E', 'exposition' => 'NORD', 'inImmeuble' => false, 'adresse' => ['2 quai du Port', '13002', 'Marseille']],
		],
	],
];

$bienIds = []; // reference => id_bien
foreach ($bienSpecs as $agenceKey => $spec) {
	[$immNom, $immAdr, $immCp, $immVille, $immEtages] = $spec['immeuble'];
	$immAdresseId = createAdresse($db, $adresseInsert, $immAdr, $immCp, $immVille);
	$immeubleInsert->execute(['id_agence' => $agenceIds[$agenceKey], 'id_adresse' => $immAdresseId, 'nom' => $immNom, 'etages' => $immEtages]);
	$immeubleId = (int) $db->lastInsertId();
	echo "  immeuble {$immNom} (#{$immeubleId})\n";

	foreach ($spec['biens'] as $b) {
		[$bAdr, $bCp, $bVille] = $b['adresse'];
		$bienAdresseId = $b['inImmeuble'] ? $immAdresseId : createAdresse($db, $adresseInsert, $bAdr, $bCp, $bVille);
		$bienInsert->execute([
			'id_agence' => $agenceIds[$agenceKey],
			'id_responsable' => $users["{$agenceKey}.gestion"],
			'id_type_bien' => $typeBienIds[$b['type']],
			'id_adresse' => $bienAdresseId,
			'id_immeuble' => $b['inImmeuble'] ? $immeubleId : null,
			'reference' => $b['ref'], 'titre' => $b['titre'], 'surface' => $b['surface'],
			'pieces' => $b['pieces'], 'etage' => $b['etage'], 'loyer' => $b['loyer'],
			'charges' => $b['charges'], 'caution' => $b['caution'], 'statut' => $b['statut'],
			'description' => $b['titre'] . ', proche commerces et transports.',
			'classe' => $b['classe'], 'exposition' => $b['exposition'], 'meuble' => 'non',
			'disponibilite' => $b['statut'] === 'AVAILABLE' ? (new DateTimeImmutable())->format('Y-m-d') : null,
		]);
		$bienIds[$b['ref']] = (int) $db->lastInsertId();
		echo "    bien {$b['ref']} — {$b['titre']} (#{$bienIds[$b['ref']]}, {$b['statut']})\n";
	}
}

// bien_proprietaire : chaque propriétaire possède les 2 biens de son agence, à 100%
$bpInsert = $db->prepare('INSERT INTO bien_proprietaire (id_bien, id_proprietaire, quote_part, date_debut) VALUES (:id_bien, :id_proprietaire, 100.00, :date_debut)');
foreach ($bienSpecs as $agenceKey => $spec) {
	foreach ($spec['biens'] as $b) {
		$bpInsert->execute([
			'id_bien' => $bienIds[$b['ref']], 'id_proprietaire' => $proprietaireIds[$agenceKey],
			'date_debut' => '2024-01-01',
		]);
	}
}

// ---------------------------------------------------------------------------
// 5) Contrats / échéances / paiements / quittances (sur le bien OCCUPIED de chaque agence)
// ---------------------------------------------------------------------------

echo "\n--- Contrats, échéances, paiements, quittances ---\n";

$contratInsert = $db->prepare(
	'INSERT INTO contrat (id_agence, id_bien, numero, date_debut, date_fin, loyer, charges, depot_garantie, frequence_paiement, jour_echeance, statut, type_contrat, date_prochaine_revision)
	 VALUES (:id_agence, :id_bien, :numero, :date_debut, :date_fin, :loyer, :charges, :depot, \'MONTHLY\', 5, \'ACTIVE\', \'HABITATION_VIDE\', :revision)'
);
$clInsert = $db->prepare('INSERT INTO contrat_locataire (id_contrat, id_locataire, titulaire_principal, date_entree) VALUES (:id_contrat, :id_locataire, 1, :date_entree)');
$echeanceInsert = $db->prepare(
	'INSERT INTO echeance (id_contrat, periode_debut, periode_fin, montant_loyer, montant_charges, date_echeance, statut) VALUES (:id_contrat, :periode_debut, :periode_fin, :loyer, :charges, :date_echeance, :statut)'
);
$paiementInsert = $db->prepare(
	'INSERT INTO paiement (id_echeance, id_contrat, id_locataire, montant, date_paiement, mode_paiement, reference_transaction, statut)
	 VALUES (:id_echeance, :id_contrat, :id_locataire, :montant, :date_paiement, \'BANK_TRANSFER\', :reference, \'PAID\')'
);
$quittanceInsert = $db->prepare(
	'INSERT INTO quittance (id_paiement, numero, periode_debut, periode_fin) VALUES (:id_paiement, :numero, :periode_debut, :periode_fin)'
);

$occupiedByAgence = ['horizon' => 'HOR-APT-001', 'atlantique' => 'ATL-APT-001', 'sud' => 'SUD-APT-001'];
$numeroByAgence = ['horizon' => 'LOKA-HOR-2026-001', 'atlantique' => 'LOKA-ATL-2026-001', 'sud' => 'LOKA-SUD-2026-001'];

$today = new DateTimeImmutable();
$previousMonthStart = $today->modify('first day of last month');
$previousMonthEnd = $today->modify('last day of last month');
$currentMonthStart = $today->modify('first day of this month');
$currentMonthEnd = $today->modify('last day of this month');

$contratIds = [];
foreach ($occupiedByAgence as $agenceKey => $ref) {
	$bien = $bienSpecs[$agenceKey]['biens'][array_search($ref, array_column($bienSpecs[$agenceKey]['biens'], 'ref'), true)];
	$contratInsert->execute([
		'id_agence' => $agenceIds[$agenceKey], 'id_bien' => $bienIds[$ref], 'numero' => $numeroByAgence[$agenceKey],
		'date_debut' => '2025-09-01', 'date_fin' => '2028-08-31',
		'loyer' => $bien['loyer'], 'charges' => $bien['charges'], 'depot' => $bien['caution'],
		'revision' => $today->modify('+1 year')->format('Y-m-d'),
	]);
	$contratId = (int) $db->lastInsertId();
	$contratIds[$agenceKey] = $contratId;
	echo "  contrat {$numeroByAgence[$agenceKey]} (#{$contratId})\n";

	$clInsert->execute(['id_contrat' => $contratId, 'id_locataire' => $locataireIds[$agenceKey], 'date_entree' => '2025-09-01']);

	$montantTotal = $bien['loyer'] + $bien['charges'];

	// Échéance du mois précédent — payée
	$echeanceInsert->execute([
		'id_contrat' => $contratId, 'periode_debut' => $previousMonthStart->format('Y-m-d'),
		'periode_fin' => $previousMonthEnd->format('Y-m-d'), 'loyer' => $bien['loyer'], 'charges' => $bien['charges'],
		'date_echeance' => $previousMonthStart->format('Y-m-') . '05', 'statut' => 'PAID',
	]);
	$echeancePayeeId = (int) $db->lastInsertId();

	$paiementInsert->execute([
		'id_echeance' => $echeancePayeeId, 'id_contrat' => $contratId, 'id_locataire' => $locataireIds[$agenceKey],
		'montant' => $montantTotal, 'date_paiement' => $previousMonthStart->format('Y-m-') . '03 09:15:00',
		'reference' => 'PAY-' . strtoupper($agenceKey) . '-' . $previousMonthStart->format('Ym'),
	]);
	$paiementId = (int) $db->lastInsertId();

	$quittanceInsert->execute([
		'id_paiement' => $paiementId, 'numero' => 'QUIT-' . strtoupper($agenceKey) . '-' . $previousMonthStart->format('Ym'),
		'periode_debut' => $previousMonthStart->format('Y-m-d'), 'periode_fin' => $previousMonthEnd->format('Y-m-d'),
	]);

	// Échéance du mois en cours — en attente
	$echeanceInsert->execute([
		'id_contrat' => $contratId, 'periode_debut' => $currentMonthStart->format('Y-m-d'),
		'periode_fin' => $currentMonthEnd->format('Y-m-d'), 'loyer' => $bien['loyer'], 'charges' => $bien['charges'],
		'date_echeance' => $currentMonthStart->format('Y-m-') . '05', 'statut' => 'PENDING',
	]);

	echo "    échéances + paiement + quittance pour {$agenceKey}\n";
}

// ---------------------------------------------------------------------------
// 6) Interventions + commentaire
// ---------------------------------------------------------------------------

echo "\n--- Interventions ---\n";

$interventionInsert = $db->prepare(
	'INSERT INTO intervention (id_agence, id_bien, id_locataire, id_utilisateur_createur, id_utilisateur_assigne, titre, description, priorite, statut, cout_estime, cout_final, compte_rendu)
	 VALUES (:id_agence, :id_bien, :id_locataire, :id_createur, :id_assigne, :titre, :description, :priorite, :statut, :cout_estime, :cout_final, :compte_rendu)'
);
$commentaireInsert = $db->prepare('INSERT INTO commentaire_intervention (id_intervention, id_utilisateur, contenu) VALUES (:id_intervention, :id_utilisateur, :contenu)');

$interventionSpecs = [
	'horizon' => [
		['bien' => 'HOR-APT-001', 'locataire' => true, 'titre' => 'Fuite robinet cuisine', 'desc' => 'Le locataire signale une fuite sous l\'évier de la cuisine.', 'priorite' => 'MEDIUM', 'statut' => 'OPEN', 'cout_estime' => 90, 'cout_final' => null],
		['bien' => 'HOR-APT-002', 'locataire' => false, 'titre' => 'Chauffage en panne', 'desc' => 'Radiateur électrique hors service dans le studio vacant.', 'priorite' => 'HIGH', 'statut' => 'RESOLVED', 'cout_estime' => 150, 'cout_final' => 180],
	],
	'atlantique' => [
		['bien' => 'ATL-APT-001', 'locataire' => true, 'titre' => 'Volet roulant bloqué', 'desc' => 'Le volet du salon ne remonte plus.', 'priorite' => 'MEDIUM', 'statut' => 'IN_PROGRESS', 'cout_estime' => 70, 'cout_final' => null],
		['bien' => 'ATL-MAI-001', 'locataire' => false, 'titre' => 'Révision chaudière', 'desc' => 'Entretien annuel obligatoire de la chaudière avant remise en location.', 'priorite' => 'LOW', 'statut' => 'CLOSED', 'cout_estime' => 120, 'cout_final' => 120],
	],
	'sud' => [
		['bien' => 'SUD-APT-001', 'locataire' => true, 'titre' => 'Climatisation bruyante', 'desc' => 'Bruit anormal de l\'unité extérieure de climatisation.', 'priorite' => 'URGENT', 'statut' => 'ASSIGNED', 'cout_estime' => 200, 'cout_final' => null],
		['bien' => 'SUD-COM-001', 'locataire' => false, 'titre' => 'Peinture façade', 'desc' => 'Rafraîchissement de la façade du local commercial avant relocation.', 'priorite' => 'LOW', 'statut' => 'OPEN', 'cout_estime' => 600, 'cout_final' => null],
	],
];

$interventionIds = [];
foreach ($interventionSpecs as $agenceKey => $items) {
	foreach ($items as $i => $spec) {
		$interventionInsert->execute([
			'id_agence' => $agenceIds[$agenceKey], 'id_bien' => $bienIds[$spec['bien']],
			'id_locataire' => $spec['locataire'] ? $locataireIds[$agenceKey] : null,
			'id_createur' => $users["{$agenceKey}.gestion"], 'id_assigne' => $users["{$agenceKey}.technicien"],
			'titre' => $spec['titre'], 'description' => $spec['desc'], 'priorite' => $spec['priorite'],
			'statut' => $spec['statut'], 'cout_estime' => $spec['cout_estime'], 'cout_final' => $spec['cout_final'],
			'compte_rendu' => in_array($spec['statut'], ['RESOLVED', 'CLOSED'], true) ? 'Intervention terminée, bien remis en état.' : null,
		]);
		$interventionId = (int) $db->lastInsertId();
		$interventionIds["{$agenceKey}.{$i}"] = $interventionId;
		echo "  + {$spec['titre']} ({$agenceKey}, {$spec['statut']})\n";

		if ($i === 0) {
			$commentaireInsert->execute([
				'id_intervention' => $interventionId, 'id_utilisateur' => $users["{$agenceKey}.technicien"],
				'contenu' => 'Intervention planifiée, passage prévu sous 48h.',
			]);
		}
	}
}

// ---------------------------------------------------------------------------
// 7) Notifications (contenu réaliste pour chaque équipe + locataires)
// ---------------------------------------------------------------------------

echo "\n--- Notifications ---\n";

$notifInsert = $db->prepare(
	'INSERT INTO notification (id_utilisateur, titre, message, type, statut, date_envoi) VALUES (:id_utilisateur, :titre, :message, \'INTERNAL\', :statut, :date_envoi)'
);
function notify(PDOStatement $stmt, int $idUtilisateur, string $titre, string $message, bool $lu = false): void
{
	$stmt->execute([
		'id_utilisateur' => $idUtilisateur, 'titre' => $titre, 'message' => $message,
		'statut' => $lu ? 'READ' : 'SENT', 'date_envoi' => (new DateTimeImmutable('-' . random_int(1, 48) . ' hours'))->format('Y-m-d H:i:s'),
	]);
}

foreach (['horizon', 'atlantique', 'sud'] as $agenceKey) {
	$agenceNom = $agences[array_search($agenceKey, array_column($agences, 'key'), true)]['nom'];

	notify($notifInsert, $users["{$agenceKey}.agence"], 'Nouvelle intervention', "Une intervention a été ouverte sur un bien de {$agenceNom}.");
	notify($notifInsert, $users["{$agenceKey}.agence"], 'Échéance à venir', 'Un loyer arrive à échéance le 05 du mois.', true);

	notify($notifInsert, $users["{$agenceKey}.gestion"], 'Bien disponible', 'Un bien est actuellement disponible à la location.');

	notify($notifInsert, $users["{$agenceKey}.compta"], 'Quittance générée', 'Une nouvelle quittance de loyer a été générée.', true);
	notify($notifInsert, $users["{$agenceKey}.compta"], 'Paiement reçu', 'Un paiement de loyer a été enregistré.');

	notify($notifInsert, $users["{$agenceKey}.technicien"], 'Intervention assignée', 'Une intervention vous a été assignée.');

	notify($notifInsert, $users["proprio.{$agenceKey}"], 'Loyer encaissé', 'Le loyer du mois dernier a bien été encaissé.', true);

	notify($notifInsert, $users["locataire.{$agenceKey}"], 'Quittance disponible', 'Votre quittance de loyer du mois dernier est disponible.', true);
	notify($notifInsert, $users["locataire.{$agenceKey}"], 'Échéance à venir', 'Votre prochain loyer est à régler avant le 05.');
}

foreach (['admin.claire', 'admin.julien', 'admin.sophie'] as $key) {
	notify($notifInsert, $users[$key], 'Bienvenue sur LOKA', 'Votre compte administrateur plateforme est actif.', true);
}

echo "\n=== Terminé : " . count($users) . " utilisateurs, " . count($agenceIds) . " agences, " . count($bienIds) . " biens, " . count($contratIds) . " contrats, " . array_sum(array_map('count', $interventionSpecs)) . " interventions. ===\n";
echo "Mot de passe partagé par tous les comptes : " . SHARED_PASSWORD . "\n";
