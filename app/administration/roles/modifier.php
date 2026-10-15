<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RoleController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$role = $id > 0 ? RoleController::detail($id) : null;

if (!$role) {
	header('Location: index.php?error=' . urlencode('Rôle introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$values = ['description' => $role['description'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values['description'] = (string) ($_POST['description'] ?? '');
	$error = RoleController::updateDescription($id, $values['description']);
	if ($error === null) {
		header('Location: index.php?success=' . urlencode('Description mise à jour.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
	$errors['_global'] = $error;
}

$pageTitle = 'Modifier ' . $role['nom'];
$activeNav = 'Roles';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez la description de ce rôle.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux rôles
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p>Le nom du rôle est fixe : il est référencé tel quel dans tout le code de contrôle d'accès.</p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="modifier.php?id=<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid full">
          <div class="form-field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($values['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <a class="btn" href="index.php"<?= $isModal ? ' data-modal-cancel' : '' ?>>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
