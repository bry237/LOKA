<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class AgenceModel
{
	private const PAGE_SIZE = 10;

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_agence, nom, statut FROM agence WHERE id_agence = :id AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/**
	 * @param array{q?: string, status?: string} $filters
	 */
	public static function list(array $filters, int $page): array
	{
		$db = Database::connection();
		[$whereSql, $params] = self::buildWhere($filters);

		$countStmt = $db->prepare("SELECT COUNT(*) FROM agence a WHERE {$whereSql}");
		self::bindFilters($countStmt, $params);
		$countStmt->execute();
		$total = (int) $countStmt->fetchColumn();

		$totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
		$page = min(max(1, $page), $totalPages);
		$offset = ($page - 1) * self::PAGE_SIZE;

		$stmt = $db->prepare(
			"SELECT a.id_agence, a.nom, a.ville, a.email, a.statut, a.created_at,
					(SELECT CONCAT(u.prenom, ' ', u.nom) FROM utilisateur u
					 WHERE u.id_agence = a.id_agence AND u.id_role = 2 AND u.deleted_at IS NULL
					 ORDER BY u.id_utilisateur LIMIT 1) AS admin_nom,
					(SELECT ab.nom FROM agence_abonnement aa
					 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
					 WHERE aa.id_agence = a.id_agence AND aa.statut = 'ACTIVE'
					 ORDER BY aa.date_debut DESC LIMIT 1) AS plan_nom
			 FROM agence a
			 WHERE {$whereSql}
			 ORDER BY a.created_at DESC
			 LIMIT :limit OFFSET :offset"
		);
		self::bindFilters($stmt, $params);
		$stmt->bindValue(':limit', self::PAGE_SIZE, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		return [
			'rows' => $stmt->fetchAll(),
			'total' => $total,
			'page' => $page,
			'pageSize' => self::PAGE_SIZE,
			'totalPages' => $totalPages,
		];
	}

	public static function findFull(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_agence, nom, email, telephone, adresse, ville, code_postal, pays, statut, stripe_customer_id, created_at, updated_at
			 FROM agence WHERE id_agence = :id AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function detail(int $id): ?array
	{
		$db = Database::connection();

		$agence = self::findFull($id);
		if (!$agence) {
			return null;
		}

		$adminStmt = $db->prepare(
			'SELECT id_utilisateur, nom, prenom, email, telephone, telephone_verifie, statut, derniere_connexion
			 FROM utilisateur
			 WHERE id_agence = :id AND id_role = 2 AND deleted_at IS NULL
			 ORDER BY id_utilisateur LIMIT 1'
		);
		$adminStmt->execute(['id' => $id]);
		$admin = $adminStmt->fetch() ?: null;

		$statsStmt = $db->prepare(
			'SELECT
				(SELECT COUNT(*) FROM utilisateur WHERE id_agence = :id1 AND deleted_at IS NULL) AS nb_utilisateurs,
				(SELECT COUNT(*) FROM bien WHERE id_agence = :id2 AND deleted_at IS NULL) AS nb_biens'
		);
		$statsStmt->execute(['id1' => $id, 'id2' => $id]);
		$stats = $statsStmt->fetch();

		$historyStmt = $db->prepare(
			'SELECT aa.id_agence_abonnement, ab.nom AS plan_nom, ab.prix_mensuel, aa.date_debut, aa.date_fin, aa.statut
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.id_agence = :id
			 ORDER BY aa.date_debut DESC'
		);
		$historyStmt->execute(['id' => $id]);
		$history = $historyStmt->fetchAll();

		return [
			'agence' => $agence,
			'admin' => $admin,
			'stats' => [
				'utilisateurs' => (int) $stats['nb_utilisateurs'],
				'biens' => (int) $stats['nb_biens'],
			],
			'subscription' => $history[0] ?? null,
			'history' => $history,
		];
	}

	/**
	 * @return array{id_utilisateur: int, telephone: ?string}|null
	 */
	public static function findAdminContact(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_utilisateur, telephone
			 FROM utilisateur
			 WHERE id_agence = :id AND id_role = 2 AND deleted_at IS NULL
			 ORDER BY id_utilisateur LIMIT 1'
		);
		$stmt->execute(['id' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function setStatus(int $id, string $status): void
	{
		$stmt = Database::connection()->prepare('UPDATE agence SET statut = :statut WHERE id_agence = :id');
		$stmt->execute(['statut' => $status, 'id' => $id]);
	}

	public static function setStripeCustomerId(int $id, string $stripeCustomerId): void
	{
		$stmt = Database::connection()->prepare('UPDATE agence SET stripe_customer_id = :stripe_customer_id WHERE id_agence = :id');
		$stmt->execute(['stripe_customer_id' => $stripeCustomerId, 'id' => $id]);
	}

	public static function emailExists(string $email, ?int $excludeId = null): bool
	{
		$sql = 'SELECT 1 FROM agence WHERE LOWER(email) = LOWER(:email) AND deleted_at IS NULL';
		$params = ['email' => $email];
		if ($excludeId !== null) {
			$sql .= ' AND id_agence != :excludeId';
			$params['excludeId'] = $excludeId;
		}
		$stmt = Database::connection()->prepare($sql . ' LIMIT 1');
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	/**
	 * @param array{nom: string, email: string, telephone: string, adresse: string, code_postal: string, ville: string, pays: string} $data
	 */
	public static function create(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO agence (nom, email, telephone, adresse, ville, code_postal, pays, statut)
			 VALUES (:nom, :email, :telephone, :adresse, :ville, :code_postal, :pays, \'ACTIVE\')'
		);
		$stmt->execute($data);
		return (int) Database::connection()->lastInsertId();
	}

	/**
	 * @param array{nom: string, email: string, telephone: string, adresse: string, code_postal: string, ville: string, pays: string} $data
	 */
	public static function update(int $id, array $data): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE agence SET nom = :nom, email = :email, telephone = :telephone, adresse = :adresse,
				ville = :ville, code_postal = :code_postal, pays = :pays
			 WHERE id_agence = :id'
		);
		$stmt->execute($data + ['id' => $id]);
	}

	private static function buildWhere(array $filters): array
	{
		$where = ['a.deleted_at IS NULL'];
		$params = [];

		if (($filters['q'] ?? '') !== '') {
			// Placeholders nommés distincts requis : PDO::ATTR_EMULATE_PREPARES=false
			// interdit de réutiliser le même paramètre plusieurs fois dans une requête MySQL.
			$where[] = '(a.nom LIKE :q1 OR a.ville LIKE :q2 OR a.email LIKE :q3)';
			$params['q1'] = $params['q2'] = $params['q3'] = '%' . $filters['q'] . '%';
		}
		if (($filters['status'] ?? '') !== '') {
			$where[] = 'a.statut = :status';
			$params['status'] = $filters['status'];
		}

		return [implode(' AND ', $where), $params];
	}

	private static function bindFilters(PDOStatement $stmt, array $params): void
	{
		if (isset($params['q1'])) {
			$stmt->bindValue(':q1', $params['q1'], PDO::PARAM_STR);
			$stmt->bindValue(':q2', $params['q2'], PDO::PARAM_STR);
			$stmt->bindValue(':q3', $params['q3'], PDO::PARAM_STR);
		}
		if (isset($params['status'])) $stmt->bindValue(':status', $params['status'], PDO::PARAM_STR);
	}
}
