<?php
declare(strict_types=1);

require_once __DIR__ . '/UtilisateurModel.php';
require_once __DIR__ . '/DocumentUtilisateurModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';

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

	public static function detail(int $id): ?array
	{
		$detail = UtilisateurModel::detail($id);
		if ($detail) {
			$detail['documents'] = DocumentUtilisateurModel::listForUtilisateur($id);
		}
		return $detail;
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function setDocumentStatus(int $idDocument, string $statut, ?string $motif = null): ?string
	{
		if (!in_array($statut, ['VALIDE', 'REJETE'], true)) {
			return 'Statut invalide.';
		}
		$document = DocumentUtilisateurModel::find($idDocument);
		if (!$document) {
			return 'Document introuvable.';
		}

		$currentUser = Auth::user();
		DocumentUtilisateurModel::setStatus($idDocument, $statut, $currentUser['id_utilisateur'] ?? null, $motif);

		Logger::audit(
			'UPDATE',
			'document_utilisateur',
			$idDocument,
			$currentUser['id_utilisateur'] ?? null,
			$currentUser['id_agence'] ?? null,
			['statut_verification' => $document['statut_verification']],
			['statut_verification' => $statut]
		);

		return null;
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

		$wasPending = $target['statut'] === 'PENDING';
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

		if ($wasPending && $status === 'ACTIVE' && $target['telephone']) {
			SmsSender::send($id, $target['telephone'], 'Votre compte LOKA est validé, vous pouvez vous connecter.');
		}

		return null;
	}
}
