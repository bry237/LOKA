<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/UtilisateurController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$error = UtilisateurController::delete($id);

$redirect = 'index.php';
if ($error !== null) $redirect .= '?error=' . urlencode($error);
else $redirect .= '?success=' . urlencode('Utilisateur supprimé.');

header('Location: ' . $redirect);
exit;
