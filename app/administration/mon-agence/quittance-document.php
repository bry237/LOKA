<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence', 'Comptable');
$idAgence = (int) $currentUser['id_agence'];

$idQuittance = (int) ($_GET['id'] ?? 0);
$quittance = $idQuittance > 0 ? MonAgenceModel::quittanceFind($idAgence, $idQuittance) : null;

if (!$quittance || empty($quittance['chemin_fichier_pdf'])) {
	http_response_code(404);
	exit('Document introuvable.');
}

$filePath = dirname(__DIR__, 3) . '/' . $quittance['chemin_fichier_pdf'];
if (!file_exists($filePath)) {
	http_response_code(404);
	exit('Fichier introuvable sur le serveur.');
}

$download = ($_GET['download'] ?? '') === '1';
$filename = 'quittance-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $quittance['numero']) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($filePath);
exit;
