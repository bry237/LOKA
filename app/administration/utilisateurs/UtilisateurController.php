<?php
declare(strict_types=1);

require_once __DIR__ . '/UtilisateurModel.php';
require_once __DIR__ . '/UtilisateurValidator.php';
require_once __DIR__ . '/DocumentUtilisateurModel.php';
require_once dirname(__DIR__) . '/agences/AgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';

final class UtilisateurController
{
	/** Données communes aux formulaires "Ajouter"/"Modifier" (rôles attribuables, agences). */
	public static function formOptions(): array
	{
		return [
			'roles' => UtilisateurModel::creatableRoles(),
			'agences' => AgenceModel::selectOptions(),
		];
	}

	public static function forEdit(int $id): ?array
	{
		return UtilisateurModel::find($id);
	}

	/**
	 * @return array{0: array<string,string>, 1: array{id_utilisateur:int,mot_de_passe_temporaire:string}|null}
	 */
	public static function create(array $input): array
	{
		[$errors, $data] = UtilisateurValidator::utilisateur($input);
		if (!$errors && UtilisateurModel::emailExists($data['email'])) {
			$errors['email'] = 'Cet email est déjà utilisé par un autre compte.';
		}
		if (!$errors && $data['id_agence'] !== null && !AgenceModel::find($data['id_agence'])) {
			$errors['id_agence'] = 'Agence introuvable.';
		}
		if ($errors) {
			return [$errors, null];
		}

		$created = UtilisateurModel::create($data);

		$currentUser = Auth::user();
		Logger::audit(
			'CREATE',
			'utilisateur',
			$created['id_utilisateur'],
			$currentUser['id_utilisateur'] ?? null,
			$data['id_agence'],
			null,
			['nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'], 'id_role' => $data['id_role']]
		);

		if ($data['telephone']) {
			SmsSender::send(
				$created['id_utilisateur'],
				$data['telephone'],
				"Un compte LOKA a été créé pour vous. Identifiant : {$data['email']} / mot de passe temporaire : {$created['mot_de_passe_temporaire']}"
			);
		}

		return [[], $created];
	}

	/**
	 * @return array<string,string> Erreurs de validation (dont '_global'), vide si succès.
	 */
	public static function update(int $id, array $input): array
	{
		$existing = UtilisateurModel::find($id);
		if (!$existing) {
			return ['_global' => 'Utilisateur introuvable.'];
		}

		$currentUser = Auth::user();
		$isSelf = $currentUser && (int) $currentUser['id_utilisateur'] === $id;
		if ($isSelf && (int) ($input['id_role'] ?? 0) !== (int) $existing['id_role']) {
			return ['_global' => 'Vous ne pouvez pas changer votre propre rôle.'];
		}

		[$errors, $data] = UtilisateurValidator::utilisateur($input, $id);
		if (!$errors && UtilisateurModel::emailExists($data['email'], $id)) {
			$errors['email'] = 'Cet email est déjà utilisé par un autre compte.';
		}
		if (!$errors && $data['id_agence'] !== null && !AgenceModel::find($data['id_agence'])) {
			$errors['id_agence'] = 'Agence introuvable.';
		}
		if ($errors) {
			return $errors;
		}

		UtilisateurModel::update($id, $data);

		Logger::audit(
			'UPDATE',
			'utilisateur',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$data['id_agence'],
			['nom' => $existing['nom'], 'prenom' => $existing['prenom'], 'email' => $existing['email'], 'id_role' => $existing['id_role']],
			['nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'], 'id_role' => $data['id_role']]
		);

		return [];
	}

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

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function delete(int $id): ?string
	{
		$currentUser = Auth::user();

		if ($currentUser && (int) $currentUser['id_utilisateur'] === $id) {
			return 'Vous ne pouvez pas supprimer votre propre compte.';
		}

		$target = UtilisateurModel::find($id);
		if (!$target) {
			return 'Utilisateur introuvable.';
		}

		UtilisateurModel::delete($id);

		Logger::audit(
			'DELETE',
			'utilisateur',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			$target['id_agence'],
			['nom' => $target['nom'], 'prenom' => $target['prenom'], 'email' => $target['email']],
			null
		);

		return null;
	}
}
