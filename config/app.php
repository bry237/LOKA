<?php

declare(strict_types=1);

return [
    'name' => 'LOKA',
    'version' => '1.0.0',
    'url' => '/LOKA',
    'timezone' => 'Europe/Paris',
    'locale' => 'fr_FR',
    'charset' => 'UTF-8',

    // Limites
    'max_upload_size' => 10 * 1024 * 1024, // 10 Mo
    'max_photo_size' => 5 * 1024 * 1024,   // 5 Mo

    // Chemins
    'uploads_dir' => dirname(__DIR__) . '/public/uploads',
    'photos_dir' => dirname(__DIR__) . '/public/uploads/photos',
    'documents_dir' => dirname(__DIR__) . '/public/uploads/documents',

    // Session
    'session_lifetime' => 8 * 3600, // 8 heures
    'csrf_token_name' => 'csrf_token',

    // E-mail (à configurer pour la production)
    'mail' => [
        'from' => 'noreply@loka.fr',
        'from_name' => 'LOKA',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_password' => '',
    ],
];
