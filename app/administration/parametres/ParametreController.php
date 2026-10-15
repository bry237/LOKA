<?php
declare(strict_types=1);

require_once __DIR__ . '/ParametreModel.php';
require_once dirname(__DIR__) . '/abonnements/AbonnementModel.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';

final class ParametreController
{
	public const CLE_PLAN_DEFAUT = 'agence.plan_defaut_id';
	public const CLE_SMS_VALIDATION_AGENCE = 'notifications.sms_validation_agence';

	/** Lecture : chaque agence créée manuellement et sans abonnement reçoit ce plan (voir AgenceController::create). */
	public static function planDefautAgence(): ?int
	{
		$value = ParametreModel::get(self::CLE_PLAN_DEFAUT);
		return $value !== null && $value !== '' ? (int) $value : null;
	}

	/** Lecture : faut-il notifier l'agence par SMS lors de la validation de son inscription (voir AgenceController::setStatus). */
	public static function smsValidationAgenceActif(): bool
	{
		$value = ParametreModel::get(self::CLE_SMS_VALIDATION_AGENCE, '1');
		return $value === '1';
	}

	public static function overview(): array
	{
		$db = Database::connection();

		$agences = $db->query(
			"SELECT statut, COUNT(*) AS n FROM agence WHERE deleted_at IS NULL GROUP BY statut"
		)->fetchAll(PDO::FETCH_KEY_PAIR);
		$utilisateursActifs = (int) $db->query("SELECT COUNT(*) FROM utilisateur WHERE statut = 'ACTIVE' AND deleted_at IS NULL")->fetchColumn();
		$abonnementsActifs = (int) $db->query("SELECT COUNT(*) FROM agence_abonnement WHERE statut = 'ACTIVE'")->fetchColumn();

		$services = require dirname(__DIR__, 3) . '/config/services.php';
		$stripeKey = (string) ($services['stripe']['publishable_key'] ?? '');
		$smsProvider = (string) ($services['sms']['provider'] ?? 'log');
		$smsConfigured = match ($smsProvider) {
			'twilio' => (string) ($services['sms']['twilio']['account_sid'] ?? '') !== '',
			'vonage' => (string) ($services['sms']['vonage']['api_key'] ?? '') !== '',
			default => true,
		};

		return [
			'agences' => [
				'ACTIVE' => (int) ($agences['ACTIVE'] ?? 0),
				'PENDING' => (int) ($agences['PENDING'] ?? 0),
				'SUSPENDED' => (int) ($agences['SUSPENDED'] ?? 0),
			],
			'utilisateurs_actifs' => $utilisateursActifs,
			'abonnements_actifs' => $abonnementsActifs,
			'integrations' => [
				'stripe' => [
					'configure' => $stripeKey !== '',
					'mode' => str_starts_with($stripeKey, 'pk_live_') ? 'live' : 'test',
				],
				'sms' => ['provider' => $smsProvider, 'configure' => $smsConfigured],
				'ia' => ['configure' => (string) ($services['anthropic']['api_key'] ?? '') !== ''],
			],
		];
	}

	/** @return array{plans: array, current: ?int} */
	public static function agenceDefaults(): array
	{
		return [
			'plans' => AbonnementModel::activePlans(),
			'current' => self::planDefautAgence(),
		];
	}

	public static function updateAgenceDefaults(array $input): ?string
	{
		$idPlan = (int) ($input['id_plan_defaut'] ?? 0) ?: null;
		if ($idPlan !== null && !AbonnementModel::find($idPlan)) {
			return 'Plan introuvable.';
		}

		$before = self::planDefautAgence();
		ParametreModel::set(self::CLE_PLAN_DEFAUT, $idPlan !== null ? (string) $idPlan : null);

		$currentUser = Auth::user();
		Logger::audit('UPDATE', 'parametre_plateforme', null, $currentUser['id_utilisateur'] ?? null, null,
			['plan_defaut_id' => $before], ['plan_defaut_id' => $idPlan]);

		return null;
	}

	public static function updateNotifications(array $input): ?string
	{
		$actif = !empty($input['sms_validation_agence']);
		$before = self::smsValidationAgenceActif();
		ParametreModel::set(self::CLE_SMS_VALIDATION_AGENCE, $actif ? '1' : '0');

		$currentUser = Auth::user();
		Logger::audit('UPDATE', 'parametre_plateforme', null, $currentUser['id_utilisateur'] ?? null, null,
			['sms_validation_agence' => $before], ['sms_validation_agence' => $actif]);

		return null;
	}
}
