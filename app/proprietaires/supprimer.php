<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0); $ownerId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $agencyId < 1 || !$ownerId || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(400); exit('Requete invalide.'); }
ProprietaireModel::archive($agencyId, $ownerId);
header('Location: index.php');
exit;