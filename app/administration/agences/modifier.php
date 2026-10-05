<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$agence = $id > 0 ? AgenceController::forEdit($id) : null;

if (!$agence) {
	header('Location: index.php?error=' . urlencode('Agence introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'nom' => $agence['nom'], 'email' => $agence['email'], 'telephone' => $agence['telephone'] ?? '',
	'adresse' => $agence['adresse'] ?? '', 'code_postal' => $agence['code_postal'] ?? '',
	'ville' => $agence['ville'] ?? '', 'pays' => $agence['pays'] ?? 'France',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = AgenceController::update($id, $_POST);
	if (!$errors) {
		header('Location: detail.php?id=' . $id . '&success=' . urlencode('Agence modifiée.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$pageTitle = 'Modifier ' . $agence['nom'];
$activeNav = 'Agences';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Modifiez les informations de l'agence.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

// Chemin absolu : cette page est aussi chargée dans la modale globale depuis le widget
// "Agences récentes" du Dashboard — voir la même note dans detail.php.
$base = '/LOKA/app/administration/agences';

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="<?= $base ?>/detail.php?id=<?= $id ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à la fiche agence
    </a>

    <div class="page-header">
      <h1>Modifier l'agence</h1>
      <p><?= htmlspecialchars($agence['nom'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="<?= $base ?>/modifier.php?id=<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
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
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <a class="btn" href="<?= $base ?>/detail.php?id=<?= $id ?>" data-modal>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
