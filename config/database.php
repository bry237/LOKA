<?php
declare(strict_types=1);

/**
 * Paramètres de connexion à sql.
 * Les variables d'environnement permettent de remplacer les valeurs locales XAMPP.
 *
 * Sur un hébergement mutualisé sans accès SSH/SetEnv fiable (voir deploy/README.md),
 * config/env.local.php (jamais versionné, uploadé une seule fois à la main) peut fournir
 * ces valeurs — voir config/env.php pour le détail (pas de putenv(), bloqué par certains
 * hébergeurs comme InfinityFree).
 */
require_once __DIR__ . '/env.php';

return [
	'host' => loka_env('LOKA_DB_HOST', '127.0.0.1'),
	'port' => (int) loka_env('LOKA_DB_PORT', 3306),
	'name' => loka_env('LOKA_DB_NAME', 'loka'),
	'user' => loka_env('LOKA_DB_USER', 'root'),
	'password' => loka_env('LOKA_DB_PASSWORD', ''),
	'charset' => loka_env('LOKA_DB_CHARSET', 'utf8mb4'),
];
