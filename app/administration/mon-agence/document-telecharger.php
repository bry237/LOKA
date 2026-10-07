<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$idDocument = (int) ($_GET['id'] ?? 0);
$document = $idDocument > 0 ? MonAgenceController::documentFind($idAgence, $idDocument) : null;

if (!$document) {
	http_response_code(404);
	exit('Document introuvable.');
}

$filePath = dirname(__DIR__, 3) . '/' . $document['chemin_stockage'];
if (!file_exists($filePath)) {
	http_response_code(404);
	exit('Fichier introuvable sur le serveur.');
}

header('Content-Type: ' . $document['mime_type']);
header('Content-Disposition: attachment; filename="' . $document['nom_original'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($filePath);
exit;
