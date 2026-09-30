<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SmsSender.php';

/**
 * Génération et vérification de codes OTP pour la validation d'un numéro de téléphone
 * à l'inscription (locataire, propriétaire, responsable d'agence).
 */
final class OtpService
{
	private const CODE_LENGTH = 6;
	private const TTL_MINUTES = 10;
	private const MAX_ATTEMPTS = 5;
	private const RESEND_COOLDOWN_SECONDS = 60;

	/**
	 * @return array{ok: bool, error: ?string, retry_after: ?int, demo_code: ?string}
	 */
	public static function generateAndSend(int $idUtilisateur, string $telephoneE164): array
	{
		$db = Database::connection();

		$last = $db->prepare(
			'SELECT created_at FROM otp_code WHERE id_utilisateur = :id ORDER BY created_at DESC LIMIT 1'
		);
		$last->execute(['id' => $idUtilisateur]);
		$lastCreatedAt = $last->fetchColumn();
		if ($lastCreatedAt) {
			$elapsed = time() - (new DateTimeImmutable((string) $lastCreatedAt))->getTimestamp();
			if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
				return ['ok' => false, 'error' => 'COOLDOWN', 'retry_after' => self::RESEND_COOLDOWN_SECONDS - $elapsed, 'demo_code' => null];
			}
		}

		$code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
		$expiration = (new DateTimeImmutable('+' . self::TTL_MINUTES . ' minutes'))->format('Y-m-d H:i:s');

		$insert = $db->prepare(
			'INSERT INTO otp_code (id_utilisateur, code_hash, tentatives_max, date_expiration)
			 VALUES (:id_utilisateur, :code_hash, :tentatives_max, :date_expiration)'
		);
		$insert->execute([
			'id_utilisateur' => $idUtilisateur,
			'code_hash' => hash('sha256', $code),
			'tentatives_max' => self::MAX_ATTEMPTS,
			'date_expiration' => $expiration,
		]);

		$message = "Bienvenue sur LOKA. Votre code de vérification est {$code} (valable " . self::TTL_MINUTES
			. ' min). Une fois validé, votre demande sera examinée par notre équipe avant activation.';
		$config = require dirname(__DIR__) . '/config/services.php';
		$isDemo = ($config['sms']['provider'] ?? 'log') === 'log';

		SmsSender::send($idUtilisateur, $telephoneE164, $message);

		return ['ok' => true, 'error' => null, 'retry_after' => null, 'demo_code' => $isDemo ? $code : null];
	}

	/**
	 * @return array{ok: bool, error: ?string}
	 */
	public static function verify(int $idUtilisateur, string $submittedCode): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			'SELECT id_otp, code_hash, tentatives, tentatives_max, date_expiration
			 FROM otp_code
			 WHERE id_utilisateur = :id AND statut = \'PENDING\'
			 ORDER BY created_at DESC LIMIT 1'
		);
		$stmt->execute(['id' => $idUtilisateur]);
		$otp = $stmt->fetch();

		if (!$otp) {
			return ['ok' => false, 'error' => 'NOT_FOUND'];
		}

		if (new DateTimeImmutable((string) $otp['date_expiration']) < new DateTimeImmutable()) {
			$db->prepare('UPDATE otp_code SET statut = \'EXPIRED\' WHERE id_otp = :id')->execute(['id' => $otp['id_otp']]);
			return ['ok' => false, 'error' => 'EXPIRED'];
		}

		if ((int) $otp['tentatives'] >= (int) $otp['tentatives_max']) {
			$db->prepare('UPDATE otp_code SET statut = \'FAILED\' WHERE id_otp = :id')->execute(['id' => $otp['id_otp']]);
			return ['ok' => false, 'error' => 'LOCKED'];
		}

		$db->prepare('UPDATE otp_code SET tentatives = tentatives + 1 WHERE id_otp = :id')->execute(['id' => $otp['id_otp']]);

		if (!hash_equals((string) $otp['code_hash'], hash('sha256', $submittedCode))) {
			$locked = (int) $otp['tentatives'] + 1 >= (int) $otp['tentatives_max'];
			if ($locked) {
				$db->prepare('UPDATE otp_code SET statut = \'FAILED\' WHERE id_otp = :id')->execute(['id' => $otp['id_otp']]);
			}
			return ['ok' => false, 'error' => $locked ? 'LOCKED' : 'INVALID'];
		}

		$db->prepare('UPDATE otp_code SET statut = \'VERIFIED\', date_verification = NOW() WHERE id_otp = :id')
			->execute(['id' => $otp['id_otp']]);
		$db->prepare('UPDATE utilisateur SET telephone_verifie = 1 WHERE id_utilisateur = :id')
			->execute(['id' => $idUtilisateur]);

		return ['ok' => true, 'error' => null];
	}
}
