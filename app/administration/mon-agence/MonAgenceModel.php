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
		];
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
		'contrat' => 'un contrat',
		'paiement' => 'un paiement',
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
