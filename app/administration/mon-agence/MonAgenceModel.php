<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class MonAgenceModel
{
	public static function agency(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_agence, nom, email, telephone, adresse, ville, code_postal, pays, statut, created_at
			 FROM agence
			 WHERE id_agence = :id_agence AND deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function subscription(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT ab.nom, ab.prix_mensuel, ab.limite_utilisateurs, ab.limite_biens, aa.date_debut, aa.date_fin
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.id_agence = :id_agence AND aa.statut = \'ACTIVE\'
			 ORDER BY aa.date_debut DESC
			 LIMIT 1'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function kpis(int $idAgence): array
	{
		$db = Database::connection();
		$interventionsEnCours = self::openInterventionsCount($db, $idAgence);
		return [
			self::countKpi($db, 'utilisateur', $idAgence, 'Équipe', 'users', 'teal'),
			self::countKpi($db, 'bien', $idAgence, 'Biens', 'home', 'blue'),
			self::activeContractsKpi($db, $idAgence),
			self::countKpi($db, 'locataire', $idAgence, 'Locataires', 'key', 'amber'),
			self::monthlyRevenueKpi($db, $idAgence),
			[
				'label' => 'Interventions en cours',
				'value' => number_format($interventionsEnCours, 0, ',', ' '),
				'icon' => 'tool',
				'tone' => $interventionsEnCours > 0 ? 'red' : 'teal',
			],
		];
	}

	/** Répartition du parc immobilier par statut, scopée à l'agence. */
	public static function propertyPortfolio(int $idAgence): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			'SELECT statut, COUNT(*) AS total FROM bien
			 WHERE id_agence = :id_agence AND deleted_at IS NULL
			 GROUP BY statut'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$rows = $stmt->fetchAll();

		$byStatut = [];
		$total = 0;
		foreach ($rows as $row) {
			$byStatut[$row['statut']] = (int) $row['total'];
			$total += (int) $row['total'];
		}

		$definitions = [
			'AVAILABLE' => ['label' => 'Disponibles', 'tone' => 'green'],
			'OCCUPIED' => ['label' => 'Occupés', 'tone' => 'teal'],
			'MAINTENANCE' => ['label' => 'En maintenance', 'tone' => 'amber'],
			'CREATED' => ['label' => 'À publier', 'tone' => 'blue'],
			'ARCHIVED' => ['label' => 'Archivés', 'tone' => 'gray'],
		];

		$segments = [];
		foreach ($definitions as $statut => $meta) {
			$count = $byStatut[$statut] ?? 0;
			if ($count === 0) {
				continue;
			}
			$segments[] = [
				'statut' => $statut,
				'label' => $meta['label'],
				'tone' => $meta['tone'],
				'count' => $count,
				'pct' => $total > 0 ? round($count / $total * 100) : 0,
			];
		}

		return ['total' => $total, 'segments' => $segments];
	}

	/** Contrats actifs dont l'échéance approche (30 jours), triés par urgence. */
	public static function contractsToWatch(int $idAgence, int $limit = 5): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT c.numero, c.date_fin, b.titre AS bien_titre,
				(SELECT CONCAT(l.prenom, ' ', l.nom)
				 FROM contrat_locataire cl
				 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
				 WHERE cl.id_contrat = c.id_contrat
				 ORDER BY cl.titulaire_principal DESC LIMIT 1) AS locataire_nom
			 FROM contrat c
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE c.id_agence = :id_agence AND c.statut = 'ACTIVE' AND c.date_fin IS NOT NULL
				AND c.date_fin <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
			 ORDER BY c.date_fin ASC
			 LIMIT :limit"
		);
		$stmt->bindValue(':id_agence', $idAgence, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		$today = new DateTimeImmutable('today');
		return array_map(static function (array $row) use ($today): array {
			$dateFin = new DateTimeImmutable($row['date_fin']);
			$daysLeft = (int) $today->diff($dateFin)->format('%r%a');
			return [
				'numero' => $row['numero'],
				'bien' => $row['bien_titre'],
				'locataire' => $row['locataire_nom'] ?? '—',
				'date_fin' => $dateFin->format('d/m/Y'),
				'days_left' => $daysLeft,
				'tone' => $daysLeft <= 7 ? 'red' : 'amber',
			];
		}, $stmt->fetchAll());
	}

	/** Éléments nécessitant une action de l'agence aujourd'hui (agrégat multi-sources). */
	public static function todayTasks(int $idAgence): array
	{
		$db = Database::connection();
		$tasks = [];

		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 WHERE c.id_agence = :id_agence
				AND (e.statut = 'LATE' OR (e.statut IN ('PENDING', 'PARTIAL') AND e.date_echeance < CURDATE()))"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		if (($count = (int) $stmt->fetchColumn()) > 0) {
			$tasks[] = [
				'icon' => 'alert',
				'tone' => 'red',
				'title' => $count > 1 ? "{$count} échéances de loyer en retard" : '1 échéance de loyer en retard',
				'sub' => 'Paiements attendus non reçus',
				'count' => $count,
			];
		}

		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM intervention
			 WHERE id_agence = :id_agence AND priorite IN ('HIGH', 'URGENT')
				AND statut NOT IN ('RESOLVED', 'CLOSED', 'CANCELLED')"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		if (($count = (int) $stmt->fetchColumn()) > 0) {
			$tasks[] = [
				'icon' => 'tool',
				'tone' => 'amber',
				'title' => $count > 1 ? "{$count} interventions prioritaires" : '1 intervention prioritaire',
				'sub' => 'Priorité haute ou urgente, non résolues',
				'count' => $count,
			];
		}

		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM contrat
			 WHERE id_agence = :id_agence AND statut = 'ACTIVE' AND date_fin IS NOT NULL
				AND date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		if (($count = (int) $stmt->fetchColumn()) > 0) {
			$tasks[] = [
				'icon' => 'calendar',
				'tone' => 'blue',
				'title' => $count > 1 ? "{$count} contrats à renouveler sous 7 jours" : '1 contrat à renouveler sous 7 jours',
				'sub' => 'Décision de renouvellement attendue',
				'count' => $count,
			];
		}

		return $tasks;
	}

	/* --------------------------------------------------------------
	 * Équipe (membres de l'agence) et affectation de responsables aux biens
	 * -------------------------------------------------------------- */

	private const TEAM_ROLE_IDS = [2, 3, 4, 7]; // Administrateur agence, Gestionnaire immobilier, Comptable, Technicien

	/** Rôles proposables lors de l'invitation d'un membre d'équipe. */
	public static function teamRoles(): array
	{
		$db = Database::connection();
		$placeholders = implode(',', self::TEAM_ROLE_IDS);
		return $db->query("SELECT id_role, nom FROM role WHERE id_role IN ({$placeholders}) ORDER BY id_role")->fetchAll();
	}

	public static function team(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.statut, u.created_at, r.nom AS role_nom
			 FROM utilisateur u
			 INNER JOIN role r ON r.id_role = u.id_role
			 WHERE u.id_agence = :id_agence AND u.deleted_at IS NULL
			 ORDER BY u.created_at ASC'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/**
	 * @param array{nom: string, prenom: string, email: string, telephone: string, id_role: int} $data
	 * @return array{id_utilisateur: int, mot_de_passe_temporaire: string}
	 */
	public static function inviteMember(int $idAgence, array $data): array
	{
		$tempPassword = bin2hex(random_bytes(5));
		$stmt = Database::connection()->prepare(
			'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut)
			 VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, \'ACTIVE\')'
		);
		$stmt->execute([
			'id_agence' => $idAgence,
			'id_role' => $data['id_role'],
			'nom' => $data['nom'],
			'prenom' => $data['prenom'],
			'email' => $data['email'],
			'mot_de_passe' => password_hash($tempPassword, PASSWORD_DEFAULT),
			'telephone' => $data['telephone'],
		]);

		return [
			'id_utilisateur' => (int) Database::connection()->lastInsertId(),
			'mot_de_passe_temporaire' => $tempPassword,
		];
	}

	public static function emailExists(string $email, ?int $excludeIdUtilisateur = null): bool
	{
		$sql = 'SELECT 1 FROM utilisateur WHERE LOWER(email) = LOWER(:email)';
		$params = ['email' => $email];
		if ($excludeIdUtilisateur !== null) {
			$sql .= ' AND id_utilisateur != :exclude_id';
			$params['exclude_id'] = $excludeIdUtilisateur;
		}
		$stmt = Database::connection()->prepare($sql . ' LIMIT 1');
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	/** Un membre de l'équipe, scopé à l'agence (jamais d'accès inter-agence par id deviné). */
	public static function findMember(int $idAgence, int $idUtilisateur): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.statut, u.id_role,
					u.telephone_verifie, u.created_at, u.updated_at, r.nom AS role_nom
			 FROM utilisateur u
			 INNER JOIN role r ON r.id_role = u.id_role
			 WHERE u.id_utilisateur = :id_utilisateur AND u.id_agence = :id_agence AND u.deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id_utilisateur' => $idUtilisateur, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/**
	 * @param array{nom: string, prenom: string, email: string, telephone: string, id_role: int} $data
	 * @return bool false si le membre n'appartient pas à l'agence (protection contre une modification croisée).
	 */
	public static function updateMember(int $idAgence, int $idUtilisateur, array $data): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, id_role = :id_role
			 WHERE id_utilisateur = :id_utilisateur AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute([
			'nom' => $data['nom'],
			'prenom' => $data['prenom'],
			'email' => $data['email'],
			'telephone' => $data['telephone'],
			'id_role' => $data['id_role'],
			'id_utilisateur' => $idUtilisateur,
			'id_agence' => $idAgence,
		]);
		return $stmt->rowCount() > 0;
	}

	/**
	 * Suppression douce (comme `agence`/`bien`) : l'historique (audit, factures...) reste cohérent,
	 * le membre disparaît simplement de l'équipe et ne peut plus se connecter.
	 *
	 * @return bool false si le membre n'appartient pas à l'agence.
	 */
	public static function deleteMember(int $idAgence, int $idUtilisateur): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE utilisateur SET deleted_at = NOW(), statut = 'INACTIVE'
			 WHERE id_utilisateur = :id_utilisateur AND id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_utilisateur' => $idUtilisateur, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	/** Biens de l'agence avec leur éventuel responsable, pour l'écran d'affectation. */
	public static function biensForAssignment(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT b.id_bien, b.reference, b.titre, b.statut, b.id_responsable,
					CONCAT(u.prenom, ' ', u.nom) AS responsable_nom
			 FROM bien b
			 LEFT JOIN utilisateur u ON u.id_utilisateur = b.id_responsable
			 WHERE b.id_agence = :id_agence AND b.deleted_at IS NULL
			 ORDER BY b.reference ASC"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/**
	 * @return bool false si le bien n'appartient pas à l'agence (protection contre une affectation croisée).
	 */
	public static function assignResponsable(int $idAgence, int $idBien, ?int $idResponsable): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE bien SET id_responsable = :id_responsable WHERE id_bien = :id_bien AND id_agence = :id_agence'
		);
		$stmt->execute(['id_responsable' => $idResponsable, 'id_bien' => $idBien, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	/* --------------------------------------------------------------
	 * Biens (parc immobilier), scopés à l'agence.
	 * -------------------------------------------------------------- */

	public static function bienTypes(): array
	{
		return Database::connection()->query('SELECT id_type_bien, nom FROM type_bien ORDER BY nom')->fetchAll();
	}

	public static function bienTypeExists(int $idTypeBien): bool
	{
		$stmt = Database::connection()->prepare('SELECT 1 FROM type_bien WHERE id_type_bien = :id');
		$stmt->execute(['id' => $idTypeBien]);
		return (bool) $stmt->fetchColumn();
	}

	/** Adresses déjà utilisées par l'agence (via un immeuble ou un bien), pour le sélecteur du formulaire. */
	public static function bienAddresses(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT DISTINCT a.id_adresse, a.ligne1, a.ligne2, a.code_postal, a.ville
			 FROM adresse a
			 LEFT JOIN immeuble i ON i.id_adresse = a.id_adresse AND i.id_agence = :id_agence_immeuble
			 LEFT JOIN bien b ON b.id_adresse = a.id_adresse AND b.id_agence = :id_agence_bien
			 WHERE i.id_immeuble IS NOT NULL OR b.id_bien IS NOT NULL
			 ORDER BY a.ville, a.ligne1'
		);
		$stmt->execute(['id_agence_immeuble' => $idAgence, 'id_agence_bien' => $idAgence]);
		return $stmt->fetchAll();
	}

	public static function bienBuildings(int $idAgence): array
	{
		$stmt = Database::connection()->prepare('SELECT id_immeuble, nom FROM immeuble WHERE id_agence = :id_agence ORDER BY nom');
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/** @param array{ligne1: string, ligne2: ?string, code_postal: ?string, ville: string, region: ?string, pays: string} $data */
	public static function createAddress(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO adresse (ligne1, ligne2, code_postal, ville, region, pays)
			 VALUES (:ligne1, :ligne2, :code_postal, :ville, :region, :pays)'
		);
		$stmt->execute($data);
		return (int) Database::connection()->lastInsertId();
	}

	public static function referenceExists(string $reference, ?int $excludeIdBien = null): bool
	{
		$sql = 'SELECT 1 FROM bien WHERE reference = :reference';
		$params = ['reference' => $reference];
		if ($excludeIdBien !== null) {
			$sql .= ' AND id_bien != :exclude_id';
			$params['exclude_id'] = $excludeIdBien;
		}
		$stmt = Database::connection()->prepare($sql . ' LIMIT 1');
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	/** @param array{search?: string, statut?: string, id_type_bien?: string} $filters */
	public static function biens(int $idAgence, array $filters = []): array
	{
		$where = ['b.id_agence = :id_agence'];
		$params = ['id_agence' => $idAgence];

		if (!empty($filters['search'])) {
			$where[] = '(b.reference LIKE :search1 OR b.titre LIKE :search2 OR a.ville LIKE :search3)';
			$term = '%' . $filters['search'] . '%';
			$params['search1'] = $term;
			$params['search2'] = $term;
			$params['search3'] = $term;
		}
		if (!empty($filters['statut'])) {
			$where[] = 'b.statut = :statut';
			$params['statut'] = $filters['statut'];
		} else {
			$where[] = "b.statut != 'ARCHIVED'";
		}
		if (!empty($filters['id_type_bien'])) {
			$where[] = 'b.id_type_bien = :id_type_bien';
			$params['id_type_bien'] = (int) $filters['id_type_bien'];
		}

		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre, b.surface, b.nombre_pieces, b.loyer, b.statut,
					t.nom AS type_bien, a.ville
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse a ON a.id_adresse = b.id_adresse
			 WHERE ' . implode(' AND ', $where) . '
			 ORDER BY b.updated_at DESC, b.id_bien DESC'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	/** KPI de la liste des biens, dérivés de la répartition déjà calculée pour le dashboard. */
	public static function bienStats(int $idAgence): array
	{
		$portfolio = self::propertyPortfolio($idAgence);
		$byStatut = [];
		foreach ($portfolio['segments'] as $segment) {
			$byStatut[$segment['statut']] = $segment['count'];
		}
		return [
			'total' => $portfolio['total'],
			'disponibles' => $byStatut['AVAILABLE'] ?? 0,
			'occupes' => $byStatut['OCCUPIED'] ?? 0,
			'maintenance' => $byStatut['MAINTENANCE'] ?? 0,
		];
	}

	public static function bienFind(int $idAgence, int $idBien): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT b.*, t.nom AS type_bien, a.ligne1, a.ligne2, a.code_postal, a.ville, i.nom AS immeuble_nom
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse a ON a.id_adresse = b.id_adresse
			 LEFT JOIN immeuble i ON i.id_immeuble = b.id_immeuble
			 WHERE b.id_bien = :id AND b.id_agence = :id_agence AND b.deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id' => $idBien, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function createBien(int $idAgence, array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO bien (id_agence, id_type_bien, id_adresse, id_immeuble, reference, titre, surface,
				nombre_pieces, etage, loyer, charges, caution, statut, description, meuble,
				date_disponibilite, dpe_classe, ges_classe)
			 VALUES (:id_agence, :id_type_bien, :id_adresse, :id_immeuble, :reference, :titre, :surface,
				:nombre_pieces, :etage, :loyer, :charges, :caution, :statut, :description, :meuble,
				:date_disponibilite, :dpe_classe, :ges_classe)'
		);
		$stmt->execute(self::bienParams($idAgence, $data));
		return (int) Database::connection()->lastInsertId();
	}

	public static function updateBien(int $idAgence, int $idBien, array $data): bool
	{
		$params = self::bienParams($idAgence, $data);
		$params['id_bien'] = $idBien;
		$stmt = Database::connection()->prepare(
			'UPDATE bien SET id_type_bien = :id_type_bien, id_adresse = :id_adresse, id_immeuble = :id_immeuble,
				reference = :reference, titre = :titre, surface = :surface, nombre_pieces = :nombre_pieces,
				etage = :etage, loyer = :loyer, charges = :charges, caution = :caution, statut = :statut,
				description = :description, meuble = :meuble, date_disponibilite = :date_disponibilite,
				dpe_classe = :dpe_classe, ges_classe = :ges_classe, updated_at = NOW()
			 WHERE id_bien = :id_bien AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public static function archiveBien(int $idAgence, int $idBien): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE bien SET statut = 'ARCHIVED', deleted_at = NOW()
			 WHERE id_bien = :id AND id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id' => $idBien, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	private static function bienParams(int $idAgence, array $data): array
	{
		return [
			'id_agence' => $idAgence,
			'id_type_bien' => (int) $data['id_type_bien'],
			'id_adresse' => (int) $data['id_adresse'],
			'id_immeuble' => $data['id_immeuble'] !== null ? (int) $data['id_immeuble'] : null,
			'reference' => $data['reference'],
			'titre' => $data['titre'],
			'surface' => (float) $data['surface'],
			'nombre_pieces' => (int) $data['nombre_pieces'],
			'etage' => $data['etage'] !== null ? (int) $data['etage'] : null,
			'loyer' => (float) $data['loyer'],
			'charges' => (float) $data['charges'],
			'caution' => (float) $data['caution'],
			'statut' => $data['statut'],
			'description' => $data['description'],
			'meuble' => $data['meuble'],
			'date_disponibilite' => $data['date_disponibilite'],
			'dpe_classe' => $data['dpe_classe'],
			'ges_classe' => $data['ges_classe'],
		];
	}

	public static function bienEquipments(): array
	{
		return Database::connection()->query('SELECT id_equipement, nom FROM equipement ORDER BY nom')->fetchAll();
	}

	/** @return int[] */
	public static function bienEquipmentAssignments(int $idBien): array
	{
		$stmt = Database::connection()->prepare('SELECT id_equipement FROM bien_equipement WHERE id_bien = :id_bien');
		$stmt->execute(['id_bien' => $idBien]);
		return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	/** @param int[] $equipmentIds */
	public static function updateBienEquipmentAssignments(int $idBien, array $equipmentIds): void
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$db->prepare('DELETE FROM bien_equipement WHERE id_bien = :id_bien')->execute(['id_bien' => $idBien]);
			$insert = $db->prepare('INSERT INTO bien_equipement (id_bien, id_equipement, quantite) VALUES (:id_bien, :id_equipement, 1)');
			foreach ($equipmentIds as $equipmentId) {
				$insert->execute(['id_bien' => $idBien, 'id_equipement' => $equipmentId]);
			}
			$db->commit();
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}

	public static function bienPhotos(int $idBien): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_photo, nom_fichier, chemin_stockage, est_principale, ordre_affichage
			 FROM photo_bien WHERE id_bien = :id_bien ORDER BY est_principale DESC, ordre_affichage, id_photo'
		);
		$stmt->execute(['id_bien' => $idBien]);
		return $stmt->fetchAll();
	}

	public static function addBienPhoto(int $idBien, string $filename, string $storagePath, bool $main): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$order = $db->prepare('SELECT COALESCE(MAX(ordre_affichage), -1) + 1 FROM photo_bien WHERE id_bien = :id_bien');
			$order->execute(['id_bien' => $idBien]);
			$displayOrder = (int) $order->fetchColumn();
			if ($main) {
				$db->prepare('UPDATE photo_bien SET est_principale = 0 WHERE id_bien = :id_bien')->execute(['id_bien' => $idBien]);
			}
			$stmt = $db->prepare(
				'INSERT INTO photo_bien (id_bien, nom_fichier, chemin_stockage, est_principale, ordre_affichage)
				 VALUES (:id_bien, :nom_fichier, :chemin_stockage, :est_principale, :ordre_affichage)'
			);
			$stmt->execute([
				'id_bien' => $idBien,
				'nom_fichier' => $filename,
				'chemin_stockage' => $storagePath,
				'est_principale' => $main ? 1 : 0,
				'ordre_affichage' => $displayOrder,
			]);
			$db->commit();
			return (int) $db->lastInsertId();
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}

	public static function setBienMainPhoto(int $idBien, int $idPhoto): void
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$db->prepare('UPDATE photo_bien SET est_principale = 0 WHERE id_bien = :id_bien')->execute(['id_bien' => $idBien]);
			$db->prepare('UPDATE photo_bien SET est_principale = 1 WHERE id_photo = :id_photo AND id_bien = :id_bien')
				->execute(['id_photo' => $idPhoto, 'id_bien' => $idBien]);
			$db->commit();
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}

	/** @return string|null Chemin de stockage du fichier supprimé, ou null si la photo n'existe pas sur ce bien. */
	public static function deleteBienPhoto(int $idBien, int $idPhoto): ?string
	{
		$db = Database::connection();
		$stmt = $db->prepare('SELECT chemin_stockage, est_principale FROM photo_bien WHERE id_photo = :id_photo AND id_bien = :id_bien');
		$stmt->execute(['id_photo' => $idPhoto, 'id_bien' => $idBien]);
		$photo = $stmt->fetch();
		if (!$photo) {
			return null;
		}
		$db->prepare('DELETE FROM photo_bien WHERE id_photo = :id_photo')->execute(['id_photo' => $idPhoto]);
		if ((int) $photo['est_principale'] === 1) {
			$db->prepare(
				'UPDATE photo_bien SET est_principale = 1
				 WHERE id_photo = (SELECT id_photo FROM (SELECT id_photo FROM photo_bien WHERE id_bien = :id_bien ORDER BY ordre_affichage, id_photo LIMIT 1) next_photo)'
			)->execute(['id_bien' => $idBien]);
		}
		return $photo['chemin_stockage'];
	}

	/** Propriétaires actuellement rattachés au bien (lecture seule — géré par le futur module Propriétaires). */
	public static function bienOwners(int $idBien): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT p.id_proprietaire, p.nom, p.prenom, bp.quote_part, bp.date_debut
			 FROM bien_proprietaire bp
			 INNER JOIN proprietaire p ON p.id_proprietaire = bp.id_proprietaire
			 WHERE bp.id_bien = :id_bien AND bp.date_fin IS NULL AND p.deleted_at IS NULL
			 ORDER BY p.nom'
		);
		$stmt->execute(['id_bien' => $idBien]);
		return $stmt->fetchAll();
	}

	/** Propriétaires de l'agence pas encore rattachés à ce bien, pour le sélecteur de rattachement. */
	public static function bienAvailableOwners(int $idAgence, int $idBien): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT p.id_proprietaire, p.nom, p.prenom
			 FROM proprietaire p
			 WHERE p.id_agence = :id_agence AND p.deleted_at IS NULL
			 AND NOT EXISTS (
				SELECT 1 FROM bien_proprietaire bp
				WHERE bp.id_bien = :id_bien AND bp.id_proprietaire = p.id_proprietaire AND bp.date_fin IS NULL
			 )
			 ORDER BY p.nom, p.prenom'
		);
		$stmt->execute(['id_agence' => $idAgence, 'id_bien' => $idBien]);
		return $stmt->fetchAll();
	}

	/* --------------------------------------------------------------
	 * Locataires, scopés à l'agence.
	 * -------------------------------------------------------------- */

	/** @param array{search?: string, statut?: string} $filters */
	public static function locataires(int $idAgence, array $filters = []): array
	{
		$where = ['l.id_agence = :id_agence', 'l.deleted_at IS NULL'];
		$params = ['id_agence' => $idAgence];

		if (!empty($filters['search'])) {
			$where[] = '(l.nom LIKE :search1 OR l.prenom LIKE :search2 OR l.email LIKE :search3)';
			$term = '%' . $filters['search'] . '%';
			$params['search1'] = $term;
			$params['search2'] = $term;
			$params['search3'] = $term;
		}
		if (!empty($filters['statut'])) {
			$where[] = 'l.statut = :statut';
			$params['statut'] = $filters['statut'];
		}

		$stmt = Database::connection()->prepare(
			"SELECT l.id_locataire, l.nom, l.prenom, l.email, l.telephone, l.statut,
					(SELECT COUNT(*) FROM contrat_locataire cl
					 INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
					 WHERE cl.id_locataire = l.id_locataire AND c.deleted_at IS NULL AND c.statut = 'ACTIVE') AS contrats_actifs
			 FROM locataire l
			 WHERE " . implode(' AND ', $where) . '
			 ORDER BY l.nom ASC, l.prenom ASC'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function locataireStats(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT COUNT(*) AS total,
					SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) AS actifs,
					SUM(CASE WHEN statut = 'INACTIVE' THEN 1 ELSE 0 END) AS inactifs
			 FROM locataire WHERE id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch() ?: [];
		return [
			'total' => (int) ($row['total'] ?? 0),
			'actifs' => (int) ($row['actifs'] ?? 0),
			'inactifs' => (int) ($row['inactifs'] ?? 0),
		];
	}

	public static function locataireFind(int $idAgence, int $idLocataire): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT * FROM locataire WHERE id_locataire = :id AND id_agence = :id_agence AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $idLocataire, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function createLocataire(int $idAgence, array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO locataire (
				id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays,
				date_naissance, profession, revenu_mensuel, revenu_mensuel_fourchette, statut,
				numero_identite, type_identite, situation_familiale, nombre_occupants,
				garant_nom, garant_telephone, garant_email, notes, created_at, updated_at
			) VALUES (
				:id_agence, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays,
				:date_naissance, :profession, :revenu_mensuel, :revenu_mensuel_fourchette, :statut,
				:numero_identite, :type_identite, :situation_familiale, :nombre_occupants,
				:garant_nom, :garant_telephone, :garant_email, :notes, NOW(), NOW()
			)'
		);
		$stmt->execute(self::locataireParams($idAgence, $data));
		return (int) Database::connection()->lastInsertId();
	}

	public static function updateLocataire(int $idAgence, int $idLocataire, array $data): bool
	{
		$params = self::locataireParams($idAgence, $data);
		$params['id_locataire'] = $idLocataire;
		$stmt = Database::connection()->prepare(
			'UPDATE locataire SET
				nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
				adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays,
				date_naissance = :date_naissance, profession = :profession,
				revenu_mensuel = :revenu_mensuel, revenu_mensuel_fourchette = :revenu_mensuel_fourchette,
				statut = :statut, numero_identite = :numero_identite, type_identite = :type_identite,
				situation_familiale = :situation_familiale, nombre_occupants = :nombre_occupants,
				garant_nom = :garant_nom, garant_telephone = :garant_telephone,
				garant_email = :garant_email, notes = :notes, updated_at = NOW()
			 WHERE id_agence = :id_agence AND id_locataire = :id_locataire AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public static function archiveLocataire(int $idAgence, int $idLocataire): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE locataire SET deleted_at = NOW(), statut = 'ARCHIVED'
			 WHERE id_agence = :id_agence AND id_locataire = :id_locataire AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $idAgence, 'id_locataire' => $idLocataire]);
		return $stmt->rowCount() > 0;
	}

	/** Contrats du locataire, en lecture seule (gestion complète dans le futur module Contrats). */
	public static function locataireContracts(int $idAgence, int $idLocataire): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT c.id_contrat, c.numero, c.statut, c.date_debut, c.date_fin, b.titre AS bien_titre
			 FROM contrat c
			 INNER JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE cl.id_locataire = :id_locataire AND c.id_agence = :id_agence AND c.deleted_at IS NULL
			 ORDER BY c.date_debut DESC'
		);
		$stmt->execute(['id_locataire' => $idLocataire, 'id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	private static function locataireParams(int $idAgence, array $data): array
	{
		return [
			'id_agence' => $idAgence,
			'nom' => $data['nom'],
			'prenom' => $data['prenom'],
			'email' => $data['email'],
			'telephone' => $data['telephone'],
			'adresse' => $data['adresse'],
			'code_postal' => $data['code_postal'],
			'ville' => $data['ville'],
			'pays' => $data['pays'],
			'date_naissance' => $data['date_naissance'],
			'profession' => $data['profession'],
			'revenu_mensuel' => $data['revenu_mensuel'],
			'revenu_mensuel_fourchette' => $data['revenu_mensuel_fourchette'],
			'statut' => $data['statut'],
			'numero_identite' => $data['numero_identite'],
			'type_identite' => $data['type_identite'],
			'situation_familiale' => $data['situation_familiale'],
			'nombre_occupants' => $data['nombre_occupants'],
			'garant_nom' => $data['garant_nom'],
			'garant_telephone' => $data['garant_telephone'],
			'garant_email' => $data['garant_email'],
			'notes' => $data['notes'],
		];
	}

	/* --------------------------------------------------------------
	 * Propriétaires, scopés à l'agence.
	 * -------------------------------------------------------------- */

	/** @param array{search?: string} $filters */
	public static function proprietaires(int $idAgence, array $filters = []): array
	{
		$where = ['p.id_agence = :id_agence', 'p.deleted_at IS NULL'];
		$params = ['id_agence' => $idAgence];
		if (!empty($filters['search'])) {
			$where[] = '(p.nom LIKE :search1 OR p.prenom LIKE :search2 OR p.email LIKE :search3)';
			$term = '%' . $filters['search'] . '%';
			$params['search1'] = $term;
			$params['search2'] = $term;
			$params['search3'] = $term;
		}
		$stmt = Database::connection()->prepare(
			'SELECT p.id_proprietaire, p.nom, p.prenom, p.email, p.telephone, p.ville,
					COUNT(DISTINCT bp.id_bien) AS biens_count
			 FROM proprietaire p
			 LEFT JOIN bien_proprietaire bp ON bp.id_proprietaire = p.id_proprietaire AND bp.date_fin IS NULL
			 WHERE ' . implode(' AND ', $where) . '
			 GROUP BY p.id_proprietaire
			 ORDER BY p.nom, p.prenom'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function proprietaireStats(int $idAgence): array
	{
		$db = Database::connection();
		$stmt = $db->prepare('SELECT COUNT(*) FROM proprietaire WHERE id_agence = :id_agence AND deleted_at IS NULL');
		$stmt->execute(['id_agence' => $idAgence]);
		$total = (int) $stmt->fetchColumn();

		$stmt = $db->prepare(
			'SELECT COUNT(DISTINCT p.id_proprietaire)
			 FROM proprietaire p
			 INNER JOIN bien_proprietaire bp ON bp.id_proprietaire = p.id_proprietaire AND bp.date_fin IS NULL
			 WHERE p.id_agence = :id_agence AND p.deleted_at IS NULL'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$avecBien = (int) $stmt->fetchColumn();

		return ['total' => $total, 'avec_bien' => $avecBien, 'sans_bien' => $total - $avecBien];
	}

	public static function proprietaireFind(int $idAgence, int $idProprietaire): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT * FROM proprietaire WHERE id_proprietaire = :id AND id_agence = :id_agence AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $idProprietaire, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function createProprietaire(int $idAgence, array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO proprietaire (id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays, date_naissance, created_at, updated_at)
			 VALUES (:id_agence, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays, :date_naissance, NOW(), NOW())'
		);
		$stmt->execute(self::proprietaireParams($idAgence, $data));
		return (int) Database::connection()->lastInsertId();
	}

	public static function updateProprietaire(int $idAgence, int $idProprietaire, array $data): bool
	{
		$params = self::proprietaireParams($idAgence, $data);
		$params['id_proprietaire'] = $idProprietaire;
		$stmt = Database::connection()->prepare(
			'UPDATE proprietaire SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
				adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays,
				date_naissance = :date_naissance, updated_at = NOW()
			 WHERE id_proprietaire = :id_proprietaire AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public static function archiveProprietaire(int $idAgence, int $idProprietaire): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE proprietaire SET deleted_at = NOW()
			 WHERE id_proprietaire = :id AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute(['id' => $idProprietaire, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	private static function proprietaireParams(int $idAgence, array $data): array
	{
		return [
			'id_agence' => $idAgence,
			'nom' => $data['nom'],
			'prenom' => $data['prenom'],
			'email' => $data['email'],
			'telephone' => $data['telephone'],
			'adresse' => $data['adresse'],
			'code_postal' => $data['code_postal'],
			'ville' => $data['ville'],
			'pays' => $data['pays'],
			'date_naissance' => $data['date_naissance'],
		];
	}

	/** Biens actuellement rattachés au propriétaire (quote-part active). */
	public static function proprietaireProperties(int $idAgence, int $idProprietaire): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre, b.statut, b.surface, b.loyer, bp.quote_part, bp.date_debut
			 FROM bien_proprietaire bp
			 INNER JOIN bien b ON b.id_bien = bp.id_bien AND b.id_agence = :id_agence
			 WHERE bp.id_proprietaire = :id_proprietaire AND bp.date_fin IS NULL AND b.deleted_at IS NULL
			 ORDER BY b.titre'
		);
		$stmt->execute(['id_agence' => $idAgence, 'id_proprietaire' => $idProprietaire]);
		return $stmt->fetchAll();
	}

	/** Biens de l'agence pas encore rattachés à ce propriétaire, pour le sélecteur de rattachement. */
	public static function proprietaireAvailableProperties(int $idAgence, int $idProprietaire): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre
			 FROM bien b
			 WHERE b.id_agence = :id_agence AND b.deleted_at IS NULL
			 AND NOT EXISTS (
				SELECT 1 FROM bien_proprietaire bp
				WHERE bp.id_bien = b.id_bien AND bp.id_proprietaire = :id_proprietaire AND bp.date_fin IS NULL
			 )
			 ORDER BY b.titre'
		);
		$stmt->execute(['id_agence' => $idAgence, 'id_proprietaire' => $idProprietaire]);
		return $stmt->fetchAll();
	}

	/** Somme des quotes-parts actives déjà attribuées sur un bien, hors un propriétaire donné. */
	public static function bienQuotePartTotal(int $idBien, int $excludeIdProprietaire): float
	{
		$stmt = Database::connection()->prepare(
			'SELECT COALESCE(SUM(quote_part), 0) FROM bien_proprietaire
			 WHERE id_bien = :id_bien AND date_fin IS NULL AND id_proprietaire <> :id_proprietaire'
		);
		$stmt->execute(['id_bien' => $idBien, 'id_proprietaire' => $excludeIdProprietaire]);
		return (float) $stmt->fetchColumn();
	}

	public static function attachProprietaireProperty(int $idProprietaire, int $idBien, float $quotePart, string $dateDebut): void
	{
		$db = Database::connection();
		$existing = $db->prepare('SELECT date_fin FROM bien_proprietaire WHERE id_bien = :id_bien AND id_proprietaire = :id_proprietaire');
		$existing->execute(['id_bien' => $idBien, 'id_proprietaire' => $idProprietaire]);
		$existingDateFin = $existing->fetchColumn();
		if ($existingDateFin === false) {
			$stmt = $db->prepare(
				'INSERT INTO bien_proprietaire (id_bien, id_proprietaire, quote_part, date_debut)
				 VALUES (:id_bien, :id_proprietaire, :quote_part, :date_debut)'
			);
		} else {
			$stmt = $db->prepare(
				'UPDATE bien_proprietaire SET quote_part = :quote_part, date_debut = :date_debut, date_fin = NULL
				 WHERE id_bien = :id_bien AND id_proprietaire = :id_proprietaire'
			);
		}
		$stmt->execute(['id_bien' => $idBien, 'id_proprietaire' => $idProprietaire, 'quote_part' => $quotePart, 'date_debut' => $dateDebut]);
	}

	public static function detachProprietaireProperty(int $idAgence, int $idProprietaire, int $idBien): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE bien_proprietaire bp INNER JOIN bien b ON b.id_bien = bp.id_bien
			 SET bp.date_fin = CURRENT_DATE
			 WHERE bp.id_bien = :id_bien AND bp.id_proprietaire = :id_proprietaire
			 AND bp.date_fin IS NULL AND b.id_agence = :id_agence'
		);
		$stmt->execute(['id_bien' => $idBien, 'id_proprietaire' => $idProprietaire, 'id_agence' => $idAgence]);
	}

	/* --------------------------------------------------------------
	 * Contrats, scopés à l'agence.
	 * -------------------------------------------------------------- */

	/** @param array{search?: string, statut?: string, type_contrat?: string} $filters */
	public static function contrats(int $idAgence, array $filters = []): array
	{
		$where = ['c.id_agence = :id_agence', 'c.deleted_at IS NULL'];
		$params = ['id_agence' => $idAgence];

		if (!empty($filters['search'])) {
			$where[] = '(c.numero LIKE :search1 OR b.titre LIKE :search2 OR b.reference LIKE :search3)';
			$term = '%' . $filters['search'] . '%';
			$params['search1'] = $term;
			$params['search2'] = $term;
			$params['search3'] = $term;
		}
		if (!empty($filters['statut'])) {
			$where[] = 'c.statut = :statut';
			$params['statut'] = $filters['statut'];
		}
		if (!empty($filters['type_contrat'])) {
			$where[] = 'c.type_contrat = :type_contrat';
			$params['type_contrat'] = $filters['type_contrat'];
		}

		$stmt = Database::connection()->prepare(
			"SELECT c.id_contrat, c.numero, c.type_contrat, c.date_debut, c.date_fin, c.loyer, c.statut,
					b.titre AS bien_titre, b.reference AS bien_reference,
					(SELECT CONCAT(l.prenom, ' ', l.nom) FROM contrat_locataire cl
					 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
					 WHERE cl.id_contrat = c.id_contrat ORDER BY cl.titulaire_principal DESC LIMIT 1) AS locataire_principal
			 FROM contrat c
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE " . implode(' AND ', $where) . '
			 ORDER BY c.created_at DESC'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function contratStats(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) AS actifs,
				SUM(CASE WHEN statut = 'DRAFT' THEN 1 ELSE 0 END) AS brouillons,
				SUM(CASE WHEN statut = 'EXPIRED' THEN 1 ELSE 0 END) AS expires
			 FROM contrat WHERE id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch() ?: [];
		return [
			'total' => (int) ($row['total'] ?? 0),
			'actifs' => (int) ($row['actifs'] ?? 0),
			'brouillons' => (int) ($row['brouillons'] ?? 0),
			'expires' => (int) ($row['expires'] ?? 0),
		];
	}

	public static function contratFind(int $idAgence, int $idContrat): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT c.*, b.titre AS bien_titre, b.reference AS bien_reference
			 FROM contrat c
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE c.id_contrat = :id AND c.id_agence = :id_agence AND c.deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id' => $idContrat, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function numeroContratExists(string $numero, ?int $excludeIdContrat = null): bool
	{
		$sql = 'SELECT 1 FROM contrat WHERE numero = :numero';
		$params = ['numero' => $numero];
		if ($excludeIdContrat !== null) {
			$sql .= ' AND id_contrat != :exclude_id';
			$params['exclude_id'] = $excludeIdContrat;
		}
		$stmt = Database::connection()->prepare($sql . ' LIMIT 1');
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	public static function createContrat(int $idAgence, array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO contrat (
				id_agence, id_bien, numero, type_contrat, date_debut, date_fin,
				loyer, charges, depot_garantie, frequence_paiement, jour_echeance, statut
			) VALUES (
				:id_agence, :id_bien, :numero, :type_contrat, :date_debut, :date_fin,
				:loyer, :charges, :depot_garantie, :frequence_paiement, :jour_echeance, :statut
			)'
		);
		$stmt->execute(self::contratParams($idAgence, $data));
		return (int) Database::connection()->lastInsertId();
	}

	public static function updateContrat(int $idAgence, int $idContrat, array $data): bool
	{
		$params = self::contratParams($idAgence, $data);
		$params['id_contrat'] = $idContrat;
		$stmt = Database::connection()->prepare(
			'UPDATE contrat SET id_bien = :id_bien, numero = :numero, type_contrat = :type_contrat,
				date_debut = :date_debut, date_fin = :date_fin, loyer = :loyer, charges = :charges,
				depot_garantie = :depot_garantie, frequence_paiement = :frequence_paiement,
				jour_echeance = :jour_echeance, statut = :statut, updated_at = NOW()
			 WHERE id_contrat = :id_contrat AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	private static function contratParams(int $idAgence, array $data): array
	{
		return [
			'id_agence' => $idAgence,
			'id_bien' => (int) $data['id_bien'],
			'numero' => $data['numero'],
			'type_contrat' => $data['type_contrat'],
			'date_debut' => $data['date_debut'],
			'date_fin' => $data['date_fin'],
			'loyer' => (float) $data['loyer'],
			'charges' => (float) $data['charges'],
			'depot_garantie' => (float) $data['depot_garantie'],
			'frequence_paiement' => $data['frequence_paiement'],
			'jour_echeance' => (int) $data['jour_echeance'],
			'statut' => $data['statut'],
		];
	}

	public static function updateContratStatus(int $idAgence, int $idContrat, string $statut): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE contrat SET statut = :statut, updated_at = NOW()
			 WHERE id_contrat = :id AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute(['statut' => $statut, 'id' => $idContrat, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	public static function terminateContrat(int $idAgence, int $idContrat, string $motif): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE contrat SET statut = 'TERMINATED', motif_resiliation = :motif, date_resiliation = CURDATE(), updated_at = NOW()
			 WHERE id_contrat = :id AND id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['motif' => $motif, 'id' => $idContrat, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	public static function archiveContrat(int $idAgence, int $idContrat): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE contrat SET statut = 'ARCHIVED', deleted_at = NOW()
			 WHERE id_contrat = :id AND id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id' => $idContrat, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	/** Locataires actuellement rattachés au contrat. */
	public static function contratTenants(int $idContrat): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT l.id_locataire, l.nom, l.prenom, cl.titulaire_principal, cl.date_entree, cl.date_sortie
			 FROM contrat_locataire cl
			 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
			 WHERE cl.id_contrat = :id_contrat AND l.deleted_at IS NULL
			 ORDER BY cl.titulaire_principal DESC, l.nom"
		);
		$stmt->execute(['id_contrat' => $idContrat]);
		return $stmt->fetchAll();
	}

	/** Locataires de l'agence pas encore rattachés à ce contrat, pour le sélecteur. */
	public static function contratAvailableTenants(int $idAgence, int $idContrat): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT l.id_locataire, l.nom, l.prenom
			 FROM locataire l
			 WHERE l.id_agence = :id_agence AND l.deleted_at IS NULL
			 AND NOT EXISTS (SELECT 1 FROM contrat_locataire cl WHERE cl.id_contrat = :id_contrat AND cl.id_locataire = l.id_locataire)
			 ORDER BY l.nom, l.prenom'
		);
		$stmt->execute(['id_agence' => $idAgence, 'id_contrat' => $idContrat]);
		return $stmt->fetchAll();
	}

	public static function attachContratTenant(int $idContrat, int $idLocataire, bool $titulairePrincipal, ?string $dateEntree): void
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO contrat_locataire (id_contrat, id_locataire, titulaire_principal, date_entree)
			 VALUES (:id_contrat, :id_locataire, :titulaire_principal, :date_entree)'
		);
		$stmt->execute([
			'id_contrat' => $idContrat,
			'id_locataire' => $idLocataire,
			'titulaire_principal' => $titulairePrincipal ? 1 : 0,
			'date_entree' => $dateEntree,
		]);
	}

	public static function detachContratTenant(int $idContrat, int $idLocataire): void
	{
		$stmt = Database::connection()->prepare(
			'DELETE FROM contrat_locataire WHERE id_contrat = :id_contrat AND id_locataire = :id_locataire'
		);
		$stmt->execute(['id_contrat' => $idContrat, 'id_locataire' => $idLocataire]);
	}

	/**
	 * Toutes les données nécessaires à la génération du document de contrat : agence, bien (avec
	 * adresse), locataires rattachés (coordonnées complètes) et propriétaires du bien (coordonnées
	 * complètes) — tout ce qui doit apparaître, cohérent entre lui, dans le document généré.
	 */
	public static function contratDocumentData(int $idAgence, int $idContrat): ?array
	{
		$contrat = self::contratFind($idAgence, $idContrat);
		if (!$contrat) {
			return null;
		}
		$bien = self::bienFind($idAgence, (int) $contrat['id_bien']);
		if (!$bien) {
			return null;
		}

		$stmt = Database::connection()->prepare(
			'SELECT l.nom, l.prenom, l.email, l.telephone, l.adresse, l.code_postal, l.ville,
					cl.titulaire_principal, cl.date_entree
			 FROM contrat_locataire cl
			 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
			 WHERE cl.id_contrat = :id_contrat AND l.deleted_at IS NULL
			 ORDER BY cl.titulaire_principal DESC, l.nom'
		);
		$stmt->execute(['id_contrat' => $idContrat]);
		$locataires = $stmt->fetchAll();

		$stmt = Database::connection()->prepare(
			'SELECT p.nom, p.prenom, p.email, p.telephone, p.adresse, p.code_postal, p.ville, bp.quote_part
			 FROM bien_proprietaire bp
			 INNER JOIN proprietaire p ON p.id_proprietaire = bp.id_proprietaire
			 WHERE bp.id_bien = :id_bien AND bp.date_fin IS NULL AND p.deleted_at IS NULL
			 ORDER BY p.nom'
		);
		$stmt->execute(['id_bien' => $bien['id_bien']]);
		$proprietaires = $stmt->fetchAll();

		return [
			'agence' => self::agency($idAgence),
			'contrat' => $contrat,
			'bien' => $bien,
			'locataires' => $locataires,
			'proprietaires' => $proprietaires,
		];
	}

	public static function setContratDocumentPath(int $idContrat, string $path): void
	{
		$stmt = Database::connection()->prepare('UPDATE contrat SET chemin_fichier_pdf = :path WHERE id_contrat = :id');
		$stmt->execute(['path' => $path, 'id' => $idContrat]);
	}

	/* --------------------------------------------------------------
	 * Paiements, échéances et quittances, scopés à l'agence.
	 * -------------------------------------------------------------- */

	/** Contrats de l'agence éligibles à une nouvelle échéance, avec loyer/charges pour préremplir le formulaire. */
	public static function contratsForEcheance(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT c.id_contrat, c.numero, c.loyer, c.charges, b.titre AS bien_titre
			 FROM contrat c
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE c.id_agence = :id_agence AND c.deleted_at IS NULL AND c.statut IN ('ACTIVE', 'DRAFT', 'RENEWED')
			 ORDER BY c.numero"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/** @param array{statut?: string} $filters */
	public static function echeances(int $idAgence, array $filters = []): array
	{
		$where = ['c.id_agence = :id_agence'];
		$params = ['id_agence' => $idAgence];
		if (!empty($filters['statut'])) {
			$where[] = 'e.statut = :statut';
			$params['statut'] = $filters['statut'];
		}
		$stmt = Database::connection()->prepare(
			"SELECT e.id_echeance, e.periode_debut, e.periode_fin, e.montant_total, e.date_echeance, e.statut,
					c.numero AS contrat_numero, b.titre AS bien_titre,
					(SELECT CONCAT(l.prenom, ' ', l.nom) FROM contrat_locataire cl
					 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
					 WHERE cl.id_contrat = c.id_contrat ORDER BY cl.titulaire_principal DESC LIMIT 1) AS locataire_principal
			 FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE " . implode(' AND ', $where) . '
			 ORDER BY e.date_echeance DESC'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function echeanceStats(int $idAgence): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT SUM(CASE WHEN p.statut = 'PAID' AND MONTH(p.date_paiement) = MONTH(CURDATE()) AND YEAR(p.date_paiement) = YEAR(CURDATE()) THEN p.montant ELSE 0 END) AS encaisse_mois
			 FROM paiement p
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 WHERE c.id_agence = :id_agence"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$encaisseMois = (float) ($stmt->fetchColumn() ?: 0);

		$stmt = $db->prepare(
			"SELECT
				SUM(CASE WHEN e.statut IN ('PENDING','PARTIAL','LATE') AND e.date_echeance < CURDATE() THEN 1 ELSE 0 END) AS en_retard_count,
				SUM(CASE WHEN e.statut IN ('PENDING','PARTIAL','LATE') AND e.date_echeance < CURDATE() THEN e.montant_total ELSE 0 END) AS en_retard_montant,
				SUM(CASE WHEN e.statut IN ('PENDING','PARTIAL') THEN 1 ELSE 0 END) AS en_attente_count
			 FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 WHERE c.id_agence = :id_agence"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch() ?: [];

		return [
			'encaisse_mois' => $encaisseMois,
			'en_retard_count' => (int) ($row['en_retard_count'] ?? 0),
			'en_retard_montant' => (float) ($row['en_retard_montant'] ?? 0),
			'en_attente_count' => (int) ($row['en_attente_count'] ?? 0),
		];
	}

	public static function echeanceFind(int $idAgence, int $idEcheance): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT e.*, c.numero AS contrat_numero, b.titre AS bien_titre, b.reference AS bien_reference
			 FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 WHERE e.id_echeance = :id AND c.id_agence = :id_agence
			 LIMIT 1'
		);
		$stmt->execute(['id' => $idEcheance, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function createEcheance(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO echeance (id_contrat, periode_debut, periode_fin, montant_loyer, montant_charges, date_echeance)
			 VALUES (:id_contrat, :periode_debut, :periode_fin, :montant_loyer, :montant_charges, :date_echeance)'
		);
		$stmt->execute([
			'id_contrat' => $data['id_contrat'],
			'periode_debut' => $data['periode_debut'],
			'periode_fin' => $data['periode_fin'],
			'montant_loyer' => $data['montant_loyer'],
			'montant_charges' => $data['montant_charges'],
			'date_echeance' => $data['date_echeance'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function echeancePaiements(int $idEcheance): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT p.*, CONCAT(l.prenom, ' ', l.nom) AS locataire_nom
			 FROM paiement p
			 LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
			 WHERE p.id_echeance = :id_echeance
			 ORDER BY p.date_paiement DESC, p.id_paiement DESC"
		);
		$stmt->execute(['id_echeance' => $idEcheance]);
		return $stmt->fetchAll();
	}

	public static function echeanceTotalPaid(int $idEcheance): float
	{
		$stmt = Database::connection()->prepare(
			"SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE id_echeance = :id_echeance AND statut = 'PAID'"
		);
		$stmt->execute(['id_echeance' => $idEcheance]);
		return (float) $stmt->fetchColumn();
	}

	/** Locataires du contrat lié à l'échéance, pour le sélecteur du formulaire d'encaissement. */
	public static function echeanceTenants(int $idEcheance): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT l.id_locataire, l.nom, l.prenom
			 FROM echeance e
			 INNER JOIN contrat_locataire cl ON cl.id_contrat = e.id_contrat
			 INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
			 WHERE e.id_echeance = :id_echeance AND l.deleted_at IS NULL
			 ORDER BY cl.titulaire_principal DESC, l.nom'
		);
		$stmt->execute(['id_echeance' => $idEcheance]);
		return $stmt->fetchAll();
	}

	public static function createPaiement(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO paiement (id_echeance, id_contrat, id_locataire, montant, date_paiement, mode_paiement, reference_transaction, statut, commentaire)
			 VALUES (:id_echeance, :id_contrat, :id_locataire, :montant, :date_paiement, :mode_paiement, :reference_transaction, :statut, :commentaire)'
		);
		$stmt->execute([
			'id_echeance' => $data['id_echeance'],
			'id_contrat' => $data['id_contrat'],
			'id_locataire' => $data['id_locataire'],
			'montant' => $data['montant'],
			'date_paiement' => $data['date_paiement'],
			'mode_paiement' => $data['mode_paiement'],
			'reference_transaction' => $data['reference_transaction'],
			'statut' => $data['statut'],
			'commentaire' => $data['commentaire'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	/** Recalcule et met à jour le statut d'une échéance d'après les paiements PAID enregistrés contre elle. */
	public static function refreshEcheanceStatut(int $idEcheance): void
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT e.montant_total, e.date_echeance, COALESCE(SUM(CASE WHEN p.statut = 'PAID' THEN p.montant ELSE 0 END), 0) AS total_paye
			 FROM echeance e
			 LEFT JOIN paiement p ON p.id_echeance = e.id_echeance
			 WHERE e.id_echeance = :id_echeance
			 GROUP BY e.id_echeance, e.montant_total, e.date_echeance"
		);
		$stmt->execute(['id_echeance' => $idEcheance]);
		$row = $stmt->fetch();
		if (!$row) {
			return;
		}
		$montantTotal = (float) $row['montant_total'];
		$totalPaye = (float) $row['total_paye'];
		if ($totalPaye >= $montantTotal) {
			$statut = 'PAID';
		} elseif ($totalPaye > 0) {
			$statut = 'PARTIAL';
		} elseif (strtotime((string) $row['date_echeance']) < strtotime('today')) {
			$statut = 'LATE';
		} else {
			$statut = 'PENDING';
		}
		$db->prepare('UPDATE echeance SET statut = :statut WHERE id_echeance = :id_echeance')
			->execute(['statut' => $statut, 'id_echeance' => $idEcheance]);
	}

	/* ---------------- Quittances ---------------- */

	public static function quittances(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT q.id_quittance, q.numero, q.date_emission, q.periode_debut, q.periode_fin, q.chemin_fichier_pdf,
					p.montant, c.numero AS contrat_numero, b.titre AS bien_titre,
					CONCAT(l.prenom, ' ', l.nom) AS locataire_nom
			 FROM quittance q
			 INNER JOIN paiement p ON p.id_paiement = q.id_paiement
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 INNER JOIN bien b ON b.id_bien = c.id_bien
			 LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
			 WHERE c.id_agence = :id_agence
			 ORDER BY q.date_emission DESC"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	public static function quittanceFind(int $idAgence, int $idQuittance): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT q.*, c.id_agence
			 FROM quittance q
			 INNER JOIN paiement p ON p.id_paiement = q.id_paiement
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 WHERE q.id_quittance = :id AND c.id_agence = :id_agence
			 LIMIT 1'
		);
		$stmt->execute(['id' => $idQuittance, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function quittanceByPaiement(int $idPaiement): ?array
	{
		$stmt = Database::connection()->prepare('SELECT * FROM quittance WHERE id_paiement = :id_paiement LIMIT 1');
		$stmt->execute(['id_paiement' => $idPaiement]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function createQuittance(int $idPaiement, string $numero, ?string $periodeDebut, ?string $periodeFin): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO quittance (id_paiement, numero, periode_debut, periode_fin) VALUES (:id_paiement, :numero, :periode_debut, :periode_fin)'
		);
		$stmt->execute(['id_paiement' => $idPaiement, 'numero' => $numero, 'periode_debut' => $periodeDebut, 'periode_fin' => $periodeFin]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function setQuittanceDocumentPath(int $idQuittance, string $path): void
	{
		$stmt = Database::connection()->prepare('UPDATE quittance SET chemin_fichier_pdf = :path WHERE id_quittance = :id');
		$stmt->execute(['path' => $path, 'id' => $idQuittance]);
	}

	/** Toutes les données nécessaires à la génération du document de quittance. */
	public static function quittanceDocumentData(int $idAgence, int $idQuittance): ?array
	{
		$quittance = self::quittanceFind($idAgence, $idQuittance);
		if (!$quittance) {
			return null;
		}
		$stmt = Database::connection()->prepare(
			"SELECT p.*, c.numero AS contrat_numero, c.id_bien,
					CONCAT(l.prenom, ' ', l.nom) AS locataire_nom, l.adresse, l.code_postal, l.ville
			 FROM paiement p
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
			 WHERE p.id_paiement = :id_paiement"
		);
		$stmt->execute(['id_paiement' => $quittance['id_paiement']]);
		$paiement = $stmt->fetch();
		if (!$paiement) {
			return null;
		}
		$bien = self::bienFind($idAgence, (int) $paiement['id_bien']);
		return [
			'agence' => self::agency($idAgence),
			'quittance' => $quittance,
			'paiement' => $paiement,
			'bien' => $bien,
		];
	}

	/* ---------------- Demandes de paiement en ligne ---------------- */

	public static function createDemandePaiement(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO demande_paiement (id_agence, id_echeance, id_locataire, montant, stripe_checkout_session_id, id_utilisateur_createur)
			 VALUES (:id_agence, :id_echeance, :id_locataire, :montant, :stripe_checkout_session_id, :id_utilisateur_createur)'
		);
		$stmt->execute([
			'id_agence' => $data['id_agence'],
			'id_echeance' => $data['id_echeance'],
			'id_locataire' => $data['id_locataire'],
			'montant' => $data['montant'],
			'stripe_checkout_session_id' => $data['stripe_checkout_session_id'],
			'id_utilisateur_createur' => $data['id_utilisateur_createur'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function setDemandePaiementSession(int $idDemande, string $sessionId, string $checkoutUrl): void
	{
		Database::connection()
			->prepare('UPDATE demande_paiement SET stripe_checkout_session_id = :session_id, checkout_url = :checkout_url WHERE id_demande = :id')
			->execute(['session_id' => $sessionId, 'checkout_url' => $checkoutUrl, 'id' => $idDemande]);
	}

	public static function demandePaiementBySessionId(string $sessionId): ?array
	{
		$stmt = Database::connection()->prepare('SELECT * FROM demande_paiement WHERE stripe_checkout_session_id = :id LIMIT 1');
		$stmt->execute(['id' => $sessionId]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/** Dernière demande (quel que soit son statut) pour une échéance, pour éviter les doublons dans l'UI. */
	public static function demandePaiementLatestForEcheance(int $idEcheance): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT * FROM demande_paiement WHERE id_echeance = :id_echeance ORDER BY id_demande DESC LIMIT 1'
		);
		$stmt->execute(['id_echeance' => $idEcheance]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/** @return array<int,array> id_echeance => dernière demande PENDING, pour l'affichage en liste. */
	public static function pendingDemandesByEcheances(array $idEcheances): array
	{
		if (!$idEcheances) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($idEcheances), '?'));
		$stmt = Database::connection()->prepare(
			"SELECT * FROM demande_paiement WHERE statut = 'PENDING' AND id_echeance IN ($placeholders)"
		);
		$stmt->execute(array_map('intval', $idEcheances));
		$result = [];
		foreach ($stmt->fetchAll() as $row) {
			$result[(int) $row['id_echeance']] = $row;
		}
		return $result;
	}

	public static function markDemandePaiementPaid(int $idDemande, int $idPaiement): void
	{
		Database::connection()
			->prepare("UPDATE demande_paiement SET statut = 'PAID', id_paiement = :id_paiement, paid_at = NOW() WHERE id_demande = :id")
			->execute(['id_paiement' => $idPaiement, 'id' => $idDemande]);
	}

	public static function markDemandePaiementExpired(int $idDemande): void
	{
		Database::connection()
			->prepare("UPDATE demande_paiement SET statut = 'EXPIRED' WHERE id_demande = :id AND statut = 'PENDING'")
			->execute(['id' => $idDemande]);
	}

	public static function cancelDemandePaiement(int $idAgence, int $idDemande): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE demande_paiement SET statut = 'CANCELLED' WHERE id_demande = :id AND id_agence = :id_agence AND statut = 'PENDING'"
		);
		$stmt->execute(['id' => $idDemande, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	/* --------------------------------------------------------------
	 * Interventions (maintenance), scopées à l'agence.
	 * -------------------------------------------------------------- */

	/** @param array{search?: string, priorite?: string, statut?: string} $filters */
	public static function interventions(int $idAgence, array $filters = []): array
	{
		$sql = "SELECT i.*, b.titre AS bien_titre, b.reference AS bien_reference,
					u.nom AS assigne_nom, u.prenom AS assigne_prenom
				FROM intervention i
				INNER JOIN bien b ON b.id_bien = i.id_bien
				LEFT JOIN utilisateur u ON u.id_utilisateur = i.id_utilisateur_assigne
				WHERE i.id_agence = :id_agence";
		$params = ['id_agence' => $idAgence];

		if (!empty($filters['search'])) {
			$sql .= ' AND i.titre LIKE :search';
			$params['search'] = '%' . $filters['search'] . '%';
		}
		if (!empty($filters['priorite'])) {
			$sql .= ' AND i.priorite = :priorite';
			$params['priorite'] = $filters['priorite'];
		}
		if (!empty($filters['statut'])) {
			$sql .= ' AND i.statut = :statut';
			$params['statut'] = $filters['statut'];
		}
		$sql .= ' ORDER BY i.created_at DESC';

		$stmt = Database::connection()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function interventionStats(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT
				SUM(CASE WHEN statut IN ('OPEN', 'ASSIGNED') THEN 1 ELSE 0 END) AS ouvertes,
				SUM(CASE WHEN statut = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS en_cours,
				SUM(CASE WHEN priorite = 'URGENT' AND statut NOT IN ('RESOLVED', 'CLOSED', 'CANCELLED') THEN 1 ELSE 0 END) AS urgentes,
				SUM(CASE WHEN statut = 'RESOLVED' AND MONTH(updated_at) = MONTH(CURDATE()) AND YEAR(updated_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS resolues_ce_mois
			 FROM intervention WHERE id_agence = :id_agence"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch() ?: [];
		return [
			'ouvertes' => (int) ($row['ouvertes'] ?? 0),
			'en_cours' => (int) ($row['en_cours'] ?? 0),
			'urgentes' => (int) ($row['urgentes'] ?? 0),
			'resolues_ce_mois' => (int) ($row['resolues_ce_mois'] ?? 0),
		];
	}

	public static function interventionFind(int $idAgence, int $idIntervention): ?array
	{
		$stmt = Database::connection()->prepare(
			"SELECT i.*, b.titre AS bien_titre, b.reference AS bien_reference,
					l.nom AS locataire_nom, l.prenom AS locataire_prenom,
					u.nom AS assigne_nom, u.prenom AS assigne_prenom
			 FROM intervention i
			 INNER JOIN bien b ON b.id_bien = i.id_bien
			 LEFT JOIN locataire l ON l.id_locataire = i.id_locataire
			 LEFT JOIN utilisateur u ON u.id_utilisateur = i.id_utilisateur_assigne
			 WHERE i.id_intervention = :id AND i.id_agence = :id_agence
			 LIMIT 1"
		);
		$stmt->execute(['id' => $idIntervention, 'id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/** Biens de l'agence, pour le sélecteur de l'écran de création d'intervention. */
	public static function biensForIntervention(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_bien, titre, reference FROM bien WHERE id_agence = :id_agence AND deleted_at IS NULL ORDER BY titre'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/** Locataires de l'agence, pour le sélecteur (optionnel) de l'écran de création d'intervention. */
	public static function locatairesForIntervention(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_locataire, nom, prenom FROM locataire WHERE id_agence = :id_agence AND deleted_at IS NULL ORDER BY nom'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return $stmt->fetchAll();
	}

	/**
	 * @param array{titre: string, description: string, priorite: string, id_bien: int, id_locataire: ?int, cout_estime: ?float} $data
	 */
	public static function createIntervention(int $idAgence, int $idUtilisateurCreateur, array $data): int
	{
		$stmt = Database::connection()->prepare(
			"INSERT INTO intervention (id_agence, id_bien, id_locataire, id_utilisateur_createur, titre, description, priorite, cout_estime, statut, created_at, updated_at)
			 VALUES (:id_agence, :id_bien, :id_locataire, :id_utilisateur_createur, :titre, :description, :priorite, :cout_estime, 'OPEN', NOW(), NOW())"
		);
		$stmt->execute([
			'id_agence' => $idAgence,
			'id_bien' => $data['id_bien'],
			'id_locataire' => $data['id_locataire'],
			'id_utilisateur_createur' => $idUtilisateurCreateur,
			'titre' => $data['titre'],
			'description' => $data['description'],
			'priorite' => $data['priorite'],
			'cout_estime' => $data['cout_estime'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function updateInterventionStatus(int $idAgence, int $idIntervention, string $statut): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE intervention SET statut = :statut, updated_at = NOW() WHERE id_intervention = :id AND id_agence = :id_agence'
		);
		$stmt->execute(['statut' => $statut, 'id' => $idIntervention, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	public static function closeIntervention(int $idAgence, int $idIntervention, ?float $coutFinal, string $compteRendu): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE intervention SET statut = 'CLOSED', cout_final = :cout_final, compte_rendu = :compte_rendu, date_fin = NOW(), updated_at = NOW()
			 WHERE id_intervention = :id AND id_agence = :id_agence"
		);
		$stmt->execute(['cout_final' => $coutFinal, 'compte_rendu' => $compteRendu, 'id' => $idIntervention, 'id_agence' => $idAgence]);
		return $stmt->rowCount() > 0;
	}

	/* --------------------------------------------------------------
	 * Documents, scopés à l'agence (table partagée avec le module Jordan :
	 * un document n'a pas de colonne id_agence directe, son agence se déduit
	 * de l'entité à laquelle il est rattaché — bien/contrat/proprietaire/
	 * locataire/intervention — chacune elle-même scopée par id_agence).
	 * -------------------------------------------------------------- */

	private const DOCUMENT_JOIN_SQL = "FROM document d
				JOIN type_document td ON td.id_type_document = d.id_type_document
				LEFT JOIN bien b ON d.id_bien = b.id_bien AND b.id_agence = :agence1
				LEFT JOIN contrat c ON d.id_contrat = c.id_contrat AND c.id_agence = :agence2
				LEFT JOIN proprietaire p ON d.id_proprietaire = p.id_proprietaire AND p.id_agence = :agence3
				LEFT JOIN locataire l ON d.id_locataire = l.id_locataire AND l.id_agence = :agence4
				LEFT JOIN intervention i ON d.id_intervention = i.id_intervention AND i.id_agence = :agence5
				WHERE (d.id_bien IS NOT NULL AND b.id_bien IS NOT NULL
					OR d.id_contrat IS NOT NULL AND c.id_contrat IS NOT NULL
					OR d.id_proprietaire IS NOT NULL AND p.id_proprietaire IS NOT NULL
					OR d.id_locataire IS NOT NULL AND l.id_locataire IS NOT NULL
					OR d.id_intervention IS NOT NULL AND i.id_intervention IS NOT NULL)";

	/** @param array{search?: string, id_type_document?: string, entity_type?: string} $filters */
	public static function documents(int $idAgence, array $filters = []): array
	{
		$sql = "SELECT d.*, td.nom AS type_nom,
					b.titre AS bien_nom, c.numero AS contrat_numero,
					p.nom AS proprietaire_nom, p.prenom AS proprietaire_prenom,
					l.nom AS locataire_nom, l.prenom AS locataire_prenom,
					i.titre AS intervention_titre
				" . self::DOCUMENT_JOIN_SQL;
		$params = ['agence1' => $idAgence, 'agence2' => $idAgence, 'agence3' => $idAgence, 'agence4' => $idAgence, 'agence5' => $idAgence];

		if (!empty($filters['search'])) {
			$sql .= ' AND d.nom LIKE :search';
			$params['search'] = '%' . $filters['search'] . '%';
		}
		if (!empty($filters['id_type_document'])) {
			$sql .= ' AND d.id_type_document = :type_id';
			$params['type_id'] = $filters['id_type_document'];
		}
		if (!empty($filters['entity_type'])) {
			$column = match ($filters['entity_type']) {
				'bien' => 'd.id_bien', 'contrat' => 'd.id_contrat', 'proprietaire' => 'd.id_proprietaire',
				'locataire' => 'd.id_locataire', 'intervention' => 'd.id_intervention', default => null,
			};
			if ($column !== null) {
				$sql .= " AND {$column} IS NOT NULL";
			}
		}
		$sql .= ' ORDER BY d.created_at DESC';

		$stmt = Database::connection()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function documentFind(int $idAgence, int $idDocument): ?array
	{
		$sql = "SELECT d.* " . self::DOCUMENT_JOIN_SQL . ' AND d.id_document = :id_document';
		$stmt = Database::connection()->prepare($sql);
		$stmt->execute(['agence1' => $idAgence, 'agence2' => $idAgence, 'agence3' => $idAgence, 'agence4' => $idAgence, 'agence5' => $idAgence, 'id_document' => $idDocument]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function documentTypes(): array
	{
		return Database::connection()->query('SELECT id_type_document, nom FROM type_document ORDER BY nom')->fetchAll();
	}

	/** Entités de l'agence pouvant recevoir un document (listes pour le formulaire d'import). */
	public static function documentEntities(int $idAgence): array
	{
		$db = Database::connection();

		$biens = $db->prepare('SELECT id_bien, titre, reference FROM bien WHERE id_agence = :a AND deleted_at IS NULL ORDER BY titre');
		$biens->execute(['a' => $idAgence]);

		$contrats = $db->prepare('SELECT id_contrat, numero FROM contrat WHERE id_agence = :a ORDER BY numero');
		$contrats->execute(['a' => $idAgence]);

		$proprietaires = $db->prepare('SELECT id_proprietaire, nom, prenom FROM proprietaire WHERE id_agence = :a AND deleted_at IS NULL ORDER BY nom');
		$proprietaires->execute(['a' => $idAgence]);

		$locataires = $db->prepare('SELECT id_locataire, nom, prenom FROM locataire WHERE id_agence = :a AND deleted_at IS NULL ORDER BY nom');
		$locataires->execute(['a' => $idAgence]);

		$interventions = $db->prepare('SELECT id_intervention, titre FROM intervention WHERE id_agence = :a ORDER BY titre');
		$interventions->execute(['a' => $idAgence]);

		return [
			'bien' => $biens->fetchAll(),
			'contrat' => $contrats->fetchAll(),
			'proprietaire' => $proprietaires->fetchAll(),
			'locataire' => $locataires->fetchAll(),
			'intervention' => $interventions->fetchAll(),
		];
	}

	/**
	 * @param array{id_type_document: int, id_bien: ?int, id_contrat: ?int, id_proprietaire: ?int, id_locataire: ?int, id_intervention: ?int, nom: string, nom_original: string, mime_type: string, taille_octets: int, chemin_stockage: string} $data
	 */
	public static function createDocument(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO document (id_type_document, id_bien, id_contrat, id_proprietaire, id_locataire, id_intervention, nom, nom_original, mime_type, taille_octets, chemin_stockage, version, created_at, updated_at)
			 VALUES (:id_type_document, :id_bien, :id_contrat, :id_proprietaire, :id_locataire, :id_intervention, :nom, :nom_original, :mime_type, :taille_octets, :chemin_stockage, 1, NOW(), NOW())'
		);
		$stmt->execute([
			'id_type_document' => $data['id_type_document'],
			'id_bien' => $data['id_bien'],
			'id_contrat' => $data['id_contrat'],
			'id_proprietaire' => $data['id_proprietaire'],
			'id_locataire' => $data['id_locataire'],
			'id_intervention' => $data['id_intervention'],
			'nom' => $data['nom'],
			'nom_original' => $data['nom_original'],
			'mime_type' => $data['mime_type'],
			'taille_octets' => $data['taille_octets'],
			'chemin_stockage' => $data['chemin_stockage'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	/** @return string|null Le chemin de stockage du fichier supprimé, ou null si le document n'appartient pas à l'agence. */
	public static function deleteDocument(int $idAgence, int $idDocument): ?string
	{
		$doc = self::documentFind($idAgence, $idDocument);
		if (!$doc) {
			return null;
		}
		$stmt = Database::connection()->prepare('DELETE FROM document WHERE id_document = :id');
		$stmt->execute(['id' => $idDocument]);
		return $doc['chemin_stockage'];
	}

	/** Compteurs légers utilisés pour les badges de la sidebar. */
	public static function navBadges(int $idAgence): array
	{
		$db = Database::connection();
		return [
			'contrats' => self::contractsExpiringSoonCount($db, $idAgence),
			'interventions' => self::openInterventionsCount($db, $idAgence),
			'biens' => self::biensToPublishCount($db, $idAgence),
			'echeances_en_retard' => self::echeancesEnRetardCount($db, $idAgence),
		];
	}

	private static function echeancesEnRetardCount(PDO $db, int $idAgence): int
	{
		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 WHERE c.id_agence = :id_agence AND e.statut IN ('PENDING','PARTIAL','LATE') AND e.date_echeance < CURDATE()"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return (int) $stmt->fetchColumn();
	}

	private static function biensToPublishCount(PDO $db, int $idAgence): int
	{
		$stmt = $db->prepare("SELECT COUNT(*) FROM bien WHERE id_agence = :id_agence AND statut = 'CREATED' AND deleted_at IS NULL");
		$stmt->execute(['id_agence' => $idAgence]);
		return (int) $stmt->fetchColumn();
	}

	private static function contractsExpiringSoonCount(PDO $db, int $idAgence): int
	{
		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM contrat
			 WHERE id_agence = :id_agence AND statut = 'ACTIVE' AND date_fin IS NOT NULL
				AND date_fin <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return (int) $stmt->fetchColumn();
	}

	private static function openInterventionsCount(PDO $db, int $idAgence): int
	{
		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM intervention
			 WHERE id_agence = :id_agence AND statut IN ('OPEN', 'ASSIGNED', 'IN_PROGRESS', 'WAITING')"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return (int) $stmt->fetchColumn();
	}

	private static function monthlyRevenueKpi(PDO $db, int $idAgence): array
	{
		$stmt = $db->prepare(
			"SELECT COALESCE(SUM(p.montant), 0) FROM paiement p
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 WHERE c.id_agence = :id_agence AND p.statut = 'PAID'
				AND MONTH(p.date_paiement) = MONTH(CURDATE()) AND YEAR(p.date_paiement) = YEAR(CURDATE())"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return [
			'label' => 'Revenus du mois',
			'value' => number_format((float) $stmt->fetchColumn(), 0, ',', ' ') . ' €',
			'icon' => 'trending',
			'tone' => 'green',
		];
	}

	public static function recentActivity(int $idAgence, int $limit = 6): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT ja.action, ja.entite, ja.date_action, ja.nouvelle_valeur, u.nom, u.prenom
			 FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 WHERE ja.id_agence = :id_agence
			 ORDER BY ja.date_action DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':id_agence', $idAgence, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return array_map(static function (array $row): array {
			$nouvelleValeur = $row['nouvelle_valeur'] ? json_decode((string) $row['nouvelle_valeur'], true) : null;
			[$icon, $tone, $title] = self::activityPresentation($row['action'], $row['entite'], is_array($nouvelleValeur) ? $nouvelleValeur : []);
			$who = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
			return [
				'icon' => $icon,
				'tone' => $tone,
				'title' => $title,
				'sub' => $who !== '' ? $who : 'Système',
				'time' => self::relativeTime(new DateTimeImmutable($row['date_action'])),
			];
		}, $stmt->fetchAll());
	}

	private static function countKpi(PDO $db, string $table, int $idAgence, string $label, string $icon, string $tone): array
	{
		$stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE id_agence = :id_agence AND deleted_at IS NULL");
		$stmt->execute(['id_agence' => $idAgence]);
		return [
			'label' => $label,
			'value' => number_format((int) $stmt->fetchColumn(), 0, ',', ' '),
			'icon' => $icon,
			'tone' => $tone,
		];
	}

	private static function activeContractsKpi(PDO $db, int $idAgence): array
	{
		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM contrat WHERE id_agence = :id_agence AND statut = 'ACTIVE' AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return [
			'label' => 'Contrats actifs',
			'value' => number_format((int) $stmt->fetchColumn(), 0, ',', ' '),
			'icon' => 'card',
			'tone' => 'violet',
		];
	}

	private const ENTITY_LABELS = [
		'agence' => 'une agence',
		'utilisateur' => 'un utilisateur',
		'bien' => 'un bien',
		'locataire' => 'un locataire',
		'proprietaire' => 'un propriétaire',
		'contrat' => 'un contrat',
		'paiement' => 'un paiement',
		'echeance' => 'une échéance',
		'quittance' => 'une quittance',
		'abonnement' => 'un abonnement',
	];

	/** @param array<string,mixed> $nouvelleValeur */
	private static function activityPresentation(string $action, string $entite, array $nouvelleValeur = []): array
	{
		if ($action === 'UPDATE' && isset($nouvelleValeur['statut']) && $entite === 'utilisateur') {
			return match ($nouvelleValeur['statut']) {
				'ACTIVE' => ['check', 'teal', 'Compte utilisateur activé'],
				'INACTIVE' => ['x', 'red', 'Compte utilisateur désactivé'],
				'LOCKED' => ['shield', 'gray', 'Compte utilisateur verrouillé'],
				default => ['edit', 'amber', 'Statut du compte modifié'],
			};
		}

		$label = self::ENTITY_LABELS[$entite] ?? 'un élément';

		return match (true) {
			$action === 'CREATE' && $entite === 'agence' => ['plus', 'teal', 'Agence créée'],
			$action === 'CREATE' && $entite === 'utilisateur' => ['user', 'blue', 'Nouvel utilisateur ajouté'],
			$action === 'CREATE' => ['plus', 'teal', "Création d’{$label}"],
			$action === 'UPDATE' && $entite === 'agence' => ['edit', 'amber', 'Informations d’agence modifiées'],
			$action === 'UPDATE' && $entite === 'utilisateur' => ['edit', 'amber', 'Profil utilisateur modifié'],
			$action === 'UPDATE' => ['edit', 'amber', "Modification d’{$label}"],
			$action === 'DELETE' => ['x', 'red', "Suppression d’{$label}"],
			$action === 'LOGIN' => ['shield', 'gray', 'Connexion'],
			$action === 'LOGOUT' => ['shield', 'gray', 'Déconnexion'],
			default => ['shield', 'gray', "Action sur {$label}"],
		};
	}

	private static function relativeTime(DateTimeImmutable $date): string
	{
		$diff = (new DateTimeImmutable())->getTimestamp() - $date->getTimestamp();
		if ($diff < 60) return 'À l’instant';
		if ($diff < 3600) return 'Il y a ' . intdiv($diff, 60) . ' min';
		if ($diff < 86400) return 'Il y a ' . intdiv($diff, 3600) . 'h';
		if ($diff < 172800) return 'Hier · ' . $date->format('H:i');
		return $date->format('d/m/Y');
	}
}
