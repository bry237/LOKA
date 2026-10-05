<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/DocumentModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

$documentId = (int)($_GET['id'] ?? 0);

if ($documentId <= 0) {
    http_response_code(400);
    die('ID invalide');
}

$document = DocumentModel::find($agencyId, $documentId);

if (!$document) {
    http_response_code(404);
    die('Document introuvable');
}

$filePath = dirname(__DIR__, 2) . '/' . $document['chemin_stockage'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Fichier introuvable sur le serveur');
}

header('Content-Type: ' . $document['mime_type']);
header('Content-Disposition: attachment; filename="' . $document['nom_original'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
