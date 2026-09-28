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
$error = UtilisateurController::setStatus($id, 'INACTIVE');

$redirect = 'index.php';
$qs = (string) ($_POST['redirect'] ?? '');
if ($qs !== '') $redirect .= '?' . $qs;
if ($error !== null) $redirect .= ($qs !== '' ? '&' : '?') . 'error=' . urlencode($error);
else $redirect .= ($qs !== '' ? '&' : '?') . 'success=' . urlencode('Utilisateur désactivé.');

header('Location: ' . $redirect);
exit;
