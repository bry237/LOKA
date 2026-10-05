<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AbonnementController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$plan = $id > 0 ? AbonnementController::find($id) : null;
if (!$plan) {
	header('Location: index.php?error=' . urlencode('Plan introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = [
	'nom' => $plan['nom'],
	'description' => (string) $plan['description'],
	'prix_mensuel' => (string) $plan['prix_mensuel'],
	'limite_utilisateurs' => $plan['limite_utilisateurs'] !== null ? (string) $plan['limite_utilisateurs'] : '',
	'limite_biens' => $plan['limite_biens'] !== null ? (string) $plan['limite_biens'] : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = AbonnementController::update($id, $_POST);
	if (!$errors) {
		header('Location: index.php?success=' . urlencode('Plan mis à jour.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$pageTitle = 'Modifier le plan';
$activeNav = 'Abonnements';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les conditions de cette formule.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux abonnements
    </a>

    <div class="page-header">
      <h1>Modifier « <?= htmlspecialchars($plan['nom'], ENT_QUOTES, 'UTF-8') ?> »</h1>
      <p>Changer le prix mensuel crée un nouveau tarif Stripe (les tarifs Stripe sont immuables).</p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="modifier.php?id=<?= (int) $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="nom">Nom du plan</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field span-2">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" value="<?= htmlspecialchars($values['description'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="form-field">
            <label for="prix_mensuel">Prix mensuel (€)</label>
            <input type="number" step="0.01" min="0" id="prix_mensuel" name="prix_mensuel" value="<?= htmlspecialchars($values['prix_mensuel'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['prix_mensuel'])): ?><span class="field-error"><?= htmlspecialchars($errors['prix_mensuel'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="limite_utilisateurs">Limite d’utilisateurs</label>
            <input type="number" min="1" id="limite_utilisateurs" name="limite_utilisateurs" placeholder="Illimité" value="<?= htmlspecialchars($values['limite_utilisateurs'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['limite_utilisateurs'])): ?><span class="field-error"><?= htmlspecialchars($errors['limite_utilisateurs'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="limite_biens">Limite de biens</label>
            <input type="number" min="1" id="limite_biens" name="limite_biens" placeholder="Illimité" value="<?= htmlspecialchars($values['limite_biens'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['limite_biens'])): ?><span class="field-error"><?= htmlspecialchars($errors['limite_biens'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <a class="btn" href="index.php" data-modal-cancel>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
