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
];
