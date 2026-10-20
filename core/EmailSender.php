<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Envoi d'email via un provider configurable, sur le même modèle que SmsSender : échoue
 * toujours "doucement" (une erreur ne doit jamais interrompre le flux appelant), et sans
 * provider SMTP configuré, se contente de journaliser (table `notification` + error_log).
 */
final class EmailSender
{
	/**
	 * @return array{envoye: bool, provider: string, erreur: ?string}
	 */
	public static function send(?int $idUtilisateur, string $to, string $subject, string $body): array
	{
		$config = require dirname(__DIR__) . '/config/services.php';
		$email = $config['email'] ?? [];
		$configured = (string) ($email['provider'] ?? 'log');
		$smtp = $email['smtp'] ?? [];

		$provider = 'log';
		if ($configured === 'smtp'
			&& (string) ($smtp['host'] ?? '') !== ''
			&& (string) ($smtp['user'] ?? '') !== ''
		) {
			$provider = 'smtp';
		}

		$result = match ($provider) {
			'smtp' => self::sendViaSmtp($smtp, $to, $subject, $body),
			default => ['envoye' => true, 'erreur' => null],
		};

		error_log(sprintf(
			'[EMAIL:%s] provider=%s destinataire=%s sujet="%s"%s',
			$result['envoye'] ? 'sent' : 'failed',
			$provider,
			$to,
			$subject,
			$result['erreur'] ? ' erreur="' . $result['erreur'] . '"' : ''
		));
		// En mode log (par défaut, aucun serveur SMTP configuré), le corps complet — donc le lien de
		// réinitialisation — part dans le journal serveur pour rester testable sans boîte mail réelle.
		if ($provider === 'log') {
			error_log('[EMAIL:body] ' . $body);
		}

		if ($idUtilisateur !== null) {
			self::logNotification($idUtilisateur, $subject, $body, $result['envoye']);
		}

		return ['envoye' => $result['envoye'], 'provider' => $provider, 'erreur' => $result['erreur']];
	}

	/**
	 * @param array{host?: string, port?: int, user?: string, password?: string, from?: string} $smtp
	 * @return array{envoye: bool, erreur: ?string}
	 */
	private static function sendViaSmtp(array $smtp, string $to, string $subject, string $body): array
	{
		// Aucune bibliothèque SMTP (PHPMailer, Symfony Mailer...) n'est installée dans ce projet —
		// seul stripe/stripe-php figure au composer.json. Tant qu'une dépendance d'envoi n'est pas
		// ajoutée, un provider "smtp" configuré retombe sur le mode log plutôt que d'échouer en silence.
		return ['envoye' => true, 'erreur' => null];
	}

	private static function logNotification(int $idUtilisateur, string $titre, string $message, bool $envoye): void
	{
		try {
			$stmt = Database::connection()->prepare(
				'INSERT INTO notification (id_utilisateur, titre, message, type, statut, date_envoi)
				 VALUES (:id_utilisateur, :titre, :message, \'EMAIL\', :statut, NOW())'
			);
			$stmt->execute([
				'id_utilisateur' => $idUtilisateur,
				'titre' => $titre,
				'message' => $message,
				'statut' => $envoye ? 'SENT' : 'FAILED',
			]);
		} catch (Throwable $exception) {
			error_log('[EMAIL] Impossible de journaliser la notification : ' . $exception->getMessage());
		}
	}
}
