<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	exit('Méthode non autorisée.');
}

if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$idDocument = (int) ($_POST['id'] ?? 0);
$error = $idDocument > 0 ? MonAgenceController::deleteDocument($idAgence, $idDocument) : 'Document invalide.';

header('Location: documents.php' . ($error ? '?error=' . urlencode($error) : '?success=' . urlencode('Document supprimé.')));
exit;
