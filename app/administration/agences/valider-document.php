<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';

Authorization::requireRole('Administrateur plateforme');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$id = (int) ($_POST['id'] ?? 0);
$agenceId = (int) ($_POST['agence_id'] ?? 0);
$statut = (string) ($_POST['statut'] ?? '');
$motif = trim((string) ($_POST['motif'] ?? '')) ?: null;
$labels = ['VALIDE' => 'validé', 'REJETE' => 'rejeté'];
$error = AgenceController::setDocumentStatus($id, $statut, $motif);

$redirect = 'detail.php?id=' . $agenceId;
$separator = '&';
if ($error !== null) $redirect .= $separator . 'error=' . urlencode($error);
else $redirect .= $separator . 'success=' . urlencode('Document ' . ($labels[$statut] ?? 'mis à jour') . '.');
if (($_POST['modal'] ?? '') === '1') $redirect .= '&modal=1';

header('Location: ' . $redirect);
exit;
