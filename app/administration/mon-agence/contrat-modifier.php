<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$idContrat = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$contrat = MonAgenceController::contratDetail($idAgence, $idContrat);
if (!$contrat) {
	header('Location: contrats.php?error=' . urlencode('Contrat introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'numero' => $contrat['numero'],
	'id_bien' => (string) $contrat['id_bien'],
	'type_contrat' => $contrat['type_contrat'],
	'statut' => $contrat['statut'],
	'date_debut' => $contrat['date_debut'],
	'date_fin' => (string) ($contrat['date_fin'] ?? ''),
	'loyer' => (string) $contrat['loyer'],
	'charges' => (string) $contrat['charges'],
	'depot_garantie' => (string) $contrat['depot_garantie'],
	'frequence_paiement' => $contrat['frequence_paiement'],
	'jour_echeance' => (string) $contrat['jour_echeance'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::updateContrat($idAgence, $idContrat, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: contrat-detail.php?id=' . $idContrat . '&success=' . urlencode('Contrat modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$biens = MonAgenceModel::biensForIntervention($idAgence);
$action = 'contrat-modifier.php?id=' . $idContrat;
$cancelHref = 'contrat-detail.php?id=' . $idContrat;

$pageTitle = 'Modifier ' . $contrat['numero'];
$activeNav = 'Contrats';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations du contrat.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="contrat-detail.php?id=<?= $idContrat ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au contrat
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier le contrat</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/contrat-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
