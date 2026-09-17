<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) { http_response_code(400); exit('Aucune agence n’est associée à ce compte.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		try {
			[$errors, $id] = BienController::save($agencyId, $_POST);
			if ($errors === []) { header('Location: detail.php?id=' . $id); exit; }
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible d’enregistrer le bien.';
		}
	}
}
$references = BienController::references($agencyId);
$csrfToken = Auth::csrfToken();
$title = 'Ajouter un bien';
$action = 'ajouter.php';
require __DIR__ . '/form.php';