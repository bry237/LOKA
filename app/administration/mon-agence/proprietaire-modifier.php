<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$idProprietaire = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$proprietaire = MonAgenceController::proprietaireDetail($idAgence, $idProprietaire);
if (!$proprietaire) {
	header('Location: proprietaires.php?error=' . urlencode('Propriétaire introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'nom' => $proprietaire['nom'],
	'prenom' => $proprietaire['prenom'],
	'email' => (string) ($proprietaire['email'] ?? ''),
	'telephone' => (string) ($proprietaire['telephone'] ?? ''),
	'date_naissance' => (string) ($proprietaire['date_naissance'] ?? ''),
	'adresse' => (string) ($proprietaire['adresse'] ?? ''),
	'code_postal' => (string) ($proprietaire['code_postal'] ?? ''),
	'ville' => (string) ($proprietaire['ville'] ?? ''),
	'pays' => (string) ($proprietaire['pays'] ?? 'France'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::updateProprietaire($idAgence, $idProprietaire, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: proprietaire-detail.php?id=' . $idProprietaire . '&success=' . urlencode('Propriétaire modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$action = 'proprietaire-modifier.php?id=' . $idProprietaire;
$cancelHref = 'proprietaire-detail.php?id=' . $idProprietaire;

$pageTitle = 'Modifier ' . $proprietaire['prenom'] . ' ' . $proprietaire['nom'];
$activeNav = 'Propriétaires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations du propriétaire.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="proprietaire-detail.php?id=<?= $idProprietaire ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au propriétaire
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier le propriétaire</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/proprietaire-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
