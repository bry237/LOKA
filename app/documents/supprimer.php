<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/DocumentController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée');
}

Auth::verifyCsrf(<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/DocumentController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée');
}

Auth::verifyCsrf(<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/DocumentController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée');
}

Auth::verifyCsrf(<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/DocumentController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée');
}

Auth::verifyCsrf($_POST['csrf_token'] ?? null);

$documentId = (int)($_POST['id_document'] ?? 0);

if ($documentId > 0) {
    DocumentController::delete($agencyId, $documentId);
}

header('Location: /LOKA/app/documents/index.php');
exit;
POST['csrf_token'] ?? null);

$documentId = (int)($_POST['id_document'] ?? 0);

if ($documentId > 0) {
    DocumentController::delete($agencyId, $documentId);
}

header('Location: /LOKA/app/documents/index.php');
exit;
POST['csrf_token'] ?? null);

$documentId = (int)($_POST['id_document'] ?? 0);

if ($documentId > 0) {
    DocumentController::delete($agencyId, $documentId);
}

header('Location: /LOKA/app/documents/index.php');
exit;
POST['csrf_token'] ?? null);

$documentId = (int)($_POST['id_document'] ?? 0);

if ($documentId > 0) {
    DocumentController::delete($agencyId, $documentId);
}

header('Location: /LOKA/app/documents/index.php');
exit;
