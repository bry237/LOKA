<?php
declare(strict_types=1);
<<<<<<< HEAD
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $model = new UtilisateurModel();
        $model->updateStatus($id, 'INACTIVE');
    }
}
header('Location: detail.php?id=' . $id);
=======

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/UtilisateurController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$error = UtilisateurController::setStatus($id, 'INACTIVE');

$redirect = (string) ($_POST['redirect'] ?? '');
if (!preg_match('/^(index|detail)\.php(\?[^\s]*)?$/', $redirect)) $redirect = 'index.php';
$separator = str_contains($redirect, '?') ? '&' : '?';
if ($error !== null) $redirect .= $separator . 'error=' . urlencode($error);
else $redirect .= $separator . 'success=' . urlencode('Utilisateur désactivé.');
if (($_POST['modal'] ?? '') === '1') $redirect .= '&modal=1';

header('Location: ' . $redirect);
>>>>>>> main
exit;
