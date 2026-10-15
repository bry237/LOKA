<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

/**
 * Les 7 rôles sont fixes : leur nom est vérifié littéralement dans tout le code
 * (Authorization::requireRole(...)) donc aucune création/suppression/renommage n'est possible
 * depuis l'admin — seule la description et les permissions informatives (role_permission) le sont.
 */
final class RoleModel
{
	public static function all(): array
	{
		return Database::connection()->query(
			"SELECT r.id_role, r.nom, r.description,
					(SELECT COUNT(*) FROM role_permission rp WHERE rp.id_role = r.id_role) AS nb_permissions,
					(SELECT COUNT(*) FROM utilisateur u WHERE u.id_role = r.id_role AND u.deleted_at IS NULL) AS nb_utilisateurs
			 FROM role r
			 ORDER BY r.id_role"
		)->fetchAll();
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare('SELECT id_role, nom, description FROM role WHERE id_role = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function updateDescription(int $id, string $description): void
	{
		$stmt = Database::connection()->prepare('UPDATE role SET description = :description WHERE id_role = :id');
		$stmt->execute(['description' => $description, 'id' => $id]);
	}

	/** Toutes les permissions, chacune indiquant si le rôle donné la possède. */
	public static function permissionsFor(int $idRole): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT p.id_permission, p.nom, p.code, p.description,
					(rp.id_role IS NOT NULL) AS accordee
			 FROM permission p
			 LEFT JOIN role_permission rp ON rp.id_permission = p.id_permission AND rp.id_role = :id_role
			 ORDER BY p.id_permission'
		);
		$stmt->execute(['id_role' => $idRole]);
		return array_map(static function (array $row): array {
			$row['accordee'] = (bool) $row['accordee'];
			return $row;
		}, $stmt->fetchAll());
	}

	/** @param int[] $idPermissions Permissions à accorder au rôle ; toutes les autres sont retirées. */
	public static function setPermissions(int $idRole, array $idPermissions): void
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$db->prepare('DELETE FROM role_permission WHERE id_role = :id_role')->execute(['id_role' => $idRole]);
			if ($idPermissions) {
				$stmt = $db->prepare('INSERT INTO role_permission (id_role, id_permission) VALUES (:id_role, :id_permission)');
				foreach (array_unique($idPermissions) as $idPermission) {
					$stmt->execute(['id_role' => $idRole, 'id_permission' => (int) $idPermission]);
				}
			}
			$db->commit();
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}
}
