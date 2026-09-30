<?php
declare(strict_types=1);

/**
 * Clés/API des services externes.
 * ANTHROPIC_API_KEY doit être fournie via variable d'environnement (jamais commitée en clair).
 */
return [
	'anthropic' => [
		'api_key' => getenv('ANTHROPIC_API_KEY') ?: '',
		'model' => getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-5',
	],
	'sms' => [
		// 'log' (par défaut) : aucun envoi réel, écrit le SMS en base (table notification) et dans les logs.
		// 'twilio' : envoi réel via l'API Twilio, nécessite TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN/TWILIO_FROM_NUMBER.
		// 'vonage' : envoi réel via l'API Vonage, nécessite VONAGE_API_KEY/VONAGE_API_SECRET.
		'provider' => getenv('SMS_PROVIDER') ?: 'log',
		'twilio' => [
			'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: '',
			'auth_token' => getenv('TWILIO_AUTH_TOKEN') ?: '',
			'from_number' => getenv('TWILIO_FROM_NUMBER') ?: '',
		],
		'vonage' => [
			'api_key' => getenv('VONAGE_API_KEY') ?: '',
			'api_secret' => getenv('VONAGE_API_SECRET') ?: '',
			'from' => getenv('VONAGE_FROM') ?: 'LOKA',
		],
	],
];
