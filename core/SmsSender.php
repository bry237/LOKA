<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Envoi de SMS via un provider configurable. Échoue toujours "doucement" : une erreur
 * réseau ne doit jamais interrompre le flux appelant, seulement laisser une trace FAILED.
 */
final class SmsSender
{
	private const TWILIO_ENDPOINT = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';
	private const VONAGE_ENDPOINT = 'https://rest.nexmo.com/sms/json';

	/**
	 * @return array{envoye: bool, provider: string, erreur: ?string}
	 */
	public static function send(int $idUtilisateur, string $toE164, string $message): array
	{
		$config = require dirname(__DIR__) . '/config/services.php';
		$sms = $config['sms'] ?? [];
		$configured = (string) ($sms['provider'] ?? 'log');
		$twilio = $sms['twilio'] ?? [];
		$vonage = $sms['vonage'] ?? [];

		$provider = 'log';
		if ($configured === 'twilio'
			&& (string) ($twilio['account_sid'] ?? '') !== ''
			&& (string) ($twilio['auth_token'] ?? '') !== ''
			&& (string) ($twilio['from_number'] ?? '') !== ''
		) {
			$provider = 'twilio';
		} elseif ($configured === 'vonage'
			&& (string) ($vonage['api_key'] ?? '') !== ''
			&& (string) ($vonage['api_secret'] ?? '') !== ''
		) {
			$provider = 'vonage';
		}

		$result = match ($provider) {
			'twilio' => self::sendViaTwilio($twilio, $toE164, $message),
			'vonage' => self::sendViaVonage($vonage, $toE164, $message),
			default => ['envoye' => true, 'erreur' => null],
		};

		self::logNotification($idUtilisateur, $message, $result['envoye']);

		return ['envoye' => $result['envoye'], 'provider' => $provider, 'erreur' => $result['erreur']];
	}

	/**
	 * @param array{account_sid?: string, auth_token?: string, from_number?: string} $twilio
	 * @return array{envoye: bool, erreur: ?string}
	 */
	private static function sendViaTwilio(array $twilio, string $toE164, string $message): array
	{
		try {
			$url = sprintf(self::TWILIO_ENDPOINT, $twilio['account_sid']);
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 15,
				CURLOPT_USERPWD => $twilio['account_sid'] . ':' . $twilio['auth_token'],
				CURLOPT_POSTFIELDS => http_build_query([
					'From' => $twilio['from_number'],
					'To' => $toE164,
					'Body' => $message,
				]),
			]);
			$response = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($response === false || $status !== 201) {
				return ['envoye' => false, 'erreur' => "Échec envoi SMS (HTTP $status)."];
			}
			return ['envoye' => true, 'erreur' => null];
		} catch (Throwable $exception) {
			return ['envoye' => false, 'erreur' => $exception->getMessage()];
		}
	}

	/**
	 * @param array{api_key?: string, api_secret?: string, from?: string} $vonage
	 * @return array{envoye: bool, erreur: ?string}
	 */
	private static function sendViaVonage(array $vonage, string $toE164, string $message): array
	{
		try {
			$ch = curl_init(self::VONAGE_ENDPOINT);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 15,
				CURLOPT_POSTFIELDS => http_build_query([
					'api_key' => $vonage['api_key'],
					'api_secret' => $vonage['api_secret'],
					'to' => ltrim($toE164, '+'),
					'from' => $vonage['from'] ?: 'LOKA',
					'text' => $message,
				]),
			]);
			$response = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($response === false || $status !== 200) {
				return ['envoye' => false, 'erreur' => "Échec envoi SMS (HTTP $status)."];
			}
			$body = json_decode((string) $response, true);
			$firstStatus = $body['messages'][0]['status'] ?? null;
			if ($firstStatus !== '0') {
				$errorText = $body['messages'][0]['error-text'] ?? 'Erreur inconnue.';
				return ['envoye' => false, 'erreur' => $errorText];
			}
			return ['envoye' => true, 'erreur' => null];
		} catch (Throwable $exception) {
			return ['envoye' => false, 'erreur' => $exception->getMessage()];
		}
	}

	private static function logNotification(int $idUtilisateur, string $message, bool $envoye): void
	{
		try {
			$stmt = Database::connection()->prepare(
				'INSERT INTO notification (id_utilisateur, titre, message, type, statut, date_envoi)
				 VALUES (:id_utilisateur, :titre, :message, \'SMS\', :statut, NOW())'
			);
			$stmt->execute([
				'id_utilisateur' => $idUtilisateur,
				'titre' => 'SMS',
				'message' => $message,
				'statut' => $envoye ? 'SENT' : 'FAILED',
			]);
			error_log(sprintf('[SMS:%s] utilisateur #%d : %s', $envoye ? 'sent' : 'failed', $idUtilisateur, $message));
		} catch (Throwable $exception) {
			error_log('[SMS] Impossible de journaliser la notification : ' . $exception->getMessage());
		}
	}
}
