<?php
declare(strict_types=1);
<<<<<<< HEAD
require_once dirname(__DIR__, 3) . '/core/Database.php';

class UtilisateurModel {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function findAll(string $search = '', string $role = '', string $statut = '', string $agence = ''): array {
        $sql = "SELECT u.*, r.nom as role_nom, a.nom as agence_nom 
                FROM utilisateur u 
                LEFT JOIN role r ON u.id_role = r.id_role 
                LEFT JOIN agence a ON u.id_agence = a.id_agence 
                WHERE u.deleted_at IS NULL";
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if ($role) {
            $sql .= " AND u.id_role = :role";
            $params[':role'] = $role;
        }
        if ($statut) {
            $sql .= " AND u.statut = :statut";
            $params[':statut'] = $statut;
        }
        if ($agence) {
            $sql .= " AND u.id_agence = :agence";
            $params[':agence'] = $agence;
        }
        
        $sql .= " ORDER BY u.nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nom as role_nom, a.nom as agence_nom 
            FROM utilisateur u 
            LEFT JOIN role r ON u.id_role = r.id_role 
            LEFT JOIN agence a ON u.id_agence = a.id_agence 
            WHERE u.id_utilisateur = :id AND u.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $sql = "INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut, created_at) 
                VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, :statut, NOW())";
        $stmt = $this->db->prepare($sql);
        $hash = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        $stmt->execute([
            ':id_agence' => !empty($data['id_agence']) ? $data['id_agence'] : null,
            ':id_role' => !empty($data['id_role']) ? $data['id_role'] : null,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':mot_de_passe' => $hash,
            ':telephone' => $data['telephone'] ?? null,
            ':statut' => $data['statut'] ?? 'ACTIVE'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data) {
        $sql = "UPDATE utilisateur 
                SET id_agence = :id_agence, id_role = :id_role, nom = :nom, prenom = :prenom, 
                    email = :email, telephone = :telephone, updated_at = NOW() 
                WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_agence' => !empty($data['id_agence']) ? $data['id_agence'] : null,
            ':id_role' => !empty($data['id_role']) ? $data['id_role'] : null,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':id' => $id
        ]);
    }
    
    public function updateStatus($id, string $statut) {
        $sql = "UPDATE utilisateur SET statut = :statut, updated_at = NOW() WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }
    
    public function getStats(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actifs
            FROM utilisateur WHERE deleted_at IS NULL
        ");
        return $stmt->fetch();
    }
=======

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class UtilisateurModel
{
	private const PAGE_SIZE = 10;

	public static function roles(): array
	{
		return Database::connection()->query('SELECT id_role, nom FROM role ORDER BY id_role')->fetchAll();
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_utilisateur, nom, prenom, email, telephone, telephone_verifie, statut, id_role, id_agence
			 FROM utilisateur WHERE id_utilisateur = :id AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/**
	 * @param array{q?: string, role?: string, status?: string} $filters
	 */
	public static function list(array $filters, int $page): array
	{
		$db = Database::connection();
		[$whereSql, $params] = self::buildWhere($filters);

		$countStmt = $db->prepare("SELECT COUNT(*) FROM utilisateur u WHERE {$whereSql}");
		self::bindFilters($countStmt, $params);
		$countStmt->execute();
		$total = (int) $countStmt->fetchColumn();

		$totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
		$page = min(max(1, $page), $totalPages);
		$offset = ($page - 1) * self::PAGE_SIZE;

		$stmt = $db->prepare(
			"SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.telephone_verifie, u.statut, u.derniere_connexion, u.created_at, u.id_role,
					r.nom AS role_nom, a.nom AS agence_nom
			 FROM utilisateur u
			 INNER JOIN role r ON r.id_role = u.id_role
			 LEFT JOIN agence a ON a.id_agence = u.id_agence
			 WHERE {$whereSql}
			 ORDER BY u.created_at DESC
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
			'SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.telephone_verifie, u.statut, u.derniere_connexion,
					u.created_at, u.updated_at, u.id_role, u.id_agence,
					r.nom AS role_nom, a.nom AS agence_nom
			 FROM utilisateur u
			 INNER JOIN role r ON r.id_role = u.id_role
			 LEFT JOIN agence a ON a.id_agence = u.id_agence
			 WHERE u.id_utilisateur = :id AND u.deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function setStatus(int $id, string $status): void
	{
		$stmt = Database::connection()->prepare('UPDATE utilisateur SET statut = :statut WHERE id_utilisateur = :id');
		$stmt->execute(['statut' => $status, 'id' => $id]);
	}

	private static function buildWhere(array $filters): array
	{
		$where = ['u.deleted_at IS NULL'];
		$params = [];

		if (($filters['q'] ?? '') !== '') {
			// Des placeholders nommés distincts sont nécessaires : avec PDO::ATTR_EMULATE_PREPARES=false,
			// MySQL ne supporte pas de réutiliser le même paramètre nommé plusieurs fois dans une requête.
			$where[] = '(u.nom LIKE :q1 OR u.prenom LIKE :q2 OR u.email LIKE :q3)';
			$params['q1'] = $params['q2'] = $params['q3'] = '%' . $filters['q'] . '%';
		}
		if (($filters['role'] ?? '') !== '') {
			$where[] = 'u.id_role = :role';
			$params['role'] = (int) $filters['role'];
		}
		if (($filters['status'] ?? '') !== '') {
			$where[] = 'u.statut = :status';
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
		if (isset($params['role'])) $stmt->bindValue(':role', $params['role'], PDO::PARAM_INT);
		if (isset($params['status'])) $stmt->bindValue(':status', $params['status'], PDO::PARAM_STR);
	}
>>>>>>> main
}
