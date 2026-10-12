<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	exit('Méthode non autorisée.');
}
if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$idDemande = (int) ($_POST['id_demande'] ?? 0);
$idEcheance = (int) ($_POST['id_echeance'] ?? 0);
$isModal = (($_POST['modal'] ?? '') === '1');
$suffix = $isModal ? '&modal=1' : '';

$ok = $idDemande > 0 && MonAgenceController::cancelDemandePaiement($idAgence, $idDemande, (int) $currentUser['id_utilisateur']);

header('Location: echeance-detail.php?id=' . $idEcheance
	. ($ok ? '&success=' . urlencode('Demande de paiement annulée.') : '&error=' . urlencode('Impossible d’annuler cette demande.'))
	. $suffix);
