<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ContratModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);

$idContrat = filter_input(INPUT_POST, 'id_contrat', FILTER_VALIDATE_INT);
$motif = $_POST['motif_resiliation'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $agencyId < 1 || !$idContrat || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400); 
    exit('Requête invalide.');
}

ContratModel::terminate($agencyId, $idContrat, $motif);

header('Location: detail.php?id=' . $idContrat);
exit;
