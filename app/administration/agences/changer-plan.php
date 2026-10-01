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
$idAbonnement = (int) ($_POST['id_abonnement'] ?? 0);
$error = AgenceController::assignPlan($id, $idAbonnement);

$redirect = (string) ($_POST['redirect'] ?? '');
if (!preg_match('/^(index|detail)\.php(\?[^\s]*)?$/', $redirect)) $redirect = 'index.php';
$separator = str_contains($redirect, '?') ? '&' : '?';
if ($error !== null) $redirect .= $separator . 'error=' . urlencode($error);
else $redirect .= $separator . 'success=' . urlencode('Plan de l’agence mis à jour.');
if (($_POST['modal'] ?? '') === '1') $redirect .= '&modal=1';

header('Location: ' . $redirect);
exit;
