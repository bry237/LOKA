<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$idBien = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bien = MonAgenceController::bienDetail($idAgence, $idBien);
if (!$bien) {
	header('Location: biens.php?error=' . urlencode('Bien introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'reference' => $bien['reference'],
	'titre' => $bien['titre'],
	'id_type_bien' => (string) $bien['id_type_bien'],
	'id_adresse' => (string) $bien['id_adresse'],
	'nouvelle_ligne1' => '', 'nouvelle_ville' => '', 'nouveau_code_postal' => '',
	'id_immeuble' => $bien['id_immeuble'] !== null ? (string) $bien['id_immeuble'] : '',
	'etage' => $bien['etage'] !== null ? (string) $bien['etage'] : '',
	'surface' => (string) $bien['surface'],
	'nombre_pieces' => (string) $bien['nombre_pieces'],
	'meuble' => $bien['meuble'] === '1' ? '1' : '0',
	'date_disponibilite' => (string) ($bien['date_disponibilite'] ?? ''),
	'dpe_classe' => (string) ($bien['dpe_classe'] ?? ''),
	'ges_classe' => (string) ($bien['ges_classe'] ?? ''),
	'loyer' => (string) $bien['loyer'],
	'charges' => (string) $bien['charges'],
	'caution' => (string) $bien['caution'],
	'statut' => $bien['statut'],
	'description' => (string) ($bien['description'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::updateBien($idAgence, $idBien, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: bien-detail.php?id=' . $idBien . '&success=' . urlencode('Bien modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

['types' => $types, 'addresses' => $addresses, 'buildings' => $buildings] = MonAgenceController::bienFormOptions($idAgence);
$action = 'bien-modifier.php?id=' . $idBien;
$cancelHref = 'bien-detail.php?id=' . $idBien;

$pageTitle = 'Modifier ' . $bien['titre'];
$activeNav = 'Biens';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations du bien.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="bien-detail.php?id=<?= $idBien ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au bien
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier le bien</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php require __DIR__ . '/bien-form.php'; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
