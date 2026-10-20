<?php
declare(strict_types=1);

/**
 * Lit une valeur de configuration : vraie variable d'environnement système si elle existe,
 * sinon le tableau renvoyé par config/env.local.php (non versionné), sinon la valeur par défaut.
 *
 * Ne dépend pas de putenv() : certains hébergements mutualisés le bloquent (ex. InfinityFree,
 * qui le liste dans disable_functions — constaté en conditions réelles), ce qui faisait
 * silencieusement échouer la configuration précédente basée sur putenv()/getenv().
 */
function loka_env(string $key, $default = false)
{
	static $local = null;
	if ($local === null) {
		$file = __DIR__ . '/env.local.php';
		$local = is_file($file) ? (require $file) : [];
	}

	$fromEnv = getenv($key);
	if ($fromEnv !== false) {
		return $fromEnv;
	}

	return $local[$key] ?? $default;
}
