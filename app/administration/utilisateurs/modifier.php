<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';
require_once __DIR__ . '/UtilisateurController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$u = $id > 0 ? UtilisateurController::forEdit($id) : null;

if (!$u) {
	header('Location: index.php?error=' . urlencode('Utilisateur introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$phoneCountryCodes = require dirname(__DIR__, 3) . '/layouts/phone-country-codes.php';
[$indicatifInitial, $telephoneInitial] = $u['telephone']
	? Phone::splitE164((string) $u['telephone'], $phoneCountryCodes)
	: ['+33', ''];

$errors = [];
$values = [
	'nom' => $u['nom'],
	'prenom' => $u['prenom'],
	'email' => $u['email'],
	'telephone' => $telephoneInitial,
	'indicatif_pays' => $indicatifInitial,
	'id_role' => (string) $u['id_role'],
	'id_agence' => (string) ($u['id_agence'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = UtilisateurController::update($id, $_POST);
	if (!$errors) {
		header('Location: detail.php?id=' . $id . '&success=' . urlencode('Utilisateur modifié.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$options = UtilisateurController::formOptions();
$roles = $options['roles'];
$agences = $options['agences'];
$isSelf = (int) $currentUser['id_utilisateur'] === $id;

$pageTitle = 'Modifier ' . $u['prenom'] . ' ' . $u['nom'];
$activeNav = 'Utilisateurs';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Modifiez les informations de ce compte.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="utilisateurs.css">';

$csrfToken = Auth::csrfToken();

// Chemins absolus : voir detail.php (modale globale depuis la recherche / le dashboard).
$base = '/LOKA/app/administration/utilisateurs';

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="<?= $base ?>/detail.php?id=<?= $id ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à l'utilisateur
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Modifier l'utilisateur</h1>
      <p><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="<?= $base ?>/modifier.php?id=<?= $id ?>">
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
          <div class="form-field">
            <label for="id_role">Rôle</label>
            <select id="id_role" name="id_role"<?= $isSelf ? ' disabled' : '' ?>>
              <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id_role'] ?>"<?= $values['id_role'] === (string) $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($isSelf): ?>
              <input type="hidden" name="id_role" value="<?= htmlspecialchars($values['id_role'], ENT_QUOTES, 'UTF-8') ?>">
              <p class="field-hint">Vous ne pouvez pas changer votre propre rôle.</p>
            <?php endif; ?>
            <?php if (!empty($errors['id_role'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_role'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="id_agence">Agence</label>
            <select id="id_agence" name="id_agence">
              <option value="">— Aucune (Administrateur plateforme) —</option>
              <?php foreach ($agences as $agence): ?>
                <option value="<?= (int) $agence['id_agence'] ?>"<?= $values['id_agence'] === (string) $agence['id_agence'] ? ' selected' : '' ?>><?= htmlspecialchars($agence['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_agence'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_agence'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <a class="btn" href="<?= $base ?>/detail.php?id=<?= $id ?>"<?= $isModal ? ' data-modal-cancel' : '' ?>>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
