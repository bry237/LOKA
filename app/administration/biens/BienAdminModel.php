<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

/** Vue plateforme (toutes agences) du patrimoine immobilier — lecture + supervision, pas de création. */
final class BienAdminModel
{
	private const PAGE_SIZE = 10;

	private static function buildWhere(array $filters): array
	{
		$where = ['b.deleted_at IS NULL'];
		$params = [];

		if (!empty($filters['q'])) {
			$where[] = '(b.reference LIKE :q1 OR b.titre LIKE :q2 OR ad.ville LIKE :q3 OR ag.nom LIKE :q4)';
			$term = '%' . $filters['q'] . '%';
			$params['q1'] = $term;
			$params['q2'] = $term;
			$params['q3'] = $term;
			$params['q4'] = $term;
		}
		if (!empty($filters['statut'])) {
			$where[] = 'b.statut = :statut';
			$params['statut'] = $filters['statut'];
		}
		if (!empty($filters['id_agence'])) {
			$where[] = 'b.id_agence = :id_agence';
			$params['id_agence'] = (int) $filters['id_agence'];
		}
		if (!empty($filters['id_type_bien'])) {
			$where[] = 'b.id_type_bien = :id_type_bien';
			$params['id_type_bien'] = (int) $filters['id_type_bien'];
		}

		return [implode(' AND ', $where), $params];
	}

	private static function bindFilters(PDOStatement $stmt, array $params): void
	{
		foreach ($params as $key => $value) {
			$stmt->bindValue(':' . $key, $value);
		}
	}

	/**
	 * @param array{q?: string, statut?: string, id_agence?: string, id_type_bien?: string} $filters
	 */
	public static function list(array $filters, int $page): array
	{
		$db = Database::connection();
		[$whereSql, $params] = self::buildWhere($filters);

		$countStmt = $db->prepare(
			"SELECT COUNT(*) FROM bien b
			 INNER JOIN adresse ad ON ad.id_adresse = b.id_adresse
			 INNER JOIN agence ag ON ag.id_agence = b.id_agence
			 WHERE {$whereSql}"
		);
		self::bindFilters($countStmt, $params);
		$countStmt->execute();
		$total = (int) $countStmt->fetchColumn();

		$totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
		$page = min(max(1, $page), $totalPages);
		$offset = ($page - 1) * self::PAGE_SIZE;

		$stmt = $db->prepare(
			"SELECT b.id_bien, b.reference, b.titre, b.surface, b.loyer, b.statut, b.created_at,
					t.nom AS type_bien, ad.ville, ag.id_agence, ag.nom AS agence_nom
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse ad ON ad.id_adresse = b.id_adresse
			 INNER JOIN agence ag ON ag.id_agence = b.id_agence
			 WHERE {$whereSql}
			 ORDER BY b.updated_at DESC, b.id_bien DESC
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

	public static function stats(): array
	{
		$db = Database::connection();
		$row = $db->query(
			"SELECT COUNT(*) AS total,
					SUM(statut = 'AVAILABLE') AS disponibles,
					SUM(statut = 'OCCUPIED') AS occupes,
					SUM(statut = 'MAINTENANCE') AS maintenance,
					SUM(statut = 'ARCHIVED') AS archives
			 FROM bien WHERE deleted_at IS NULL"
		)->fetch();

		return [
			'total' => (int) ($row['total'] ?? 0),
			'disponibles' => (int) ($row['disponibles'] ?? 0),
			'occupes' => (int) ($row['occupes'] ?? 0),
			'maintenance' => (int) ($row['maintenance'] ?? 0),
			'archives' => (int) ($row['archives'] ?? 0),
		];
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			"SELECT b.*, t.nom AS type_bien, ad.ligne1, ad.ligne2, ad.code_postal, ad.ville,
					ag.id_agence, ag.nom AS agence_nom, ag.statut AS agence_statut
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse ad ON ad.id_adresse = b.id_adresse
			 INNER JOIN agence ag ON ag.id_agence = b.id_agence
			 WHERE b.id_bien = :id AND b.deleted_at IS NULL
			 LIMIT 1"
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function setStatus(int $id, string $statut): void
	{
		$stmt = Database::connection()->prepare('UPDATE bien SET statut = :statut WHERE id_bien = :id');
		$stmt->execute(['statut' => $statut, 'id' => $id]);
	}

	public static function typeOptions(): array
	{
		return Database::connection()->query('SELECT id_type_bien, nom FROM type_bien ORDER BY nom')->fetchAll();
	}

	public static function agenceOptions(): array
	{
		return Database::connection()
			->query("SELECT id_agence, nom FROM agence WHERE deleted_at IS NULL ORDER BY nom")
			->fetchAll();
	}
}
