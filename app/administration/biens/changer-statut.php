<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/BienAdminController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$statut = (string) ($_POST['statut'] ?? '');
$motif = trim((string) ($_POST['motif'] ?? ''));
$error = BienAdminController::setStatus($id, $statut, $motif !== '' ? $motif : null);

$redirect = (string) ($_POST['redirect'] ?? '');
if (!preg_match('/^(index|detail)\.php(\?[^\s]*)?$/', $redirect)) $redirect = 'index.php';
$separator = str_contains($redirect, '?') ? '&' : '?';
if ($error !== null) $redirect .= $separator . 'error=' . urlencode($error);
else $redirect .= $separator . 'success=' . urlencode('Statut du bien mis à jour.');
if (($_POST['modal'] ?? '') === '1') $redirect .= '&modal=1';

header('Location: ' . $redirect);
exit;
