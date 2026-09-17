<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $agencyId < 1 || !$propertyId || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}
BienModel::archive($agencyId, $propertyId);
header('Location: index.php');
exit;