<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$propertyId) { http_response_code(400); exit('Paramètres invalides.'); }
$property = BienModel::find($agencyId, $propertyId);
if (!$property) { http_response_code(404); exit('Bien introuvable.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		try {
			[$errors, $id] = BienController::save($agencyId, $_POST, $propertyId);
			if ($errors === []) { header('Location: detail.php?id=' . $propertyId); exit; }
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible de modifier le bien.';
		}
	}
	$property = array_merge($property, $_POST);
}
$references = BienController::references($agencyId);
$csrfToken = Auth::csrfToken();
$title = 'Modifier un bien';
$action = 'modifier.php?id=' . $propertyId;
require __DIR__ . '/form.php';