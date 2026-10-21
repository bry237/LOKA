<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$errors = [];
$successMessage = (string) ($_GET['success'] ?? '');
$agence = MonAgenceModel::agency($idAgence);
if (!$agence) {
	http_response_code(404);
	exit('Agence introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$action = (string) ($_POST['action'] ?? 'profil');

	if ($action === 'logo') {
		$accentColor = (string) ($_POST['couleur_accent'] ?? '');
		$error = MonAgenceController::uploadAgencyLogo($idAgence, (int) $currentUser['id_utilisateur'], UploadHelper::resolve('logo'), $accentColor !== '' ? $accentColor : null);
		if ($error === null) {
			$successMessage = 'Logo mis à jour.';
		} else {
			$errors['logo'] = $error;
		}
	} elseif ($action === 'logo_supprimer') {
		MonAgenceController::removeAgencyLogo($idAgence, (int) $currentUser['id_utilisateur']);
		$successMessage = 'Logo supprimé.';
	} else {
		$errors = MonAgenceController::updateAgencyProfile($idAgence, (int) $currentUser['id_utilisateur'], $_POST);
		if (!$errors) {
			$successMessage = 'Informations de l’agence mises à jour.';
		}
	}
	$agence = MonAgenceModel::agency($idAgence);
}

$values = [
	'nom' => $agence['nom'], 'email' => $agence['email'], 'telephone' => $agence['telephone'] ?? '',
	'adresse' => $agence['adresse'] ?? '', 'code_postal' => $agence['code_postal'] ?? '',
	'ville' => $agence['ville'] ?? '', 'pays' => $agence['pays'] ?? 'France',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'profil') === 'profil') {
	$values = array_merge($values, array_intersect_key($_POST, $values));
}

$statutLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente de validation', 'SUSPENDED' => 'Suspendu'];
$statutTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'SUSPENDED' => 'suspendu'];

$pageTitle = 'Paramètres';
$activeNav = 'Paramètres';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les informations de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css?v=' . filemtime(__DIR__ . '/dashboard.css')
	. '"><link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';
$extraScripts = '<script src="parametres.js?v=' . filemtime(__DIR__ . '/parametres.js') . '"></script>';

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Paramètres</h1>
      <p>Les informations de votre agence, visibles sur vos contrats et quittances.</p>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Informations de l'agence</div>
        <span class="status-badge <?= $statutTones[$agence['statut']] ?? '' ?>"><?= $statutLabels[$agence['statut']] ?? $agence['statut'] ?></span>
      </div>
      <form method="post" action="parametres.php" class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="profil">
        <div class="form-field span-2">
          <label for="nom">Nom de l'agence</label>
          <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="email">Email professionnel</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['email'])): ?><span class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="telephone">Téléphone</label>
          <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($values['telephone'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['telephone'])): ?><span class="field-error"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field span-2">
          <label for="adresse">Adresse</label>
          <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($values['adresse'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['adresse'])): ?><span class="field-error"><?= htmlspecialchars($errors['adresse'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="ville">Ville</label>
          <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($values['ville'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['ville'])): ?><span class="field-error"><?= htmlspecialchars($errors['ville'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="code_postal">Code postal</label>
          <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($values['code_postal'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['code_postal'])): ?><span class="field-error"><?= htmlspecialchars($errors['code_postal'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="pays">Pays</label>
          <input type="text" id="pays" name="pays" value="<?= htmlspecialchars($values['pays'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['pays'])): ?><span class="field-error"><?= htmlspecialchars($errors['pays'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-header"><div class="panel-title">Logo & couleur</div></div>
      <div class="logo-section">
        <form method="post" action="parametres.php" enctype="multipart/form-data" id="logoForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="logo">
          <input type="hidden" name="couleur_accent" id="couleurAccentField">

          <label class="logo-upload" for="logoInput" title="Changer le logo">
            <?php if ($agence['logo']): ?>
              <img src="<?= htmlspecialchars('/LOKA/' . $agence['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="" id="logoPreviewImg">
            <?php else: ?>
              <span class="logo-upload-placeholder"><?= htmlspecialchars(mb_strtoupper(mb_substr($agence['nom'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <span class="logo-upload-overlay">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
            </span>
          </label>
          <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png" hidden>
        </form>

        <div class="logo-section-info">
          <p class="logo-section-title">Logo de l'agence</p>
          <p class="logo-section-hint">JPEG ou PNG, 2 Mo max. Sa couleur dominante personnalise l'interface de votre agence.</p>
          <?php if (!empty($errors['logo'])): ?><span class="field-error"><?= htmlspecialchars($errors['logo'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          <?php if ($agence['couleur_accent']): ?>
            <div class="logo-color-chip">
              <span class="logo-color-swatch" style="background:<?= htmlspecialchars($agence['couleur_accent'], ENT_QUOTES, 'UTF-8') ?>"></span>
              <?= htmlspecialchars($agence['couleur_accent'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>
          <?php if ($agence['logo']): ?>
            <form method="post" action="parametres.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="action" value="logo_supprimer">
              <button type="submit" class="logo-remove-link">Retirer le logo et la couleur</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
