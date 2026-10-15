<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class AuditModel
{
	private const PAGE_SIZE = 20;

	public const ACTIONS = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'DOWNLOAD', 'OTHER'];

	public static function distinctEntities(): array
	{
		return Database::connection()
			->query('SELECT DISTINCT entite FROM journal_audit ORDER BY entite')
			->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * @param array{q?: string, action?: string, entite?: string, date_from?: string, date_to?: string} $filters
	 */
	public static function list(array $filters, int $page): array
	{
		$db = Database::connection();
		[$whereSql, $params] = self::buildWhere($filters);

		$countStmt = $db->prepare(
			"SELECT COUNT(*) FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 LEFT JOIN agence a ON a.id_agence = ja.id_agence
			 WHERE {$whereSql}"
		);
		self::bindFilters($countStmt, $params);
		$countStmt->execute();
		$total = (int) $countStmt->fetchColumn();

		$totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
		$page = min(max(1, $page), $totalPages);
		$offset = ($page - 1) * self::PAGE_SIZE;

		$stmt = $db->prepare(
			"SELECT ja.id_audit, ja.action, ja.entite, ja.id_entite, ja.date_action, ja.adresse_ip,
					u.nom, u.prenom, a.nom AS agence_nom
			 FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 LEFT JOIN agence a ON a.id_agence = ja.id_agence
			 WHERE {$whereSql}
			 ORDER BY ja.date_action DESC, ja.id_audit DESC
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

	public static function detail(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT ja.*, u.nom, u.prenom, u.email, a.nom AS agence_nom
			 FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 LEFT JOIN agence a ON a.id_agence = ja.id_agence
			 WHERE ja.id_audit = :id LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		if (!$row) {
			return null;
		}
		$row['ancienne_valeur'] = $row['ancienne_valeur'] ? json_decode((string) $row['ancienne_valeur'], true) : null;
		$row['nouvelle_valeur'] = $row['nouvelle_valeur'] ? json_decode((string) $row['nouvelle_valeur'], true) : null;
		return $row;
	}

	private static function buildWhere(array $filters): array
	{
		$where = ['1=1'];
		$params = [];

		if (($filters['action'] ?? '') !== '') {
			$where[] = 'ja.action = :action';
			$params['action'] = $filters['action'];
		}
		if (($filters['entite'] ?? '') !== '') {
			$where[] = 'ja.entite = :entite';
			$params['entite'] = $filters['entite'];
		}
		if (($filters['date_from'] ?? '') !== '') {
			$where[] = 'ja.date_action >= :date_from';
			$params['date_from'] = $filters['date_from'] . ' 00:00:00';
		}
		if (($filters['date_to'] ?? '') !== '') {
			$where[] = 'ja.date_action <= :date_to';
			$params['date_to'] = $filters['date_to'] . ' 23:59:59';
		}
		if (($filters['q'] ?? '') !== '') {
			$where[] = '(u.nom LIKE :q1 OR u.prenom LIKE :q2 OR u.email LIKE :q3 OR a.nom LIKE :q4)';
			$params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = '%' . $filters['q'] . '%';
		}

		return [implode(' AND ', $where), $params];
	}

	private static function bindFilters(PDOStatement $stmt, array $params): void
	{
		foreach ($params as $key => $value) {
			$stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
		}
	}
}
