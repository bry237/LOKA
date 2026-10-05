<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');

header('Content-Type: application/json; charset=UTF-8');

try {
	echo json_encode(MonAgenceController::data((int) $currentUser['id_agence']), JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
	http_response_code(500);
	echo json_encode(['error' => 'Une erreur interne est survenue.'], JSON_THROW_ON_ERROR);
}
