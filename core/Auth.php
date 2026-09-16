<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

final class Auth
{
	private const SESSION_KEY = 'loka_auth';
	private const CSRF_KEY = 'loka_csrf';

	public static function csrfToken(): string
	{
		Session::start();
		if (empty($_SESSION[self::CSRF_KEY])) {
			$_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
		}
		return $_SESSION[self::CSRF_KEY];
	}

	public static function verifyCsrf(?string $token): bool
	{
		Session::start();
		return is_string($token) && hash_equals((string) ($_SESSION[self::CSRF_KEY] ?? ''), $token);
	}

	public static function login(string $email, string $password): ?array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT u.id_utilisateur, u.id_agence, u.nom, u.prenom, u.email, u.mot_de_passe,
					u.statut AS statut_utilisateur, r.id_role, r.nom AS role_nom
			 FROM utilisateur u
			 INNER JOIN role r ON r.id_role = u.id_role
			 WHERE LOWER(u.email) = LOWER(:email)
				AND u.deleted_at IS NULL
			 LIMIT 1"
		);
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch();

		if (!$user || $user['statut_utilisateur'] !== 'ACTIVE' || !password_verify($password, $user['mot_de_passe'])) {
			return null;
		}

		Session::regenerate();
		$token = bin2hex(random_bytes(32));
		$expiration = (new DateTimeImmutable('+8 hours'))->format('Y-m-d H:i:s');
		$insert = $db->prepare(
			'INSERT INTO session_utilisateur (id_utilisateur, token_hash, adresse_ip, user_agent, date_expiration, date_derniere_activite)
			 VALUES (:id_utilisateur, :token_hash, :adresse_ip, :user_agent, :date_expiration, NOW())'
		);
		$insert->execute([
			'id_utilisateur' => $user['id_utilisateur'],
			'token_hash' => hash('sha256', $token),
			'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
			'date_expiration' => $expiration,
		]);

		$update = $db->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = :id');
		$update->execute(['id' => $user['id_utilisateur']]);

		$_SESSION[self::SESSION_KEY] = [
			'id_utilisateur' => (int) $user['id_utilisateur'],
			'id_agence' => $user['id_agence'] !== null ? (int) $user['id_agence'] : null,
			'id_role' => (int) $user['id_role'],
			'role_nom' => $user['role_nom'],
			'token' => $token,
		];

		return self::user();
	}

	public static function user(): ?array
	{
		Session::start();
		$session = $_SESSION[self::SESSION_KEY] ?? null;
		if (!$session) {
			return null;
		}

		$stmt = Database::connection()->prepare(
			"SELECT u.id_utilisateur, u.id_agence, u.nom, u.prenom, u.email, u.statut AS statut_utilisateur,
					r.id_role, r.nom AS role_nom, s.date_expiration
			 FROM session_utilisateur s
			 INNER JOIN utilisateur u ON u.id_utilisateur = s.id_utilisateur
			 INNER JOIN role r ON r.id_role = u.id_role
			 WHERE s.token_hash = :token_hash AND s.date_expiration > NOW()
				AND u.statut = 'ACTIVE' AND u.deleted_at IS NULL
			 LIMIT 1"
		);
		$stmt->execute(['token_hash' => hash('sha256', $session['token'])]);
		$user = $stmt->fetch();

		if (!$user) {
			self::logout();
			return null;
		}

		return $user;
	}

	public static function logout(): void
	{
		Session::start();
		$session = $_SESSION[self::SESSION_KEY] ?? null;
		if ($session) {
			$stmt = Database::connection()->prepare('DELETE FROM session_utilisateur WHERE token_hash = :token_hash');
			$stmt->execute(['token_hash' => hash('sha256', $session['token'])]);
		}
		Session::destroy();
	}

	public static function requireLogin(): array
	{
		$user = self::user();
		if (!$user) {
			header('Location: /LOKA/app/authentification/connexion.php');
			exit;
		}
		return $user;
	}

	public static function requireRoles(array $roles): array
	{
		$user = self::requireLogin();
		if (!in_array($user['role_nom'], $roles, true)) {
			http_response_code(403);
			exit('Accès interdit.');
		}
		return $user;
	}
}
