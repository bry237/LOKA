<?php
declare(strict_types=1);

require_once __DIR__ . '/UtilisateurModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';

final class UtilisateurController
{
	public static function list(array $query): array
	{
		$filters = [
			'q' => trim((string) ($query['q'] ?? '')),
			'role' => (string) ($query['role'] ?? ''),
			'status' => (string) ($query['status'] ?? ''),
		];
		$page = max(1, (int) ($query['page'] ?? 1));

		return [
			'filters' => $filters,
			'roles' => UtilisateurModel::roles(),
			'result' => UtilisateurModel::list($filters, $page),
		];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function setStatus(int $id, string $status): ?string
	{
		$currentUser = Auth::user();

		if ($currentUser && (int) $currentUser['id_utilisateur'] === $id) {
			return 'Vous ne pouvez pas modifier le statut de votre propre compte.';
		}

		$target = UtilisateurModel::find($id);
		if (!$target) {
			return 'Utilisateur introuvable.';
		}

		UtilisateurModel::setStatus($id, $status);

		Logger::audit(
			'UPDATE',
			'utilisateur',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$currentUser['id_agence'] ?? null,
			['statut' => $target['statut']],
			['statut' => $status]
		);

		return null;
	}
}
