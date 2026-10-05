<?php
declare(strict_types=1);

require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';

final class MonAgenceController
{
	private const INVITABLE_ROLE_IDS = [2, 3, 4, 7];

	public static function data(int $idAgence): array
	{
		return [
			'agency' => MonAgenceModel::agency($idAgence),
			'subscription' => MonAgenceModel::subscription($idAgence),
			'kpis' => MonAgenceModel::kpis($idAgence),
			'portfolio' => MonAgenceModel::propertyPortfolio($idAgence),
			'contractsToWatch' => MonAgenceModel::contractsToWatch($idAgence, 5),
			'todayTasks' => MonAgenceModel::todayTasks($idAgence),
			'activity' => MonAgenceModel::recentActivity($idAgence, 8),
		];
	}

	/**
	 * @return array{members: array, roles: array, usage: array}
	 */
	public static function team(int $idAgence): array
	{
		return [
			'members' => MonAgenceModel::team($idAgence),
			'roles' => MonAgenceModel::teamRoles(),
			'usage' => SubscriptionLimiter::usage($idAgence),
		];
	}

	/**
	 * Validation partagée invitation/modification : nom, prénom, email (unicité hors le membre
	 * lui-même en cas de modification), téléphone normalisé en E.164, rôle autorisé pour une agence.
	 *
	 * @return array{0: array<string,string>, 1: array{nom:string,prenom:string,email:string,telephone:string,id_role:int}}
	 */
	private static function validateMemberInput(array $input, ?int $excludeIdUtilisateur = null): array
	{
		$data = [
			'nom' => trim((string) ($input['nom'] ?? '')),
			'prenom' => trim((string) ($input['prenom'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['telephone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['indicatif_pays'] ?? '+33')),
			'id_role' => (int) ($input['id_role'] ?? 0),
		];

		$errors = [];
		foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom', 'email' => 'L’email', 'telephone' => 'Le téléphone'] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label est requis.";
		}
		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Adresse email invalide.';
		} elseif ($data['email'] !== '' && MonAgenceModel::emailExists($data['email'], $excludeIdUtilisateur)) {
			$errors['email'] = 'Cet email est déjà utilisé.';
		}
		if (!isset($errors['telephone'])) {
			// Numéro normalisé en E.164 (indicatif + national) : indispensable pour que le SMS
			// d'invitation et, surtout, l'OTP de vérification au premier login (voir
			// AuthController::handleLogin) partent vers le bon pays plutôt qu'un numéro local
			// incomplet envoyé tel quel au provider SMS.
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) {
				$errors['telephone'] = 'Numéro de téléphone invalide.';
			} else {
				$data['telephone'] = $e164;
			}
		}
		if (!in_array($data['id_role'], self::INVITABLE_ROLE_IDS, true)) {
			$errors['id_role'] = 'Rôle invalide.';
		}

		return [$errors, $data];
	}

	/**
	 * @return array<string,string> Erreurs de validation (dont '_global' pour la limite de plan), vide si succès.
	 */
	public static function invite(int $idAgence, array $input): array
	{
		[$errors, $data] = self::validateMemberInput($input);
		if ($errors) {
			return $errors;
		}

		if (!SubscriptionLimiter::canAddUser($idAgence)) {
			return ['_global' => 'Limite d’utilisateurs de votre plan atteinte. Passez à un plan supérieur pour ajouter des membres.'];
		}

		$result = MonAgenceModel::inviteMember($idAgence, $data);

		$currentUser = Auth::user();
		Logger::audit(
			'CREATE',
			'utilisateur',
			$result['id_utilisateur'],
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			null,
			['nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email']]
		);

		if ($data['telephone']) {
			SmsSender::send(
				$result['id_utilisateur'],
				$data['telephone'],
				"Vous avez été ajouté à l'équipe LOKA. Identifiant : {$data['email']} / mot de passe temporaire : {$result['mot_de_passe_temporaire']}"
			);
		}

		return [];
	}

	public static function memberDetail(int $idAgence, int $idUtilisateur): ?array
	{
		return MonAgenceModel::findMember($idAgence, $idUtilisateur);
	}

	/**
	 * @return array<string,string> Erreurs de validation (dont '_global'), vide si succès.
	 */
	public static function updateMember(int $idAgence, int $idUtilisateur, array $input): array
	{
		$existing = MonAgenceModel::findMember($idAgence, $idUtilisateur);
		if (!$existing) {
			return ['_global' => 'Membre introuvable.'];
		}

		[$errors, $data] = self::validateMemberInput($input, $idUtilisateur);
		if ($errors) {
			return $errors;
		}

		MonAgenceModel::updateMember($idAgence, $idUtilisateur, $data);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'utilisateur',
			$idUtilisateur,
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			['nom' => $existing['nom'], 'prenom' => $existing['prenom'], 'email' => $existing['email'], 'telephone' => $existing['telephone'], 'id_role' => $existing['id_role']],
			$data
		);

		return [];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function deleteMember(int $idAgence, int $idUtilisateur, int $currentUserId): ?string
	{
		if ($idUtilisateur === $currentUserId) {
			return 'Vous ne pouvez pas retirer votre propre compte de l’équipe.';
		}

		$existing = MonAgenceModel::findMember($idAgence, $idUtilisateur);
		if (!$existing) {
			return 'Membre introuvable.';
		}

		MonAgenceModel::deleteMember($idAgence, $idUtilisateur);

		Logger::audit(
			'DELETE',
			'utilisateur',
			$idUtilisateur,
			$currentUserId,
			$idAgence,
			['nom' => $existing['nom'], 'prenom' => $existing['prenom'], 'email' => $existing['email']],
			null
		);

		return null;
	}

	/**
	 * @return array{biens: array, team: array}
	 */
	public static function biensForAssignment(int $idAgence): array
	{
		return [
			'biens' => MonAgenceModel::biensForAssignment($idAgence),
			'team' => MonAgenceModel::team($idAgence),
		];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function assignResponsable(int $idAgence, int $idBien, ?int $idResponsable): ?string
	{
		$ok = MonAgenceModel::assignResponsable($idAgence, $idBien, $idResponsable);
		if (!$ok) {
			return 'Bien introuvable pour votre agence.';
		}

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'bien',
			$idBien,
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			null,
			['id_responsable' => $idResponsable]
		);

		return null;
	}
}
