<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'indicatif_pays' => '+33', 'id_role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::invite($idAgence, $_POST);
	if (!$errors) {
		header('Location: equipe.php?success=' . urlencode('Membre invité. Ses identifiants lui ont été envoyés par SMS.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$team = MonAgenceController::team($idAgence);
$roles = $team['roles'];
$usage = $team['usage'];
$canInvite = $usage['limitUsers'] === null || $usage['users'] < $usage['limitUsers'];

$pageTitle = 'Inviter un membre';
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Invitez un nouveau membre dans votre équipe.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="equipe.css">';
$extraScripts = '<script src="equipe.js"></script>';

$csrfToken = Auth::csrfToken();
$phoneCountryCodes = require dirname(__DIR__, 3) . '/layouts/phone-country-codes.php';
$roleHints = [
	'Administrateur agence' => 'Accès complet à la gestion de l’agence.',
	'Gestionnaire immobilier' => 'Suit les biens, contrats et locataires au quotidien.',
	'Comptable' => 'Gère les paiements, quittances et la facturation.',
	'Technicien' => 'Intervient sur la maintenance et les réparations.',
];

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="equipe.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à l’équipe
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Inviter un membre</h1>
      <p>Les identifiants de connexion seront envoyés par SMS au numéro renseigné.</p>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$canInvite): ?>
      <div class="invite-limit-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
          <strong>Limite d’utilisateurs atteinte</strong>
          <p>Votre plan autorise <?= $usage['limitUsers'] ?> membre<?= $usage['limitUsers'] > 1 ? 's' : '' ?> maximum (<?= $usage['users'] ?>/<?= $usage['limitUsers'] ?> utilisés).
          <a href="abonnement.php"<?= $isModal ? ' data-modal' : '' ?>>Changer de plan</a> pour inviter davantage de membres.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel">
        <form method="post" action="equipe-inviter.php">
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
              <p class="field-hint">Un code de vérification sera envoyé à ce numéro lors de la première connexion.</p>
            </div>
            <div class="form-field span-2">
              <label for="id_role">Rôle dans l’agence</label>
              <select id="id_role" name="id_role">
                <option value="">— Choisir —</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= (int) $role['id_role'] ?>"<?= (string) $values['id_role'] === (string) $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['id_role'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_role'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
              <div class="role-hints">
                <?php foreach ($roleHints as $roleName => $hint): ?>
                  <p class="role-hint" data-role-hint="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Envoyer l’invitation</button>
            <?php if ($isModal): ?>
              <a class="btn" href="equipe.php" data-modal-cancel>Annuler</a>
            <?php else: ?>
              <a class="btn" href="equipe.php">Annuler</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php endif; ?>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
