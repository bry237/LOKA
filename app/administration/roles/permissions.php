<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RoleController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$role = $id > 0 ? RoleController::detail($id) : null;

if (!$role) {
	header('Location: index.php?error=' . urlencode('Rôle introuvable.'));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$error = RoleController::updatePermissions($id, (array) ($_POST['permissions'] ?? []));
	header('Location: permissions.php?id=' . $id . ($error ? '&error=' . urlencode($error) : '&success=' . urlencode('Permissions mises à jour.')));
	exit;
}

$pageTitle = 'Permissions · ' . $role['nom'];
$activeNav = 'Roles';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Choisissez les permissions associées à ce rôle.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux rôles
    </a>

    <div class="page-header">
      <h1>Permissions · <?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p>Ces permissions documentent les droits du rôle ; elles n'affectent pas (encore) les contrôles d'accès du code, qui reposent sur le nom du rôle.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="permissions.php?id=<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="document-list" style="padding:18px;">
          <?php foreach ($role['permissions'] as $permission): ?>
            <label class="document-item" style="cursor:pointer;align-items:center;">
              <div class="document-main">
                <div class="document-title"><?= htmlspecialchars($permission['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="document-sub"><?= htmlspecialchars($permission['description'] ?? $permission['code'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="document-actions">
                <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id_permission'] ?>"<?= $permission['accordee'] ? ' checked' : '' ?> style="width:18px;height:18px;">
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer les permissions</button>
          <a class="btn" href="index.php">Annuler</a>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
