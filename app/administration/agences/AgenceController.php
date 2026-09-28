<?php
declare(strict_types=1);

require_once __DIR__ . '/AgenceModel.php';
require_once __DIR__ . '/AgenceValidator.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';

final class AgenceController
{
	private const ALLOWED_STATUSES = ['ACTIVE', 'SUSPENDED'];

	public static function list(array $query): array
	{
		$filters = [
			'q' => trim((string) ($query['q'] ?? '')),
			'status' => (string) ($query['status'] ?? ''),
		];
		$page = max(1, (int) ($query['page'] ?? 1));

		return [
			'filters' => $filters,
			'result' => AgenceModel::list($filters, $page),
		];
	}

	public static function detail(int $id): ?array
	{
		return AgenceModel::detail($id);
	}

	public static function forEdit(int $id): ?array
	{
		return AgenceModel::findFull($id);
	}

	/**
	 * @return array{0: array<string,string>, 1: int|null} [erreurs de validation, id créé ou null]
	 */
	public static function create(array $input): array
	{
		[$errors, $data] = AgenceValidator::agence($input);
		if (!$errors && AgenceModel::emailExists($data['email'])) {
			$errors['email'] = 'Cet email est déjà utilisé par une autre agence.';
		}
		if ($errors) {
			return [$errors, null];
		}

		$id = AgenceModel::create($data);

		$currentUser = Auth::user();
		Logger::audit(
			'CREATE',
			'agence',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$id,
			null,
			$data
		);

		return [[], $id];
	}

	/**
	 * @return array<string,string> Erreurs de validation, vide si l'opération a réussi.
	 */
	public static function update(int $id, array $input): array
	{
		$existing = AgenceModel::findFull($id);
		if (!$existing) {
			return ['_global' => 'Agence introuvable.'];
		}

		[$errors, $data] = AgenceValidator::agence($input);
		if (!$errors && AgenceModel::emailExists($data['email'], $id)) {
			$errors['email'] = 'Cet email est déjà utilisé par une autre agence.';
		}
		if ($errors) {
			return $errors;
		}

		AgenceModel::update($id, $data);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'agence',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$id,
			array_intersect_key($existing, $data),
			$data
		);

		return [];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function setStatus(int $id, string $status): ?string
	{
		if (!in_array($status, self::ALLOWED_STATUSES, true)) {
			return 'Statut invalide.';
		}

		$target = AgenceModel::find($id);
		if (!$target) {
			return 'Agence introuvable.';
		}

		AgenceModel::setStatus($id, $status);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'agence',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$currentUser['id_agence'] ?? null,
			['statut' => $target['statut']],
			['statut' => $status]
		);

		return null;
	}
}
