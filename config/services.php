<?php
declare(strict_types=1);

/**
 * Clés/API des services externes.
 * ANTHROPIC_API_KEY doit être fournie via variable d'environnement (jamais commitée en clair).
 *
 * Voir config/env.php : config/env.local.php (non versionné) peut fournir ces valeurs sans
 * dépendre de putenv() (bloqué sur certains hébergeurs comme InfinityFree).
 */
require_once __DIR__ . '/env.php';

return [
	'anthropic' => [
		'api_key' => loka_env('ANTHROPIC_API_KEY', ''),
		'model' => loka_env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
	],
	'stripe' => [
		// Clés de test (sk_test_.../pk_test_...) depuis le dashboard Stripe. whsec_... fourni par
		// `stripe listen` (CLI) en local, ou par la configuration du webhook en production.
		'secret_key' => loka_env('STRIPE_SECRET_KEY', ''),
		'publishable_key' => loka_env('STRIPE_PUBLISHABLE_KEY', ''),
		'webhook_secret' => loka_env('STRIPE_WEBHOOK_SECRET', ''),
	],
	'sms' => [
		// 'log' (par défaut) : aucun envoi réel, écrit le SMS en base (table notification) et dans les logs.
		// 'twilio' : envoi réel via l'API Twilio, nécessite TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN/TWILIO_FROM_NUMBER.
		// 'vonage' : envoi réel via l'API Vonage, nécessite VONAGE_API_KEY/VONAGE_API_SECRET.
		'provider' => loka_env('SMS_PROVIDER', 'log'),
		'twilio' => [
			'account_sid' => loka_env('TWILIO_ACCOUNT_SID', ''),
			'auth_token' => loka_env('TWILIO_AUTH_TOKEN', ''),
			'from_number' => loka_env('TWILIO_FROM_NUMBER', ''),
		],
		'vonage' => [
			'api_key' => loka_env('VONAGE_API_KEY', ''),
			'api_secret' => loka_env('VONAGE_API_SECRET', ''),
			'from' => loka_env('VONAGE_FROM', 'LOKA'),
		],
	],
	'email' => [
		// 'log' (par défaut) : aucun envoi réel, écrit l'email dans les logs serveur (et le lien de
		// réinitialisation est affiché directement à l'écran — pratique en local sans serveur SMTP).
		// 'smtp' : envoi réel via un serveur SMTP, nécessite SMTP_HOST/SMTP_PORT/SMTP_USER/SMTP_PASSWORD/SMTP_FROM.
		'provider' => loka_env('EMAIL_PROVIDER', 'log'),
		'smtp' => [
			'host' => loka_env('SMTP_HOST', ''),
			'port' => (int) loka_env('SMTP_PORT', 587),
			'user' => loka_env('SMTP_USER', ''),
			'password' => loka_env('SMTP_PASSWORD', ''),
			'from' => loka_env('SMTP_FROM', 'no-reply@loka.fr'),
		],
	],
];
