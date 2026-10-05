<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once dirname(__DIR__) . '/authentification/AuthController.php';

header('Content-Type: application/json; charset=UTF-8');

try {
	if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'csrf') {
		echo json_encode(['csrf_token' => Auth::csrfToken()], JSON_THROW_ON_ERROR);
		exit;
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['error' => 'Méthode non autorisée.'], JSON_THROW_ON_ERROR);
		exit;
	}

	$type = (string) ($_POST['account_type'] ?? '');
	[$errors, $redirect] = match ($type) {
		'proprietor' => AuthController::handleProprietorRegistration($_POST),
		'agency' => AuthController::handleAgencyRegistration($_POST),
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
}
