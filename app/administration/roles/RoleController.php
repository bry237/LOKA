<?php
declare(strict_types=1);

require_once __DIR__ . '/RoleModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';

final class RoleController
{
	public static function list(): array
	{
		return RoleModel::all();
	}

	public static function detail(int $id): ?array
	{
		$role = RoleModel::find($id);
		if (!$role) {
			return null;
		}
		$role['permissions'] = RoleModel::permissionsFor($id);
		return $role;
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function updateDescription(int $id, string $description): ?string
	{
		$role = RoleModel::find($id);
		if (!$role) {
			return 'Rôle introuvable.';
		}

		$description = trim($description);
		RoleModel::updateDescription($id, $description);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'role',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			null,
			['description' => $role['description']],
			['description' => $description]
		);

		return null;
	}

	/**
	 * @param string[] $idPermissions
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function updatePermissions(int $id, array $idPermissions): ?string
	{
		$role = RoleModel::find($id);
		if (!$role) {
			return 'Rôle introuvable.';
		}

		$before = array_values(array_map(
			static fn (array $p): int => (int) $p['id_permission'],
			array_filter(RoleModel::permissionsFor($id), static fn (array $p): bool => $p['accordee'])
		));
		$after = array_map('intval', $idPermissions);
		sort($before);
		sort($after);

		RoleModel::setPermissions($id, $after);

		if ($before !== $after) {
			$currentUser = Auth::user();
			Logger::audit(
				'UPDATE',
				'role_permission',
				$id,
				$currentUser['id_utilisateur'] ?? null,
				null,
				['permissions' => $before],
				['permissions' => $after]
			);
		}

		return null;
	}
}
