<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$idLocataire = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$locataire = MonAgenceController::locataireDetail($idAgence, $idLocataire);
if (!$locataire) {
	header('Location: locataires.php?error=' . urlencode('Locataire introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'nom' => $locataire['nom'],
	'prenom' => $locataire['prenom'],
	'email' => (string) ($locataire['email'] ?? ''),
	'telephone' => (string) ($locataire['telephone'] ?? ''),
	'date_naissance' => (string) ($locataire['date_naissance'] ?? ''),
	'profession' => (string) ($locataire['profession'] ?? ''),
	'revenu_mensuel' => $locataire['revenu_mensuel'] !== null ? (string) $locataire['revenu_mensuel'] : '',
	'nombre_occupants' => (string) $locataire['nombre_occupants'],
	'statut' => $locataire['statut'],
	'adresse' => (string) ($locataire['adresse'] ?? ''),
	'code_postal' => (string) ($locataire['code_postal'] ?? ''),
	'ville' => (string) ($locataire['ville'] ?? ''),
	'pays' => (string) ($locataire['pays'] ?? 'France'),
	'type_identite' => (string) ($locataire['type_identite'] ?? ''),
	'numero_identite' => (string) ($locataire['numero_identite'] ?? ''),
	'situation_familiale' => (string) ($locataire['situation_familiale'] ?? ''),
	'garant_nom' => (string) ($locataire['garant_nom'] ?? ''),
	'garant_telephone' => (string) ($locataire['garant_telephone'] ?? ''),
	'garant_email' => (string) ($locataire['garant_email'] ?? ''),
	'notes' => (string) ($locataire['notes'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::updateLocataire($idAgence, $idLocataire, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: locataire-detail.php?id=' . $idLocataire . '&success=' . urlencode('Locataire modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$action = 'locataire-modifier.php?id=' . $idLocataire;
$cancelHref = 'locataire-detail.php?id=' . $idLocataire;

$pageTitle = 'Modifier ' . $locataire['prenom'] . ' ' . $locataire['nom'];
$activeNav = 'Locataires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations du locataire.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="locataire-detail.php?id=<?= $idLocataire ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au locataire
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier le locataire</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/locataire-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
