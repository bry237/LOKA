<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';
require_once __DIR__ . '/UtilisateurController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = [
	'nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'indicatif_pays' => '+33',
	'id_role' => (string) ($_GET['id_role'] ?? ''),
	'id_agence' => (string) ($_GET['id_agence'] ?? ''),
];
// Pré-remplissage pratique quand on arrive depuis la fiche d'une agence sans administrateur
// (voir agences/detail.php) : on suggère directement le rôle "Administrateur agence".
if ($values['id_agence'] !== '' && $values['id_role'] === '') {
	$values['id_role'] = '2';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	[$errors, $created] = UtilisateurController::create($_POST);
	if (!$errors) {
		$message = 'Utilisateur créé.';
		if (empty($_POST['telephone'])) {
			$message .= ' Mot de passe temporaire : ' . $created['mot_de_passe_temporaire'] . ' (à communiquer vous-même, aucun numéro n’a été renseigné).';
		} else {
			$message .= ' Ses identifiants lui ont été envoyés par SMS.';
		}
		header('Location: detail.php?id=' . $created['id_utilisateur'] . '&success=' . urlencode($message) . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$options = UtilisateurController::formOptions();
$roles = $options['roles'];
$agences = $options['agences'];
$phoneCountryCodes = require dirname(__DIR__, 3) . '/layouts/phone-country-codes.php';

$pageTitle = 'Nouvel utilisateur';
$activeNav = 'Utilisateurs';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Créez un compte et rattachez-le à une agence si nécessaire.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="utilisateurs.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux utilisateurs
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Nouvel utilisateur</h1>
      <p>Les rôles Propriétaire et Locataire ne sont pas disponibles ici : ils se créent via leurs parcours dédiés.</p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="ajouter.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
              <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($values['telephone'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel" placeholder="6 12 34 56 78">
            </div>
            <?php if (!empty($errors['telephone'])): ?><span class="field-error"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <p class="field-hint">Requis pour tout rôle d’agence (sert à l’OTP de première connexion). Facultatif pour un Administrateur plateforme.</p>
          </div>
          <div class="form-field">
            <label for="id_role">Rôle</label>
            <select id="id_role" name="id_role">
              <option value="">— Choisir —</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id_role'] ?>"<?= $values['id_role'] === (string) $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
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
            <p class="field-hint">Requise pour tout rôle sauf Administrateur plateforme.</p>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
          <a class="btn" href="index.php"<?= $isModal ? ' data-modal-cancel' : '' ?>>Annuler</a>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
