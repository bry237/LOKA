<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['nom' => '', 'email' => '', 'telephone' => '', 'adresse' => '', 'code_postal' => '', 'ville' => '', 'pays' => 'France'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	[$errors, $newId] = AgenceController::create($_POST);
	if (!$errors) {
		header('Location: detail.php?id=' . $newId . '&success=' . urlencode('Agence créée.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$pageTitle = 'Nouvelle agence';
$activeNav = 'Agences';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Créez une nouvelle agence sur la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux agences
    </a>

    <div class="page-header">
      <h1>Nouvelle agence</h1>
      <p>Renseignez les informations de l'agence à ajouter à la plateforme.</p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="ajouter.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid">
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
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Créer l'agence</button>
          <a class="btn" href="index.php" data-modal-cancel>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
