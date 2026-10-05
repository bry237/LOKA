<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AbonnementController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$actif = ($_POST['actif'] ?? '') === '1';
$error = AbonnementController::setActif($id, $actif);

$redirect = 'index.php';
if ($error !== null) {
	$redirect .= '?error=' . urlencode($error);
} else {
	$redirect .= '?success=' . urlencode($actif ? 'Plan activé.' : 'Plan désactivé.');
}

header('Location: ' . $redirect);
exit;
