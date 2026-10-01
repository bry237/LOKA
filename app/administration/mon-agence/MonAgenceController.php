<?php
declare(strict_types=1);

require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';

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
	 * @return array<string,string> Erreurs de validation (dont '_global' pour la limite de plan), vide si succès.
	 */
	public static function invite(int $idAgence, array $input): array
	{
		$data = [
			'nom' => trim((string) ($input['nom'] ?? '')),
			'prenom' => trim((string) ($input['prenom'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['telephone'] ?? '')),
			'id_role' => (int) ($input['id_role'] ?? 0),
		];

		$errors = [];
		foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom', 'email' => 'L’email', 'telephone' => 'Le téléphone'] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label est requis.";
		}
		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Adresse email invalide.';
		} elseif ($data['email'] !== '' && MonAgenceModel::emailExists($data['email'])) {
			$errors['email'] = 'Cet email est déjà utilisé.';
		}
		if (!in_array($data['id_role'], self::INVITABLE_ROLE_IDS, true)) {
			$errors['id_role'] = 'Rôle invalide.';
		}
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
