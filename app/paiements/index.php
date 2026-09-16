<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Comptable', 'Proprietaire', 'Locataire');
