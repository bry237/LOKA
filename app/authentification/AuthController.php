<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthModel.php';
require_once __DIR__ . '/AuthValidator.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';

final class AuthController
{
	public static function handleLogin(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::login($input);
		if ($errors) return [$errors, null];
		$user = Auth::login($data['email'], $data['password']);
		if (!$user) return [['general' => 'Adresse email ou mot de passe incorrect.'], null];
		return [[], self::redirectForRole($user['role_nom'])];
	}

	public static function handleRegistration(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::registration($input);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email'])) return [['general' => 'Impossible de créer ce compte avec ces informations.'], null];
		try {
			AuthModel::registerTenant($data);
		} catch (Throwable $exception) {
			return [['general' => 'Inscription impossible pour le moment.'], null];
		}
		return [[], 'connexion.php?registered=1'];
	}

	public static function handleProprietorRegistration(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::proprietorRegistration($input);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email'])) return [['general' => 'Cette adresse email est déjà utilisée.'], null];
		try {
			AuthModel::registerProprietor($data);
		} catch (Throwable $exception) {
			return [['general' => 'Création de l’espace propriétaire impossible pour le moment.'], null];
		}
		return [[], 'connexion.php?registered=1'];
	}

	public static function handleAgencyRegistration(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::agencyRegistration($input);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email']) || AuthModel::emailExists($data['agence_email'])) return [['general' => 'Cette adresse email est déjà utilisée.'], null];
		try {
			AuthModel::registerAgency($data);
		} catch (Throwable $exception) {
			return [['general' => 'Création de l’espace agence impossible pour le moment.'], null];
		}
		return [[], 'connexion.php?registered=1'];
	}

	public static function redirectForRole(string $role): string
	{
		return match ($role) {
			'Administrateur plateforme' => '../administration/agences/index.php',
			'Administrateur agence' => '../administration/utilisateurs/index.php',
			'Gestionnaire immobilier' => '../biens/index.php',
			'Comptable' => '../paiements/index.php',
			'Proprietaire' => '../proprietaires/index.php',
			'Locataire' => '../locataires/index.php',
			'Technicien' => '../maintenance/index.php',
			default => 'connexion.php',
		};
	}
}
