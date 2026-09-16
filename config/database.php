<?php

declare(strict_types=1);

/**
 * Paramètres de connexion à sql.
 * Les variables d'environnement permettent de remplacer les valeurs locales XAMPP.
 */
return [
    'host' => getenv('LOKA_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('LOKA_DB_PORT') ?: 3306),
    'name' => getenv('LOKA_DB_NAME') ?: 'loka',
    'user' => getenv('LOKA_DB_USER') ?: 'root',
    'password' => getenv('LOKA_DB_PASSWORD') ?: '',
    'charset' => getenv('LOKA_DB_CHARSET') ?: 'utf8mb4',
];
