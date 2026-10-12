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

$idEcheance = (int) ($_POST['id'] ?? 0);
$isModal = (($_POST['modal'] ?? '') === '1');
$suffix = $isModal ? '&modal=1' : '';

$result = $idEcheance > 0
	? MonAgenceController::createDemandePaiement($idAgence, $idEcheance, (int) $currentUser['id_utilisateur'])
	: ['error' => 'Échéance invalide.', 'url' => null];

if ($result['error'] !== null) {
	header('Location: echeance-detail.php?id=' . $idEcheance . '&error=' . urlencode($result['error']) . $suffix);
	exit;
}

header('Location: echeance-detail.php?id=' . $idEcheance . '&success=' . urlencode('Lien de paiement généré.') . $suffix);
