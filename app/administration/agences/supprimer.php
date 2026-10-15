<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$error = AgenceController::delete($id);

$redirect = 'index.php';
if ($error !== null) $redirect .= '?error=' . urlencode($error);
else $redirect .= '?success=' . urlencode('Agence supprimée.');

header('Location: ' . $redirect);
exit;
