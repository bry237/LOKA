<?php

declare(strict_types=1);

/**
 * Configuration et accès centralisé à la base de données MySQL de LOKA.
 */
final class DatabaseConfig
{
    private const HOST = '127.0.0.1';
    private const PORT = '3306';
    private const NAME = 'loka';
    private const USERNAME = 'root';
    private const PASSWORD = '';

    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::HOST,
            self::PORT,
            self::NAME
        );

        try {
            self::$connection = new PDO($dsn, self::USERNAME, self::PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Impossible de se connecter à la base de données LOKA.',
                0,
                $exception
            );
        }

        return self::$connection;
    }
}
