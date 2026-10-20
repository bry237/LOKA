<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthModel.php';
require_once __DIR__ . '/AuthValidator.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/OtpService.php';
require_once dirname(__DIR__, 2) . '/core/PasswordResetService.php';

final class AuthController
{
	public static function handleLogin(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::login($input);
		if ($errors) return [$errors, null];
		$user = Auth::findByCredentials($data['email'], $data['password']);
		if (!$user || in_array($user['statut_utilisateur'], ['INACTIVE', 'LOCKED'], true)) {
			return [['general' => 'Adresse email ou mot de passe incorrect.'], null];
		}
		if ($user['statut_utilisateur'] === 'PENDING') {
			if (!$user['telephone_verifie']) {
				return [[], self::startPhoneVerification((int) $user['id_utilisateur'], $user['telephone'])];
			}
			return [['general' => "Votre compte est en cours de validation par notre équipe. Vous serez notifié dès l'activation."], null];
		}
		if ($user['id_agence'] !== null && !$user['telephone_verifie']) {
			return [[], self::startPhoneVerification((int) $user['id_utilisateur'], $user['telephone'])];
		}
		if (Auth::isAgencyPending($user)) {
			return [['general' => "Votre agence est en cours de validation par notre équipe. Vous serez notifié dès l'activation."], null];
		}
		$remember = !empty($input['remember']);
		$sessionUser = Auth::createSession($user, $remember);
		return [[], self::redirectForRole($sessionUser['role_nom'])];
	}

	public static function handleRegistration(array $input, array $files = []): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::registration($input, $files);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email'])) return [['general' => 'Impossible de créer ce compte avec ces informations.'], null];
		try {
			$userId = AuthModel::registerTenant($data, $files);
		} catch (Throwable $exception) {
			return [['general' => 'Inscription impossible pour le moment.'], null];
		}
		return [[], self::startPhoneVerification($userId, $data['telephone'])];
	}

	public static function handleProprietorRegistration(array $input, array $files = []): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::proprietorRegistration($input, $files);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email'])) return [['general' => 'Cette adresse email est déjà utilisée.'], null];
		try {
			$userId = AuthModel::registerProprietor($data, $files);
		} catch (Throwable $exception) {
			return [['general' => 'Création de l’espace propriétaire impossible pour le moment.'], null];
		}
		return [[], self::startPhoneVerification($userId, $data['telephone'])];
	}

	public static function handleAgencyRegistration(array $input, array $files = []): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		[$errors, $data] = AuthValidator::agencyRegistration($input, $files);
		if ($errors) return [$errors, null];
		if (AuthModel::emailExists($data['email']) || AuthModel::emailExists($data['agence_email'])) return [['general' => 'Cette adresse email est déjà utilisée.'], null];
		try {
			$userId = AuthModel::registerAgency($data, $files);
		} catch (Throwable $exception) {
			return [['general' => 'Création de l’espace agence impossible pour le moment.'], null];
		}
		return [[], self::startPhoneVerification($userId, $data['telephone'])];
	}

	/**
	 * @return array{0: array<string, string>, 1: ?string} [errors, lien de réinitialisation (mode développement uniquement, sinon null)]
	 */
	public static function handleForgotPassword(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) {
			return [['general' => 'Votre session a expiré. Rechargez la page.'], null];
		}
		$email = trim((string) ($input['email'] ?? ''));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return [['email' => 'Adresse email invalide.'], null];
		}

		$result = PasswordResetService::requestReset($email);
		return [[], $result['link']];
	}

	/**
	 * @return array{0: array<string, string>, 1: bool} [errors, succès]
	 */
	public static function handleResetPassword(array $input): array
	{
		if (!Auth::verifyCsrf($input['csrf_token'] ?? null)) {
			return [['general' => 'Votre session a expiré. Rechargez la page.'], false];
		}
		$token = (string) ($input['token'] ?? '');
		$password = (string) ($input['password'] ?? '');
		$confirm = (string) ($input['password_confirm'] ?? '');

		if ($token === '') {
			return [['general' => 'Lien de réinitialisation invalide.'], false];
		}
		if (strlen($password) < 8) {
			return [['password' => 'Le mot de passe doit contenir au moins 8 caractères.'], false];
		}
		if ($password !== $confirm) {
			return [['password_confirm' => 'Les mots de passe ne correspondent pas.'], false];
		}

		$success = PasswordResetService::resetPassword($token, $password);
		if (!$success) {
			return [['general' => 'Ce lien de réinitialisation est invalide ou a expiré.'], false];
		}

		return [[], true];
	}

	private static function startPhoneVerification(int $userId, string $telephoneE164): string
	{
		Session::start();
		OtpService::generateAndSend($userId, $telephoneE164);
		$_SESSION['loka_pending_verification'] = [
			'id_utilisateur' => $userId,
			'telephone' => $telephoneE164,
			'expires_at' => time() + 900,
		];
		return 'verification-code.php';
	}

	public static function redirectForRole(string $role): string
	{
		return match ($role) {
			'Administrateur plateforme' => '../administration/dashboard/index.php',
			'Administrateur agence' => '../administration/mon-agence/index.php',
			'Gestionnaire immobilier' => '../jordan/dashboard/index.php',
			'Comptable' => '../administration/mon-agence/paiements.php',
			'Proprietaire' => '../proprietaires/index.php',
			'Locataire' => '../locataires/index.php',
			'Technicien' => '../administration/mon-agence/interventions.php',
			default => 'connexion.php',
		};
	}
}
