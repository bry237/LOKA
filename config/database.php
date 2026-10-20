<?php
declare(strict_types=1);

/**
 * Paramètres de connexion à sql.
 * Les variables d'environnement permettent de remplacer les valeurs locales XAMPP.
 *
 * Sur un hébergement mutualisé sans accès SSH/SetEnv fiable (voir deploy/README.md),
 * config/env.local.php (jamais versionné, uploadé une seule fois à la main) peut définir
 * ces variables via putenv() — ça marche quel que soit le mode PHP de l'hébergeur (mod_php,
 * CGI, FastCGI), contrairement à un SetEnv Apache qui ne remonte pas toujours jusqu'à getenv().
 */
if (is_file(__DIR__ . '/env.local.php')) {
	require_once __DIR__ . '/env.local.php';
}

return [
	'host' => getenv('LOKA_DB_HOST') ?: '127.0.0.1',
	'port' => (int) (getenv('LOKA_DB_PORT') ?: 3306),
	'name' => getenv('LOKA_DB_NAME') ?: 'loka',
	'user' => getenv('LOKA_DB_USER') ?: 'root',
	'password' => getenv('LOKA_DB_PASSWORD') ?: '',
	'charset' => getenv('LOKA_DB_CHARSET') ?: 'utf8mb4',
];
