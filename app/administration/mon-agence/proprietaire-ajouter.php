<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = [
	'nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'date_naissance' => '',
	'adresse' => '', 'code_postal' => '', 'ville' => '', 'pays' => 'France',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::createProprietaire($idAgence, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: proprietaires.php?success=' . urlencode('Propriétaire créé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$idProprietaire = null;
$action = 'proprietaire-ajouter.php';
$cancelHref = 'proprietaires.php';

$pageTitle = 'Nouveau propriétaire';
$activeNav = 'Propriétaires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Ajoutez un propriétaire au suivi de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="proprietaires.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux propriétaires
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Nouveau propriétaire</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/proprietaire-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
