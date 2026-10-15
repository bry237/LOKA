<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/ParametreController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$error = ParametreController::updateAgenceDefaults($_POST);
	if ($error === null) {
		$successMessage = 'Réglage enregistré.';
	} else {
		$errorMessage = $error;
	}
}

$defaults = ParametreController::agenceDefaults();
$plans = $defaults['plans'];
$current = $defaults['current'];

$pageTitle = 'Paramètres · Agences';
$activeNav = 'Parametres';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Réglages par défaut appliqués aux agences.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Paramètres</h1>
      <p>Réglages par défaut appliqués aux agences.</p>
    </div>

    <div style="display:flex;gap:8px;margin:0 0 18px;">
      <a class="btn" href="index.php">Général</a>
      <a class="btn btn-primary" href="agence.php">Agences</a>
      <a class="btn" href="notifications.php">Notifications</a>
    </div>

    <?php if ($successMessage): ?><div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="feedback error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Plan par défaut à la création</div></div>
      <p class="cell-sub" style="display:block;padding:0 18px 10px;">
        Une agence créée depuis « Agences &rarr; Ajouter une agence » n’a, sinon, aucun abonnement :
        ce plan lui est alors automatiquement affecté. Sans réglage, le comportement actuel (aucun plan) est conservé.
        Les agences qui s’inscrivent elles-mêmes ou souscrivent via Stripe ne sont pas concernées.
      </p>
      <form method="post" action="agence.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid full">
          <div class="form-field">
            <label for="id_plan_defaut">Plan par défaut</label>
            <select id="id_plan_defaut" name="id_plan_defaut">
              <option value="">— Aucun (comportement actuel) —</option>
              <?php foreach ($plans as $plan): ?>
                <option value="<?= (int) $plan['id_abonnement'] ?>"<?= $current === (int) $plan['id_abonnement'] ? ' selected' : '' ?>><?= htmlspecialchars($plan['nom'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float) $plan['prix_mensuel'], 2, ',', ' ') ?> €/mois)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
