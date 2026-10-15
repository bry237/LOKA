<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/ParametreController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	ParametreController::updateNotifications($_POST);
	$successMessage = 'Réglage enregistré.';
}

$smsActif = ParametreController::smsValidationAgenceActif();

$pageTitle = 'Paramètres · Notifications';
$activeNav = 'Parametres';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Notifications envoyées automatiquement par la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Paramètres</h1>
      <p>Notifications envoyées automatiquement par la plateforme.</p>
    </div>

    <div style="display:flex;gap:8px;margin:0 0 18px;">
      <a class="btn" href="index.php">Général</a>
      <a class="btn" href="agence.php">Agences</a>
      <a class="btn btn-primary" href="notifications.php">Notifications</a>
    </div>

    <?php if ($successMessage): ?><div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">SMS</div></div>
      <form method="post" action="notifications.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div style="padding:4px 18px 18px;">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="checkbox" name="sms_validation_agence" value="1"<?= $smsActif ? ' checked' : '' ?> style="width:18px;height:18px;">
            <span>Notifier l’agence par SMS lorsque son inscription est validée</span>
          </label>
          <p class="cell-sub" style="display:block;margin-top:6px;">Envoyé au numéro de son administrateur, via le provider SMS configuré (voir Général).</p>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
