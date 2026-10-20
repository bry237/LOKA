<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EmailSender.php';

/**
 * Réinitialisation de mot de passe par lien à usage unique, envoyé par email. Même structure
 * que OtpService (génération + vérification d'un secret à durée de vie limitée), mais un
 * token web à usage unique plutôt qu'un code à 6 chiffres saisi manuellement.
 */
final class PasswordResetService
{
	private const TTL_MINUTES = 30;
	private const RESEND_COOLDOWN_SECONDS = 60;

	/**
	 * Ne révèle jamais si l'email existe : retourne toujours ok=true côté appelant. Le lien
	 * n'est renvoyé que pour un usage local (affichage en mode développement) — jamais à exposer
	 * tel quel si un vrai provider SMTP est un jour branché.
	 *
	 * @return array{ok: bool, link: ?string}
	 */
	public static function requestReset(string $email): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT id_utilisateur, nom, prenom, email FROM utilisateur
			 WHERE LOWER(email) = LOWER(:email) AND statut = 'ACTIVE' AND deleted_at IS NULL LIMIT 1"
		);
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch();

		if (!$user) {
			return ['ok' => true, 'link' => null];
		}

		$last = $db->prepare(
			'SELECT created_at FROM password_reset WHERE id_utilisateur = :id ORDER BY created_at DESC LIMIT 1'
		);
		$last->execute(['id' => $user['id_utilisateur']]);
		$lastCreatedAt = $last->fetchColumn();
		if ($lastCreatedAt) {
			$elapsed = time() - (new DateTimeImmutable((string) $lastCreatedAt))->getTimestamp();
			if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
				return ['ok' => true, 'link' => null];
			}
		}

		$token = bin2hex(random_bytes(32));
		$expiration = (new DateTimeImmutable('+' . self::TTL_MINUTES . ' minutes'))->format('Y-m-d H:i:s');

		$insert = $db->prepare(
			'INSERT INTO password_reset (id_utilisateur, token_hash, date_expiration, adresse_ip)
			 VALUES (:id_utilisateur, :token_hash, :date_expiration, :adresse_ip)'
		);
		$insert->execute([
			'id_utilisateur' => $user['id_utilisateur'],
			'token_hash' => hash('sha256', $token),
			'date_expiration' => $expiration,
			'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
		]);

		$link = self::buildResetLink($token);
		$body = "Bonjour {$user['prenom']},\n\n"
			. "Vous avez demandé la réinitialisation de votre mot de passe LOKA.\n"
			. "Ce lien est valable " . self::TTL_MINUTES . " minutes :\n{$link}\n\n"
			. "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.";

		EmailSender::send((int) $user['id_utilisateur'], $user['email'], 'Réinitialisation de votre mot de passe LOKA', $body);

		return ['ok' => true, 'link' => $link];
	}

	/**
	 * @return array{id_utilisateur: int, nom: string, prenom: string, email: string}|null
	 */
	public static function verifyToken(string $token): ?array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			'SELECT pr.id_password_reset, pr.date_expiration, pr.date_utilisation,
					u.id_utilisateur, u.nom, u.prenom, u.email
			 FROM password_reset pr
			 INNER JOIN utilisateur u ON u.id_utilisateur = pr.id_utilisateur
			 WHERE pr.token_hash = :token_hash
			 ORDER BY pr.created_at DESC LIMIT 1'
		);
		$stmt->execute(['token_hash' => hash('sha256', $token)]);
		$reset = $stmt->fetch();

		if (!$reset || $reset['date_utilisation'] !== null) {
			return null;
		}
		if (new DateTimeImmutable((string) $reset['date_expiration']) < new DateTimeImmutable()) {
			return null;
		}

		return [
			'id_utilisateur' => (int) $reset['id_utilisateur'],
			'nom' => $reset['nom'],
			'prenom' => $reset['prenom'],
			'email' => $reset['email'],
		];
	}

	public static function resetPassword(string $token, string $newPassword): bool
	{
		$user = self::verifyToken($token);
		if (!$user) {
			return false;
		}

		$db = Database::connection();
		$db->beginTransaction();
		try {
			$update = $db->prepare('UPDATE utilisateur SET mot_de_passe = :hash WHERE id_utilisateur = :id');
			$update->execute([
				'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
				'id' => $user['id_utilisateur'],
			]);

			$markUsed = $db->prepare(
				'UPDATE password_reset SET date_utilisation = NOW()
				 WHERE token_hash = :token_hash AND date_utilisation IS NULL'
			);
			$markUsed->execute(['token_hash' => hash('sha256', $token)]);

			// Un changement de mot de passe invalide toute session déjà ouverte (y compris sur
			// d'autres appareils) — comportement attendu si le mot de passe a pu être compromis.
			$invalidateSessions = $db->prepare('DELETE FROM session_utilisateur WHERE id_utilisateur = :id');
			$invalidateSessions->execute(['id' => $user['id_utilisateur']]);

			$db->commit();
			return true;
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}
	}

	private static function buildResetLink(string $token): string
	{
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		return "{$scheme}://{$host}/LOKA/app/authentification/nouveau-mot-de-passe.php?token={$token}";
	}
}
