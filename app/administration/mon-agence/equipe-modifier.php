<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$member = $id > 0 ? MonAgenceController::memberDetail($idAgence, $id) : null;

if (!$member) {
	header('Location: equipe.php?error=' . urlencode('Membre introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$phoneCountryCodes = require dirname(__DIR__, 3) . '/layouts/phone-country-codes.php';
[$indicatifInitial, $telephoneInitial] = Phone::splitE164((string) $member['telephone'], $phoneCountryCodes);

$errors = [];
$values = [
	'nom' => $member['nom'],
	'prenom' => $member['prenom'],
	'email' => $member['email'],
	'telephone' => $telephoneInitial,
	'indicatif_pays' => $indicatifInitial,
	'id_role' => (string) $member['id_role'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::updateMember($idAgence, $id, $_POST);
	if (!$errors) {
		header('Location: equipe-detail.php?id=' . $id . '&success=' . urlencode('Membre modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$team = MonAgenceController::team($idAgence);
$roles = $team['roles'];

$pageTitle = 'Modifier ' . $member['prenom'] . ' ' . $member['nom'];
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations de ce membre.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="equipe.css">';
$extraScripts = '<script src="equipe.js"></script>';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="equipe-detail.php?id=<?= $id ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au membre
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier le membre</h1>
      <p><?= htmlspecialchars($member['prenom'] . ' ' . $member['nom'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="equipe-modifier.php?id=<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid">
          <div class="form-field">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($values['prenom'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="given-name">
            <?php if (!empty($errors['prenom'])): ?><span class="field-error"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="family-name">
            <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="telephone">Téléphone</label>
            <div class="phone-group">
              <select id="indicatif_pays" name="indicatif_pays">
                <?php foreach ($phoneCountryCodes as $code => $label): ?>
                  <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"<?= $values['indicatif_pays'] === $code ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($values['telephone'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel">
            </div>
            <?php if (!empty($errors['telephone'])): ?><span class="field-error"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field span-2">
            <label for="id_role">Rôle dans l’agence</label>
            <select id="id_role" name="id_role">
              <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id_role'] ?>"<?= (string) $values['id_role'] === (string) $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_role'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_role'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <a class="btn" href="equipe-detail.php?id=<?= $id ?>"<?= $isModal ? ' data-modal' : '' ?>>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
