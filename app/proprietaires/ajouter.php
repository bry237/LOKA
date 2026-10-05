<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) { http_response_code(400); exit('Aucune agence associee a ce compte.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expire. Rechargez la page.';
	} else {
		try {
			[$errors, $id] = ProprietaireController::save($agencyId, $_POST);
			if ($errors === []) { header('Location: detail.php?id=' . $id); exit; }
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible d’enregistrer le proprietaire.';
		}
	}
<<<<<<< HEAD
=======

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['error' => 'Méthode non autorisée.'], JSON_THROW_ON_ERROR);
		exit;
	}

	$type = (string) ($_POST['account_type'] ?? '');
	[$errors, $redirect] = match ($type) {
		'proprietor' => AuthController::handleProprietorRegistration($_POST, $_FILES),
		'agency' => AuthController::handleAgencyRegistration($_POST, $_FILES),
		default => [['general' => 'Type de compte invalide.'], null],
	};

	if ($errors) {
		http_response_code(422);
		echo json_encode(['errors' => $errors], JSON_THROW_ON_ERROR);
		exit;
	}

	echo json_encode(['success' => true, 'redirect' => '../authentification/' . $redirect], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
	http_response_code(500);
	echo json_encode(['error' => 'Une erreur interne est survenue.'], JSON_THROW_ON_ERROR);
>>>>>>> main
}
$csrfToken = Auth::csrfToken();
$title = 'Ajouter un proprietaire';
$action = 'ajouter.php';
require __DIR__ . '/form.php';
