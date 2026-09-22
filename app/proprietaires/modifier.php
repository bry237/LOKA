<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0); $ownerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$ownerId) { http_response_code(400); exit('Parametres invalides.'); }
$owner = ProprietaireModel::find($agencyId, $ownerId); if (!$owner) { http_response_code(404); exit('Proprietaire introuvable.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) { $errors['general'] = 'Votre session a expire. Rechargez la page.'; }
	else { try { [$errors, $id] = ProprietaireController::save($agencyId, $_POST, $ownerId); if ($errors === []) { header('Location: detail.php?id=' . $ownerId); exit; } } catch (Throwable $exception) { error_log($exception->getMessage()); $errors['general'] = 'Impossible de modifier le proprietaire.'; } }
	$owner = array_merge($owner, $_POST);
}
$csrfToken = Auth::csrfToken(); $title = 'Modifier un proprietaire'; $action = 'modifier.php?id=' . $ownerId; require __DIR__ . '/form.php';