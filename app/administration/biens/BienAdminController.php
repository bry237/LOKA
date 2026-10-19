<?php
declare(strict_types=1);

require_once __DIR__ . '/BienAdminModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';

final class BienAdminController
{
	private const ALLOWED_STATUSES = ['CREATED', 'AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'ARCHIVED'];

	public static function list(array $query): array
	{
		$filters = [
			'q' => trim((string) ($query['q'] ?? '')),
			'statut' => (string) ($query['statut'] ?? ''),
			'id_agence' => (string) ($query['id_agence'] ?? ''),
			'id_type_bien' => (string) ($query['id_type_bien'] ?? ''),
		];
		$page = max(1, (int) ($query['page'] ?? 1));

		return [
			'filters' => $filters,
			'result' => BienAdminModel::list($filters, $page),
			'stats' => BienAdminModel::stats(),
			'types' => BienAdminModel::typeOptions(),
			'agences' => BienAdminModel::agenceOptions(),
		];
	}

	public static function detail(int $id): ?array
	{
		return BienAdminModel::find($id);
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function setStatus(int $id, string $statut, ?string $motif = null): ?string
	{
		if (!in_array($statut, self::ALLOWED_STATUSES, true)) {
			return 'Statut invalide.';
		}

		$target = BienAdminModel::find($id);
		if (!$target) {
			return 'Bien introuvable.';
		}

		BienAdminModel::setStatus($id, $statut);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'bien',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$target['id_agence'],
			['statut' => $target['statut']],
			array_filter(['statut' => $statut, 'motif' => $motif], static fn ($v) => $v !== null && $v !== '')
		);

		return null;
	}
}
