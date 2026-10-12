<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = [
	'reference' => '', 'titre' => '', 'id_type_bien' => '', 'id_adresse' => '',
	'nouvelle_ligne1' => '', 'nouvelle_ville' => '', 'nouveau_code_postal' => '',
	'id_immeuble' => '', 'etage' => '', 'surface' => '', 'nombre_pieces' => '0',
	'meuble' => '0', 'date_disponibilite' => '', 'dpe_classe' => '', 'ges_classe' => '',
	'loyer' => '0', 'charges' => '0', 'caution' => '0', 'statut' => 'CREATED', 'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::createBien($idAgence, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: biens.php?success=' . urlencode('Bien créé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

['types' => $types, 'addresses' => $addresses, 'buildings' => $buildings] = MonAgenceController::bienFormOptions($idAgence);
$idBien = null;
$action = 'bien-ajouter.php';
$cancelHref = 'biens.php';

$pageTitle = 'Nouveau bien';
$activeNav = 'Biens';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Ajoutez un bien au patrimoine de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="biens.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux biens
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Nouveau bien</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/bien-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
