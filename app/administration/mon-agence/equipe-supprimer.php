<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$error = MonAgenceController::deleteMember($idAgence, $id, (int) $currentUser['id_utilisateur']);

$redirect = 'equipe.php';
$redirect .= $error !== null ? '?error=' . urlencode($error) : '?success=' . urlencode('Membre retiré de l’équipe.');
if ($isModal) $redirect .= '&modal=1';

header('Location: ' . $redirect);
